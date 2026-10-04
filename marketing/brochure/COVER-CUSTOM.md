# Portada personalizada del brochure (imagen propia / IA)

El brochure tiene un banco de portadas intercambiables. La predeterminada es **`dark`** (la que ves por defecto).
Puedes sustituirla por **tu propia imagen** —por ejemplo, una generada con IA— sin tocar el resto del brochure.

## Cómo poner tu portada

1. Genera o elige tu imagen de portada.
2. Guárdala exactamente aquí, con este nombre:

   ```
   marketing/brochure/img/cover-custom.jpg
   ```

3. Genera el PDF con la portada personalizada:

   ```
   powershell -File tools\render-brochure.ps1 -Cover custom
   ```

   Salida: `Akdemy-Kpex-brochure-es-custom.pdf` y `-en-custom.pdf`.
   (O ábrelo en el navegador con `brochure.html?lang=es&cover=custom`.)

## Recomendaciones para la imagen

- **Proporción A4 vertical** (210 × 297 mm). Tamaño ideal: **2480 × 3508 px** (300 dpi) o al menos **1240 × 1754 px**.
- Formato **JPG** (o PNG). Nómbrala exactamente `cover-custom.jpg`.
- **Deja “aire” a la izquierda y abajo:** encima de tu imagen van el **logo** (arriba izq.), el **título y los chips**
  (centro izq.) y la **tarjeta de precio** (abajo izq.). El texto es blanco sobre un velo oscuro automático,
  así que el lado izquierdo quedará algo oscurecido para que se lea. Coloca el sujeto/figura principal hacia la
  **derecha**.
- Una sola imagen sirve para **español e inglés** (el texto de la portada se traduce solo).

## Opciones

- **Predeterminada:** `dark` (actual). Las otras del banco: `light`, `window`, `curve`.
- Si prefieres una portada **de arte completo sin el overlay de marca** (sin logo/título/precio encima),
  dímelo y te dejo también esa variante.
