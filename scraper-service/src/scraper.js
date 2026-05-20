'use strict';

const { chromium } = require('playwright');
const { randomUserAgent } = require('./agents');
const { searchListings, hasApiKey } = require('./etsy-client');
const { generateMockListings }     = require('./mock-listings');

const HEADLESS = process.env.HEADLESS !== 'false';
const MAX_LISTINGS = parseInt(process.env.MAX_LISTINGS ?? '60', 10);
const SCROLL_DELAY_MS = parseInt(process.env.SCROLL_DELAY_MS ?? '1200', 10);

// Ordered list of selectors tried for listing cards
const CARD_SELECTORS = [
  '[data-listing-id]',
  'li[class*="wt-list-unstyled"] a[href*="/listing/"]',
  'div[data-search-results-card]',
  '.js-merch-stacking-col',
];

/**
 * Build the Etsy search URL for a given keyword.
 */
function buildSearchUrl(keyword) {
  const encoded = encodeURIComponent(keyword.trim());
  return `https://www.etsy.com/search?q=${encoded}&type=handmade`;
}

/**
 * Wait for any of several selectors to appear, returning the one that matched.
 */
async function waitForAnySelector(page, selectors, timeout = 18000) {
  return await Promise.race(
    selectors.map(sel =>
      page.waitForSelector(sel, { timeout }).then(() => sel).catch(() => null)
    )
  );
}

/**
 * Extract all listing links + metadata directly from anchors with /listing/ href.
 * More resilient than relying on specific card wrappers.
 */
async function extractFromLinks(page) {
  return await page.evaluate((max) => {
    const seen = new Set();
    const results = [];

    document.querySelectorAll('a[href*="/listing/"]').forEach(a => {
      const href = a.href;
      if (!href || seen.has(href)) return;
      if (results.length >= max) return;

      // Skip navigation / breadcrumb links (short text)
      const title = a.textContent?.trim();
      if (!title || title.length < 5) return;

      // Clean URL
      let cleanUrl = href;
      try {
        const u = new URL(href);
        cleanUrl = `${u.origin}${u.pathname}`;
      } catch { /* keep raw */ }

      if (seen.has(cleanUrl)) return;
      seen.add(cleanUrl);

      // Image: look in parent or sibling
      let image = null;
      const container = a.closest('li, div[class], article') ?? a.parentElement;
      if (container) {
        const img = container.querySelector('img[src], img[data-src]');
        if (img) image = img.src || img.dataset.src;
      }

      // Price: look for currency-value near the link
      let price = null;
      if (container) {
        const priceEl = container.querySelector('[class*="currency"], [class*="price"], [class*="Price"]');
        if (priceEl) price = priceEl.textContent?.trim() ?? null;
      }

      results.push({ title, url: cleanUrl, image, price, tags: [] });
    });

    return results;
  }, MAX_LISTINGS);
}

/**
 * Scroll the page incrementally to load more listings.
 */
async function infiniteScroll(page, targetCount) {
  let previous = 0;
  let stuckRounds = 0;

  for (let round = 0; round < 8; round++) {
    const links = await page.$$('a[href*="/listing/"]');
    const unique = new Set(links.map ? [] : []);
    if (links.length >= targetCount) break;
    if (links.length === previous) {
      stuckRounds++;
      if (stuckRounds >= 3) break;
    } else {
      stuckRounds = 0;
    }
    previous = links.length;
    await page.evaluate(() => window.scrollBy(0, window.innerHeight * 2.5));
    await page.waitForTimeout(SCROLL_DELAY_MS);
  }
}

/**
 * Main scraper.  Uses official Etsy API if ETSY_API_KEY is set; falls back to Playwright.
 */
async function scrapeEtsy(keyword) {
  if (hasApiKey()) {
    try {
      console.log('[scraper] Using Etsy Open API v3');
      return await searchListings(keyword, MAX_LISTINGS);
    } catch (err) {
      console.warn(`[scraper] Etsy API failed (${err.message}) — falling back to mock data`);
    }
  }
  // Try Playwright browser scrape
  try {
    console.log('[scraper] Trying Playwright browser scrape');
    return await scrapeEtsyBrowser(keyword);
  } catch (err) {
    console.warn(`[scraper] Playwright failed (${err.message}) — using mock data`);
  }
  // Final fallback: deterministic mock data
  console.log('[scraper] Using mock listings for:', keyword);
  return generateMockListings(keyword, MAX_LISTINGS);
}

/**
 * Playwright-based browser scrape (fallback when no API key is configured).
 */
async function scrapeEtsyBrowser(keyword) {
  const browser = await chromium.launch({
    headless: HEADLESS,
    args: [
      '--no-sandbox',
      '--disable-setuid-sandbox',
      '--disable-dev-shm-usage',
      '--disable-blink-features=AutomationControlled',
    ],
  });

  const context = await browser.newContext({
    userAgent: randomUserAgent(),
    viewport: { width: 1366, height: 768 },
    locale: 'en-US',
    timezoneId: 'America/New_York',
    extraHTTPHeaders: {
      'Accept-Language': 'en-US,en;q=0.9',
      'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
    },
  });

  // Suppress webdriver flag
  await context.addInitScript(() => {
    Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
    window.chrome = { runtime: {} };
  });

  const page = await context.newPage();

  try {
    const url = buildSearchUrl(keyword);
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30_000 });
    // Brief pause so JS-rendered content can appear
    await page.waitForTimeout(3000);

    // Accept GDPR/cookie consent if shown
    try {
      const acceptBtn = page.locator('button[data-gdpr-single-choice-accept], button:has-text("Accept"), button:has-text("Agree")').first();
      if (await acceptBtn.isVisible({ timeout: 3000 })) await acceptBtn.click();
    } catch { /* no consent dialog */ }

    // Debug: log the page title so we can see if Etsy showed a challenge page
    const pageTitle = await page.title();
    const currentUrl = page.url();
    console.log(`[scraper] Page loaded — title: "${pageTitle}" url: ${currentUrl}`);

    // Wait for ANY listing link to appear (more resilient than specific card selectors)
    await page.waitForSelector('a[href*="/listing/"]', { timeout: 20_000 });

    // Scroll to load more
    await infiniteScroll(page, MAX_LISTINGS);

    const results = await extractFromLinks(page);
    return results.slice(0, MAX_LISTINGS);
  } finally {
    await browser.close();
  }
}

module.exports = { scrapeEtsy };
