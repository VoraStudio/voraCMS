# Proposal: User Profile Dropdown & Profile View

## 1. Problem Statement
Actualment, la targeta d'usuari situada a la barra superior (`.s-user-card` a `templates/admin/_user_card.html.twig`) mostra el nom, el rol i un botó directe de desconnexió (`admin_logout`). L'usuari no disposa d'un espai o fitxa de perfil on consultar la seva informació (rol, correu electrònic, data d'alta, etc.), ni té una experiència d'interfície moderna basada en menú desplegable d'usuari (*user menu / dropdown*).

## 2. Proposed Solution
1. **Targeta d'usuari amb menú desplegable (Dropdown)**:
   - Convertir la targeta superior en un element desplegable interactiu.
   - En fer-hi clic, mostrar un menú flotant amb dues opcions:
     - **El meu perfil** (`admin_profile`): Redirigeix a la fitxa de dades de l'usuari.
     - **Tancar sessió** (`admin_logout`): Desconnecta la sessió de forma segura.
2. **Pàgina de Perfil d'Usuari (`/admin/profile`)**:
   - Crear el controlador `ProfileController` i la plantilla `templates/admin/user/profile.html.twig`.
   - Mostrar de manera visual (estil fitxa neon/cyber) la informació del compte de l'usuari autenticat:
     - Nom i cognoms / Organització
     - Correu electrònic
     - Rol assignat (Admin, Gestor, Usuari)
     - Empresa (si escau)
     - Data d'alta / creació
     - Estat del compte (Actiu)

## 3. Impact
- Millora de la UI/UX global del panell d'administració.
- Zero canvis estructurals a la base de dades (aprofitament complet de l'entitat `User` existent).
