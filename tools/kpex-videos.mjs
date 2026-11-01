// «Watch it in Kpex» — inserts the Kpex navigation videos (module / sub-module tours recorded in the real app)
// into the lessons of the Kpex course, between markers, so it can be re-run whenever videos are added.
//   node tools/kpex-videos.mjs [en|es]        (default en)
// Video catalogue: courses/kpex-user-course/video/kpex-navigation-videos.json (exported from Kpex config).
// Files: Akdemy's own copy in wp-content/uploads/kpex-videos/ (tools/sync-kpex-videos.sh), independent of the Kpex server.
// The estimate chapters (Class 5, Class 4) are the guided-demo videos on Vimeo (embed-only, Akdemy domain allowed).
import fs from 'node:fs';
import path from 'node:path';

const lang = process.argv[2] || 'en';
// Optional staged rollout: `node tools/kpex-videos.mjs es 6.1,6.2` touches only those lessons; others stay untouched.
const only = (process.argv[3] || '').split(',').map(s => s.trim()).filter(Boolean);
const BASE = process.env.KPEX_VIDEO_BASE || '/wp-content/uploads/kpex-videos/';   // Akdemy's own copy (tools/sync-kpex-videos.sh)
const ROOT = path.resolve('courses/kpex-user-course');
const cat = JSON.parse(fs.readFileSync(path.join(ROOT, 'video/kpex-navigation-videos.json'), 'utf8'));

const VIMEO = {
  class5: { code: 'Chapter 1', title: { en: 'Class 5 estimate from a capacity curve', es: 'Estimado Clase 5 por curva de capacidad' }, id: '1234328822', h: 'b2dc007046', secs: 121 },
  class4: { code: 'Chapter 2', title: { en: 'Class 4: equipment, ISBL bulk and crews', es: 'Clase 4: equipos, bulk ISBL y cuadrillas' }, id: '1234348981', h: '0dc9bea106', secs: 144 },
};

// lesson id → videos, in viewing order
const MAP = {
  '1.2': ['1g'],
  '4.2': ['vimeo:class4'],
  '4.3': ['reports'],
  '4.4': ['vimeo:class5'],
  '5.1': ['a', 'a1', 'a2', 'a3', 'a4', 'a5', 'a6'],
  '5.2': ['b', 'b1', 'b2', 'b3', 'b4'],
  '5.3': ['c', 'c1', 'c2', 'c3', 'c4', 'c5', 'c6'],
  '5.4': ['d', 'd1', 'e', 'e11', 'e12', 'e2', 'e3', 'e4', 'e5'],
  '6.1': ['z', 'z1', 'z2', 'z3'],
  '6.2': ['z4'],
  '6.3': ['z5'],
  '6.4': ['z6'],
};

const T = {
  en: { h: 'Watch it in Kpex', p: 'Short tours of the real platform, recorded in Kpex itself. English voice — press CC in the player for English or Spanish subtitles.', key: 'Key idea' },
  es: { h: 'Míralo en Kpex', p: 'Recorridos breves de la plataforma real, grabados en el propio Kpex. Voz en inglés — pulsa CC en el reproductor para subtítulos en español o inglés.', key: 'Idea clave' },
}[lang];

const mmss = s => s ? `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}` : '';
const esc = s => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

function block(key) {
  if (key.startsWith('vimeo:')) {
    const v = VIMEO[key.slice(6)];
    const q = new URLSearchParams({ h: v.h, title: 0, byline: 0, portrait: 0, dnt: 1, texttrack: lang }).toString();
    return `<figure class="kpex-vid"><div class="kpex-vid-frame"><iframe src="https://player.vimeo.com/video/${v.id}?${q}" title="${esc(v.title[lang])}" loading="lazy" allow="fullscreen; picture-in-picture" allowfullscreen></iframe></div>`
      + `<figcaption><strong>${esc(v.code)} · ${esc(v.title[lang])}</strong> · ${mmss(v.secs)}</figcaption></figure>`;
  }
  const v = cat[key];
  if (!v) throw new Error(`unknown video key: ${key}`);
  const u = BASE + v.file;
  const tracks = [['en', 'English'], ['es', 'Español']]
    .map(([l, n]) => `<track kind="subtitles" srclang="${l}" label="${n}" src="${u}_${l.toUpperCase()}.vtt"${l === lang ? ' default' : ''}>`).join('');
  return `<figure class="kpex-vid"><video controls preload="none" playsinline controlslist="nodownload" oncontextmenu="return false" poster="${u}.jpg">`
    + `<source src="${u}.mp4" type="video/mp4">${tracks}</video>`
    + `<figcaption><strong>${esc(v.code)} · ${esc(v.title)}</strong> · ${mmss(v.secs)}</figcaption></figure>`;
}

const START = '<!-- kpex-videos:start -->', END = '<!-- kpex-videos:end -->';           // bottom gallery
const ISTART = '<!-- kpex-intro:start -->', IEND = '<!-- kpex-intro:end -->';           // top intro video
const dir = path.join(ROOT, lang);
let done = 0;
for (const f of fs.readdirSync(dir).filter(f => f.endsWith('.md'))) {
  const id = f.split('-')[0];
  if (only.length && !only.includes(id)) continue;                        // staged rollout: leave the rest untouched
  const file = path.join(dir, f);
  let md = fs.readFileSync(file, 'utf8');
  md = md.replace(new RegExp(`\\n?${ISTART}[\\s\\S]*?${IEND}\\n?`), '\n');  // remove a previous intro
  md = md.replace(new RegExp(`\\n?${START}[\\s\\S]*?${END}\\n?`), '\n');    // remove a previous gallery
  if (!MAP[id]) { fs.writeFileSync(file, md); continue; }

  // The lesson's primary video becomes the top intro (full-width); any extras stay in the bottom gallery.
  const [intro, ...rest] = MAP[id];
  const introFig = block(intro).replace('class="kpex-vid"', 'class="kpex-vid kpex-intro"');
  const introSec = `${ISTART}\n${introFig}\n${IEND}\n`;
  const fm = md.match(/^(---\n[\s\S]*?\n---\n)/);                           // insert right after the front matter
  md = fm ? fm[1] + '\n' + introSec + md.slice(fm[1].length) : introSec + md;

  if (rest.length) {
    const section = `${START}\n## ${T.h}\n\n${T.p}\n\n<div class="kpex-vids">\n${rest.map(block).join('\n')}\n</div>\n${END}\n`;
    const at = md.indexOf(`\n## ${T.key}`);
    md = at >= 0 ? md.slice(0, at + 1) + section + '\n' + md.slice(at + 1) : md.trimEnd() + '\n\n' + section;
  }
  fs.writeFileSync(file, md);
  done++;
  console.log(`${lang}/${f}: intro=${intro} gallery=${rest.length}`);
}
console.log(`${done} lessons updated`);
