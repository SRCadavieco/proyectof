'use strict';

require('dotenv').config({ path: require('path').join(__dirname, '..', '.env') });

const express = require('express');
const { scrapeEtsy } = require('./scraper');

const app = express();
const PORT = parseInt(process.env.PORT ?? '3100', 10);
const SECRET = process.env.SCRAPER_SECRET ?? '';

// ─── Middleware ───────────────────────────────────────────────────────────────

app.use(express.json());

/**
 * Simple bearer-token auth guard.
 * Skips check when SCRAPER_SECRET is empty (local dev convenience).
 */
function authGuard(req, res, next) {
  if (!SECRET) return next();

  const auth = req.headers['authorization'] ?? '';
  const token = auth.startsWith('Bearer ') ? auth.slice(7) : null;

  if (token !== SECRET) {
    return res.status(401).json({ error: 'Unauthorized' });
  }
  next();
}

// ─── Routes ──────────────────────────────────────────────────────────────────

app.get('/health', (_req, res) => res.json({ status: 'ok', ts: Date.now() }));

/**
 * GET /scrape?q=keyword
 *
 * Scrapes Etsy search results for the given keyword.
 * Returns { keyword, count, listings: [...] }
 */
app.get('/scrape', authGuard, async (req, res) => {
  const keyword = typeof req.query.q === 'string' ? req.query.q.trim() : '';

  if (!keyword) {
    return res.status(400).json({ error: 'Missing required query param: q' });
  }

  console.log(`[scraper] Starting scrape for: "${keyword}"`);

  try {
    const listings = await scrapeEtsy(keyword);

    console.log(`[scraper] Done — ${listings.length} listings for "${keyword}"`);

    return res.json({
      keyword,
      count: listings.length,
      listings,
    });
  } catch (err) {
    console.error(`[scraper] Error scraping "${keyword}":`, err.message);

    return res.status(500).json({
      error: 'Scrape failed',
      message: err.message,
    });
  }
});

// ─── Start ────────────────────────────────────────────────────────────────────

app.listen(PORT, () => {
  console.log(`[scraper] Listening on http://localhost:${PORT}`);
  if (!SECRET) {
    console.warn('[scraper] WARNING: SCRAPER_SECRET not set — auth disabled');
  }
});
