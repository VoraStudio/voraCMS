# Tasks: Admin Interface Multilingual Support (CAT, ES, EN)

- [x] 1. Backend Locale Switcher & Session Handling
  - [x] 1.1 Crear `src/Controller/Admin/LocaleController.php` amb ruta `/admin/switch-locale/{locale}`.
  - [x] 1.2 Crear `src/EventSubscriber/LocaleSubscriber.php` per aplicar el `_locale` de la sessió a cada petició.

- [x] 2. Catàlegs de Traducció (`translations/`)
  - [x] 2.1 Crear `translations/messages.ca.yaml` amb claus i textos en Català.
  - [x] 2.2 Crear `translations/messages.es.yaml` amb claus i textos en Castellà.
  - [x] 2.3 Crear `translations/messages.en.yaml` amb claus i textos en Anglès.

- [x] 3. Frontend & UI Selector
  - [x] 3.1 Afegir el component selector d'idioma `.s-lang-switcher` a `templates/admin/layout.html.twig`.
  - [x] 3.2 Afegir estils per al selector d'idioma a `public/css/admin/layout.css` i `public/css/admin/theme.css`.
  - [x] 3.3 Afegir listeners JavaScript per al toggle del desplegable a `public/js/admin.js`.
  - [x] 3.4 Adaptar les plantilles base (`layout.html.twig`, `_user_card.html.twig`, `_project_header_bar.html.twig`, `dashboard.html.twig`, `project/show.html.twig`) amb filtres `|trans`.

- [x] 4. Verificació
  - [x] 4.1 Comprovar el canvi fluid a CAT, ES i EN des de qualsevol pàgina de l'admin.
