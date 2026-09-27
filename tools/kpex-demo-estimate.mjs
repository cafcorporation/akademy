// Creates the DEMO Class 5 estimate used by the course screenshots (lessons 4.2 / 4.3), through the Kpex UI,
// in the demo project "Seville Solar PV Farm": Solar PV — Utility Scale (EPC), 100 MWp, Spain.
// Requires the signed-in Edge window (--remote-debugging-port=9333). Idempotent: skips if the demo exists.
import puppeteer from 'puppeteer-core';

const BASE = 'https://kpex2g.test';
const TITLE = 'DEMO — Seville Solar PV Farm · Class 5 Screening (100 MWp)';

const browser = await puppeteer.connect({ browserURL: 'http://127.0.0.1:9333', defaultViewport: { width: 1440, height: 900 } });
const page = await browser.newPage();

await page.goto(`${BASE}/estimates`, { waitUntil: 'networkidle2' });
const existing = await page.evaluate((t) => {
  const a = [...document.querySelectorAll('a[href]')].find((x) => x.textContent.includes(t));
  return a ? a.href : null;
}, TITLE);
if (existing) {
  console.log(`exists: ${existing}`);
  await page.close(); await browser.disconnect(); process.exit(0);
}

// Demo project "Seville Solar PV Farm"
await page.goto(`${BASE}/projects`, { waitUntil: 'networkidle2' });
const pid = await page.evaluate(() => {
  const a = [...document.querySelectorAll('a[href]')].find((x) => /^\/projects\/\d+$/.test(new URL(x.href).pathname) && x.closest('*')?.textContent.includes('Seville Solar PV'));
  const any = [...document.querySelectorAll('[class*=card], tr, article')].find((c) => c.textContent.includes('Seville Solar PV Farm'));
  const link = any && [...any.querySelectorAll('a[href]')].find((x) => /^\/projects\/\d+$/.test(new URL(x.href).pathname));
  return (link || a) ? new URL((link || a).href).pathname.split('/').pop() : null;
});
if (!pid) throw new Error('Demo project "Seville Solar PV Farm" not found');

await page.goto(`${BASE}/estimates/create?project_id=${pid}&aace_class=5`, { waitUntil: 'networkidle2' });
await page.type('input[name=title]', TITLE);
const pick = async (selector, re) => page.evaluate((s, src) => {
  const el = document.querySelector(s);
  const o = [...el.options].find((x) => new RegExp(src, 'i').test(x.text));
  if (!o) return false;
  el.value = o.value;
  el.dispatchEvent(new Event('change', { bubbles: true }));
  return o.text;
}, selector, re.source);

console.log('curve:', await pick('select[name=curve_id]', /Solar PV — Utility Scale/));
await page.click('#cb_capacity', { clickCount: 3 });
await page.type('#cb_capacity', '100');
console.log('country:', await pick('select[name=lfi_country_id]', /^Spain$/));
await new Promise((r) => setTimeout(r, 800));
await page.evaluate(() => [...document.querySelectorAll('button')].find((b) => /Calculate from Curve/i.test(b.textContent))?.click());
await new Promise((r) => setTimeout(r, 2500));
console.log('tic_total:', await page.$eval('input[name=tic_total]', (e) => e.value));

await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 60000 }), page.click('#submitBtn')]);
console.log('created:', page.url());
const errors = await page.evaluate(() => [...document.querySelectorAll('.invalid-feedback, .text-danger, .alert-danger')].map((e) => e.textContent.trim()).filter(Boolean));
if (errors.length) console.log('errors:', errors);
await page.close(); await browser.disconnect();
