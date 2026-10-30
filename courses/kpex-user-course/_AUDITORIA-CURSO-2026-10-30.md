# Auditoría del curso vs. estado actual de Kpex — 30 oct 2026

Comparación de las 27 lecciones del curso contra la **documentación autoritativa del estado actual de la plataforma** (`Kpex-AI/Videos_Modulos_Kpex/*/guion.md` + el código real de la app `Kpex-AI/Kpex2G/`). Auditoría por 6 agentes, uno por bloque de módulos.

**Veredicto:** el curso quedó **meaningfully atrás** respecto a la app. El armazón conceptual (clases AACE, ciclo de vida CAPEX/OPEX/ABEX, calibración) es correcto, pero hay **errores de estructura, nombres de herramienta y cifras** en casi todos los módulos de costo. Cada corrección afecta a **4 archivos** (lección ES/EN + guion de video ES/EN) y, tras corregir, hay que **regenerar kits + redeploy**.

Leyenda severidad: 🔴 Alta (error factual visible) · 🟡 Media · ⚪ Baja/opcional.

---

## Transversales (afectan a varias lecciones)

- 🔴 **El índice de escalación se llama "Kpex Cost Index"**, no "CAF Cost Index" ni "Cost Index (CAI)". El curso inventó un híbrido que no coincide ni con la app ni con la WBS (que lo codifica "CAF CAI"). Afecta a **6.1, 6.3, 5.2** y cualquier mención.
- 🔴 **Los reportes son UN libro de Excel con marca** (no "un PDF + un Export Excel"). Distinción actual: **Kpex 1G = reporte de la sesión**, **Kpex 2G = reporte por estimado**. Afecta a **4.3, 7.2**.
- 🟡 **Paridad EN→ES rota:** varias lecciones EN ya tienen un bloque **"Watch it in Kpex"** con los videos reales de la plataforma que la versión ES no tiene (5.1, 5.4). Además, esos videos ya reflejan estructura nueva que la prosa contradice.
- 🟡 **Cifras desactualizadas:** curvas 430→**653**; países 196→**197**; componentes 700+→**741**; facilities 150 pero grupos 6→**7**.

---

## Módulo A — Lección 5.1

> La lección escrita ya se amplió a A.1–A.6 + A.7, pero quedó parcial; el **guion de video 5.1 NO se actualizó**.

- 🔴 **A.2 Piping tiene 7 familias, no 4.** Real: A.2.1 Pipes, **A.2.2 Valves**, A.2.3 Fittings, A.2.4 Strainers, A.2.5 Hangers & Supports, A.2.6 Steam Specialties, A.2.7 Fabrication & Assembly (incl. Hot Taps). Las **válvulas están en A.2.2** (el curso las puso en A.2.3). 207 componentes.
- 🔴 **Video 5.1 sin la corrección:** sigue con "A.1.5–A.1.6 Packages · A.2–A.6 BOP" y afirma en absoluto que los bulks "no se cotizan pieza por pieza" — falso (A.2 tiene 207 componentes, A.3 tiene estimadores). Aplicar el mismo ajuste que a la lección + suavizar la frase de allowances.
- 🟡 **A.1 muestra hoy 5 grupos (A.1.1–A.1.5); A.1.6 Technologic Packages está pendiente.** El curso dice "seis familias" como desplegadas.
- 🟡 **Cada modelo devuelve costo + peso + horas-hombre**, no solo "un rango de costo". Añadirlo.
- 🟡 **Concepto ISBL (2G):** el Módulo A entrega el **ISBL** = equipo + su instalación (MTO, límite de batería), no el equipo desnudo. El video real se titula "A.1 · Process Equipment & ISBL".
- 🟡 **A.7 Assemblies está pendiente:** en `/wbs/a` se ven **6 tarjetas**, no 7. Mantener A.7 como nota conceptual (el montaje aflora vía el ISBL de A.1), no como submódulo navegable.
- ⚪ 700+ → **741** (el contador de la app tiene un bug que muestra 742). · A.1.4/A.1.5: "agitadores" va en A.1.1, "secado/separación" en A.1.4; A.1.5 = auxiliares/HVAC. · Falta bloque de videos en ES.
- ✅ Correcto: A.6 Civil Works (5 familias), nombres de los 6 submódulos, propiedades del precio (base USGC=1,0, material, rango), familias de A.1.

---

## Módulo B — Lección 5.2

- 🔴 **"430+ curvas" → 653** (en 10 sectores, 60 familias). Afecta lección + video.
- 🟡 **Tres niveles de curva**, no dos: **Component → Functional Unit → Facility**.
- 🟡 **El selector se llama "Guide me to a facility"**, no "Find your facility".
- 🟡 **KPEX-HF es la 4.ª pestaña de la KPI Library** (con su matriz P10/P50/P90), no un bloque suelto. Los rangos de la KPI Library se muestran como **bajo/medio/alto**; el P10/P50/P90 es específico de la pestaña KPEX-HF.
- 🟡 **Herramientas nuevas poco reflejadas:** Offshore O&G (B.3), Railway (B.4), Construction Costs, Benchmark Data — el Módulo B tiene 4 herramientas / 6 páginas.
- ⚪ La fórmula C=a·Qⁿ ahora es **solo-admin** (el usuario ve el punto + banda, no la ecuación). · Botón **"Create Class 5 Estimate"** + benchmarking de proyecto (meter tu costo, leer desviación %). · Nombrar el índice **Kpex Cost Index**.
- ✅ Correcto: 10 sectores, país de origen + Location Factor, concepto C=a·Qⁿ con n<1, rol de KPEX-HF.

---

## Módulo C — Lección 5.3

- 🔴 **7 grupos, no 6:** falta **C.7 New Technologies** (gigafábricas de baterías, data centers/IA, fabs de semiconductores — todas LIVE). Cabecera app: "150 facilities · 7 WBS groups · 126 con curva espejo".
- 🔴 **C.4 Renewable: "H₂ azul" no existe.** Real: Solar PV, Wind, Battery, CSP, Green H₂, Hydro, Geothermal, **Bioenergy**, Power-to-X, **Heat Pumps**. Quitar H₂ azul; añadir bioenergía y bombas de calor.
- 🟡 **C.2 Infrastructure:** los tipos reales son Highway, Tunnel, **Airport Runway**, Substation, HV Transmission, Pipeline, Aqueduct, **Dam**. El curso lista "ferrocarriles" y "terminales marítimas" que no están.
- 🟡 **C.5 "metodología IAEA" sin respaldo.** Describir por sus diseños (PWR, BWR, SMR, CANDU, HTGR, Life Extension) o confirmar con Bernardo.
- 🟡 **"uno a uno" con las curvas está sobredicho:** solo **126 de 150** tienen curva espejo. Matizar.
- ⚪ Añadir banda P10/P50/P90 por cuenta, Location Factor/Kpex Cost Index y botón Report.
- ✅ Correcto: 150 facilities, apertura por CBS (AACE 18R-97), paso Cost Drivers, C.1 (91 modelos), C.3 (20 tipos).

---

## Módulos D y E — Lección 5.4

- 🔴 **D.1 no es "Oil & Gas OPEX":** es el **OPEX Estimator** — 80 tipos de facility en **9 sectores** (químicas, energía, agua, refino…), paramétrico top-down (`/wbs/d/estimator`). Ejemplos reales: metanol, NGCC, desalación, amoníaco, refinería.
- 🔴 **Módulo E = 6 catálogos LIVE (171 APU), no 5:** E.1.1 Onshore (32 APU), E.1.2 Offshore (30), E.2 Process (35), E.3 Renewables (31), E.4 Mining (23), E.5 Nuclear (20). Separar onshore/offshore.
- 🟡 **E es un estimador bottom-up por APU → estimado Clase 3** (con P10/P50/P90, país, escalación, Location Factor, CAc 12 %, contingencia 15 %). El curso lo presenta solo como "catálogos".
- 🟡 **D.1 entrega** costo unitario + OPEX anual, 4 componentes escalados (base 2020→Ago 2026), factor de capacidad, economía de escala, clase ±25 % — no la taxonomía "fijo/variable/dotación".
- ⚪ **D.2 (OPEX bottom-up) "coming soon".** · Añadir bloque de videos a la lección ES (la EN ya lo tiene). · Verificar alcance real de E.3 (el guion solo evidencia eólica).
- ✅ Correcto: paso **Lifecycle Cost Scope** del wizard (CAPEX ALWAYS, OPEX/ABEX OPTIONAL, Plant Lifetime, LCE/NPV/IRR), nivel Professional+, marco CAPEX/OPEX/ABEX.

---

## Módulo Z — Lecciones 6.1–6.4

- 🔴 **6.3 — "CAF Cost Index" → "Kpex Cost Index"** en título, cuerpo, menú y video (la propia lección se contradice: el pie del video ya dice "Z.5 · Kpex Cost Index").
- 🔴 **6.3 — El índice compuesto es CAF_CI, no "CME-CI".** CME_CI es el subíndice de **maquinaria de construcción** (y sin datos tras may-2025). Corregir el ejemplo.
- 🟡 **6.2 — 196 → 197 países.**
- 🟡 **6.1 — "CAF Cost Index" → "Kpex Cost Index"** (escalación de Z.3) y menú **"Z.5 Cost Index (CAI)" → "Z.5 Kpex Cost Index"**.
- ⚪ 6.3: códigos de subíndice llevan sufijo `_CI`; "PPI Source Data" es solo-admin. · 6.2: Factor A ahora se rotula "Governance & infrastructure". · 6.1: Z.1 tiene 4 herramientas (MH Estimator, Discipline Spread, HO Services Ratio, Eng. MH Calculator). · 6.4: añadir umbrales **200/600** y estructura 70/15/3 del PDRI industrial.
- ✅ Correcto: **6.4 las 7 variantes del PDRI** (ya corregido), las 4 evaluaciones de Z.6, CAF Accuracy Calculator, fórmula LFI E=A×(B+C+D)/3, base USGC=1,0, origen÷destino, base 2010=100 / mensual / multirregión / 5 regiones / 14 índices.

---

## Estimados y reportes — Lecciones 3.x, 4.x, 7.x

- 🔴 **3.2 — Clase 4 mal mapeada:** se construye con el **Equipment List Builder (Módulo A, ISBL) + bulk por disciplina (Módulo B)**, no con modelos de facility del Módulo C (eso es Clase 5). Confirmado en el código (`class4.blade.php`).
- 🟡 **4.3 — El reporte es un libro Excel con marca** (no "PDF + Excel aparte"): portada con **tu logo + logo de cliente ("Prepared for")**, color del Excel desde Professional. **Sin "bloque de firma del estimador"** (Kpex solo firma como herramienta). Hojas reales: cover, basis of estimate, summary, components, sources & data quality. Resolver la **contradicción del PDRI** (l.37 vs l.48: se corre aparte en Z.6, no va en el reporte).
- 🟡 **7.2 — Añadir los reportes 1G (sesión) y 2G (por estimado)** en Excel con marca, que son el núcleo actual del sistema de reportes.
- ⚪ **4.2 — El botón es "Mature to Class N"**, no "Continue Estimate". · **3.2 —** distinguir *Procurement Contracts* in-estimate (Clase 3) del *Kpex Exchange / RFQ* (marketplace); hay un puente **Kpex 2G ↔ 4Builders** para Clase 2 no mencionado. · **7.3 Exchange —** existe en la app; confirmar rótulos del lateral y que el acceso va por **tier** (Professional/Enterprise/Data Partner), no por "tipo de perfil". · **7.1 —** confirmar teaser = 5 países y rótulo "Insights".
- ✅ Correcto (verificado en código): **4.1 Estimate Wizard** (5 tarjetas, LFI/CAI en Basis of Estimate, bloque Clase 5 Cost Curve Basis), **4.2 Procurement en Clase 3**, **4.4 ejercicio Clase 5**, **7.1 Global CAPEX Monitor** (público, Practitioner+), **7.2 nombres de los 3 reportes**, **3.1 clases AACE / PMI**.

---

## Prioridades recomendadas

**Alta (errores factuales visibles, corregir primero):**
1. Índice → **"Kpex Cost Index"** en todo el curso (6.1, 6.3, 5.2).
2. **A.2 Piping = 7 familias** + actualizar el **video 5.1** (5.1).
3. **C: 7 grupos + C.7**, y **C.4 sin H₂ azul / con bioenergía y bombas de calor** (5.3).
4. **D.1 = OPEX Estimator (80/9)** y **E = 6 catálogos / 171 APU** (5.4).
5. **Reportes = Excel con marca (1G/2G)** (4.3, 7.2).
6. **Clase 4 = Equipment List Builder** (3.2).
7. **CME-CI → CAF_CI** como compuesto (6.3).

**Media:** niveles de curva (3), selector "Guide me to a facility", KPEX-HF como pestaña, C.2 tipos reales, C.5 sin IAEA, 196→197, naturaleza APU/Clase 3 de E, paridad de videos ES.

**Baja/opcional:** conteos finos (741, _CI), umbrales PDRI 200/600, rótulos ("Mature to Class N", "Governance & infrastructure"), herramientas de Z.1, etc.

> Nota: algunos puntos (C.5 IAEA, alcance de E.3, teaser del CAPEX Monitor, rótulos del Exchange) quedan como **"confirmar"** porque la fuente autoritativa no los cierra del todo.
