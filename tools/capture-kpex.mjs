// Captures the Kpex screens used by the course lessons, from an Edge window where the user is signed in.
// Start Edge with --remote-debugging-port=9333 (see README), sign in to https://kpex2g.test, then:
//   node tools/capture-kpex.mjs            (all)      node tools/capture-kpex.mjs 3.2 6.3   (only these)
// Output: courses/kpex-user-course/screenshots/<id>-<name>.png — the names the lessons reference.
import path from 'node:path';
import puppeteer from 'puppeteer-core';

const BASE = 'https://kpex2g.test';
const OUT = path.resolve(import.meta.dirname, '../courses/kpex-user-course/screenshots');
const VIEW = { width: 1440, height: 900 };

// action: 'page' (viewport) | 'sidebar' (clip the side menu) | {scrollTo: 'text'}
const SHOTS = [
  { file: '1.1-dashboard.png', url: '/dashboard' },
  { file: '1.2-sidebar.png', url: '/dashboard', action: 'sidebar' },
  { file: '2.2-dashboard-zones.png', url: '/dashboard' },
  { file: '2.3-sidebar-by-profile.png', url: '/marketplace', action: 'sidebar' },
  { file: '3.1-aace-classes.png', url: 'wizard:' },
  { file: '3.2-cost-curves.png', url: '/benchmarks/curves' },
  { file: '4.1-estimate-wizard.png', url: 'wizard:' },
  { file: '4.2-estimate-workspace.png', url: 'estimate:' },
  { file: '4.3-estimate-pdf.png', url: 'estimate:/print' },
  { file: '4.4-class5-result.png', url: '/benchmarks/curves', action: 'curve-calc' },
  { file: '5.1-modulo-a-catalogo.png', url: '/wbs' },
  { file: '5.2-curva-de-costo.png', url: '/benchmarks/curves', action: 'curve-calc-chart' },
  { file: '5.3-facility-cbs.png', url: '/cost-curves' },
  { file: '5.4-lifecycle-cost-scope.png', url: 'wizard:', action: { scrollTo: 'Lifecycle Cost Scope' } },
  { file: '6.1-z3-sesion-tarifa.png', url: '/z3/rental-equipment' },
  { file: '6.2-z4-pagina-pais.png', url: '/z4' },
  { file: '6.3-cost-index.png', url: '/benchmarks/cost-index' },
  { file: '6.4-pdri.png', url: '/wbs/z/assessment-tools' },
  { file: '7.1-cost-curve.png', url: '/benchmarks/kpi-library' },
  { file: '7.2-reports-menu.png', url: '/reports' },
  { file: '7.3-exchange-home.png', url: '/marketplace' },
  { file: '8.1-knowledge-library.png', url: '/knowledge' },
];

const only = process.argv.slice(2);
const browser = await puppeteer.connect({ browserURL: 'http://127.0.0.1:9333', defaultViewport: VIEW });
const page = await browser.newPage();
await page.setViewport(VIEW);

// Session check + dark side menu (the app reads these on load), light content, expanded menu.
await page.goto(`${BASE}/dashboard`, { waitUntil: 'networkidle2' });
if (new URL(page.url()).pathname.startsWith('/login')) {
  console.error('Not signed in — sign in to https://kpex2g.test in the Edge window first.');
  process.exit(1);
}
await page.evaluate(() => {
  localStorage.setItem('kpex-sidebar-theme', 'dark');
  localStorage.setItem('kpex-theme', 'light');
  localStorage.setItem('kpex-sb-rail', '0');
  localStorage.setItem('kpex-sb-hidden', '0');
});

// KPEX_ESTIMATE_ID=<id> pins the demo estimate (see tools/kpex-demo-estimate.mjs); otherwise the first listed one.
let estimateUrl = process.env.KPEX_ESTIMATE_ID ? `${BASE}/estimates/${process.env.KPEX_ESTIMATE_ID}` : null;
let projectId = null;
async function resolve(url) {
  // Estimates are created inside a project → open the wizard from the first visible (demo) project.
  if (url.startsWith('wizard:')) {
    if (!projectId) {
      await page.goto(`${BASE}/projects`, { waitUntil: 'networkidle2' });
      projectId = await page.evaluate(() => {
        const a = [...document.querySelectorAll('a[href]')].map((x) => new URL(x.href).pathname.match(/^\/projects\/(\d+)$/)).find(Boolean);
        return a ? a[1] : null;
      });
    }
    return projectId ? `${BASE}/estimates/create?project_id=${projectId}` : null;
  }
  if (!url.startsWith('estimate:')) return BASE + url;
  if (!estimateUrl) {
    await page.goto(`${BASE}/estimates`, { waitUntil: 'networkidle2' });
    // The list is filled by AJAX (DataTables) — wait for an estimate link.
    await page.waitForFunction(() => [...document.querySelectorAll('a[href]')].some((x) => /\/estimates\/\d+$/.test(new URL(x.href).pathname)), { timeout: 15000 }).catch(() => {});
    estimateUrl = await page.evaluate(() => {
      const a = [...document.querySelectorAll('a[href]')].find((x) => /\/estimates\/\d+$/.test(new URL(x.href).pathname));
      return a ? a.href : null;
    });
    if (!estimateUrl) return null;
  }
  return estimateUrl + url.slice('estimate:'.length);
}

for (const s of SHOTS) {
  if (only.length && !only.some((id) => s.file.startsWith(id + '-'))) continue;
  const url = await resolve(s.url);
  if (!url) { console.log(`SKIP ${s.file} (no estimate found)`); continue; }
  await page.goto(url, { waitUntil: 'networkidle2', timeout: 60000 });
  await new Promise((r) => setTimeout(r, 1200)); // charts / fonts settle
  const file = path.join(OUT, s.file);
  if (typeof s.action === 'string' && s.action.startsWith('curve-calc')) {
    // Lesson 4.4 exercise: Solar PV — Utility Scale (EPC), 100 MWp, Spain → Calculate.
    await page.type('#curveSearch', 'Solar PV');
    await new Promise((r) => setTimeout(r, 800));
    const picked = await page.evaluate(() => {
      const el = [...document.querySelectorAll('*')].find((e) => e.children.length === 0 && /^solar pv\s*[—-]\s*utility scale/i.test(e.textContent.trim()) && (e.offsetWidth || e.offsetHeight));
      (el?.closest('[onclick], a, button, li') ?? el)?.click();
      return el?.textContent.trim();
    });
    await new Promise((r) => setTimeout(r, 1200));
    await page.click('#calcCapacity', { clickCount: 3 });
    await page.type('#calcCapacity', '100');
    await page.evaluate(() => {
      const c = document.querySelector('#calcCountry');
      const o = c && [...c.options].find((x) => /^Spain\b/i.test(x.text.trim()));
      if (o) { c.value = o.value; c.dispatchEvent(new Event('change', { bubbles: true })); }
      [...document.querySelectorAll('button')].find((b) => /^calculate$/i.test(b.textContent.trim()) && (b.offsetWidth || b.offsetHeight))?.click();
    });
    await new Promise((r) => setTimeout(r, 2500));
    if (s.action === 'curve-calc-chart') {
      // Park the chart (with the plotted point) in view.
      await page.evaluate(() => {
        const svg = [...document.querySelectorAll('canvas, svg.apexcharts-svg, .apexcharts-canvas, [id*=chart]')].find((e) => e.getBoundingClientRect().height > 200);
        svg?.scrollIntoView({ block: 'center' });
      });
      await new Promise((r) => setTimeout(r, 800));
    } else {
      await page.evaluate(() => {
        const res = [...document.querySelectorAll('*')].find((e) => e.children.length === 0 && /total installed cost/i.test(e.textContent) && (e.offsetWidth || e.offsetHeight));
        res?.scrollIntoView({ block: 'start' });
        window.scrollBy(0, -120);
      });
      await new Promise((r) => setTimeout(r, 600));
    }
    console.log(`   picked: ${picked ?? 'NOT FOUND'}`);
    await page.screenshot({ path: file });
  } else if (s.action === 'sidebar') {
    const box = await page.evaluate(() => {
      const el = document.querySelector('#kt-sidebar');
      if (!el) return null;
      const r = el.getBoundingClientRect();
      return { x: 0, y: 0, width: Math.ceil(r.width), height: window.innerHeight };
    });
    await page.screenshot({ path: file, clip: box ?? { x: 0, y: 0, width: 300, height: VIEW.height } });
  } else if (s.action?.scrollTo) {
    await page.evaluate((text) => {
      // Exact-text match on the section header, then park it just under the top bar.
      const el = [...document.querySelectorAll('h1,h2,h3,h4,h5,div,span,label')].find((e) => e.children.length === 0 && e.textContent.trim() === text);
      const card = el?.closest('.card, [class*="card"]') ?? el;
      card?.scrollIntoView({ block: 'start' });
      window.scrollBy(0, -80);
    }, s.action.scrollTo);
    await new Promise((r) => setTimeout(r, 400));
    // Crop to the section card itself (+ margin) so unrelated UI stays out of the lesson image.
    const clip = await page.evaluate((text) => {
      const el = [...document.querySelectorAll('h1,h2,h3,h4,h5,div,span,label')].find((e) => e.children.length === 0 && e.textContent.trim() === text);
      let card = el;
      while (card && card.parentElement && card.getBoundingClientRect().height < 180) card = card.parentElement;
      if (!card) return null;
      // Puppeteer clips in document coordinates → add the scroll offset.
      const r = card.getBoundingClientRect(), m = 24;
      return { x: Math.max(0, r.left - m), y: Math.max(0, r.top + window.scrollY - m), width: r.width + 2 * m, height: r.height + 2 * m };
    }, s.action.scrollTo);
    await page.screenshot(clip ? { path: file, clip } : { path: file });
  } else {
    await page.screenshot({ path: file });
  }
  console.log(`OK ${s.file}  ← ${new URL(url).pathname}`);
}

await page.close();
await browser.disconnect();
