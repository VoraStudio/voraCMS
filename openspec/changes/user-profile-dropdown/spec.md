# Specification: User Profile Dropdown & Profile View

## 1. User Stories
- **Com a usuari/administrador autenticat**, vull poder fer clic a la meva targeta d'usuari a la capçalera per desplegar les opcions del meu compte.
- **Com a usuari/administrador autenticat**, vull poder accedir a una pàgina de perfil ("El meu perfil") per revisar les meves dades bàsiques, rol i data de registre.
- **Com a usuari/administrador autenticat**, vull poder tancar la sessió fàcilment des del menú desplegable.

## 2. Requirements & UI/UX Constraints

### Req 1: User Card Dropdown
- El trigger de la targeta mantindrà l'avatar, el nom de l'usuari i el rol.
- S'afegirà una icona d'indicador desplegable (`chevron-down`).
- En fer clic al trigger, el menú s'obrirà / tancarà (toggle).
- En fer clic fora de l'element (`click outside`), el menú es tancarà automàticament.
- Menú amb estil visual coherent amb el disseny Cyber/Neon de VoraCMS (compatibilitat amb temes Dark i Light).

### Req 2: Dropdown Menu Options
- **Opció 1**: "El meu perfil" -> Enllaça a `{{ path('admin_profile') }}`. Icona `bi bi-person-circle` o `bi bi-person-badge`.
- **Separador**: línia divisòria subtil.
- **Opció 2**: "Tancar sessió" -> Enllaça a `{{ path('admin_logout') }}`. Icona `bi bi-box-arrow-right` amb accent d'alerta suau (vermell/hover).

### Req 3: User Profile Page (`/admin/profile`)
- Accessible per a qualsevol usuari autenticat (`ROLE_USUARIO`, `ROLE_MOD`, `ROLE_ADMIN`).
- Vista dissenyada com a fitxa de dades d'usuari:
  - Header amb Avatar ampliat, Nom i Badge de Rol.
  - Secció de dades: Email, Nom de slug/identificador, Empresa, Data de creació, Estat del compte (Actiu/Inactiu).
  - Breadcrumbs correctes a la barra superior (`Inici / Perfil`).
  - Botó d'acció per tornar al Dashboard.
