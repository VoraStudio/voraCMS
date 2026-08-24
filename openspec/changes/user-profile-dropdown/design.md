# Technical Design: User Profile Dropdown & Profile View

## 1. Controller & Routing

### `ProfileController` (`src/Controller/Admin/ProfileController.php`)
- Controlador protegit per seguretat (requereix autenticació al firewall admin).
- Ruta: `#[Route('/admin/profile', name: 'admin_profile')]`
- Mètode: `index(): Response`
- Lògica:
  - Recupera `$user = $this->getUser();` (instància de `App\Entity\User`).
  - Renderitza `admin/user/profile.html.twig` passant `user` i `breadcrumbs`.

## 2. Twig Templates

### `templates/admin/_user_card.html.twig`
- Estructura HTML:
  ```html
  <div class="s-user-dropdown" id="userDropdown">
      <button type="button" class="s-user-card s-user-dropdown-toggle" id="userDropdownToggle" aria-expanded="false">
          <img src="..." class="s-user-card-avatar" alt="...">
          <div class="s-user-card-info">
              <span class="s-user-card-name">{{ user.name }}</span>
              <span class="s-user-card-role">{{ userRole }}</span>
          </div>
          <i class="bi bi-chevron-down s-user-card-chevron"></i>
      </button>
      <div class="s-user-dropdown-menu" id="userDropdownMenu">
          <a href="{{ path('admin_profile') }}" class="s-user-dropdown-item">
              <i class="bi bi-person-circle"></i>
              <span>El meu perfil</span>
          </a>
          <div class="s-user-dropdown-divider"></div>
          <a href="{{ path('admin_logout') }}" class="s-user-dropdown-item s-user-dropdown-item--logout">
              <i class="bi bi-box-arrow-right"></i>
              <span>Tancar sessió</span>
          </a>
      </div>
  </div>
  ```

### `templates/admin/user/profile.html.twig`
- Extén `admin/layout.html.twig`.
- Blocs: `page_title` ("El meu Perfil"), `page_desc` ("Dades del teu compte i accés a la plataforma").
- Targeta d'estil corporatiu Cyber/Neon dividida en:
  - Hero header: Avatar gran, nom, badges de rol i estat.
  - Graella de propietats de perfil (Email, Organització/Empresa, Slug d'usuari, Data d'alta).
  - Accions de peu de fitxa (Enllaç a Dashboard).

## 3. CSS Styling (`public/css/admin/layout.css` & `public/css/admin/theme.css`)
- `.s-user-dropdown`: Posicionament relatiu (`position: relative;`).
- `.s-user-card-chevron`: Indicador de fletxa amb transició de rotació (`transform: rotate(180deg)` quan actiu).
- `.s-user-dropdown-menu`:
  - `position: absolute; right: 0; top: calc(100% + 8px); z-index: 1050; min-width: 190px;`
  - Fons translúcid amb efecte glassmorphism (`backdrop-filter: blur(12px)`), vora suau i ombra flotant.
  - Estat ocult per defecte (`opacity: 0; pointer-events: none; transform: translateY(-6px); transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);`).
  - Estat visible (`.is-open`: `opacity: 1; pointer-events: auto; transform: translateY(0);`).
- Adaptació de colors específics per a `[data-theme="light"]`.

## 4. JavaScript Interactions (`public/js/admin.js`)
- Gestió de l'esdeveniment clic al botó toggle (`#userDropdownToggle`).
- Alternança de la classe `.is-open` i atribut `aria-expanded`.
- Listener a `document` per tancar el desplegable en fer clic fora (`!userDropdown.contains(e.target)`).
- Tancament amb la tecla `Escape`.
