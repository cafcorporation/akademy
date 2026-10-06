// Builds a HeyGen recording kit from a video script (guion video/<lang>/<id>.md):
// a folder with the scene background images numbered in order (01-…, 02-…), SCRIPT.txt
// (the spoken script, one paragraph per scene) and INSTRUCTIONS.txt (HeyGen production notes).
// Output: courses/<Course Name> (ES|EN)/<id> - <title>/
// Usage: node tools/make-recording-kit.mjs <lessonId> <es|en>
import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(import.meta.dirname, '..');
const COURSE = path.join(ROOT, 'courses/kpex-user-course');
const COURSE_DIR = { es: 'Cómo usar Kpex (ES)', en: 'How to Use Kpex (EN)' };

const [id, lang = 'es'] = process.argv.slice(2);
if (!id || !['es', 'en'].includes(lang)) {
  console.error('Usage: node tools/make-recording-kit.mjs <lessonId> <es|en>');
  process.exit(1);
}

const guionPath = path.join(COURSE, 'video', lang, `${id}.md`);
if (!fs.existsSync(guionPath)) { console.error(`No hay guion: video/${lang}/${id}.md`); process.exit(1); }

const raw = fs.readFileSync(guionPath, 'utf8').replace(/^﻿/, '');
const fm = raw.match(/^---\r?\n([\s\S]*?)\r?\n---\r?\n?([\s\S]*)$/);
const meta = {};
(fm ? fm[1] : '').split(/\r?\n/).forEach((ln) => { const m = ln.match(/^(\w+):\s*"?(.*?)"?\s*$/); if (m) meta[m[1]] = m[2]; });
const body = fm ? fm[2] : raw;

const blocks = body.split(/^##\s+(?:Escena|Scene)\s+/m).slice(1);
const scenes = blocks.map((b) => {
  const layout = (b.match(/\*\*Layout:\*\*\s*([AB])/) || [])[1] || 'B';
  const fondoLine = ((b.match(/\*\*(?:Fondo|Background):\*\*\s*(.+)/) || [])[1] || '').trim();
  const bgPaths = [...fondoLine.matchAll(/`([^`]+)`/g)].map((m) => m[1].trim());
  const textoLine = ((b.match(/\*\*(?:Texto en pantalla|On-screen text):\*\*\s*(.+)/) || [])[1] || '').trim();
  const sm = b.match(/\*\*(?:Guion|Script)\*\*\s*```\r?\n([\s\S]*?)```/);
  return { layout, fondoLine, bgPaths, textoLine, script: sm ? sm[1].trim() : '' };
});

// ── Ordered, de-duplicated background images → NN-<basename> ──────────────────
const map = new Map(); // abspath → {name}
const order = [];
for (const sc of scenes) {
  for (const rel of sc.bgPaths) {
    const abs = path.resolve(COURSE, rel);
    if (!map.has(abs)) {
      const name = `${String(order.length + 1).padStart(2, '0')}-${path.basename(abs)}`;
      map.set(abs, { name });
      order.push(abs);
    }
  }
}
const numbered = (rel) => { const e = map.get(path.resolve(COURSE, rel)); return e ? e.name : path.basename(rel); };
const bgDark = (order.map((a) => map.get(a).name).find((n) => /bg-dark\./.test(n))) || '01-bg-dark.jpg';

// ── Per-scene instruction text ────────────────────────────────────────────────
const fondoDesc = (sc) => sc.fondoLine.replace(/`([^`]+)`/g, (_, p) => numbered(p.trim())).trim();
const textoDesc = (sc) => {
  const em = [...sc.textoLine.matchAll(/\*([^*]+)\*/g)].map((m) => m[1].trim());
  if (!em.length) return sc.textoLine.replace(/\*/g, '').trim();
  const lead = sc.textoLine.slice(0, sc.textoLine.indexOf('*')).trim().replace(/[:,]\s*$/, '');
  const quoted = em.map((t) => `"${t}"`).join(' · ');
  return lead ? `${lead}: ${quoted}` : quoted;
};

const [mm, ss] = (meta.target_length || '0:00').split(':').map(Number);
const pron = (body.match(/\*\*(?:Pronunciación|Pronunciation):\*\*\s*([^\n]+)/) || [])[1];
const pronClean = pron ? pron.replace(/[*`]/g, '').replace(/\s+/g, ' ').trim() : '';

const T = {
  es: {
    dur: `unos ${mm} minutos${ss ? ` ${ss} segundos` : ''}`,
    header: (n, d) => `Vídeo educativo en español, 16:9, 1080p, ${d}. Usa el guion TAL CUAL, palabra por palabra: no lo reescribas, no lo resumas y no añadas frases. Cada párrafo del guion es una escena (${n} escenas).`,
    style: 'Estilo: sobrio y profesional, marca CAF: naranja #F5A011, gris oscuro #585856, fondos oscuros #1a1a18. Tipografía sans-serif. Textos en pantalla cortos. Sin música estridente; si hay música, suave y baja. Subtítulos activados.',
    layoutA: (bg) => `Layout A = avatar grande sobre el fondo oscuro ${bg}.`,
    layoutB: 'Layout B = la imagen adjunta a pantalla completa, bien legible, con el avatar pequeño en un círculo abajo a la derecha, sin tapar la zona importante de la imagen. No recortes ni deformes las imágenes.',
    scene: (n, sc) => `Escena ${n} (párrafo ${n}): Layout ${sc.layout}. Fondo: ${fondoDesc(sc)}.${textoDesc(sc) ? ` Texto en pantalla: ${textoDesc(sc)}.` : ''}`,
    pron: (p) => `Pronunciación: ${p}`,
  },
  en: {
    dur: `about ${mm} minutes${ss ? ` ${ss} seconds` : ''}`,
    header: (n, d) => `Educational video in English, 16:9, 1080p, ${d}. Use the script EXACTLY as written, word for word: do not rewrite, summarise or add sentences. Each paragraph of the script is one scene (${n} scenes).`,
    style: 'Style: sober and professional, CAF brand: orange #F5A011, dark grey #585856, dark backgrounds #1a1a18. Sans-serif type. Short on-screen text. No loud music; if any, soft and low. Captions on.',
    layoutA: (bg) => `Layout A = large avatar over the dark background ${bg}.`,
    layoutB: 'Layout B = the attached screenshot full screen and clearly readable, with the avatar small in a circle at the bottom right, not covering the important part of the screenshot. Do not crop or distort the screenshots.',
    scene: (n, sc) => `Scene ${n} (paragraph ${n}): Layout ${sc.layout}. Background: ${fondoDesc(sc)}.${textoDesc(sc) ? ` On-screen text: ${textoDesc(sc)}.` : ''}`,
    pron: (p) => `Pronunciation: ${p}`,
  },
}[lang];

const instructions = [
  T.header(scenes.length, T.dur),
  T.style,
  `${T.layoutA(bgDark)}\n${T.layoutB}`,
  scenes.map((sc, i) => T.scene(i + 1, sc)).join('\n'),
  ...(pronClean ? [T.pron(pronClean)] : []),
].join('\n\n') + '\n';

const script = scenes.map((s) => s.script).filter(Boolean).join('\n\n') + '\n';

// ── Write the kit ─────────────────────────────────────────────────────────────
const kitDir = path.join(ROOT, 'courses', COURSE_DIR[lang], `${id} - ${meta.title}`);
fs.mkdirSync(kitDir, { recursive: true });
let copied = 0, missing = 0;
for (const abs of order) {
  const dest = path.join(kitDir, map.get(abs).name);
  if (fs.existsSync(abs)) { fs.copyFileSync(abs, dest); copied++; }
  else { console.warn(`  ⚠ falta imagen: ${path.relative(ROOT, abs)}`); missing++; }
}
fs.writeFileSync(path.join(kitDir, 'SCRIPT.txt'), script);
fs.writeFileSync(path.join(kitDir, 'INSTRUCTIONS.txt'), instructions);

console.log(`✔ ${path.relative(ROOT, kitDir)}  (${scenes.length} escenas · ${copied} imágenes${missing ? ` · ${missing} faltan` : ''})`);
