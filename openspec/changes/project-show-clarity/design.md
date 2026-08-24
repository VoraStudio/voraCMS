# Technical Design: Project Show UI/UX Clarity & Content Type Hierarchy

## 1. Twig Template Changes

### `templates/admin/project/show.html.twig`
- **Secció de Navegació ràpida entre Seccions (si `totalSections > 1`)**:
  - Un component tipus pill-bar amb les seccions del projecte i el seu recompte.
- **Bloc de Secció (`.d2-panel`)**:
  - Actualitzar `.d2-panel-head` per incloure:
    - `.d2-section-badge`: Pill de secció amb fons taronja suau.
    - `.d2-panel-title`: Títol amb mida i pes destacat.
    - `.d2-section-meta`: Contenidor de metadades (recompte + slug).
    - `.d2-panel-subtitle`: Descripció del tipus de contingut.
  - Taula `.cyber-table`:
    - Eliminar `<th>Client</th>` i la corresponent cel·la `<td>{{ entry.author ... }}</td>`.
    - Ajustar `colspan="6"` al missatge d'estat buit.

## 2. CSS Design System Updates

### `public/css/admin/dashboard.css` & `public/css/admin/layout.css`
- `.d2-section-badge`:
  ```css
  .d2-section-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 2px 8px;
    border-radius: 6px;
    background: rgba(249, 115, 22, 0.15);
    border: 1px solid rgba(249, 115, 22, 0.35);
    color: #fb923c;
  }
  ```
- `.d2-section-meta`:
  ```css
  .d2-section-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-left: 6px;
  }
  .d2-section-meta-item {
    font-size: 0.72rem;
    font-weight: 500;
    color: var(--s-text-secondary);
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 2px 8px;
    border-radius: 12px;
  }
  ```
- Suport per a `[data-theme="light"]`.
