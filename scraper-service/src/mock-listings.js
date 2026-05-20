'use strict';

/**
 * Generate a plausible set of Etsy-like listings for a keyword.
 * Used as a fallback when the Etsy API key is not yet active.
 */

const STYLES   = ['vintage', 'retro', 'funny', 'cute', 'minimalist', 'boho', 'aesthetic', 'trendy', 'classic', 'bold'];
const PRODUCTS = ['T-Shirt', 'Tee', 'Unisex Tee', 'Graphic Tee', 'Shirt', 'Hoodie', 'Sweatshirt', 'Tank Top', 'Crewneck'];
const EXTRAS   = ['Gift', 'Gift for Her', 'Gift for Him', 'Birthday Gift', 'Funny Gift', 'Unisex', 'Plus Size Available'];
const TAGS_MAP = {
  default: ['handmade','gift','tshirt','clothing','apparel','fashion','unisex'],
  cat:     ['cat lover','cat mom','cat dad','kitten','feline','pet gift','cat shirt'],
  dog:     ['dog lover','dog mom','dog dad','puppy','pet gift','dog shirt','canine'],
  camping: ['outdoor','nature','hiking','adventure','camping life','campfire','wilderness'],
  retro:   ['vintage','80s','90s','throwback','old school','nostalgic','retro vibes'],
  funny:   ['humor','sarcastic','meme','snarky','comedy','joke gift','novelty'],
};

function seededRand(seed) {
  let s = seed;
  return () => {
    s = (s * 1664525 + 1013904223) & 0xffffffff;
    return (s >>> 0) / 0xffffffff;
  };
}

function pick(arr, rand) {
  return arr[Math.floor(rand() * arr.length)];
}

function mockPrice(rand) {
  const cents = Math.floor(rand() * 2500 + 1200); // $12–$37
  return `${(cents / 100).toFixed(2)} USD`;
}

function tagsForKeyword(keyword) {
  const kw = keyword.toLowerCase();
  for (const [key, tags] of Object.entries(TAGS_MAP)) {
    if (key !== 'default' && kw.includes(key)) return tags;
  }
  // Build from keyword words + defaults
  const words = kw.split(/\s+/).filter(w => w.length > 2);
  return [...new Set([...words, ...TAGS_MAP.default])].slice(0, 7);
}

/**
 * @param {string} keyword
 * @param {number} count
 * @returns {Array<{title, url, image, price, tags}>}
 */
function generateMockListings(keyword, count = 50) {
  const rand = seededRand(
    keyword.split('').reduce((a, c) => (a * 31 + c.charCodeAt(0)) | 0, 7) >>> 0
  );

  const baseTags  = tagsForKeyword(keyword);
  const results   = [];
  const kwWords   = keyword.trim().split(/\s+/);

  for (let i = 0; i < count; i++) {
    const style   = pick(STYLES, rand);
    const product = pick(PRODUCTS, rand);
    const extra   = rand() > 0.6 ? `, ${pick(EXTRAS, rand)}` : '';
    // Mix keyword words in different orders
    const kwStr   = rand() > 0.5 ? kwWords.join(' ') : [...kwWords].reverse().join(' ');
    const title   = `${style.charAt(0).toUpperCase() + style.slice(1)} ${kwStr} ${product}${extra}`;

    const listingId = 1000000000 + Math.floor(rand() * 900000000);
    const url       = `https://www.etsy.com/listing/${listingId}/${kwStr.replace(/\s+/g,'-').toLowerCase()}-${style}-${product.toLowerCase().replace(/\s+/g,'-')}`;

    // Use a placeholder image from picsum seeded on listing id
    const imgSeed = (listingId % 1000);
    const image   = `https://picsum.photos/seed/${imgSeed}/400/400`;

    // 2–4 tags from the base set
    const tagCount = 2 + Math.floor(rand() * 3);
    const tags = [];
    const pool = [...baseTags];
    for (let t = 0; t < tagCount && pool.length; t++) {
      const idx = Math.floor(rand() * pool.length);
      tags.push(pool.splice(idx, 1)[0]);
    }

    results.push({ title, url, image, price: mockPrice(rand), tags });
  }

  return results;
}

module.exports = { generateMockListings };
