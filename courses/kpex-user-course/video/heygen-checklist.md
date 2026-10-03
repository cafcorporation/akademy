# Checklist de producción en HeyGen — Cursos Kpex

Guía de una página para grabar los videos del curso. Acompaña a [`brand.md`](brand.md) (marca) y a los guiones de `video/es|en/<lección>.md`.
Los **marcos de fondo** se generan con `npm run render-frames <lección> <es|en>` → `build/frames/`.

---

## 0 · Preparación (una sola vez)

- [ ] **Avatar** creado con tu foto y tu voz (outfit y brand kit ya configurados).
- [ ] **Proyecto 16:9**, exportación **1080p**.
- [ ] **Subtítulos activados** (idioma del video: ES o EN).
- [ ] **Voz:** tu avatar (o una voz de HeyGen que te guste). Velocidad **normal**.
- [ ] **Diccionario de pronunciación** cargado (ver tabla abajo) — es lo que evita "cápex" mal dicho.
- [ ] Genera los marcos de la lección: `npm run render-frames 1.2 es` → `build/frames/1.2-es-01.png …`

---

## 1 · Flujo por lección (repetible)

1. Abre el guion `video/es/<lección>.md` (o `en/`). Tiene **una escena por bloque**.
2. En HeyGen crea **una escena por marco** (mismo número que escenas del guion).
3. En cada escena:
   - [ ] **Fondo** = el PNG del marco (`<lección>-<es|en>-NN.png`), en orden.
   - [ ] **Avatar encima** (posición según el tipo de marco — ver §2).
   - [ ] **Script** = pega el texto del bloque **Guion / Script** de esa escena, tal cual.
4. [ ] Revisa primero la **escena más cargada de siglas** (la que indica cada guion en "Notas de producción") y **escúchala**; ajusta el diccionario si algo no suena bien.
5. [ ] Genera el video completo → revisa de corrido → exporta.

---

## 2 · Dónde va el avatar según el marco

| Tipo de marco | Cómo se ve | Posición del avatar |
|---|---|---|
| **Intro** (A) | Fondo oscuro, logos Kpex+Akdemy, "CURSO OFICIAL", título, rótulo inferior | **Izquierda** (el marco deja esa zona libre) |
| **Contenido** (B) | Captura de Kpex a pantalla completa, barra superior, panel crema con chips | **Círculo abajo a la derecha** (~22 % del ancho), sobre el anillo naranja |
| **Idea clave** (A) | Fondo oscuro, "IDEA CLAVE", frase grande centrada | Esquina pequeña o sin avatar (deja leer la frase) |
| **Outro** (A) | "SIGUIENTE → próxima lección", flecha, tres logos | Esquina pequeña o sin avatar |

> La marca (colores, texto en pantalla, logos) **ya va horneada** en el PNG. En HeyGen no configures nada de marca: solo fondo + avatar + script.

---

## 3 · Diccionario de pronunciación (copiar en HeyGen)

Carga cada término con su forma hablada. Son los acrónimos que aparecen en los guiones.

| Término (como está escrito) | Cómo debe sonar |
|---|---|
| **Kpex** | "ke-pex" |
| **CAPEX** | "cápex" (KA-pex) |
| **OPEX** | "ópex" |
| **ABEX** | "ábex" |
| **CAF** | "caf" (una sílaba) |
| **CAF Corporation** | "caf corporéishon" (*Corporation* en inglés) |
| **AACE** | deletreado en inglés: "A-A-C-E" (ei-ei-ci-i) |
| **PMI** | deletreado: "P-M-I" |
| **PMBOK** | "P-M-bok" |
| **TIC** | deletreado: "T-I-C" |
| **PDRI · PEI · CRI · LFI · CAI · WBS · FID** | deletreados |

**Nombres de la interfaz (en inglés, pronunciación inglesa):** *Location Factor, Cost Index, Estimate Wizard, New Estimate, Cost Data, Basis of Estimate, Fetch CAI, Create Estimate, Cost Summary, Estimate Progression, Project Portfolio, Knowledge Library, Data Partner, Sign In, Sign Out.*

> 💡 El diccionario se configura **una vez** y aplica a todos los videos. Si una sigla nueva aparece en una lección, añádela antes de generar.

---

## 4 · Exportar y publicar

- [ ] Exporta **1080p con subtítulos**, sin marca de agua (requiere plan de pago activo).
- [ ] Sube a **Vimeo** o a **YouTube (no listado)**.
- [ ] Copia el enlace y pégalo en la lección como front-matter: `video_url: https://…`
      (en `courses/kpex-user-course/es/<lección>.md` o `en/`).
- [ ] `npm run build` y publica. El video aparece **arriba de la lección**.

---

## 5 · Inventario de marcos por lección

`npm run render-frames <id> <es|en>` genera estos PNG en `build/frames/`.

| Lección | Título | Escenas | Nota |
|---|---|---|---|
| **0.1** | Cómo funciona el curso | 7 | onboarding |
| **0.2** | Activa tu cuenta de prueba | 6 | conversión |
| **1.1** | Qué hace única a Kpex | 8 | 🎯 **vista previa gratis** (marketing) |
| **1.2** | La plataforma de un vistazo | 6 | incluye marco *Idea clave* (escena 5) |
| **2.1** | Acceso y registro | 6 | procedimental |
| **3.1** | Clasificación AACE + PMI | 7 | escena más cargada de siglas: la 5 (PMI) |
| **4.1** | El Estimate Wizard en 5 pasos | 7 | escena 6 = bloque Clase 5 |
| **7.2** | Los tres reportes de portafolio | 7 | — |
| **8.1** | Soporte y próximos pasos | 5 | cierre → examen final |

*(mismas escenas en ES y EN)*

---

## 6 · QA antes de dar por buena cada lección

- [ ] El **audio** dice bien Kpex, CAPEX, AACE, CAF (escucha la escena de más siglas).
- [ ] **Subtítulos** sincronizados y en el idioma correcto.
- [ ] El **avatar no tapa** datos importantes de la captura (en Marco B va sobre zona secundaria).
- [ ] **Orden** de escenas = orden del guion.
- [ ] **Sin marca de agua** de HeyGen.
- [ ] Enlace `video_url` pegado en la lección correcta (ES en `es/`, EN en `en/`).

---

### Orden sugerido de grabación

1. **1.1 (ES)** — es la pieza de marketing (preview gratis); que quede impecable primero.
2. **0.1 → 0.2** — onboarding.
3. **1.2 → 2.1 → 3.1 → 4.1** — el recorrido de producto.
4. **7.2 → 8.1** — salida y cierre.
5. Repite en **EN** reutilizando los mismos marcos `-en-`.
