'use strict';

// Etsy Open API v3 — https://developers.etsy.com/documentation/
// Requires ETSY_API_KEY env var.  Register at: https://www.etsy.com/developers/documentation/getting_started/register

const ETSY_BASE = 'https://openapi.etsy.com/v3/application';

function hasApiKey() {
  return !!(process.env.ETSY_API_KEY && process.env.ETSY_API_KEY.trim());
}

/**
 * Search active listings via the official Etsy v3 API.
 * @param {string} keyword
 * @param {number} limit   max 100 per request
 * @returns {Promise<Array<{title,url,image,price,tags}>>}
 */
async function searchListings(keyword, limit = 60) {
  const apiKey = process.env.ETSY_API_KEY.trim();

  const params = new URLSearchParams({
    q: keyword.trim(),
    limit: String(Math.min(limit, 100)),
    sort_on: 'score',
    sort_order: 'desc',
  });
  // Etsy v3 uses repeated bracket notation for includes
  params.append('includes[]', 'Images');
  params.append('includes[]', 'Shop');

  const url = `${ETSY_BASE}/listings/active?${params}`;

  const res = await fetch(url, {
    headers: {
      'x-api-key': apiKey,
      'Accept': 'application/json',
    },
  });

  if (!res.ok) {
    const text = await res.text();
    throw new Error(`Etsy API ${res.status}: ${text.slice(0, 300)}`);
  }

  const data = await res.json();
  const listings = data.results ?? [];

  return listings.map(item => {
    const image =
      item.images?.[0]?.url_570xN ??
      item.images?.[0]?.url_fullxfull ??
      item.images?.[0]?.url_75x75 ??
      null;

    let price = null;
    if (item.price) {
      const amount = (item.price.amount / item.price.divisor).toFixed(2);
      price = `${amount} ${item.price.currency_code}`;
    }

    const cleanUrl = `https://www.etsy.com/listing/${item.listing_id}`;

    return {
      title: item.title ?? '',
      url: cleanUrl,
      image,
      price,
      tags: Array.isArray(item.tags) ? item.tags : [],
    };
  });
}

module.exports = { searchListings, hasApiKey };
