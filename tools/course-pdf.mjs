// Course → print-ready PDF (A4), from the same build/<slug>-<lang>.json the LearnPress importer uses.
//   review  : for the authors — every quiz with its answer key, and the <!-- VERIFY --> notes shown as yellow boxes.
//   student : for a learner — name on every page, module quizzes as practice (no answers), no final exam, no notes.
// Usage: node tools/course-pdf.mjs [--lang es|en|all] [--mode review|student] [--student "Name"] [--slug kpex-user-course]
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { pathToFileURL } from 'node:url';

const ROOT = path.resolve(import.meta.dirname, '..');
const arg = (k, d) => { const i = process.argv.indexOf(`--${k}`); return i > 0 ? process.argv[i + 1] : d; };
const slug = arg('slug', 'kpex-user-course');
const mode = arg('mode', 'review');
const student = arg('student', '');
const langs = arg('lang', 'all') === 'all' ? ['es', 'en'] : [arg('lang')];
if (!['review', 'student'].includes(mode)) throw new Error('--mode must be review or student');
if (mode === 'student' && !student) throw new Error('--mode student needs --student "Name"');

const EDGE = `${process.env['ProgramFiles(x86)']}\\Microsoft\\Edge\\Application\\msedge.exe`;
const OUT = path.join(ROOT, 'build', 'pdf');
const IMG = path.join(ROOT, 'marketing', 'brochure', 'img');
const fileUrl = (p) => pathToFileURL(p).href;
const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
const cssStr = (s) => `"${String(s).replace(/\\/g, '\\\\').replace(/"/g, '\\"')}"`;

const L = {
  es: {
    review: 'Edición para revisión', studentEd: 'Edición del estudiante', licensed: 'Licenciado a', personal: 'Uso personal · no distribuir',
    draft: 'BORRADOR PARA REVISIÓN', contents: 'Contenido', module: 'Módulo', lessons: 'lecciones', quiz: 'Quiz', practice: 'Preguntas de práctica',
    practiceNote: 'Responde en la plataforma para obtener tu puntuación. El examen final solo está disponible en línea.',
    questions: 'preguntas', answerKey: 'Respuesta', why: 'Por qué', verify: 'Por verificar', pass: 'Aprobado con', tf: 'Verdadero o falso', multi: 'Varias respuestas',
    howTitle: 'Cómo usar este PDF', page: 'Página', of: 'de',
    how: [
      'Este PDF reúne el texto y las capturas de todas las lecciones del curso, en el mismo orden que en la plataforma.',
      'Los vídeos, ejercicios interactivos y quizzes con puntuación están en Akdemy; úsalo como material de lectura y consulta.',
      'Los nombres de pantallas y botones de Kpex se mantienen en inglés, tal como aparecen en la aplicación.',
    ],
    reviewHow: [
      'Esta edición es para el equipo autor: incluye las respuestas correctas y su explicación en cada quiz, también el examen final.',
      'Los recuadros amarillos «Por verificar» son dudas abiertas del contenido frente a la app. No aparecen en la edición del estudiante.',
    ],
    supported: 'Supported by', date: 'Fecha',
  },
  en: {
    review: 'Review edition', studentEd: 'Student edition', licensed: 'Licensed to', personal: 'Personal use · do not distribute',
    draft: 'DRAFT FOR REVIEW', contents: 'Contents', module: 'Module', lessons: 'lessons', quiz: 'Quiz', practice: 'Practice questions',
    practiceNote: 'Answer them on the platform to get your score. The final exam is available online only.',
    questions: 'questions', answerKey: 'Answer', why: 'Why', verify: 'To verify', pass: 'Pass mark', tf: 'True or false', multi: 'Multiple answers',
    howTitle: 'How to use this PDF', page: 'Page', of: 'of',
    how: [
      'This PDF brings together the text and screenshots of every lesson in the course, in the same order as on the platform.',
      'Videos, interactive exercises and scored quizzes live on Akdemy; use this as reading and reference material.',
      'Kpex screen and button names are kept exactly as they appear in the application.',
    ],
    reviewHow: [
      'This edition is for the authoring team: every quiz, including the final exam, shows the correct answers and their explanation.',
      'Yellow “To verify” boxes are open questions about the content versus the app. They do not appear in the student edition.',
    ],
    supported: 'Supported by', date: 'Date',
  },
};

function lessonHtml(html, review, t) {
  let out = html.replace(/src="akdemy-local:([^"]+)"/g, (_, p) => `src="${fileUrl(p)}"`).replace(/ loading="lazy"/g, '');
  out = out.replace(/<!--\s*VERIFY:?\s*([\s\S]*?)-->/g, (_, note) =>
    review ? `<aside class="verify"><b>${t.verify}</b>${esc(note.trim())}</aside>` : '');
  out = out.replace(/<!--[\s\S]*?-->/g, '');
  return out.replace(/<a [^>]*>([\s\S]*?)<\/a>/g, '<span class="link">$1</span>');
}

function quizHtml(q, review, t, isFinal) {
  const letters = 'ABCDEFGH';
  const qs = q.questions.map((qq, i) => {
    const kind = qq.type === 'true_or_false' ? t.tf : qq.type === 'multi_choice' ? t.multi : '';
    const opts = qq.answers.map((a, j) =>
      `<li class="${review && a.is_correct ? 'ok' : ''}"><span class="let">${letters[j]}</span>${esc(a.title)}</li>`).join('');
    const key = review
      ? `<div class="key"><b>${t.answerKey}: ${qq.answers.map((a, j) => a.is_correct ? letters[j] : '').join(' ').trim().replace(/ /g, ', ')}</b>${qq.explanation ? ` · ${t.why}: ${esc(qq.explanation)}` : ''}</div>`
      : '';
    return `<div class="q"><div class="qh"><span class="qn">${i + 1}</span><div>${esc(qq.title)}${kind ? ` <em class="qk">${kind}</em>` : ''}</div></div><ol class="opts">${opts}</ol>${key}</div>`;
  }).join('');
  const head = review ? q.title : `${t.practice} · ${q.title.replace(/^[^:]+:\s*/, '')}`;
  return `<section class="quiz${isFinal ? ' final' : ''}"><div class="quiz-head"><span class="eyebrow">${t.quiz}</span><h2>${esc(head)}</h2>
    <p class="meta">${q.questions.length} ${t.questions} · ${t.pass} ${q.passing_grade}%${review ? '' : ` — ${t.practiceNote}`}</p></div>${qs}</section>`;
}

function build(lang) {
  const data = JSON.parse(fs.readFileSync(path.join(ROOT, 'build', `${slug}-${lang}.json`), 'utf8'));
  const t = L[lang];
  const review = mode === 'review';
  const title = data.course.title;
  const [main, sub] = title.split(/:\s*/);
  const today = new Date().toLocaleDateString(lang === 'es' ? 'es-ES' : 'en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
  const footLeft = `Akdemy · ${main}`;
  const footCenter = review ? t.draft : `${t.licensed} ${student} · ${t.personal}`;

  const mods = data.sections.map((s, mi) => {
    const [label, name] = s.name.includes(':') ? [s.name.split(':')[0], s.name.split(':').slice(1).join(':').trim()] : [`${t.module} ${mi}`, s.name];
    const items = s.items.filter((it) => it.kind === 'lesson' || review || it.ref !== 'final');
    return { id: `m${mi}`, label, name, items, nLessons: s.items.filter((i) => i.kind === 'lesson').length };
  });

  const toc = mods.map((m) => `<li><a href="#${m.id}"><span class="tl">${esc(m.label)}</span><span class="tn">${esc(m.name)}</span></a>
    <ol>${m.items.filter((i) => i.kind === 'lesson').map((i) => `<li><a href="#l${i.ref.replace('.', '-')}">${esc(i.title)}</a><span>${esc(i.duration || '')}</span></li>`).join('')}</ol></li>`).join('');

  const body = mods.map((m) => `
    <section class="module" id="${m.id}">
      <div class="mod-band"><span class="eyebrow">${esc(m.label)}</span><h1>${esc(m.name)}</h1><p>${m.nLessons} ${t.lessons}</p></div>
      ${m.items.map((it) => it.kind === 'lesson'
        ? `<article class="lesson" id="l${it.ref.replace('.', '-')}"><header><span class="ref">${esc(it.ref)}</span><h2>${esc(it.title.replace(/^[\d.]+\s*·\s*/, ''))}</h2>${it.duration ? `<span class="dur">${esc(it.duration)}</span>` : ''}</header>
           <div class="content">${lessonHtml(it.content, review, t)}</div></article>`
        : quizHtml(it, review, t, it.ref === 'final')).join('')}
    </section>`).join('');

  return `<!doctype html><html lang="${lang}"><head><meta charset="utf-8"><title>${esc(title)}</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
  :root { --o:#F5A011; --oh:#d4880a; --g:#585856; --ink:#1a1a1a; --cream:#F8F6F2; --line:#e8e4dc; }
  @page { size: A4; margin: 20mm 17mm 18mm;
    @top-left { content: ${cssStr(review ? t.draft : '')}; font: 800 7.5pt Inter, Arial; letter-spacing: 1.2px; color: #d4880a; }
    @top-right { content: ${cssStr(review ? t.review : t.studentEd)}; font: 600 7.5pt Inter, Arial; color: #8d8a84; }
    @bottom-left { content: ${cssStr(footLeft)}; font: 600 7.5pt Inter, Arial; color: #8d8a84; }
    @bottom-center { content: ${cssStr(review ? '' : footCenter)}; font: 700 7.5pt Inter, Arial; color: #585856; }
    @bottom-right { content: ${cssStr(t.page + ' ')} counter(page) ${cssStr(' ' + t.of + ' ')} counter(pages); font: 600 7.5pt Inter, Arial; color: #8d8a84; } }
  @page cover { margin: 0; @top-left { content: none } @top-right { content: none } @bottom-left { content: none } @bottom-center { content: none } @bottom-right { content: none } }
  * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  html, body { margin: 0; font-family: Inter, Arial, sans-serif; color: var(--ink); font-size: 10pt; line-height: 1.55; }
  .eyebrow { display: inline-block; font-size: 7.5pt; font-weight: 800; letter-spacing: 1.4px; text-transform: uppercase; color: var(--oh);
    background: #FDF1DC; border-radius: 999px; padding: 2px 10px; }

  /* Cover */
  .cover { page: cover; height: 297mm; position: relative; color: #fff; overflow: hidden; break-after: page;
    /* Scrim pre-baked into a flat image (courses/<slug>/pdf/cover-bg.html): translucent gradients over photos turn pink in some PDF readers. */
    background: #1a1a18 url('${fileUrl(path.join(ROOT, 'courses', slug, 'pdf', 'cover-bg.png'))}') center / cover; }
  .cover .in { position: absolute; inset: 22mm 20mm 20mm; display: flex; flex-direction: column; }
  .cover .logo { width: 62mm; }
  .cover .ed { margin-top: auto; }
  .cover .ed .eyebrow { background: rgba(245,160,17,.16); color: var(--o); border: 1px solid rgba(245,160,17,.5); }
  .cover h1 { font-size: 40pt; line-height: 1.02; font-weight: 900; letter-spacing: -1.5px; margin: 8mm 0 4mm; }
  .cover h1 span { color: var(--o); }
  .cover .sub { font-size: 15pt; color: #e6e3dc; font-weight: 500; margin: 0 0 10mm; max-width: 150mm; }
  .cover .who { border-left: 3px solid var(--o); padding: 1mm 0 1mm 5mm; font-size: 10pt; color: #d9d5cc; }
  .cover .who b { display: block; font-size: 14pt; color: #fff; }
  .cover .base { margin-top: 14mm; padding-top: 6mm; border-top: 1px solid rgba(255,255,255,.18); display: flex; align-items: center; gap: 7mm; font-size: 8.5pt; color: #cfcbc2; }
  .cover .base img { height: 10mm; }
  .cover .base small { display: block; color: var(--o); font-weight: 800; letter-spacing: 1px; text-transform: uppercase; font-size: 7pt; }
  .cover .base .date { margin-left: auto; text-align: right; }

  /* Front matter */
  .front { break-after: page; }
  .front h2 { font-size: 16pt; font-weight: 900; letter-spacing: -.5px; margin: 0 0 3mm; } .front h2 + .toc, .how + h2 { margin-top: 6mm; }
  .how { background: var(--cream); border-radius: 3mm; padding: 3mm 5mm; margin-bottom: 5mm; font-size: 9pt; line-height: 1.45; }
  .how ul { margin: 0; padding-left: 5mm; } .how li { margin: 1mm 0; }
  .how.rev { background: #FFF6DC; border-left: 3px solid var(--o); }
  .toc { list-style: none; padding: 0; margin: 0; columns: 2; column-gap: 9mm; }
  .toc > li { break-inside: avoid; margin: 0 0 3.5mm; }
  .toc a { color: inherit; text-decoration: none; }
  .toc > li > a { display: block; border-bottom: 2px solid var(--o); padding-bottom: 1mm; margin-bottom: 1.5mm; }
  .toc .tl { display: block; font-size: 7pt; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase; color: var(--oh); }
  .toc .tn { font-weight: 800; font-size: 10.5pt; }
  .toc ol { list-style: none; padding: 0; margin: 0; font-size: 8pt; line-height: 1.35; color: var(--g); }
  .toc ol li { display: flex; justify-content: space-between; gap: 3mm; padding: .4mm 0; }
  .toc ol li span { color: #a19d95; white-space: nowrap; }

  /* Modules and lessons */
  .module { break-before: page; }
  .mod-band { background: linear-gradient(135deg, #1a1a18, #2e2e2c); color: #fff; border-radius: 3mm; padding: 8mm 9mm 7mm; margin-bottom: 8mm; border-bottom: 3px solid var(--o); }
  .mod-band .eyebrow { background: rgba(245,160,17,.16); color: var(--o); }
  .mod-band h1 { font-size: 24pt; font-weight: 900; letter-spacing: -.8px; line-height: 1.1; margin: 3mm 0 1mm; }
  .mod-band p { margin: 0; color: #cfcbc2; font-size: 9pt; }
  .lesson { margin: 0 0 10mm; }
  .lesson + .lesson { border-top: 1px solid var(--line); padding-top: 8mm; }
  .lesson header { display: flex; align-items: baseline; gap: 4mm; margin-bottom: 3mm; break-after: avoid; }
  .lesson .ref { font-weight: 900; color: var(--o); font-size: 17pt; letter-spacing: -.5px; }
  .lesson header h2 { font-size: 17pt; font-weight: 900; letter-spacing: -.5px; line-height: 1.15; margin: 0; flex: 1; }
  .lesson .dur { font-size: 8pt; color: #8d8a84; white-space: nowrap; }
  .content h2 { font-size: 12.5pt; font-weight: 800; margin: 6mm 0 2mm; break-after: avoid; }
  .content h3 { font-size: 10.5pt; font-weight: 800; color: var(--g); margin: 4.5mm 0 1.5mm; break-after: avoid; }
  .content p { margin: 0 0 2.5mm; orphans: 3; widows: 3; }
  .content ul, .content ol { margin: 0 0 3mm; padding-left: 6mm; } .content li { margin: .8mm 0; }
  .content li::marker { color: var(--o); font-weight: 800; }
  .content strong { font-weight: 700; }
  .content code { background: var(--cream); border-radius: 1mm; padding: 0 1mm; font-size: 9pt; }
  .content input[type=checkbox] { accent-color: var(--o); margin-right: 2mm; }
  .content .link { color: var(--oh); font-weight: 600; }
  .content table { width: 100%; border-collapse: collapse; margin: 2mm 0 4mm; font-size: 8.8pt; break-inside: auto; }
  .content thead { display: table-header-group; }
  .content tr { break-inside: avoid; }
  .content th { background: #2e2e2c; color: #fff; text-align: left; font-weight: 700; padding: 2mm 2.5mm; }
  .content td { border-bottom: 1px solid var(--line); padding: 1.8mm 2.5mm; vertical-align: top; }
  .content tbody tr:nth-child(even) td { background: #FBFAF7; }
  .content blockquote { margin: 3mm 0 4mm; background: var(--cream); border-left: 3px solid var(--o); border-radius: 0 2mm 2mm 0; padding: 3mm 5mm; break-inside: avoid; }
  .content blockquote p:last-child { margin: 0; }
  .content img.akdemy-shot { display: block; max-width: 100%; max-height: 120mm; margin: 3mm auto 5mm; border: 1px solid var(--line);
    border-radius: 2mm; box-shadow: 0 2mm 5mm -2mm rgba(26,26,24,.25); break-inside: avoid; }
  .content p:has(> img.akdemy-shot:only-child) { break-inside: avoid; }
  .verify { background: #FFF6CC; border: 1px solid #F2D15C; border-radius: 2mm; padding: 2.5mm 4mm; margin: 2.5mm 0 3.5mm; font-size: 8.5pt; color: #6b5510; break-inside: avoid; }
  .verify b { display: block; font-size: 7pt; letter-spacing: 1.2px; text-transform: uppercase; color: #a8830b; margin-bottom: .5mm; }

  /* Quizzes */
  .quiz { margin: 4mm 0 8mm; background: var(--cream); border-radius: 3mm; padding: 6mm 7mm 3mm; border-top: 3px solid var(--o); }
  .quiz.final { break-before: page; }
  .quiz-head h2 { font-size: 15pt; font-weight: 900; letter-spacing: -.4px; margin: 2.5mm 0 1mm; }
  .quiz-head .meta { font-size: 8.5pt; color: var(--g); margin: 0 0 4mm; }
  .q { background: #fff; border-radius: 2mm; padding: 3.5mm 4.5mm; margin-bottom: 3mm; break-inside: avoid; }
  .qh { display: flex; gap: 3mm; font-weight: 700; }
  .qn { flex: 0 0 6mm; height: 6mm; border-radius: 50%; background: var(--ink); color: #fff; font-size: 8pt; display: grid; place-items: center; }
  .qk { font-style: normal; font-weight: 600; font-size: 7.5pt; color: #8d8a84; }
  .opts { list-style: none; padding: 0 0 0 9mm; margin: 2mm 0 0; font-size: 9.2pt; }
  .opts li { display: flex; gap: 2.5mm; padding: 1mm 2mm; border-radius: 1.5mm; }
  .opts .let { flex: 0 0 4.5mm; height: 4.5mm; border: 1px solid #c9c4ba; border-radius: 50%; font-size: 7pt; font-weight: 800; display: grid; place-items: center; color: var(--g); margin-top: .5mm; }
  .opts li.ok { background: #E7F4EC; font-weight: 600; } .opts li.ok .let { background: #1A7A4A; border-color: #1A7A4A; color: #fff; }
  .key { margin: 2mm 0 0 9mm; font-size: 8.3pt; color: var(--g); } .key b { color: #1A7A4A; }
</style></head><body>
<section class="cover"><div class="in">
  <img class="logo" src="${fileUrl(path.join(IMG, 'akdemy-white.svg'))}" alt="Akdemy">
  <div class="ed">
    <span class="eyebrow">${review ? t.review : t.studentEd}</span>
    <h1>${esc(main)}${sub ? `:<br><span>${esc(sub)}</span>` : ''}</h1>
    <p class="sub">${esc(data.course.excerpt || '')}</p>
    ${review ? '' : `<div class="who">${t.licensed}<b>${esc(student)}</b></div>`}
  </div>
  <div class="base">
    <img src="${fileUrl(path.join(IMG, 'caf-white.svg'))}" alt="CAF">
    <div><small>${t.supported}</small><b>KPEX</b> · Project Cost Intelligence Platform</div>
    <div class="date"><small>${t.date}</small>${today}</div>
  </div>
</div></section>
<section class="front">
  <h2>${t.howTitle}</h2>
  <div class="how"><ul>${t.how.map((x) => `<li>${x}</li>`).join('')}</ul></div>
  ${review ? `<div class="how rev"><ul>${t.reviewHow.map((x) => `<li>${x}</li>`).join('')}</ul></div>` : ''}
  <h2>${t.contents}</h2>
  <ol class="toc">${toc}</ol>
</section>
${body}
</body></html>`;
}

fs.mkdirSync(OUT, { recursive: true });
const tag = mode === 'review' ? 'review' : `student-${student.normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^A-Za-z0-9]+/g, '-').replace(/^-|-$/g, '').toLowerCase()}`;
for (const lang of langs) {
  const html = path.join(OUT, `${slug}-${lang}-${tag}.html`);
  const pdf = path.join(OUT, `Akdemy-${slug}-${lang}-${tag}.pdf`);
  fs.writeFileSync(html, build(lang));
  // Edge sometimes exits before writing the file (another headless instance holding the profile): retry.
  if (fs.existsSync(pdf)) fs.rmSync(pdf);
  for (let tries = 0; tries < 3 && !fs.existsSync(pdf); tries++) {
    execFileSync(EDGE, ['--headless=new', '--disable-gpu', '--no-pdf-header-footer', '--virtual-time-budget=15000',
      `--print-to-pdf=${pdf}`, fileUrl(html)], { stdio: 'ignore', timeout: 180000 });
  }
  console.log(`OK ${path.basename(pdf)} (${Math.round(fs.statSync(pdf).size / 1024)} KB)`);
}
