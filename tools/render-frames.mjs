// Renders branded 16:9 PNG frames (CAF/Kpex/CAPEX) for each scene of a video script.
// The screenshot + on-screen text + brand are baked in; in HeyGen each frame is used as the scene
// background and the avatar is placed on top. Usage: node tools/render-frames.mjs <lessonId> [lang]
import fs from 'node:fs';
import path from 'node:path';
import puppeteer from 'puppeteer-core';

const ROOT = path.resolve(import.meta.dirname, '..');
const COURSE = path.join(ROOT, 'courses/kpex-user-course');
const OUT = path.join(ROOT, 'build/frames');

const CHROME = process.env.CHROME_PATH
  || ['C:/Program Files/Google/Chrome/Application/chrome.exe',
      'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
      '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
      '/usr/bin/google-chrome-stable', '/usr/bin/chromium'].find((p) => fs.existsSync(p));

const L = {
  es: { official: 'Curso oficial', next: 'Siguiente', sub: 'Kpex · CAF Corporation' },
  en: { official: 'Official course', next: 'Next', sub: 'Kpex · CAF Corporation' },
};

const esc = (s) => String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
const clean = (s) => s.replace(/\*/g, '').trim();               // strip markdown emphasis
// On-screen text lives inside *...* groups (the rest is a production note). One group → split by ·;
// several groups → each group is its own chip (keeps "1 · Sigue el orden" whole).
const chips = (s) => {
  const em = [...s.matchAll(/\*([^*]+)\*/g)].map((m) => m[1].trim());
  const groups = em.length ? em : [clean(s)];
  const out = groups.length === 1 ? groups[0].split(/\s·\s/) : groups;
  return out.map((x) => x.trim()).filter(Boolean);
};

function parse(file, lang) {
  const raw = fs.readFileSync(file, 'utf8').replace(/^\uFEFF/, '');
  const fm = raw.match(/^---\r?\n([\s\S]*?)\r?\n---\r?\n?([\s\S]*)$/);
  const meta = {}; (fm ? fm[1] : '').split(/\r?\n/).forEach((ln) => {
    const m = ln.match(/^(\w+):\s*"?(.*?)"?\s*$/); if (m) meta[m[1]] = m[2];
  });
  const body = fm ? fm[2] : raw;
  const blocks = body.split(/^##\s+(?:Escena|Scene)\s+/m).slice(1);
  const scenes = blocks.map((b) => {
    const layout = (b.match(/\*\*Layout:\*\*\s*([AB])/) || [])[1] || 'B';
    const bg = (b.match(/\*\*(?:Fondo|Background):\*\*\s*`([^`]+)`/) || [])[1] || '';
    const onscreen = (b.match(/\*\*(?:Texto en pantalla|On-screen text):\*\*\s*(.+)/) || [])[1] || '';
    return { layout, bg: bg.trim(), onscreen: onscreen.trim() };
  });
  return { meta, scenes, lang };
}

function dataUri(rel) {
  const abs = path.resolve(COURSE, rel);
  if (!fs.existsSync(abs)) return null;
  const ext = path.extname(abs).slice(1).toLowerCase();
  const mime = ext === 'jpg' ? 'jpeg' : ext;
  return `data:image/${mime};base64,${fs.readFileSync(abs).toString('base64')}`;
}

// Real brand logos (white versions from the brochure kit), embedded as data URIs.
const svgUri = (name) => `data:image/svg+xml;base64,${fs.readFileSync(path.join(ROOT, 'marketing/brochure/img', name)).toString('base64')}`;
const LOGO = { kpex: svgUri('kpex-white.svg'), akdemy: svgUri('akdemy-white.svg'), caf: svgUri('caf-white.svg') };

const CSS = `
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap');
  *{margin:0;padding:0;box-sizing:border-box}
  html,body{width:1920px;height:1080px;overflow:hidden;font-family:'Inter','Calibri',Arial,sans-serif}
  .f{position:relative;width:1920px;height:1080px;overflow:hidden}
  .kpex{font-weight:900;letter-spacing:-.02em} .kpex .x{color:#F5A011}
  /* A: intro/outro dark */
  .A{background:radial-gradient(120% 95% at 78% 15%,#3a2f1e 0%,#1d1a17 44%,#141210 100%)}
  .A.out{background:radial-gradient(120% 100% at 50% 20%,#3a2f1e 0%,#1d1a17 46%,#141210 100%);text-align:center;
         display:flex;flex-direction:column;align-items:center;justify-content:center}
  .grid{position:absolute;inset:0;background:
      linear-gradient(transparent 96%,rgba(245,160,17,.05) 96%) 0 0/104px 104px,
      linear-gradient(90deg,transparent 96%,rgba(245,160,17,.05) 96%) 0 0/104px 104px}
  .curve{position:absolute;right:118px;top:172px;font-size:78px;font-weight:800;color:#F5A011;opacity:.16}
  .avz{position:absolute;left:77px;top:270px;bottom:0;width:660px;border:2px dashed rgba(255,255,255,.16);
       border-bottom:none;border-radius:14px 14px 0 0}
  .logo{width:auto;display:block}
  .brandtop{position:absolute;top:70px;right:96px;display:flex;align-items:center;gap:34px}
  .brandtop .logo{height:50px} .brandtop .sep{width:1px;height:50px;background:rgba(255,255,255,.28)}
  .body{position:absolute;right:96px;top:500px;width:1010px;text-align:left}
  .eyebrow{display:inline-block;background:#F5A011;color:#1a1208;font-size:33px;font-weight:800;
           letter-spacing:.16em;text-transform:uppercase;padding:15px 31px;border-radius:38px}
  .title{color:#fff;font-size:122px;font-weight:900;line-height:1.04;letter-spacing:-.02em;margin:46px 0 30px}
  .sub{color:#cbb48c;font-size:48px;font-weight:600}
  .lt{position:absolute;left:0;bottom:96px;display:flex;align-items:stretch}
  .lt .bar{width:21px;background:#F5A011}
  .lt .tx{background:rgba(20,18,16,.82);padding:27px 50px 27px 38px}
  .lt .nm{color:#fff;font-size:52px;font-weight:800;line-height:1}
  .lt .ro{color:#cbc3b6;font-size:33px;font-weight:600;margin-top:13px}
  /* outro */
  .out .eyebrow{background:none;color:#F5A011;font-size:42px;letter-spacing:.24em;padding:0}
  .out .title{font-size:134px;margin:38px 0 40px}
  .out .arrow{color:#F5A011;font-size:76px;font-weight:900}
  .out .logos{position:absolute;bottom:92px;display:flex;gap:40px;align-items:center}
  .out .logos .logo{height:48px} .out .logos .sep{width:1px;height:46px;background:rgba(255,255,255,.28)}
  /* B: content */
  .B{background:#e9e4dc}
  .shot{position:absolute;inset:0;background-size:cover;background-position:center}
  .topbar{position:absolute;left:0;right:0;top:0;height:127px;background:rgba(20,18,16,.9);
          display:flex;align-items:center;justify-content:space-between;padding:0 77px;color:#fff}
  .topbar .tag{font-size:42px;font-weight:700} .topbar .tag i{color:#F5A011;font-style:normal;font-weight:900;margin-right:19px}
  .topbar .logo{height:46px}
  .accent{position:absolute;left:0;right:0;top:127px;height:10px;background:#F5A011}
  .panel{position:absolute;left:77px;bottom:96px;max-width:1080px;background:rgba(248,246,242,.96);
         border-left:15px solid #F5A011;border-radius:12px;padding:42px 50px;box-shadow:0 19px 57px rgba(0,0,0,.18)}
  .panel h3{color:#1a1a1a;font-size:50px;font-weight:800;margin-bottom:27px}
  .chips{display:flex;flex-wrap:wrap;gap:19px}
  .chip{background:#fff;border:1px solid #e0dcd6;color:#585856;font-size:36px;font-weight:600;padding:13px 29px;border-radius:60px}
  .chip.on{background:#F5A011;border-color:#F5A011;color:#1a1208}
  .avatar{position:absolute;right:77px;bottom:77px;width:384px;height:384px;border-radius:50%;
          background:radial-gradient(circle at 50% 35%,#3a3733,#1d1a17);border:12px solid #F5A011}
`;

function frame(scene, ctx) {
  const t = L[ctx.lang];
  if (scene.layout === 'A') {
    const isOut = /^(siguiente|next)\b/i.test(clean(scene.onscreen));
    if (isOut) {
      const em = (scene.onscreen.match(/\*([^*]+)\*/) || [, clean(scene.onscreen)])[1];
      const big = em.replace(/^(siguiente|next)[:\s]*/i, '').trim();
      return `<div class="f A out"><div class="grid"></div>
        <div class="eyebrow">${esc(t.next)}</div>
        <div class="title">${esc(big)}</div><div class="arrow">→</div>
        <div class="logos"><img class="logo" src="${LOGO.kpex}" alt="Kpex"><span class="sep"></span><img class="logo" src="${LOGO.akdemy}" alt="Akdemy"><span class="sep"></span><img class="logo" src="${LOGO.caf}" alt="CAF"></div></div>`;
    }
    return `<div class="f A"><div class="grid"></div><div class="curve">C = a · Qⁿ</div>
      <div class="avz"></div>
      <div class="brandtop"><img class="logo" src="${LOGO.kpex}" alt="Kpex"><span class="sep"></span><img class="logo" src="${LOGO.akdemy}" alt="Akdemy"></div>
      <div class="body"><span class="eyebrow">${esc(t.official)}</span>
        <div class="title">${esc(ctx.title)}</div><div class="sub">${esc(t.sub)}</div></div>
      <div class="lt"><div class="bar"></div><div class="tx"><div class="nm">Carlos Fuenmayor</div><div class="ro">${esc(ctx.lang === 'es' ? 'Facilitador · CAF Corporation' : 'Facilitator · CAF Corporation')}</div></div></div></div>`;
  }
  // Layout B — content
  const img = dataUri(scene.bg);
  const cs = chips(scene.onscreen);
  const chipsHtml = cs.map((c, i) => `<span class="chip${i === 0 ? ' on' : ''}">${esc(c)}</span>`).join('');
  return `<div class="f B">
    <div class="shot" style="background-image:url('${img || ''}')"></div>
    <div class="topbar"><span class="tag"><i>${esc(ctx.id)}</i>${esc(ctx.shortTitle)}</span><img class="logo" src="${LOGO.kpex}" alt="Kpex"></div>
    <div class="accent"></div>
    ${cs.length ? `<div class="panel"><div class="chips">${chipsHtml}</div></div>` : ''}
    <div class="avatar"></div></div>`;
}

async function main() {
  const [id, lang = 'es'] = process.argv.slice(2);
  if (!id) { console.error('Usage: node tools/render-frames.mjs <lessonId> [lang]'); process.exit(1); }
  if (!CHROME) { console.error('Chrome not found — set CHROME_PATH'); process.exit(1); }
  const gdir = path.join(COURSE, 'video', lang);
  const file = fs.readdirSync(gdir).find((f) => f.startsWith(`${id}.`) && f.endsWith('.md'));
  if (!file) { console.error(`No script ${lang}/${id}.md`); process.exit(1); }
  const { meta, scenes } = parse(path.join(gdir, file), lang);
  const ctx = { id, lang, title: meta.title || id, shortTitle: meta.title || id };

  fs.mkdirSync(OUT, { recursive: true });
  const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox'] });
  const page = await browser.newPage();
  await page.setViewport({ width: 1920, height: 1080, deviceScaleFactor: 1 });
  let n = 0;
  for (const scene of scenes) {
    n++;
    const html = `<!doctype html><html><head><meta charset="utf8"><style>${CSS}</style></head><body>${frame(scene, ctx)}</body></html>`;
    await page.setContent(html, { waitUntil: 'load', timeout: 30000 });
    await page.evaluate(async () => { try { await Promise.race([document.fonts.ready, new Promise((r) => setTimeout(r, 3000))]); } catch (e) {} });
    await new Promise((r) => setTimeout(r, 300));
    const out = path.join(OUT, `${id}-${lang}-${String(n).padStart(2, '0')}.png`);
    await page.screenshot({ path: out, clip: { x: 0, y: 0, width: 1920, height: 1080 } });
    console.log(`✔ escena ${n} (Layout ${scene.layout}) → ${path.relative(ROOT, out)}`);
  }
  await browser.close();
  console.log(`\nListo: ${n} marcos en build/frames/`);
}
main();
