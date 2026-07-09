# Status Toggles — Spec

## Resumen
Rediseñar los toggles de estado del formulario de entrada del admin VoraCMS:
reducir de 4 a 3 opciones, añadir color/contraste, y feedback visual interactivo.

## Requisitos

### Funcionales
- RF1: Mostrar solo 3 toggles: **Publicat**, **Arxivat**, **Programat**
- RF2: Al clicar un toggle, se selecciona y cambia su label al estado "on"
- RF3: Al volver a clicar el mismo toggle, se deselecciona y vuelve al label "off" (estado draft implícito)
- RF4: Al seleccionar "Programat", mostrar selector de fechas (Inici / Fi) + botón "Programar"
- RF5: El botón "Programar" debe tener el mismo estilo naranja que los cyber-btn del admin

### Visuales (UI)
- RV1: Switch con **degradado de alto contraste** cuando está activo
- RV2: Icono con **degradado** visible (background-clip: text)
- RV3: **Glow sutil** alrededor del toggle activo
- RV4: Etiqueta (label) con más contraste en ambos modos (oscuro/claro)
- RV5: Misma apariencia en modo claro y oscuro, adaptando el degradado

### Técnicos
- RT1: No requiere JS externo — el comportamiento va inline en el template
- RT2: Estado "draft" sigue existiendo como valor interno (hidden input)
- RT3: El schedule picker se ocuestra/muestra vía display toggle

## Colores por estado

| Estado | Color | Hex |
|--------|-------|-----|
| Publicat | Verde | `#22c55e` |
| Arxivat | Ámbar | `#f59e0b` |
| Programat | Azul | `#3b82f6` |
