// Dev helper: full-page screenshot of a local URL through the debug Edge (anonymous visitor).
// Usage: node tools/_shot-page.mjs <url> <out.png> [width] [selector-to-start-from]
import puppeteer from 'puppeteer-core';
const [url, out, width = '1440', sel] = process.argv.slice(2);
const browser = await puppeteer.connect({ browserURL: 'http://127.0.0.1:9333' });
const ctx = await browser.createBrowserContext(); // no cookies: see what a visitor sees
const page = await ctx.newPage();
await page.setViewport({ width: +width, height: 900 });
await page.goto(url, { waitUntil: 'networkidle2' });
if (sel) {
  const r = await page.$eval(sel, (e) => { const b = e.getBoundingClientRect(); return { x: 0, y: b.top + scrollY - 20, w: innerWidth, h: b.height + 40 }; });
  await page.screenshot({ path: out, clip: { x: r.x, y: r.y, width: r.w, height: Math.min(r.h, 6000) }, captureBeyondViewport: true });
} else await page.screenshot({ path: out, fullPage: true });
await ctx.close(); await browser.disconnect();
