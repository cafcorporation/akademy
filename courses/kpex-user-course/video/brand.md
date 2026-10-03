# Brand kit de video — Akdemy / Kpex / CAF (para HeyGen)

Identidad de los videos. Tokens reales de `wordpress/plugins/akdemy-bridge/assets/akdemy-brand.css`.
Vista de los marcos: `video/brand/brand-frames.html`.
La marca (colores, fuente, logos, texto en pantalla) **va horneada en los marcos** que genera `npm run render-frames`
(`build/frames/`). En HeyGen esos PNG se usan como **fondo** y tu avatar va encima — no hay que configurar nada de marca en HeyGen.

## Colores (referencia)
| Rol | HEX |
|---|---|
| **Naranja CAF** (acento, chips, barras, anillo del avatar) | `#F5A011` |
| Naranja oscuro | `#d4880a` |
| **Gris CAF** (texto secundario, subtítulos) | `#585856` |
| Tinta | `#1a1a1a` |
| Crema (fondo de paneles) | `#F8F6F2` |
| Línea suave / fuerte | `#f0ece6` / `#e0dcd6` |
| Fondo oscuro (intro/outro) | `#141210` → `#1d1a17` (radial con leve brillo naranja) |

## Tipografía
- **Inter** (fallback Calibri/Arial). Título Inter **900**; subtítulo **600**; chips/eyebrow **600–800** en mayúsculas.

## Marco A — Intro / Outro (avatar completo)
- Fondo **oscuro** + rejilla tenue + motivo **`C = a · Qⁿ`** (firma CAPEX).
- Arriba a la derecha: logos **Kpex + Akdemy** (reales).
- Eyebrow naranja (“CURSO OFICIAL”), **título** grande, subtítulo “Kpex · CAF Corporation”.
- **Lower-third** (abajo izq.): barra naranja + `Carlos Fuenmayor` / `Facilitador · CAF Corporation`.
- **Avatar:** a la izquierda (el marco deja esa zona libre).
- Outro: “SIGUIENTE” + próxima lección + flecha + los tres logos (Kpex · Akdemy · CAF).

## Marco B — Contenido (captura + panel + avatar en esquina)
- Fondo = **captura de Kpex** a pantalla completa.
- **Barra superior** oscura: etiqueta de lección (`0.1` naranja + título) a la izq., logo **Kpex** a la der.; franja naranja debajo.
- **Panel** (abajo izq.): tarjeta crema con borde naranja + **chips** (el texto en pantalla, uno resaltado).
- **Avatar:** en **círculo**, abajo a la derecha (el marco deja el hueco con anillo naranja).

## Cómo producir en HeyGen (flujo)
1. Genera los marcos: `npm run render-frames 0.1 es` → `build/frames/0.1-es-01.png …`.
2. En HeyGen: una escena por marco. **Fondo** = el PNG del marco. **Avatar** encima (esquina inf. derecha en Marco B, izquierda en Marco A).
3. Pega el **script** de esa escena (del guion `video/es|en/<lección>.md`).
4. **Voz:** tu avatar de HeyGen (o una voz de HeyGen). **Subtítulos activados.** 16:9 · 1080p.
5. Exporta final → súbelo a Vimeo → pega el enlace como `video_url:` en la lección → `npm run build`.

## Pronunciación de acrónimos — diccionario de HeyGen
Los scripts usan los acrónimos **normales** (CAPEX, AACE, Kpex, CAF Corporation). Carga en el **diccionario de pronunciación de HeyGen** cómo debe sonar cada uno:

| Acrónimo | Cómo debe sonar |
|---|---|
| CAPEX | “cápex” (KA-pex) |
| OPEX | “ópex” |
| ABEX | “ábex” |
| CAF | “caf” (una sílaba) |
| CAF Corporation | “caf corporéishon” (Corporation en inglés) |
| Kpex | “ke-pex” |
| AACE | deletreado en inglés: “A-A-C-E” (ei-ei-ci-i) |
| PMI · PDRI · PEI · CRI · LFI · CAI · WBS | deletreados |

> 💡 Genera primero la escena con más términos y **escúchala**; si algo no suena bien, ajusta esa entrada del diccionario. (Mismo criterio que usaste en el 1.1.)
