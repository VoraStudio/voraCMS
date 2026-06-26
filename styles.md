# VoraCMS · Guía de Estilo del Admin

Todas las normas de diseño UI/UX del panel de administración de VoraCMS.

---

## Botones — Neon Frame (dark) + Gradient Solid (light)

- **Dark mode**: fondo glass oscuro `rgba(2, 6, 23, 0.40)` con borde degradado de 2px mediante máscara CSS (`-webkit-mask` + `mask-composite: exclude`). Texto/icono en color neón (no blanco). Sin clip-path, sin LED line, sin backdrop-filter en disabled.
- **Light mode**: degradado sólido del color del botón (claro → oscuro), texto blanco, SIN borde (`::before { display: none }`), con `box-shadow` sutil 0 2px 8px.
- **Icon buttons**: siempre con clase `cyber-btn--icon` (36x36, padding 0, border 1.5px).
- **Text buttons**: padding 7px 14px, border 2px.
- **Hover**: color/texto a `#fff`, glow intenso `0 0 100px rgba(color, 0.55) + 0 0 160px rgba(color, 0.20)`.
- **Form submit y btn-primary**: degradado sólido cyan→azul, texto blanco, top LED line, shine sweep al hover. En light: gradiente azul más claro.
- **Disabled**: opacidad 0.85, fondo `rgba(148,163,184,0.18)`, texto `rgba(148,163,184,0.65)`, borde `rgba(148,163,184,0.25)`. En light: fondo gris `#E2E8F0 → #CBD5E1`, texto `rgba(100,116,139,0.50)`.

### Paleta de colores neón por variante

| Variante | Color borde/texto (dark) | Gradient light |
|---|---|---|
| Projects / primary / submit | Cyan `rgb(6, 182, 212)` | `#7DD3FC → #0E7490` |
| Toggle active | Green `rgb(34, 197, 94)` | `#86EFAC → #16A34A` |
| Toggle inactive | Red `rgb(239, 68, 68)` | `#FCA5A5 → #DC2626` |
| Edit | Blue `rgb(59, 130, 246)` | `#93C5FD → #2563EB` |
| Delete | Rose `rgb(225, 29, 72)` | `#FDA4AF → #BE123C` |

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
- `.row-inactive`: opacidad 0.75, fondo `rgba(239, 68, 68, 0.03)`.
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
