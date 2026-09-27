// Dev helper: rasterize PDF pages with pdf.js inside the debug Edge, for visual checks.
// Usage: node tools/_pdf-peek.mjs <file.pdf> <outPrefix> <page> [page...]   (pages: numbers or "all")
import puppeteer from 'puppeteer-core';
import { readFileSync, writeFileSync } from 'node:fs';
const [pdf, prefix, ...pages] = process.argv.slice(2);
const browser = await puppeteer.connect({ browserURL: 'http://127.0.0.1:9333' });
const page = await browser.newPage();
await page.setContent('<html><body></body></html>');
await page.addScriptTag({ url: 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js' });
const b64 = readFileSync(pdf).toString('base64');
const out = await page.evaluate(async (b64, pages) => {
  const lib = window.pdfjsLib;
  lib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js';
  const doc = await lib.getDocument({ data: Uint8Array.from(atob(b64), (c) => c.charCodeAt(0)) }).promise;
  if (pages[0] === 'find') { // find <text>: list the pages that contain it
    const hits = [];
    for (let n = 1; n <= doc.numPages; n++) {
      const tc = await (await doc.getPage(n)).getTextContent();
      if (tc.items.map((i) => i.str).join(' ').includes(pages[1])) hits.push(n);
    }
    return { total: doc.numPages, res: [], hits };
  }
  const list = pages[0] === 'all' ? Array.from({ length: doc.numPages }, (_, i) => i + 1) : pages.map(Number);
  const res = [];
  for (const n of list) {
    const p = await doc.getPage(n);
    const vp = p.getViewport({ scale: 1.1 });
    const c = document.createElement('canvas'); c.width = vp.width; c.height = vp.height;
    await p.render({ canvasContext: c.getContext('2d'), viewport: vp }).promise;
    res.push([n, c.toDataURL('image/png').split(',')[1]]);
  }
  return { total: doc.numPages, res };
}, b64, pages);
for (const [n, data] of out.res) writeFileSync(`${prefix}-p${n}.png`, Buffer.from(data, 'base64'));
console.log('pages:', out.total, out.hits ? `hits: ${out.hits.join(',')}` : '');
await page.close(); await browser.disconnect();
