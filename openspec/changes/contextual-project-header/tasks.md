# Tasks: Capçalera Contextual Adaptativa de Projectes i Entrades

- [x] 1. Afegir claus de traducció per a mètriques contextuals (`status.total_entries`, `status.active_projects`, `status.inactive_projects`) a `messages.ca.yaml`, `messages.es.yaml` i `messages.en.yaml`. <!-- id: 1 -->
- [x] 2. Marcar files de taula amb `data-status="{{ entry.status }}"` a `templates/admin/project/show.html.twig` i `templates/admin/entry/index.html.twig` per suportar el filtrat dinàmic. <!-- id: 2 -->
- [x] 3. Refactoritzar `templates/admin/project/_project_header_bar.html.twig`: <!-- id: 3 -->
  - [x] 3.1. Detecció de context (`isInsideProject` per distingir vista global d'administració de vista de detall/secció/client).
  - [x] 3.2. Càlcul de mètriques diferenciades (Projectes + Entrades en global vs Només Entrades contextuals).
  - [x] 3.3. Graelles de 6 KPIs adaptatives amb icones i estils cuidats.
  - [x] 3.4. Selectors d'estat i cercadors dinàmics per a projectes i files d'entrades.
- [x] 4. Assegurar la inclusió correcta de la capçalera a `project/show.html.twig` i la seva absència a `project/form.html.twig`. <!-- id: 4 -->
- [x] 5. Eliminar la línia redundant `.d2-section-bar` («Seccio: [Nom del projecte]») de `_project_header_bar.html.twig`. <!-- id: 5 -->
- [x] 6. Eliminar la capçalera de mètriques de les vistes de configuració de tipus de contingut (`content-type` i `base-content`). <!-- id: 6 -->
