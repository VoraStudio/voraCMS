# Tasks: User Profile Dropdown & Profile View

- [x] 1. Controller & Routing
  - [x] 1.1 Crear `src/Controller/Admin/ProfileController.php` amb la ruta `#[Route('/admin/profile', name: 'admin_profile')]`.
  - [x] 1.2 Recuperar l'usuari autenticat i passar dades a la vista.

- [x] 2. Templates Twig
  - [x] 2.1 Refactoritzar `templates/admin/_user_card.html.twig` per estructurar el trigger del dropdown i el menú emergent.
  - [x] 2.2 Crear la plantilla `templates/admin/user/profile.html.twig` amb la fitxa completa de perfil d'usuari.

- [x] 3. CSS & Design System
  - [x] 3.1 Afegir les classes de dropdown (`.s-user-dropdown`, `.s-user-dropdown-menu`, `.s-user-dropdown-item`, etc.) a `public/css/admin/layout.css` i `public/css/admin.css`.
  - [x] 3.2 Afegir les regles per a tema clar a `public/css/admin/theme.css`.
  - [x] 3.3 Afegir estils de la fitxa de perfil d'usuari (`.profile-card`, `.profile-grid`, etc.).

- [x] 4. JavaScript Interactions
  - [x] 4.1 Afegir a `public/js/admin.js` el control de clic obrir/tancar per al desplegable d'usuari.
  - [x] 4.2 Afegir el tancament automàtic per clic exterior (`click outside`) i tecla `Escape`.

- [x] 5. Verificació
  - [x] 5.1 Provar la interacció del desplegable en mode Dark i Light.
  - [x] 5.2 Comprovar la navegació a `/admin/profile` i la sortida via `/admin/logout`.
