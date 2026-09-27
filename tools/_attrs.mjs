// Dev helper: list placeholders/aria-labels/titles on a page (anonymous visitor). Usage: node tools/_attrs.mjs <url>
import puppeteer from 'puppeteer-core';
const b = await puppeteer.connect({ browserURL: 'http://127.0.0.1:9333' });
const ctx = await b.createBrowserContext(); const p = await ctx.newPage();
await p.goto(process.argv[2], { waitUntil: 'networkidle2' });
console.log(await p.evaluate(() => [...new Set([...document.querySelectorAll('[placeholder],[title],[aria-label]')].map((e) => e.getAttribute('placeholder') || e.getAttribute('title') || e.getAttribute('aria-label')))].join(' | ')));
await ctx.close(); await b.disconnect();
