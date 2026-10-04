// Renders each brochure page as a JPG image (for carousels / social), from marketing/brochure/brochure.html.
// One file per page: Akdemy-Kpex-brochure-<lang>[-<cover>]-NN.jpg, written next to the brochure.
// Usage: node tools/render-brochure-images.mjs [lang=es|en|all] [cover=dark|light|window|curve|custom]
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import puppeteer from 'puppeteer-core';

const ROOT = path.resolve(import.meta.dirname, '..');
const DIR = path.join(ROOT, 'marketing/brochure');
const HTML = pathToFileURL(path.join(DIR, 'brochure.html')).href;

const CHROME = process.env.CHROME_PATH
  || ['C:/Program Files/Google/Chrome/Application/chrome.exe',
      'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
      '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
      '/usr/bin/google-chrome-stable', '/usr/bin/chromium'].find((p) => fs.existsSync(p));

const langArg = (process.argv[2] || 'all').toLowerCase();
const cover = (process.argv[3] || 'dark').toLowerCase();
const langs = langArg === 'all' ? ['es', 'en'] : [langArg];

async function main() {
  if (!CHROME) { console.error('Chrome not found — set CHROME_PATH'); process.exit(1); }
  const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox'] });
  const page = await browser.newPage();
  await page.setViewport({ width: 820, height: 1160, deviceScaleFactor: 2 }); // A4 @ ~2× for crisp carousel images
  for (const lang of langs) {
    await page.goto(`${HTML}?lang=${lang}&cover=${cover}`, { waitUntil: 'networkidle0', timeout: 60000 });
    await page.evaluate(async () => { try { await Promise.race([document.fonts.ready, new Promise((r) => setTimeout(r, 4000))]); } catch (e) {} });
    // Strip the on-screen page margin/shadow so each image is just the A4 page.
    await page.addStyleTag({ content: '.page{margin:0 !important;box-shadow:none !important} html,body{background:#fff !important}' });
    await new Promise((r) => setTimeout(r, 400));
    const suffix = cover === 'dark' ? '' : `-${cover}`;
    const pages = await page.$$('.page');
    let n = 0;
    for (const el of pages) {
      n++;
      const out = path.join(DIR, `Akdemy-Kpex-brochure-${lang}${suffix}-${String(n).padStart(2, '0')}.jpg`);
      await el.screenshot({ path: out, type: 'jpeg', quality: 90 });
      console.log(`✔ ${path.relative(ROOT, out)}`);
    }
  }
  await browser.close();
  console.log('\nListo.');
}
main();
