// Dev helper: log in (auth cookies from WP-CLI), put a course in the cart and screenshot the checkout.
// Usage: node tools/_shot-checkout.mjs <cookies.json> <courseUrl> <out.png> [width]
import puppeteer from 'puppeteer-core';
import { readFileSync } from 'node:fs';
const [cookieFile, courseUrl, out, width = '1440'] = process.argv.slice(2);
const cookies = JSON.parse(readFileSync(cookieFile, 'utf8').replace(/^﻿/, ''));
const browser = await puppeteer.connect({ browserURL: 'http://127.0.0.1:9333' });
const page = await browser.newPage();
page.on('pageerror', (e) => console.log('PAGEERROR:', e.message));
await page.setViewport({ width: +width, height: 900 });
await page.setCookie(...cookies.map((c) => ({ ...c, domain: 'localhost', path: '/' })));
await page.goto(courseUrl, { waitUntil: 'networkidle2' });
const clicked = await page.evaluate(() => {
  const b = [...document.querySelectorAll('form.purchase-course button, .lp-btn-purchase, button.button-purchase-course')]
    .find((e) => e.offsetParent !== null);
  if (b) b.click();
  return !!b;
});
if (clicked) await page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 15000 }).catch(() => {});
await new Promise((r) => setTimeout(r, 1500));
if (!page.url().includes('checkout')) await page.goto('http://localhost:10016/lp-checkout/', { waitUntil: 'networkidle2' });
console.log('url:', page.url());
await page.screenshot({ path: out, fullPage: true });
await page.close(); await browser.disconnect();
