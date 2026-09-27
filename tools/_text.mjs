// Dev helper: print where a string appears on a page (anonymous visitor). Usage: node tools/_text.mjs <url> <regex>
import puppeteer from 'puppeteer-core';
const [url, re] = process.argv.slice(2);
const b = await puppeteer.connect({ browserURL: 'http://127.0.0.1:9333' });
const ctx = await b.createBrowserContext(); const p = await ctx.newPage();
await p.goto(url, { waitUntil: 'networkidle2' });
console.log(await p.evaluate((re) => (document.body.innerText.match(new RegExp('.{0,30}(' + re + ').{0,30}', 'g')) || []).join('\n'), re));
await ctx.close(); await b.disconnect();
