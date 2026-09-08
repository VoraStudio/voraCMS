# Design: Unificar l'estat d'entrades i eliminar el camp redundant Actiu/Inactiu a la UI

## 1. Arquitectura i Model de Domini
El cicle de vida d'una entrada a VoraCMS queda estrictament governat per `Entry::$status`:
* **`draft` (Esborrany)**: Contingut en creació o desactivat temporalment. No surt a la web pública.
* **`published` (Publicat)**: Contingut visible i disponible a la web pública.
* **`scheduled` (Programat)**: Contingut preparat per a una data de llançament futura.
* **`archived` (Arxivat)**: Contingut històric no visible públicament.

El camp de base de dades `Entry::$active` i la ruta `admin_entry_toggle_active` es mantenen per compatibilitat amb codi existent, però es **retiren de la capa de presentació (UI/UX)** d'entrades.

A les entitats `User` i `Project`, `active` continua plenament vigent a la interfície i al model de seguretat.

---

## 2. Canvis a les Vistes Twig

### 2.1. `templates/admin/project/show.html.twig`
* **Capçalera `<thead>`**:
  * Eliminar `<th class="col-active">{{ 'project.active_col'|trans }}</th>`.
  * La taula passa de 6 columnes a 5 columnes: Títol (28%), Data (16%), Descripció (26%), Estat (15%), Accions (15%).
* **Cos `<tbody>`**:
  * Eliminar la cel·la `<td class="cyber-cell cyber-cell--status" data-label="Actiu">...</td>`.
  * A la cel·la d'accions (`.cyber-actions`), retirar la inclusió del botó `_toggle_btn.html.twig`. Les accions d'una entrada seran:
    1. **Veure** (`.cyber-btn--view`)
    2. **Editar** (`.cyber-btn--edit`)
    3. **Eliminar** (`.cyber-btn--delete`)
  * A l'estat buit (`{% if entries is empty %}`), canviar `<td colspan="6">` per `<td colspan="5">`.
* **Classe de fila**:
  * La fila ja no requereix la classe condicional `.row-inactive` per a entrades, ja que la seva visibilitat depèn exclusivament del seu badge d'Estat (`cyber-status`).

### 2.2. `templates/admin/entry/index.html.twig`
* Eliminar la columna de capçalera `<th>{{ 'status.active'|trans }}</th>`.
* Eliminar la cel·la corresponent a `entry.active`.
* Retirar el botó `_toggle_btn.html.twig` del grup d'accions.
* Ajustar els `colspan` de l'estat buit.

---

## 3. CSS Design System (`public/css/admin/tables.css`)

### 3.1. Taula de seccions de projecte (`.cyber-table--project-show`)
Reajustament d'amplades de columnes per a una distribució equilibrada del 100%:
* `th.col-title, td:nth-child(1)`: `28%`
* `th.col-date, td:nth-child(2)`: `16%`
* `th.col-desc, td:nth-child(3)`: `26%`
* `th.col-status, td:nth-child(4)`: `15%` (centrat)
* `th.col-actions, td:nth-child(5)`: `15%` (alineat a la dreta)

Eliminar la regla específica `th.col-active, td:nth-child(5)` d'aquesta taula.
