'use strict';
const { chromium } = require('playwright');
const { randomUserAgent } = require('./src/agents');

(async () => {
  const b = await chromium.launch({ headless: false, args: ['--no-sandbox', '--disable-setuid-sandbox'] });
  const ctx = await b.newContext({
    userAgent: randomUserAgent(),
    locale: 'en-US',
    viewport: { width: 1366, height: 768 },
  });
  await ctx.addInitScript(() => {
    Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
    window.chrome = { runtime: {} };
  });
  const p = await ctx.newPage();
  await p.goto('https://www.etsy.com/search?q=cat+shirt&type=handmade', { waitUntil: 'domcontentloaded', timeout: 30000 });
  await p.waitForTimeout(5000);
  await p.screenshot({ path: 'debug-etsy.png', fullPage: false });
  const title = await p.title();
  const linkCount = await p.evaluate(() => document.querySelectorAll('a[href*="/listing/"]').length);
  const bodySnippet = await p.evaluate(() => document.body ? document.body.innerText.slice(0, 400) : 'no body');
  const htmlSnippet = await p.evaluate(() => document.documentElement.outerHTML.slice(0, 800));
  console.log('Title:', title);
  console.log('Listing links:', linkCount);
  console.log('Body:', bodySnippet);
  console.log('HTML:', htmlSnippet);
  await b.close();
})().catch(e => { console.error(e); process.exit(1); });
