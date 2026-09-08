# Tasks: Unificar l'estat d'entrades i eliminar el camp redundant Actiu/Inactiu a la UI

- [x] 1. Vistes Twig d'Entrades
  - [x] 1.1 `templates/admin/project/show.html.twig`:
    - Eliminar `<th class="col-active">{{ 'project.active_col'|trans }}</th>` de la capçalera de taula.
    - Eliminar la cel·la `<td class="cyber-cell cyber-cell--status" data-label="Actiu">` de cada fila.
    - Retirar el component `_toggle_btn.html.twig` del grup d'accions (`.cyber-actions`).
    - Ajustar el `colspan` de l'estat buit de 6 a 5.
    - Netejar la classe de la fila perquè no apliqui `.row-inactive` per a entrades.
  - [x] 1.2 `templates/admin/entry/index.html.twig`:
    - Eliminar la columna de capçalera `Actiu`.
    - Eliminar la cel·la de valor d'actiu.
    - Retirar el botó `_toggle_btn.html.twig` d'accions.
    - Ajustar els `colspan` corresponents a l'estat buit.

- [x] 2. CSS Design System
  - [x] 2.1 `public/css/admin/tables.css` i `public/css/admin.css`:
    - Reajustar les amplades de les columnes de `.cyber-table--project-show` per a 5 columnes (Títol 28%, Data 16%, Descripció 26%, Estat 15%, Accions 15%).
    - Eliminar la definició de columna `th.col-active`.
  - [x] 2.2 Incrementar la versió del cache buster per a `tables.css` a `layout.html.twig`.

- [x] 3. Verificació
  - [x] 3.1 Comprovar que la vista de projecte (`/admin/project/{id}`) i llistat d'entrades mostren les 5 columnes netes sense la columna ni botó d'actiu.
  - [x] 3.2 Comprovar que a Usuaris (`/admin/users`) i Projectes (`/admin/projects`) els toggles i columnes d'actiu continuen funcionant amb normalitat.
