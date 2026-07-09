# Status Toggles — Technical Design

## Estructura HTML

```html
<div class="s-status__toggles" id="statusSeg">
  <!-- Published -->
  <div class="s-toggle s-toggle--sel" data-value="published" ...>
    <span class="s-toggle__switch"></span>
    <span class="s-toggle__icon"><i class="bi bi-globe"></i></span>
    <span class="s-toggle__label">Publicat</span>
  </div>
  <!-- Archived -->
  <div class="s-toggle" data-value="archived" ...>
    ...
  </div>
  <!-- Scheduled -->
  <div class="s-toggle" data-value="scheduled" ...>
    ...
  </div>
</div>
```

## JS Lógica (inline en template)

```
click → detectar si ya está seleccionado
  ├─ Sí: deseleccionar → hidden input = 'draft', label = off
  └─ No: deseleccionar todos → seleccionar clickado → hidden input = valor, label = on
```

## CSS Arquitectura

### Dark mode (global)
- `.s-toggle--sel`: box-shadow glow 28px 25% color
- `.s-toggle--sel .s-toggle__switch`: gradient 3-stop (dark → pure → light)
- `.s-toggle--sel .s-toggle__icon`: background-clip: text gradient (pure → light)

### Light mode (data-theme overrides)
- Misma estructura pero mezclando con negro en lugar de blanco
- `.s-toggle--sel .s-toggle__switch`: gradient dark → pure → light
- `.s-toggle--sel .s-toggle__icon`: gradient dark → pure

### Icono font
- `display: inline-flex` + `width/height: 1.2em` fijo
- Necesario para que `background-clip: text` funcione con Bootstrap Icons (font-based)

## Archivos modificados

| Archivo | Cambio |
|---------|--------|
| `templates/admin/entry/edit.html.twig` | Eliminar draft toggle, JS labels/icons/colors, añadir botón Programar |
| `templates/admin/entry/new.html.twig` | Eliminar draft toggle, JS, default status a published |
| `public/css/admin/forms.css` | Gradientes, glow, icon color, light mode overrides |
