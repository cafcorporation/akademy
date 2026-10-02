// Turns a video script into a Synthesia video: renders branded frames, uploads them, places the
// avatar on top (per-scene layout), and downloads the MP4. The CAF brand lives in the frames — no
// Synthesia Brand Kit needed. Usage: node tools/render-video.mjs <lessonId> [lang] [--final] [--dry-run]
//   --dry-run  build + print the payload, no uploads, no API calls (no key/credits needed)
//   --final    real render without watermark (spends credits); default is test mode (watermark, free)
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { execFileSync } from 'node:child_process';

const ROOT = path.resolve(import.meta.dirname, '..');
const COURSE = path.join(ROOT, 'courses/kpex-user-course');
const FRAMES = path.join(ROOT, 'build/frames');
const VIDEO = path.join(ROOT, 'build/video');
const CACHE = path.join(ROOT, 'build/.synthesia-assets.json');
const CFG = JSON.parse(fs.readFileSync(path.join(COURSE, 'video/synthesia.config.json'), 'utf8'));
const API = 'https://api.synthesia.io/v2';
const UPLOAD = 'https://upload.api.synthesia.io/v2/assets';

// Minimal .env loader (KEY=VALUE), does not override already-set env.
function loadEnv() {
  const f = path.join(ROOT, '.env');
  if (!fs.existsSync(f)) return;
  for (const ln of fs.readFileSync(f, 'utf8').split(/\r?\n/)) {
    const m = ln.match(/^\s*([A-Z0-9_]+)\s*=\s*(.*)\s*$/);
    if (m && !process.env[m[1]]) process.env[m[1]] = m[2].trim().replace(/^["']|["']$/g, '').trim();
  }
}

// Parse a script: scene layout + the narration (first fenced code block after **Guion**/**Script**).
function parseScenes(file) {
  const raw = fs.readFileSync(file, 'utf8').replace(/^﻿/, '');
  const body = (raw.match(/^---\r?\n[\s\S]*?\r?\n---\r?\n?([\s\S]*)$/) || [, raw])[1];
  return body.split(/^##\s+(?:Escena|Scene)\s+/m).slice(1).map((b) => ({
    layout: (b.match(/\*\*Layout:\*\*\s*([AB])/) || [])[1] || 'B',
    script: (b.match(/```[a-z]*\r?\n([\s\S]*?)```/) || [, ''])[1].trim(),
  }));
}

const readCache = () => (fs.existsSync(CACHE) ? JSON.parse(fs.readFileSync(CACHE, 'utf8')) : {});
const writeCache = (c) => fs.writeFileSync(CACHE, JSON.stringify(c, null, 2));

async function uploadFrame(pngPath, key, cache) {
  const hash = crypto.createHash('md5').update(fs.readFileSync(pngPath)).digest('hex');
  if (cache[hash]) return cache[hash];
  const res = await fetch(UPLOAD, {
    method: 'POST',
    headers: { Authorization: key, 'Content-Type': 'image/png' },
    body: fs.readFileSync(pngPath),
  });
  if (!res.ok) throw new Error(`upload ${path.basename(pngPath)}: ${res.status} ${await res.text()}`);
  const { id } = await res.json();
  cache[hash] = id; writeCache(cache);
  return id;
}

async function main() {
  const args = process.argv.slice(2);
  const dry = args.includes('--dry-run');
  const final = args.includes('--final');
  const [id, lang = 'es'] = args.filter((a) => !a.startsWith('--'));
  if (!id) { console.error('Usage: node tools/render-video.mjs <lessonId> [lang] [--final] [--dry-run]'); process.exit(1); }

  // 1) Ensure branded frames exist (render them if missing).
  const gdir = path.join(COURSE, 'video', lang);
  const gfile = fs.readdirSync(gdir).find((f) => f.startsWith(`${id}.`) && f.endsWith('.md'));
  if (!gfile) { console.error(`No script ${lang}/${id}.md`); process.exit(1); }
  const scenes = parseScenes(path.join(gdir, gfile));
  const framePath = (n) => path.join(FRAMES, `${id}-${lang}-${String(n).padStart(2, '0')}.png`);
  if (!fs.existsSync(framePath(1))) {
    console.log('· Renderizando marcos…');
    execFileSync(process.execPath, [path.join(ROOT, 'tools/render-frames.mjs'), id, lang], { stdio: 'inherit' });
  }

  loadEnv();
  const key = process.env.SYNTHESIA_API_KEY;
  if (!dry && CFG.avatar.includes('REEMPLAZA')) { console.error('⚠ Pon tu avatar ID en video/synthesia.config.json'); process.exit(1); }

  // 2) Build the per-scene input (frame as background + avatar on top per layout).
  const cache = readCache();
  const input = [];
  for (let i = 0; i < scenes.length; i++) {
    const s = scenes[i];
    const png = framePath(i + 1);
    if (!fs.existsSync(png)) throw new Error(`falta el marco ${png}`);
    const lay = CFG.layouts[s.layout] || CFG.layouts.B;
    const avatarSettings = { style: lay.style, scale: lay.scale };
    // horizontalAlign is only valid for rectangular avatars (Synthesia rejects it for circular).
    if (lay.style !== 'circular' && lay.horizontalAlign) avatarSettings.horizontalAlign = lay.horizontalAlign;
    if (CFG.voice) avatarSettings.voice = CFG.voice;
    const background = dry ? `<frame:${path.basename(png)}>` : await uploadFrame(png, key, cache);
    input.push({ scriptText: s.script, avatar: CFG.avatar, avatarSettings, background });
  }

  const payload = { title: `Akdemy ${id} (${lang})`, visibility: 'private', test: !final, aspectRatio: CFG.aspectRatio, input };

  if (dry) {
    console.log(JSON.stringify(payload, null, 2));
    console.log(`\n[dry-run] ${input.length} escenas · test=${!final}. Sin subir nada.`);
    return;
  }
  if (!key) { console.error('⚠ Falta SYNTHESIA_API_KEY en .env'); process.exit(1); }

  // 3) Create the video and poll until complete.
  console.log(`· Creando video (${input.length} escenas, ${final ? 'FINAL' : 'test/watermark'})…`);
  let res = await fetch(`${API}/videos`, {
    method: 'POST', headers: { Authorization: key, 'Content-Type': 'application/json' }, body: JSON.stringify(payload),
  });
  if (!res.ok) throw new Error(`create video: ${res.status} ${await res.text()}`);
  const video = await res.json();
  console.log(`· Video ${video.id} en proceso (suele tardar 3–5 min)…`);

  let status = video.status, dl = video.download;
  for (let t = 0; t < 80 && status !== 'complete'; t++) {
    await new Promise((r) => setTimeout(r, 15000));
    res = await fetch(`${API}/videos/${video.id}`, { headers: { Authorization: key } });
    const v = await res.json(); status = v.status; dl = v.download;
    process.stdout.write(`\r  estado: ${status}${status === 'in_progress' ? ' …' : ''}   `);
  }
  console.log('');
  if (status !== 'complete' || !dl) { console.error(`No completó (estado ${status}). Revisa en el dashboard de Synthesia.`); process.exit(1); }

  // 4) Download the MP4.
  fs.mkdirSync(VIDEO, { recursive: true });
  const out = path.join(VIDEO, `${id}-${lang}.mp4`);
  const buf = Buffer.from(await (await fetch(dl)).arrayBuffer());
  fs.writeFileSync(out, buf);
  console.log(`\n✔ Listo: ${path.relative(ROOT, out)}  (${(buf.length / 1e6).toFixed(1)} MB)`);
  console.log('  Súbelo a Vimeo y pega el enlace como video_url en la lección.');
}
main().catch((e) => { console.error('✖', e.message); process.exit(1); });
