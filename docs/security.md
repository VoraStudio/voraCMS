# VoraCMS — Rols i Seguretat

---

## 1. Jerarquia de Rols

VoraCMS te tres rols ordenats jerarquicament. Cada rol hereda els permisos de l'inferior.

```
ROLE_SUPER_ADMIN
  ├── Pot gestionar clients (crear, editar, eliminar)
  ├── Veu el dashboard global (tots els clients)
  ├── Acces a /admin/client/*
  └── HEREA: ROLE_ADMIN

ROLE_ADMIN
  ├── Gestiona el contingut del seu client
  ├── Crea/edita/esborra entrades
  ├── Crea/edita/esborra content types i camps
  ├── Gestiona media (pujar, esborrar)
  └── HEREA: ROLE_USER

ROLE_USER
  ├── Acces a l'API autenticada
  ├── Pot autenticar-se via JWT
  └── NO te acces al panell admin
```

Implementat a `config/packages/security.yaml`:

```yaml
security:
    role_hierarchy:
        ROLE_SUPER_ADMIN: ROLE_ADMIN
        ROLE_ADMIN: ROLE_USER
```

---

## 2. Firewalls (punts d'entrada)

L'aplicacio te tres firewalls independents. Cada un protegeix una zona diferent.

```
                 ┌──────────────────────┐
                 │     Sol.licitud       │
                 └──────────┬───────────┘
                            │
              ┌─────────────┼─────────────┐
              │             │             │
              ▼             ▼             ▼
     ┌────────────┐ ┌────────────┐ ┌────────────┐
     │  api_login │ │    api     │ │   admin    │
     │            │ │            │ │            │
     │ ^/api/auth │ │ ^/api      │ │ ^/admin    │
     │            │ │            │ │            │
     │ Stateless  │ │ Stateless  │ │ Stateful   │
     │ JWT login  │ │ JWT verify │ │ Form login │
     └────────────┘ └────────────┘ └────────────┘
```

### 2.1 Firewall api_login

```
Zona:    /api/auth
Mode:    Stateless (sense sessio)
Metode:
  POST /api/auth/login -> json_login amb email + password
  Si ok -> lexik/jwt-authentication-bundle genera token
  Si ko -> retorna 401 "Invalid credentials"
```

El login es l'unic punt de la API que no requereix token.

### 2.2 Firewall api

```
Zona:    /api
Mode:    Stateless
Metode:  Bearer JWT (Authorization: Bearer <token>)
Acces:
  GET /api/*      -> PUBLIC_ACCESS (no cal autenticacio)
  POST/PUT/DELETE -> IS_AUTHENTICATED_FULLY
```

Els endpoints GET son publics. La identificacio del client es fa via query parameter (?client={slug}).

### 2.3 Firewall admin

```
Zona:    /admin
Mode:    Stateful (amb sessio)
Metode:
  GET /admin/login    -> PUBLIC_ACCESS (formulari)
  POST /admin/login   -> form_login amb email + password
  GET /admin/logout   -> tanca sessio
  Qualsevol altre     -> requereix ROLE_ADMIN
```

Un cop loguejat, Symfony manté la sessio via cookie.

---

## 3. Access Control (regles per ruta)

```
Ordre | Patro        | Metode | Rol necessari
------|---------------|--------|---------------
1     | /api/auth      | tot    | PUBLIC_ACCESS
2     | /api           | GET    | PUBLIC_ACCESS
3     | /api           | POST.. | IS_AUTHENTICATED_FULLY
4     | /admin/login   | tot    | PUBLIC_ACCESS
5     | /admin         | tot    | ROLE_ADMIN
```

**Per que l'ordre importa:**

```yaml
- { path: ^/api/auth,     roles: PUBLIC_ACCESS }
- { path: ^/api,          methods: [GET], roles: PUBLIC_ACCESS }
- { path: ^/api,          roles: IS_AUTHENTICATED_FULLY }
```

Si la regla 3 estigues abans que la 2, els GET requeririen autenticacio. L'ordre es critica.

---

## 4. Com s'apliquen els rols als controllers

### 4.1 API (EntryController)

```php
// GET /api/{slug}?client=... -> public, sense check
// L'access control ho fa automaticament
```

### 4.2 API (AuthController)

```php
// GET /api/auth/me -> nomes usuaris autenticats
// El firewall api ja ho protegeix
```

### 4.3 Admin (ClientController)

```php
// /admin/client/* -> EXCLUSIU per super-admin
public function index(): Response
{
    $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');
}
```

Check explicit perque, tot i que l'access control requereix ROLE_ADMIN,
la gestio de clients es nomes per super-admin.

### 4.4 Admin (la resta)

```php
// /admin/entry/*, /admin/content-type/*, /admin/media/*
// L'access control ja requereix ROLE_ADMIN
// NO cal denyAccessUnlessGranted a cada metode
```

---

## 5. Visibilitat per rol al Dashboard

| Rol | Que veu |
|-----|---------|
| ROLE_SUPER_ADMIN | Tots els clients + estadistiques + selector |
| ROLE_ADMIN | Nomes el seu client + estadistiques scoped |

```php
// DashboardController
$currentClient = $this->clientScope->getClient();
$isSuperAdmin = $this->clientScope->isSuperAdmin();

$contentTypes = $ctRepo->findActive(); // ja scoped

if ($isSuperAdmin) {
    $clients = $clientRepo->findBy([], ['name' => 'ASC']);
}
```

---

## 6. ClientScope i rols (la connexio)

ClientScope tambe sap si l'usuari es super-admin.

| Situacio | ClientScope->getClientId() | Filtre Doctrine |
|----------|---------------------------|-----------------|
| Super-admin al dashboard | null (veu tot) | Desactivat |
| Super-admin editant client | client_id del client | Activat |
| Admin normal | el seu client_id | Activat |
| API publica | client_id del ?client | Activat |
| Visitant no autenticat | null | Desactivat |

---

## 7. JWT i claims de seguretat

```json
{
  "iat": 1700000000,
  "exp": 1700003600,
  "roles": ["ROLE_ADMIN", "ROLE_USER"],
  "username": "victoria@victoriataylor.com",
  "client_id": 4,
  "client_slug": "victoria-taylor"
}
```

Els claims client_id i client_slug permeten:
1. Al frontend saber a quin client pertany l'usuari sense cridar la API
2. Al backend validar que l'usuari nomes accedeix al seu client
3. Al ClientScope activar el filtre sense consultar la BD

---

## 8. Esquema complet de decisions

```
Sol.licitud HTTP
      |
      v
Firewall matcheja
      |
      +-- api_login -> login proces -> JWT
      +-- api -> extreu JWT -> valida signatura -> extreu claims
      +-- admin -> llegeix sessio -> extreu usuari
              |
              v
      L'usuari te el rol necessari?
              |
      +-- NO -> 401 / redirect login
      |
      +-- SI -> ClientScope s'activa
                  |
                  +-- JWT -> client_id del token
                  +-- Admin ROLE_ADMIN -> client_id de l'usuari
                  +-- Admin ROLE_SUPER_ADMIN -> null (veu tot)
                  +-- API publica -> client_id del ?client={slug}
                          |
                          v
                  Filtre Doctrine activat/desactivat
                          |
                          v
                  Controller executa
                          |
                          v
                  Resposta
```

---

## 9. Resum de regles

**ROLE_SUPER_ADMIN**
- /admin/client/*       -> Si
- /admin/entry/*        -> Si (tots els clients)
- /admin/content-type/* -> Si (tots els clients)
- /admin/media/*        -> Si (tots els clients)
- /admin/               -> Dashboard global
- /api/auth/login       -> Si
- /api/auth/me          -> Si

**ROLE_ADMIN**
- /admin/client/*       -> NO
- /admin/entry/*        -> Si (nomes el seu)
- /admin/content-type/* -> Si (nomes el seu)
- /admin/media/*        -> Si (nomes el seu)
- /admin/               -> Dashboard scoped
- /api/auth/login       -> Si
- /api/auth/me          -> Si

**ROLE_USER (o no autenticat)**
- /api/{slug}?client=   -> Si (GET public)
- /api/auth/login       -> Si
