// Builds a fallback snapshot of CAF Insights from the public page (until /insights/feed.json is deployed).
// Output: wordpress/plugins/akdemy-bridge/assets/insights-snapshot.json — same shape as the JSON feed.
// Usage: node tools/snapshot-insights.mjs [https://caf-corporation.com/insights]
import fs from 'node:fs';
import path from 'node:path';

const SRC = process.argv[2] ?? 'https://caf-corporation.com/insights';
const OUT = path.resolve(import.meta.dirname, '../wordpress/plugins/akdemy-bridge/assets/insights-snapshot.json');

const decode = (s) => s
  .replace(/<[^>]+>/g, '')
  .replace(/&amp;/g, '&').replace(/&#0?39;|&apos;/g, "'").replace(/&quot;/g, '"')
  .replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&nbsp;/g, ' ')
  .replace(/\s+/g, ' ').trim();
const pick = (html, cls) => {
  const m = html.match(new RegExp(`class="${cls}"[^>]*>([\\s\\S]*?)</div>`));
  return m ? decode(m[1]) : '';
};

const html = await (await fetch(SRC)).text();
const origin = new URL(SRC).origin;
const items = [];

// Featured hero
const feat = html.match(/<a href="([^"]*\/insights\/[^"]+)"[^>]*>\s*<div class="article-featured">([\s\S]*?)<\/a>/);
if (feat) {
  const body = feat[2];
  const cat = pick(body, 'art-cat').replace(/^Featured · /, '');
  items.push({
    title: pick(body, 'art-title'),
    url: new URL(feat[1], origin).href,
    excerpt: pick(body, 'art-excerpt'),
    cover_url: (body.match(/background-image:url\('([^']+)'\)/) || [])[1] ?? null,
    category_label: cat,
    category_color: (body.match(/class="art-cat" style="color:(#[0-9A-Fa-f]{3,6})/) || [])[1] ?? '#F5A011',
    reading_time: parseInt((body.match(/(\d+)\s*min read/) || [])[1] ?? '5', 10),
    featured: true,
    members_only: false,
  });
}

// Grid cards
// Split on the card wrapper only ("article-card " / "article-card article-gated"), not on article-card-img etc.
for (const card of html.split(/<div class="article-card(?=[ "])/).slice(1)) {
  const title = pick(card, 'article-card-title');
  const href = (card.match(/href="([^"]*\/insights\/[a-z0-9-]+)"/) || [])[1];
  if (!title || !href) continue;
  items.push({
    title,
    url: new URL(href, origin).href,
    excerpt: pick(card, 'article-card-excerpt'),
    cover_url: (card.match(/background-image:url\('([^']+)'\)/) || [])[1] ?? null,
    category_label: pick(card, 'article-card-cat'),
    category_color: (card.match(/class="article-card-cat" style="color:(#[0-9A-Fa-f]{3,6})/) || [])[1] ?? '#F5A011',
    reading_time: parseInt((card.match(/(\d+)\s*min read/) || [])[1] ?? '5', 10),
    featured: false,
    members_only: /article-gated/.test(card.slice(0, 40)),
  });
}

const seen = new Set();
const unique = items.filter((i) => !seen.has(i.url) && seen.add(i.url));
fs.writeFileSync(OUT, JSON.stringify({ source: 'snapshot', insights_url: SRC, generated_at: new Date().toISOString(), items: unique }, null, 2));
console.log(`OK ${unique.length} insights -> ${path.relative(process.cwd(), OUT)}`);
for (const i of unique) console.log(` - [${i.category_label}] ${i.title}${i.cover_url ? '' : '  (no cover)'}`);
