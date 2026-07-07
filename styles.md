# VoraCMS · Guía de Estilo del Admin

Todas las normas de diseño UI/UX del panel de administración de VoraCMS.

---

## Botones

### Botones de acción (icono) — Minimalist Corporate

Estilo para CRUD actions en tablas: toggle, editar, borrar.

- **Icon buttons**: clase `cyber-btn--icon` (34×34, padding 0, border-radius 8px).
- **Default**: fondo transparente, icono color neutro mutado `rgba(148,163,184,0.85)` (dark) / `rgba(100,116,139,0.85)` (light). Sin bordes, sin glows, sin glass.
- **Hover**: background `rgba(color, 0.08)` + color semántico del icono. Sin glow, sin transform.
- **Active**: background `rgba(color, 0.14)`.
- **Toggle active**: icono verde sutil `rgba(34,197,94,0.90)` por defecto (indica estado ON).

### Botones de texto (con label) — Neon Frame (dark) + Gradient Solid (light)

- **Dark mode**: fondo glass oscuro `rgba(2, 6, 23, 0.40)` con borde degradado de 2px mediante máscara CSS.
- **Light mode**: degradado sólido, texto blanco, sin borde, `box-shadow` sutil.
- **Text buttons**: padding 7px 14px, border-radius 10px.
- **Hover**: color/texto a `#fff`, glow intenso.
- **Disabled**: opacidad 0.85.

### Form submit y btn-primary

Degradado sólido cyan→azul, texto blanco, top LED line, shine sweep al hover. En light: gradiente azul más claro.

### Paleta de colores por variante

| Variante | Color icono (dark) | Color hover bg |
|---|---|---|
| Toggle active | Green `rgba(34, 197, 94, 0.90)` | `rgba(34, 197, 94, 0.08)` |
| Toggle inactive | Muted `rgba(148, 163, 184, 0.85)` | `rgba(239, 68, 68, 0.08)` |
| Edit | Muted `rgba(148, 163, 184, 0.85)` | `rgba(59, 130, 246, 0.08)` |
| Delete | Muted `rgba(148, 163, 184, 0.85)` | `rgba(239, 68, 68, 0.08)` |

| Variante (neón) | Color texto/borde (dark) | Gradient light |
|---|---|---|
| Projects / primary / submit | Cyan `rgb(6, 182, 212)` | `#7DD3FC → #0E7490` |

---

## Textos — opacidades mínimas

| Contexto | Dark mode | Light mode |
|---|---|---|
| Texto principal (`--s-text`) | 0.92 | 0.88 |
| Texto secundario (`--s-text-secondary`) | 0.65 | 0.72 |
| Section description | 0.8 | 0.8 |
| Stat mini labels | 0.6 | 0.7 |
| Form labels | 0.85 | 0.85 |
| Títulos con gradiente | `#F8FAFC → #CBD5E1` | `#1E293B → #475569` |

---

## Filas de tabla

- `.row-active`: fondo `rgba(59, 130, 246, 0.04)` (dark) / `rgba(59, 130, 246, 0.06)` (light) — azul, NO verde.
- `.row-inactive`: opacidad 0.55, fondo `rgba(239, 68, 68, 0.10)` (dark) / `rgba(239, 68, 68, 0.12)` (light). Celdas (excepto estado) con opacidad 0.20. El texto "Inactiu" usa color sólido `rgb(239,68,68)` (no opacidad reducida).
- Filas con `.cyber-row`: border-radius 12px, glass con `box-shadow`, hover con glow sutil.

---

## Cards y contenedores

- `.cyber-card`: fondo `rgba(15, 23, 42, 0.50)`, borde `1px solid rgba(148,163,184,0.06)`, blur.
- `.cyber-section-header`: glass oscuro con `::before` radial glow.
- Padding de cards: 24px 28px.

---

## Animaciones GSAP (admin-animations.js)

- cyber-card: fade in + scaleY (0.97 → 1) + translateY
- cyber-row: stagger slide desde abajo
- cyber-btn: bounce con `back.out(1.7)`
- Siempre data-anim en el HTML, JS lee con `[data-anim]`
- **NUNCA usar `opacity: 0`** en `tl.from()` o `gsap.from()` para elementos que deben ser visibles siempre. Si un botón empieza con `opacity: 0` por GSAP y la timeline falla, el botón queda invisible. Usar solo transform (y, scale) sin opacidad.

---

## Validación de formularios

- Inputs con `required` que fallan al enviar: `:user-invalid` con borde rojo `#EF4444` (dark) / `#DC2626` (light)
- Glow rojo dark: `box-shadow: 0 0 0 3px rgba(239,68,68,0.20), 0 0 24px rgba(239,68,68,0.08)`
- Glow rojo light: `box-shadow: 0 0 0 3px rgba(220,38,38,0.25), 0 0 28px rgba(220,38,38,0.12)`
- No requiere JavaScript, es CSS nativo con `:user-invalid`

---

## Toggle activar/desactivar usuarios

- Vía AJAX (fetch + JSON), sin recarga de página
- Al hacer click: envía POST, servidor actualiza y devuelve JSON
- JS actualiza: botón (clase, icono, title), fila (row-active/row-inactive), celda de estado (Actiu/Inactiu)
- El admin puede activarse/desactivarse y eliminarse a sí mismo sin restricciones
