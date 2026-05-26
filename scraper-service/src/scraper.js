'use strict';

const { chromium } = require('playwright');
const { randomUserAgent } = require('./agents');
const { searchListings, hasApiKey } = require('./etsy-client');

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
 * Build the Etsy search URL for a given keyword — scoped to Clothing category.
 * taxonomy_id 68887475 = Clothing (Etsy buyer taxonomy, used in browse URLs).
 */
function buildSearchUrl(keyword) {
  const encoded = encodeURIComponent(keyword.trim());
  return `https://www.etsy.com/search?q=${encoded}&type=handmade&explicit=1&ref=pagination`;
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
async function extractFromLinks(page, max) {
  return await page.evaluate((max) => {
    /**
     * Find the best available image URL from a container element.
     * Tries multiple strategies to handle Etsy's lazy-loading patterns.
     */
    function getBestImage(container) {
      if (!container) return null;

      // 1. Direct Etsy CDN src already loaded
      const etsyDirect = container.querySelector('img[src*="etsystatic.com"], img[src*="etsy.com/il/"]');
      if (etsyDirect) return etsyDirect.src;

      // 2. Lazy-loaded data attributes (various Etsy patterns)
      const lazySelectors = ['img[data-src]', 'img[data-lazy-src]', 'img[data-original]', '[data-src]'];
      for (const sel of lazySelectors) {
        const el = container.querySelector(sel);
        if (el) {
          const url = el.dataset.src || el.dataset.lazySrc || el.dataset.original;
          if (url && url.startsWith('http') && !url.includes('blank') && !url.includes('placeholder')) return url;
        }
      }

      // 3. srcset — pick the first real URL
      const srcsetImg = container.querySelector('img[srcset]');
      if (srcsetImg && srcsetImg.srcset) {
        const first = srcsetImg.srcset.split(',')[0]?.trim().split(' ')[0];
        if (first && first.startsWith('http')) return first;
      }

      // 4. Any https img src that isn't a known placeholder
      const anyImg = container.querySelector('img[src^="https"]');
      if (anyImg) {
        const s = anyImg.src;
        if (!s.includes('blank') && !s.includes('placeholder') && !s.includes('spinner') && !s.includes('1x1')) return s;
      }

      return null;
    }

    const seen = new Set();
    const results = [];

    document.querySelectorAll('a[href*="/listing/"]').forEach(a => {
      const href = a.href;
      if (!href || seen.has(href)) return;
      if (results.length >= max) return;

      // Skip navigation / breadcrumb links (short text)
      const title = a.textContent?.trim();
      if (!title || title.length < 5) return;

      // Clean URL — strip query string
      let cleanUrl = href;
      try {
        const u = new URL(href);
        cleanUrl = `${u.origin}${u.pathname}`;
      } catch { /* keep raw */ }

      if (seen.has(cleanUrl)) return;
      seen.add(cleanUrl);

      // Search container hierarchy (Etsy cards are often li > div > a)
      const container = a.closest('[data-listing-id], li[class], div[data-search-results-card], article') ?? a.parentElement;
      const image = getBestImage(container);

      // Price
      let price = null;
      if (container) {
        const priceEl = container.querySelector('[class*="currency"], [class*="price"], [class*="Price"]');
        if (priceEl) price = priceEl.textContent?.trim() ?? null;
      }

      results.push({ title, url: cleanUrl, image, price, tags: [] });
    });

    return results;
  }, max);
}

/**
 * Extract listings from JSON-LD structured data on Etsy search pages.
 * Etsy often includes image URLs here even when lazy-loaded img tags are missing.
 */
async function extractFromStructuredData(page, max) {
  return await page.evaluate((max) => {
    const results = [];
    const seen = new Set();

    const scripts = Array.from(document.querySelectorAll('script[type="application/ld+json"]'));
    for (const script of scripts) {
      if (results.length >= max) break;

      let parsed;
      try {
        parsed = JSON.parse(script.textContent || '{}');
      } catch {
        continue;
      }

      const nodes = Array.isArray(parsed) ? parsed : [parsed];
      for (const node of nodes) {
        if (!node || results.length >= max) continue;
        const itemList = Array.isArray(node.itemListElement) ? node.itemListElement : [];
        for (const el of itemList) {
          if (results.length >= max) break;
          const item = el?.item ?? el;
          if (!item) continue;

          const rawUrl = item.url ?? item['@id'] ?? null;
          if (!rawUrl || typeof rawUrl !== 'string') continue;

          let cleanUrl = rawUrl;
          try {
            const u = new URL(rawUrl);
            cleanUrl = `${u.origin}${u.pathname}`;
          } catch { /* keep raw */ }

          if (!cleanUrl.includes('/listing/')) continue;
          if (seen.has(cleanUrl)) continue;
          seen.add(cleanUrl);

          const title = typeof item.name === 'string' ? item.name.trim() : '';
          if (!title) continue;

          let image = null;
          const imageField = item.image;
          if (typeof imageField === 'string') {
            image = imageField;
          } else if (Array.isArray(imageField)) {
            image = imageField.find(v => typeof v === 'string') ?? null;
          } else if (imageField && typeof imageField === 'object') {
            image = imageField.url ?? imageField.contentUrl ?? null;
          }

          let price = null;
          const offer = item.offers;
          if (offer && typeof offer === 'object') {
            const p = offer.price;
            const c = offer.priceCurrency;
            if (p != null) {
              price = c ? `${p} ${c}` : String(p);
            }
          }

          results.push({ title, url: cleanUrl, image, price, tags: [] });
        }
      }
    }

    return results;
  }, max);
}

function normalizeImageUrl(url) {
  if (!url || typeof url !== 'string') return null;
  const trimmed = url.trim();
  if (!trimmed) return null;
  if (trimmed.startsWith('//')) return `https:${trimmed}`;
  if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) return trimmed;
  return null;
}

function unique(arr) {
  return Array.from(new Set(arr));
}

/**
 * Lightweight HTML fallback for Etsy search pages.
 * This avoids full browser rendering when Playwright is blocked.
 */
async function scrapeEtsyHtml(keyword) {
  const url = buildSearchUrl(keyword);
  const res = await fetch(url, {
    headers: {
      'User-Agent': randomUserAgent(),
      'Accept-Language': 'en-US,en;q=0.9',
      'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
    },
  });

  if (!res.ok) {
    throw new Error(`HTML fallback HTTP ${res.status}`);
  }

  const html = await res.text();

  // Etsy listing links in page HTML.
  const urlMatches = html.match(/https:\/\/www\.etsy\.com\/listing\/\d+\/[^"'\s<]+/gi) ?? [];
  const listingUrls = unique(urlMatches).slice(0, MAX_LISTINGS * 2);

  // Etsy CDN image URLs in page HTML.
  const imageMatches = html.match(/https:\/\/i\.etsystatic\.com\/[^"'\s<]+/gi) ?? [];
  const imageUrls = unique(imageMatches)
    .map(normalizeImageUrl)
    .filter(Boolean);

  const listings = listingUrls.slice(0, MAX_LISTINGS).map((u, idx) => {
    let cleanUrl = u;
    try {
      const parsed = new URL(u);
      cleanUrl = `${parsed.origin}${parsed.pathname}`;
    } catch {
      // keep raw URL when parsing fails
    }

    const slug = cleanUrl.split('/').pop() || keyword;
    const title = decodeURIComponent(slug.replace(/-/g, ' '));
    return {
      title: title.length >= 5 ? title : `${keyword} listing`,
      url: cleanUrl,
      image: imageUrls[idx] ?? null,
      price: null,
      tags: [],
    };
  });

  if (listings.length === 0) {
    throw new Error('HTML fallback found no listing URLs');
  }

  return listings;
}

/**
 * For listings without image, open the detail page and extract og:image/twitter:image.
 */
async function enrichMissingImagesFromListingPages(context, listings, maxChecks = 12) {
  const missingIdx = [];
  for (let i = 0; i < listings.length; i++) {
    if (!normalizeImageUrl(listings[i].image) && listings[i].url) missingIdx.push(i);
  }

  if (missingIdx.length === 0) return listings;

  const page = await context.newPage();
  try {
    let checks = 0;
    for (const idx of missingIdx) {
      if (checks >= maxChecks) break;
      checks++;

      const listing = listings[idx];
      try {
        await page.goto(listing.url, { waitUntil: 'domcontentloaded', timeout: 25_000 });
        const candidate = await page.evaluate(() => {
          const og = document.querySelector('meta[property="og:image"]')?.getAttribute('content');
          if (og) return og;

          const tw = document.querySelector('meta[name="twitter:image"]')?.getAttribute('content');
          if (tw) return tw;

          const ld = Array.from(document.querySelectorAll('script[type="application/ld+json"]'));
          for (const s of ld) {
            try {
              const parsed = JSON.parse(s.textContent || '{}');
              const nodes = Array.isArray(parsed) ? parsed : [parsed];
              for (const node of nodes) {
                const image = node?.image;
                if (typeof image === 'string') return image;
                if (Array.isArray(image)) {
                  const first = image.find(v => typeof v === 'string');
                  if (first) return first;
                }
                if (image && typeof image === 'object') {
                  if (typeof image.url === 'string') return image.url;
                  if (typeof image.contentUrl === 'string') return image.contentUrl;
                }
              }
            } catch {
              // ignore malformed JSON-LD blocks
            }
          }

          return null;
        });

        const normalized = normalizeImageUrl(candidate);
        if (normalized) {
          listing.image = normalized;
        }
      } catch {
        // Ignore individual listing failures; keep trying others.
      }
    }
  } finally {
    await page.close();
  }

  return listings;
}

/**
 * Scroll the page incrementally to load more listings AND trigger lazy image loading.
 */
async function infiniteScroll(page, targetCount) {
  let previousLinks = 0;
  let stuckRounds   = 0;

  for (let round = 0; round < 10; round++) {
    const links = await page.$$('a[href*="/listing/"]');
    if (links.length >= targetCount) break;
    if (links.length === previousLinks) {
      stuckRounds++;
      if (stuckRounds >= 3) break;
    } else {
      stuckRounds = 0;
    }
    previousLinks = links.length;
    await page.evaluate(() => window.scrollBy(0, window.innerHeight * 2));
    // Wait for lazy images to load after scroll
    await page.waitForTimeout(SCROLL_DELAY_MS);
  }

  // Scroll back to top so the full page re-renders images in view
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.waitForTimeout(800);
  // Final slow scroll to ensure ALL images in the listing grid have loaded
  await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
  await page.waitForTimeout(1200);
}

// Terms that indicate a clothing / apparel listing
const CLOTHING_TERMS = [
  'shirt', 'tshirt', 't-shirt', 'tee', 'hoodie', 'sweatshirt',
  'tank', 'crewneck', 'pullover', 'jersey', 'top', 'blouse',
  'jacket', 'vest', 'cardigan', 'sweater', 'dress', 'skirt',
  'apparel', 'clothing', 'wear',
];

/**
 * Return true if the listing title suggests it is a clothing / apparel item.
 */
function isClothing(listing) {
  const title = (listing.title ?? '').toLowerCase();
  return CLOTHING_TERMS.some(term => title.includes(term));
}

/**
 * Main scraper.  Uses official Etsy API if ETSY_API_KEY is set; falls back to Playwright.
 * Results are always filtered to clothing / apparel items.
 */
async function scrapeEtsy(keyword) {
  let listings;
  if (hasApiKey()) {
    try {
      console.log('[scraper] Using Etsy Open API v3');
      listings = await searchListings(keyword, MAX_LISTINGS);
    } catch (err) {
      console.warn(`[scraper] Etsy API failed (${err.message}) — falling back to Playwright`);
      listings = null;
    }
  }

  if (!listings) {
    try {
      console.log('[scraper] Trying Playwright browser scrape');
      listings = await scrapeEtsyBrowser(keyword);
    } catch (err) {
      console.warn(`[scraper] Playwright failed (${err.message})`);
      listings = null;
    }
  }

  if (!listings) {
    try {
      console.log('[scraper] Trying HTML fallback scrape');
      listings = await scrapeEtsyHtml(keyword);
    } catch (err) {
      console.warn(`[scraper] HTML fallback failed (${err.message})`);
      listings = null;
    }
  }

  if (!listings) {
    throw new Error('Unable to scrape real Etsy listings from API and Playwright');
  }

  listings = listings.map(item => ({
    ...item,
    image: normalizeImageUrl(item.image),
  }));

  // Recover missing images from listing detail pages when needed.
  const missingImages = listings.filter(l => !l.image).length;
  if (missingImages > 0) {
    console.log(`[scraper] Missing images before enrichment: ${missingImages}/${listings.length}`);
  }

  // Keep only apparel results; fall back to full set if filter removes everything
  const clothingOnly = listings.filter(isClothing);
  let result = clothingOnly.length >= 3 ? clothingOnly : listings;

  // Enrich after clothing filter so we only fetch detail pages for likely-final rows.
  // Create an ephemeral context only when we need page-level recovery.
  const stillMissing = result.filter(l => !l.image).length;
  if (stillMissing > 0) {
    const browser = await chromium.launch({
      headless: HEADLESS,
      args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
    });
    const ctx = await browser.newContext({
      userAgent: randomUserAgent(),
      viewport: { width: 1280, height: 720 },
      locale: 'en-US',
    });
    try {
      result = await enrichMissingImagesFromListingPages(ctx, result, 12);
    } finally {
      await ctx.close();
      await browser.close();
    }
  }

  console.log(`[scraper] Clothing filter: ${listings.length} → ${result.length} listings`);
  const withImages = result.filter(l => !!l.image).length;
  console.log(`[scraper] Final image coverage: ${withImages}/${result.length}`);
  return result;
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

    const linkResults = await extractFromLinks(page, MAX_LISTINGS);
    const ldResults = await extractFromStructuredData(page, MAX_LISTINGS);

    // Merge structured data + DOM extraction, preferring entries with image/price.
    const merged = new Map();
    [...ldResults, ...linkResults].forEach(item => {
      if (!item?.url) return;
      const existing = merged.get(item.url);
      if (!existing) {
        merged.set(item.url, {
          ...item,
          image: normalizeImageUrl(item.image),
        });
        return;
      }

      if (!existing.image && item.image) existing.image = normalizeImageUrl(item.image);
      if (!existing.price && item.price) existing.price = item.price;
      if ((!existing.title || existing.title.length < 6) && item.title) existing.title = item.title;
    });

    return Array.from(merged.values()).slice(0, MAX_LISTINGS);
  } finally {
    await browser.close();
  }
}

module.exports = { scrapeEtsy };
