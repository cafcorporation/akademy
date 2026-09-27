// Dev helper: print the checkout form's element tree (logged in via cookies, cart already filled).
import puppeteer from 'puppeteer-core';
import { readFileSync } from 'node:fs';
const cookies = JSON.parse(readFileSync(process.argv[2], 'utf8').replace(/^\uFEFF/, ''));
const browser = await puppeteer.connect({ browserURL: 'http://127.0.0.1:9333' });
const page = await browser.newPage();
await page.setCookie(...cookies.map((c) => ({ ...c, domain: 'localhost', path: '/' })));
await page.goto('http://localhost:10016/lp-checkout/', { waitUntil: 'networkidle2' });
console.log(await page.evaluate(() => {
  const walk = (el, d) => d > 4 ? '' : [...el.children].map((c) =>
    '  '.repeat(d) + c.tagName.toLowerCase() + (c.id ? '#' + c.id : '') + (c.className && typeof c.className === 'string' ? '.' + c.className.trim().split(/\s+/).join('.') : '') + '\n' + walk(c, d + 1)).join('');
  const f = document.querySelector('#learn-press-checkout-form');
  const t = document.querySelector('#checkout-order .course-name');
  return walk(f, 0) + '\nTITLE CSS: ' + (t ? getComputedStyle(t.querySelector('*') || t).cssText.slice(0,0) + [...(t.querySelectorAll('*'))].map(e=>e.tagName+'.'+e.className+' ws='+getComputedStyle(e).whiteSpace+' clamp='+getComputedStyle(e).webkitLineClamp).join(' | ') : '')
    + '\nTOTAL color: ' + getComputedStyle(document.querySelector('#checkout-order .order-total td')).color;
}));
await page.close(); await browser.disconnect();
