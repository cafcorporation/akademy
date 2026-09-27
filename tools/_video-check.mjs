// Dev helper: is the lesson "Media" player present, and is it above the lesson text? Usage: node tools/_video-check.mjs <url> [shot.png]
import puppeteer from 'puppeteer-core';
const [url, shot] = process.argv.slice(2);
const b = await puppeteer.connect({ browserURL: 'http://127.0.0.1:9333' });
const ctx = await b.createBrowserContext(); const p = await ctx.newPage();
await p.setViewport({ width: 1440, height: 900 });
await p.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
await new Promise((r) => setTimeout(r, 6000)); // embedded players keep the network busy
console.log(await p.evaluate(() => {
  const v = document.querySelector('.learn-press-video-intro'); const t = document.querySelector('.content-item-description, .lesson-description');
  if (!v) return 'NO VIDEO BLOCK';
  const f = v.querySelector('iframe');
  return `video block: iframe=${f ? f.src.slice(0, 50) : 'none'} · above text: ${t ? v.getBoundingClientRect().top < t.getBoundingClientRect().top : '?'}`;
}));
if (shot) { const v = await p.$('.learn-press-video-intro'); if (v) { await v.scrollIntoView(); await p.screenshot({ path: shot }); } }
await ctx.close(); await b.disconnect();
