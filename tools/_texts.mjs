// Dev helper: dump the visible text of a page (anonymous visitor). Usage: node tools/_texts.mjs <url>
import puppeteer from 'puppeteer-core';
const b = await puppeteer.connect({ browserURL: 'http://127.0.0.1:9333' });
const ctx = await b.createBrowserContext(); const p = await ctx.newPage();
await p.setViewport({ width: 1440, height: 900 });
await p.goto(process.argv[2], { waitUntil: 'networkidle2' });
console.log(await p.evaluate(() => document.body.innerText));
await ctx.close(); await b.disconnect();
