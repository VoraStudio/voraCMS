# Status Toggles — Tasks

## [x] 1. Eliminar draft toggle del template edit.html.twig
- [x] Quitar bloque HTML del toggle draft
- [x] Actualizar JS: labels, icons, colors (3 en lugar de 4)

## [x] 2. Eliminar draft toggle del template new.html.twig
- [x] Quitar bloque HTML del toggle draft
- [x] Actualizar JS: labels, icons, colors
- [x] Cambiar default hidden input de 'draft' a 'published'

## [x] 3. Añadir comportamiento toggle on/off al clicar
- [x] Si el toggle ya está seleccionado → deseleccionar, hidden input = 'draft'
- [x] Si no está seleccionado → seleccionar, deseleccionar los demás

## [x] 4. Layout: 3 toggles por fila
- [x] Cambiar .s-fields grid a repeat(6, 1fr)
- [x] .s-field: span 3 (2 por fila para campos normales)
- [x] .field-boolean: span 2 (3 por fila para booleanos)
- [x] Responsive: reset grid-column a auto en móvil

## [x] 5. Color & contraste en toggles de estado
- [x] Switch: gradient 3-stop (dark → pure → light)
- [x] Icono: gradient background-clip text
- [x] Glow en toggle activo (box-shadow)
- [x] Light mode: gradientes mezclados con negro

## [x] 6. Color & contraste en checkbox toggles (boolean fields)
- [x] Switch: gradient cuando checked
- [x] Icono: gradient background-clip text cuando checked
- [x] Mejor contraste en labels (unchecked 0.6→0.75, checked bg 12%→20%)

## [x] 7. Botón "Programar" en schedule picker
- [x] Añadir botón submit dentro del #schedulePicker
- [x] Estilo cyber-btn--projects (naranja, borde degradado, glow)
- [x] Margin-top 16px para separar de los inputs de fecha

## [x] 8. Verificar modo claro
- [x] Degradados visibles con fondo claro
- [x] Overrides light mode para switch, icon, glow
