# Tasks: Project Show UI/UX Clarity & Content Type Hierarchy

- [x] 1. Template Twig (`templates/admin/project/show.html.twig`)
  - [x] 1.1 Millorar la capçalera `.d2-panel-head` afegint badge `SECCIÓ`, títol destacat, pill de recompte i slug.
  - [x] 1.2 Eliminar el botó redundant `Veure totes` de la capçalera de secció.
  - [x] 1.3 Eliminar la columna `Client` a la taula d'entrades i ajustar el `colspan` a 6.
  - [x] 1.4 Unificar el botó `Veure` a la taula d'entrades com a icona pura (`.cyber-btn--icon .cyber-btn--view` amb `bi-eye`).
  - [x] 1.5 Aplicar la classe `.cyber-table--project-show` i classes per columna a la taula.

- [x] 2. CSS Design System
  - [x] 2.1 Afegir classes per a `.d2-section-badge`, `.d2-section-meta`, `.d2-section-meta-item` a `public/css/admin/dashboard.css` i `public/css/admin.css`.
  - [x] 2.2 Definir amplades percentuals fixes i `table-layout: fixed` amb `.cyber-table--project-show` per alinear perfectament les columnes entre totes les seccions del projecte.
  - [x] 2.3 Garantir amplada suficient (`17%`) i `white-space: nowrap` per al grup d'accions (`.cyber-actions`).
  - [x] 2.4 Afegir regles per a tema clar a `public/css/admin/theme.css`.

- [x] 3. Verificació
  - [x] 3.1 Comprovar la visualització de `/admin/project/{id}` tant en mode Dark com Light.
  - [x] 3.2 Verificar l'alineació vertical mil·limètrica de les columnes entre múltiples taules de secció.
