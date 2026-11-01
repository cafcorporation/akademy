# Auditoría de contenido — lecciones de FUERA de módulo (2026-11-01)

Alcance: lecciones 0.x, 1.x, 2.x, 3.x, 4.x, 7.x, 8.x (las que el audit WBS de oct no cubrió a fondo).
Fuente de verdad: código Kpex2G (`resources/views/layouts/app.blade.php` [sidebar vivo], `estimates/create.blade.php`, `show.blade.php`, `benchmarks/curves.blade.php`, `ReportController`, `CapexMonitorController`, `routes/web.php`), guía de navegación y guiones de video.

**Ya aplicado y desplegado (31 oct–1 nov):** 196→197 países, 430→653 curvas, "CAF Cost Index"→"Kpex Cost Index" (1.1, 1.2, 3.2, 4.2, 5.1), e imagen vertical del sidebar sin estirar (CSS). "700+" componentes y "150 facilities" son VIGENTES (no se tocan).

---

## A. Hallazgos transversales (máxima prioridad — rompen la experiencia del alumno)

### A.1 🔴 Los "Reports" de portafolio son SOLO-ADMIN (desde 2026-10-11)
El grupo Reports (Cost Summary, Estimate Progression, Project Portfolio) está envuelto en `@if($navUser->isAdmin())` y las rutas llevan `role:admin` (`app.blade.php:640-655`, `routes/web.php:1090-1096`). **Una cuenta de prueba no los ve ni los abre.**
Afecta: **1.2** (fila Intelligence), **2.2** (menú Intelligence), **3.2** (Estimate Progression en Clase 2), **7.2** (lección entera + ejercicio), **8.2** (recap), y el mapa de módulos en **0.1/1.3**.
→ DECISIÓN DE PRODUCTO: ¿el acceso admin-only es definitivo o temporal (rollout)? Según eso: reescribir como "vista de gestor/admin" o replantear los ejercicios de 7.2.

### A.2 🟡 Kpex Exchange requiere plan ENTERPRISE
Exchange Home, Product Catalog, Vendors y las RFQ del owner están bloqueados salvo Enterprise+ ("Available from the Enterprise plan", `app.blade.php:704-721,806-825`).
Afecta: **7.3** (lección + ejercicio), **2.3** (RFQ del owner). → Aclarar el tier; el ejercicio no es realizable en tiers inferiores.

### A.3 🟡 Dominio de la app = `kpex.caf-corporation.com`
Las referencias vivas (legal, términos, producto, guía) usan el subdominio; `caf-corporation.com` (ápex) es el sitio corporativo.
Afecta: **0.1**, **0.2** (sign-in), **8.1** (Knowledge Library `…/knowledge`). → Confirmar si el ápex redirige; si no, corregir a `kpex.caf-corporation.com`.

### A.4 🟡 "Cost Modules A–E" aparece en el menú como Kpex 2G / Kpex ISBL / Kpex 1G
El concepto A–E es válido, pero el alumno ve esos tres ítems (tier-gated), no "A–E" (`app.blade.php:461-551`).
Afecta: **1.2**, **2.2**. → Ajuste de redacción para alinear con lo que se ve en pantalla.

---

## B. Hallazgos por lección

- **2.3 🟡** — El perfil NO se cambia en "My Account → Profile": no existe ese selector; `updateProfile()` solo valida name/title/phone/bio/avatar (`UserController.php:265-294`). Reescribir.
- **4.2 🔴** — *(línea que introduje yo en el audit de módulos)* El puente "Kpex 2G ↔ 4Builders" para Clase 2 está MAL: es diseño **Fase 0** (guía CEO-only), NO está en el workspace, y 4Builders es el **estimador del contratista**, no una "execution tool" (`routes/web.php:145-149`, `guides/ceo-class2-bridge.blade.php`). **Quitar/reescribir.**
- **4.2 🟡** — El botón **Procurement** se muestra en Clase **2 y 3** (la lección dice solo Clase 3) (`show.blade.php:379`).
- **4.3 🟡** — La lista de hojas del reporte no coincide con el libro real. Hojas 2G reales: Cover · Basis of estimate · Summary · Class 5 basis · Equipment list · ISBL bulk · WBS detail (seleccionables). No existen "Components" ni "Sources & data quality" en el 2G (`EstimateWorkbook.php:36-43`).
- **4.4 🟡** — El botón del Paso 5 se llama **"Class 5 report"**, no "Print Class 5 report (PDF)" (`curves.blade.php:704-707`).
- **8.1 🟡** — URL de Knowledge Library → `kpex.caf-corporation.com/knowledge` (`guides/user-navigation.blade.php:923`).

## C. Pulidos menores (🟢)

- **1.1/1.2** — Encima de los 5 grupos hay una sección "Project / Project Portfolio" (el alumno ve 6 encabezados, no 5). Aclaración opcional.
- **2.2** — El dashboard omite: banner de bienvenida + CTA "New Project", tarjeta "AACE Classification", banner "Kpex 1G" (Enterprise+). Incompletitud.
- **2.3** — Sidebars de Vendor/Data Partner incompletos (faltan Vendor Dashboard, Token Wallet, Revenue Dashboard, Submit Dataset). Incompletitud.
- **3.1** — "una entrada por clase" sugiere 5; en la barra hay 4 (Clase 5→2) + All Estimates.

## D. Confirmados / sin cambios

- ✅ Teaser del Global CAPEX Monitor = **5 países** (`CapexMonitorController.php:26`).
- ✅ Acceso completo al Monitor = **Practitioner+** (`CapexMonitorController.php:50-51`).
- ✅ **4.1 CORRECTA**: la UI rotula "Cost Adjustment Index (CAI — Z.5)" y botón "Fetch CAI" (`create.blade.php:476,519`). No tocar.
- ✅ **4.3 PDRI correcto**: 7 variantes, rango 0–1000 (menor = mejor).
- ✅ **2.1 sin hallazgos** (register: Choose Plan·Account·Payment, código de 6 dígitos, reglas de contraseña).
- ⚪ 2.2: el "20 notificaciones" no verificado.
- ⚪ Inconsistencia del propio código (no de la lección): Z.5 se muestra como "Cost Index (CAI)" en sidebar/form pero "Kpex Cost Index" en resultados/reportes; y el 1.er ítem del desplegable sigue como legacy "CAF Plant Cost Index (composite)".

---

## Prioridad de aplicación sugerida
1. **🔴 ahora:** A.1 (Reports admin-only, pend. decisión de producto) · 4.2 el puente 4Builders (error que introduje, está en vivo).
2. **🟡 que rompen experiencia:** A.2 (Exchange Enterprise), A.3 (dominio), A.4 (Cost Modules), 2.3 perfil, 4.2 Procurement C2+C3, 4.3 hojas, 4.4 botón, 8.1 URL.
3. **🟢/⚪ señalados**, se aplican si hay tiempo o se confirman con Bernardo.
