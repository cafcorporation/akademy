// Builds each course in courses/<slug>/ into build/<slug>.<lang>.json for the Akdemy Bridge importer.
// Usage: node tools/build-course.mjs [slug]
import fs from 'node:fs';
import path from 'node:path';
import yaml from 'js-yaml';
import { marked } from 'marked';

const ROOT = path.resolve(import.meta.dirname, '..');
const COURSES = path.join(ROOT, 'courses');
const OUT = path.join(ROOT, 'build');

const PENDING = {
  es: '<p class="akdemy-pending"><strong>🚧 Lección en preparación.</strong> Este contenido se publicará muy pronto.</p>',
  en: '<p class="akdemy-pending"><strong>🚧 Lesson in preparation.</strong> This content will be published very soon.</p>',
};
const MISSING_IMG = {
  es: 'Captura pendiente',
  en: 'Screenshot pending',
};
const TF = { es: ['Verdadero', 'Falso'], en: ['True', 'False'] };
const QTYPE = { single: 'single_choice', multi: 'multi_choice', true_false: 'true_or_false' };

function readFrontMatter(file) {
  const raw = fs.readFileSync(file, 'utf8').replace(/^﻿/, '');
  const m = raw.match(/^---\r?\n([\s\S]*?)\r?\n---\r?\n?([\s\S]*)$/);
  return m ? { meta: yaml.load(m[1]) ?? {}, body: m[2] } : { meta: {}, body: raw };
}

function findLessonFile(dir, id) {
  if (!fs.existsSync(dir)) return null;
  const hit = fs.readdirSync(dir).find((f) => f.startsWith(`${id}-`) && f.endsWith('.md'));
  return hit ? path.join(dir, hit) : null;
}

// Markdown → HTML. Images whose file does not exist yet become a visible placeholder box.
function renderMarkdown(body, baseDir, lang) {
  // Strip authoring comments (<!-- VERIFY … -->, <!-- DECISION PENDING … -->) so internal notes
  // never reach the published HTML — marked passes HTML comments through verbatim otherwise.
  body = body.replace(/<!--[\s\S]*?-->/g, '');
  const html = marked.parse(body, { gfm: true });
  return html.replace(/<img src="([^"]+)" alt="([^"]*)"\s*\/?>/g, (tag, src, alt) => {
    if (/^https?:/.test(src)) return tag;
    // Language-specific version wins: screenshots/<lang>/<file> (e.g. Akdemy's own ES/EN pages), else shared.
    const shared = path.resolve(baseDir, src);
    const perLang = path.join(path.dirname(shared), lang, path.basename(shared));
    const local = fs.existsSync(perLang) ? perLang : shared;
    if (fs.existsSync(local)) {
      // The importer uploads it to the media library and swaps in the real URL.
      return `<img src="akdemy-local:${local.replace(/\\/g, '/')}" alt="${alt}" loading="lazy" class="akdemy-shot">`;
    }
    return `<span class="akdemy-shot-pending" style="display:block;padding:28px;border:2px dashed #f6a10e;border-radius:8px;text-align:center;color:#8a6d1f;background:#fff8ec">📷 ${MISSING_IMG[lang]}: ${alt}</span>`;
  });
}

// Catches authoring mistakes before they reach LearnPress (which rejects them one at a time).
function validateQuestion(q, where) {
  const fail = (msg) => { throw new Error(`${where}: ${msg}`); };
  if (!QTYPE[q.type]) fail(`unknown type "${q.type}"`);
  if (!q.question?.es || !q.question?.en) fail('question needs es + en');
  if (q.type === 'true_false') {
    if (typeof q.answer !== 'boolean') fail('true_false answer must be true/false');
    return;
  }
  const n = q.options?.es?.length;
  if (!n || n < 2 || q.options?.en?.length !== n) fail('options es/en must exist, match in length, and have ≥2 items');
  const answers = [].concat(q.answer);
  if (answers.some((a) => !Number.isInteger(a) || a < 0 || a >= n)) fail(`answer index out of range 0..${n - 1}`);
  if (q.type === 'single' && answers.length !== 1) fail('single needs exactly one answer');
}

function buildQuestion(q, lang, i) {
  const type = QTYPE[q.type];
  let answers;
  if (q.type === 'true_false') {
    answers = TF[lang].map((title, idx) => ({ title, is_correct: (idx === 0) === q.answer }));
  } else {
    const correct = new Set([].concat(q.answer));
    answers = q.options[lang].map((title, idx) => ({ title, is_correct: correct.has(idx) }));
  }
  return {
    title: q.question[lang].replace(/\*\*/g, ''),
    type,
    order: i + 1,
    mark: 1,
    answers,
    explanation: q.explanation?.[lang] ?? '',
  };
}

function coverPath(dir, pattern, lang) {
  const file = path.join(dir, pattern.replace('{lang}', lang));
  return fs.existsSync(file) ? file : null;
}

function buildCourse(slug) {
  const dir = path.join(COURSES, slug);
  const course = yaml.load(fs.readFileSync(path.join(dir, 'course.yml'), 'utf8'));
  const outputs = [];

  for (const lang of course.editions) {
    const stats = { lessons: 0, written: 0, quizzes: 0, questions: 0 };
    const sections = course.modules.map((mod, mi) => {
      const items = mod.lessons.map((l) => {
        stats.lessons++;
        const file = findLessonFile(path.join(dir, lang), l.id);
        let content = PENDING[lang];
        let duration = '';
        let videoUrl = '';
        if (file) {
          const { meta, body } = readFrontMatter(file);
          if (String(meta.id) !== l.id) console.warn(`⚠ ${path.basename(file)}: front-matter id "${meta.id}" ≠ "${l.id}"`);
          if (meta.title && meta.title !== l.title[lang]) console.warn(`⚠ ${lang}/${path.basename(file)}: title differs from course.yml`);
          content = renderMarkdown(body, path.dirname(file), lang);
          duration = meta.duration ?? '';
          // Optional: a hosted video (YouTube/Vimeo/Bunny… link) shown above the text via Eduma's lesson "Media" field.
          videoUrl = String(meta.video_url ?? '').trim();
          if (videoUrl && !/^https:\/\//.test(videoUrl)) throw new Error(`${lang}/${path.basename(file)}: video_url must be an https:// link`);
          if (videoUrl) stats.videos = (stats.videos ?? 0) + 1;
          stats.written++;
        }
        return {
          kind: 'lesson',
          ref: l.id,
          title: `${l.id} · ${l.title[lang]}`,
          content,
          duration,
          preview: Boolean(l.preview),
          video_url: videoUrl,
        };
      });

      if (mod.quiz) {
        const qFile = path.join(dir, 'quiz', `${mod.quiz}.yml`);
        if (fs.existsSync(qFile)) {
          const quiz = yaml.load(fs.readFileSync(qFile, 'utf8'));
          quiz.questions.forEach((q, i) => validateQuestion(q, `quiz/${mod.quiz}.yml #${i + 1}`));
          stats.quizzes++;
          stats.questions += quiz.questions.length;
          items.push({
            kind: 'quiz',
            ref: mod.quiz,
            title: lang === 'es' ? `Quiz: ${mod.title.es}` : `Quiz: ${mod.title.en}`,
            duration: `${quiz.duration_min ?? 10} minute`,
            passing_grade: quiz.passing_grade ?? 70,
            questions: quiz.questions.map((q, i) => buildQuestion(q, lang, i)),
          });
        }
      }

      return { order: mi + 1, name: `${lang === 'es' ? 'Módulo' : 'Module'} ${mi}: ${mod.title[lang]}`, items };
    });

    // Course page "Overview": overview/<lang>.md when written, else just the subtitle.
    const overviewFile = path.join(dir, 'overview', `${lang}.md`);
    const description = fs.existsSync(overviewFile)
      ? renderMarkdown(readFrontMatter(overviewFile).body, path.dirname(overviewFile), lang)
      : `<p>${course.subtitle[lang]}</p>`;

    const pkg = {
      key: `${course.slug}-${lang}`,
      lang,
      course: {
        title: course.title[lang],
        excerpt: course.subtitle[lang],
        description,
        price: course.pricing.regular_price,
        sale_price: course.pricing.launch_price,
        status: 'publish',
      },
      cover_file: course.cover ? coverPath(dir, course.cover, lang) : null,
      sections,
      stats,
    };

    fs.mkdirSync(OUT, { recursive: true });
    const outFile = path.join(OUT, `${pkg.key}.json`);
    fs.writeFileSync(outFile, JSON.stringify(pkg, null, 2));
    outputs.push({ file: path.relative(ROOT, outFile), ...stats });
  }
  return outputs;
}

const only = process.argv[2];
const slugs = fs.readdirSync(COURSES).filter((s) => (!only || s === only) && fs.existsSync(path.join(COURSES, s, 'course.yml')));
for (const slug of slugs) {
  for (const o of buildCourse(slug)) {
    console.log(`✔ ${o.file}  lessons ${o.written}/${o.lessons} written · videos ${o.videos ?? 0} · quizzes ${o.quizzes} (${o.questions} questions)`);
  }
}
