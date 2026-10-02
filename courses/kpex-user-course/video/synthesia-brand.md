# Brand kit de video — Akdemy / Kpex / CAPEX (para Synthesia)

Identidad para los templates de video. Tokens reales de `wordpress/plugins/akdemy-bridge/assets/akdemy-brand.css`.
Vista de los marcos: `video/brand/synthesia-frames.html`.

## Colores (cárgalos en Brand Kit → Colors)
| Rol | HEX |
|---|---|
| **Naranja CAF** (acento, chips, barras, avatar ring) | `#F5A011` |
| Naranja oscuro (hover/sombra) | `#d4880a` |
| **Gris CAF** (texto secundario, subtítulos) | `#585856` |
| Tinta (texto principal claro) | `#1a1a1a` |
| Crema (fondo de paneles) | `#F8F6F2` |
| Línea suave / fuerte | `#f0ece6` / `#e0dcd6` |
| Fondo oscuro (intro/outro) | `#141210` → `#1d1a17` (radial con leve brillo naranja) |
| Blanco | `#FFFFFF` |

## Tipografía (Brand Kit → Fonts)
- **Inter** (sube el archivo a Library → Fonts). Fallback: Calibri, Arial.
- Título: Inter **900**, tracking `-0.02em`. Subtítulo: Inter **600** en gris/naranja.
- Chips/labels: Inter **600–800**, mayúsculas con tracking `.12–.16em` para los eyebrow.

## Template A — Intro / Outro (avatar completo)
- Fondo **oscuro** con leve degradado naranja + rejilla tenue y el motivo **`C = a · Qⁿ`** (firma CAPEX) al 16 % de opacidad.
- Arriba a la derecha: **KPEX** (la X naranja) + **AKDEMY.** (punto naranja).
- Eyebrow naranja (“CURSO OFICIAL”), **título** grande, subtítulo “Kpex · CAF Corporation”.
- **Lower-third** (abajo izq.): barra naranja + `Carlos Fuenmayor` / `Facilitador · CAF Corporation` sobre panel oscuro translúcido.
- Avatar: a la izquierda/centro (deja libre esa zona).
- Outro: “SIGUIENTE” + “0.2 · Activa tu cuenta” + flecha naranja + logos.

## Template B — Contenido (captura + panel + avatar esquina)
- Variable **`media`** = la captura de Kpex a pantalla completa.
- **Barra superior** oscura: etiqueta de lección (`0.1` en naranja + título) a la izq., **KPEX** a la der.; debajo, **franja naranja** de 0.5 cqw.
- **Panel de texto** (abajo izq.): tarjeta **crema** con **borde izquierdo naranja**; título en tinta + **chips** (uno resaltado en naranja) → aquí va la variable **`onscreen`** (canvas text).
- **Avatar** en **círculo** con **anillo naranja**, abajo a la derecha (~20 cqw).

## Variables del template (para la API `fromTemplate`)
- `{{script}}` — narración de la escena.
- `{{onscreen}}` — texto en pantalla (canvas text).
- `{{media}}` — fondo (captura); URL pública o asset subido.
- `{{avatar}}` — id del avatar (tu Personal Avatar cuando lo tengas; stock mientras tanto).

## Voz (recomendado)
Creator **no** clona tu voz. Clónala en **ElevenLabs** (con diccionario: Kpex, AACE, CAPEX…), genera el audio y pásalo por API como **`scriptAudio`** (asset de audio subido) en lugar de `scriptText`. Así consigues **tu voz** sin pagar Enterprise.

## Subtítulos y formato
- 16:9 · 1080p · subtítulos activados (ES/EN).
- Música de fondo suave y baja; duración 2–2:30.

## Pronunciación de acrónimos (LISTA MAESTRA) — solo en el texto HABLADO (ES)
En los guiones en español, los acrónimos se escriben fonéticos para que la voz los diga bien.
El **texto en pantalla** se deja normal (CAPEX, AACE, Kpex…). El **inglés** no se toca (la voz inglesa los dice nativos).

| Acrónimo | Se escribe en el script ES |
|---|---|
| CAPEX | **cápex** |
| OPEX | **ópex** |
| ABEX | **ábex** |
| CAF | **Caf** |
| CAF Corporation | **Caf corporéishon** |
| Kpex | **Képex** |
| AACE | **ei ei ci i** (letras en inglés) |
| PMI / PDRI / PEI / CRI / LFI / CAI / WBS | se dejan igual (la voz los deletrea) |

> ⚠️ "ei ei ci i" y "corporéishon" son **aproximaciones fonéticas**: genera una escena y **escúchala**; si no suena bien, se ajusta el deletreo.
> Alternativa "pro": cargar estos términos en el **diccionario de pronunciación** de Synthesia (si tu plan lo tiene) y dejar el script normal.
