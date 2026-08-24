# Specification & Tasks: Global UI/UX Theme Refinement

## 1. Requirements

### Req 1: Dark Mode Warmth & Contrast
- Modificar `--s-bg` a `#111015` i `--s-main-bg` a un degradat fosc càlid grafit (`#111015` a `#1a1622`).
- Sidebar definit amb fons més fosc (`#0c0b0f`), vora lateral dreta nítida (`1px solid var(--s-border-light)`).
- Topbar amb efecte glassmorphism sobre el fons del sidebar (`backdrop-filter: blur(14px)`).

### Req 2: Light Mode Modern Clean & Logo Contrast
- Canviar el fons a un to gris clar modern de programari SaaS (`--s-bg: #f8fafc`, `--s-main-bg: #f1f5f9`).
- Targetes blanques pures (`#ffffff`) amb ombres suaus i vores elegants.
- Filtre per al logotip de la barra lateral en mode clar: `[data-theme="light"] .s-sidebar-logo { filter: brightness(0) opacity(0.85); }` per garantir que es visualitzi al 100% de contrast.

## 2. Tasks Status

- [x] 1. Actualitzar tokens a `public/css/root.css` per al mode fosc unificat i topbar translúcida.
- [x] 2. Actualitzar tokens a `public/css/admin/theme.css` per al mode clar unificat i topbar translúcida.
- [x] 3. Afegir regla de filtre per al logo en mode clar a `public/css/admin/theme.css`.
- [x] 4. Retirar ratlles divisòries (`border-right` i `border-bottom`) a `layout.css`, `admin.css` i `theme.css`.
- [x] 5. Aplicar efecte glassmorphism blur a `s-topbar` (`backdrop-filter: blur(18px)`) per a un scroll fluid i nítid.
