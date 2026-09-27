// Dev helper: open a local page in the debug Edge and print console errors.
import puppeteer from 'puppeteer-core';
const url = process.argv[2];
const browser = await puppeteer.connect({ browserURL: 'http://127.0.0.1:9333' });
const page = await browser.newPage();
page.on('pageerror', (e) => console.log('PAGEERROR:', e.message));
page.on('console', (m) => m.type() === 'error' && console.log('CONSOLE:', m.text()));
await page.goto(url, { waitUntil: 'networkidle0' });
console.log('app length:', await page.$eval('#app', (e) => e.innerHTML.length));
await page.close(); await browser.disconnect();
