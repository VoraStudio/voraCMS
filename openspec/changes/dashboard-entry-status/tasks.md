# Tasks: Dashboard Recent Entries Status Indicator

- [x] 1. Backend Controller (`src/Controller/Admin/DashboardController.php`)
  - [x] 1.1 Afegir `'active' => $entry->isActive()` al mapping principal de `latestEntries`.
  - [x] 1.2 Afegir `'active' => $entry->isActive()` al fallback de `latestEntries`.

- [x] 2. Frontend Twig (`templates/admin/dashboard.html.twig`)
  - [x] 2.1 Inserir el bloc `.d2-news-card__status-corner` amb badges dinàmics segons l'estat i l'activació de l'entrada.
  - [x] 2.2 Retirar el badge redundant inferior `.d2-news-card__status--draft`.

- [x] 3. CSS Design System (`dashboard.css`, `admin.css` & `theme.css`)
  - [x] 3.1 Definir `.d2-news-card` amb `position: relative`.
  - [x] 3.2 Crear classes per a `.d2-news-card__status-corner` i variants `.d2-corner-badge--*`.
  - [x] 3.3 Afegir regles de tema clar per a `.d2-corner-badge--*` a `theme.css`.

- [x] 4. Verificació
  - [x] 4.1 Comprovar la correcta posició i estils tant en mode Fosc com en mode Clar al Dashboard.
