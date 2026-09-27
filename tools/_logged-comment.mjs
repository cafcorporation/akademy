import puppeteer from 'puppeteer-core';
import { readFileSync } from 'node:fs';
const cookies = JSON.parse(readFileSync(process.argv[2], 'utf8').replace(/^\uFEFF/, ''));
const b = await puppeteer.connect({ browserURL: 'http://127.0.0.1:9333' });
const ctx = await b.createBrowserContext(); const p = await ctx.newPage();
await p.setCookie(...cookies.map((c) => ({ ...c, domain: 'localhost', path: '/' })));
await p.goto(process.argv[3], { waitUntil: 'networkidle2' });
console.log(await p.evaluate(() => { const f = document.querySelector('#commentform'); return f ? 'FORM: ' + [...f.querySelectorAll('textarea,input[type=submit],button')].map((e) => e.getAttribute('placeholder') || e.value || e.innerText).join(' | ') + ' || ' + (document.querySelector('#respond h3, .comment-reply-title')?.innerText || '') : 'NO FORM'; }));
await ctx.close(); await b.disconnect();
