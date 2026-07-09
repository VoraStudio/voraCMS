# VoraCMS — Rols, Autenticació i API

---

## 1. Jerarquia de Rols

```
ROLE_ADMIN
  ├── Accés complet al panell admin (/admin/*)
  ├── Gestiona entrades, content types, media
  ├── Veu tots els projectes i usuaris
  └── HEREA: ROLE_USUARIO

ROLE_USUARIO
  ├── Accés a l'API autenticada
  ├── Pot autenticar-se via JWT o apiToken
  └── Dades scoped al seu user_id (Doctrine filter)
```

Implementat a `config/packages/security.yaml`:

```yaml
role_hierarchy:
    ROLE_ADMIN: ROLE_USUARIO
```

---

## 2. Esquema d'autenticació API

```
                  ┌──────────────────────┐
                  │     Petició HTTP      │
                  └──────────┬───────────┘
                             │
               ┌─────────────┼─────────────┐
               │             │             │
               ▼             ▼             ▼
      ┌────────────┐ ┌────────────┐ ┌────────────┐
      │ api_login  │ │    api     │ │   admin    │
      │            │ │            │ │            │
      │ ^/api/auth │ │ ^/api      │ │ ^/admin    │
      │            │ │            │ │            │
      │ Stateless  │ │ Stateless  │ │ Stateful   │
      │ JWT login  │ │ Dual auth  │ │ Form login │
      └────────────┘ └────────────┘ └────────────┘
```

### 2.1 Firewall `api_login` — `/api/auth`

```
POST /api/auth/login   → json_login (email + password) → JWT
GET  /api/auth/me      → Retorna dades de l'usuari + apiToken + allowedDomains
```

- Públic (no requereix token previ)
- Gestionat per `lexik/jwt-authentication-bundle`

### 2.2 Firewall `api` — `/api/*`

Utilitza `ApiTokenAuthenticator` (autenticador híbrid):

| Mètode | Descripció |
|--------|-----------|
| **JWT** | Token JWT obtingut via `/api/auth/login` |
| **apiToken** | Token pla de 32 chars de l'usuari (no expira) |

**Capçalera requerida:**
```
Authorization: Bearer <token>
```

**Si no s'envia token → 401 "Authentication required"**
**Si el token no és vàlid → 401 "Invalid API token"**

### 2.3 Firewall `admin` — `/admin/*`

```
GET  /admin/login   → Públic (formulari de login)
POST /admin/login   → form_login (email + password)
GET  /admin/logout  → Tanca sessió
Qualsevol altre     → Requereix ROLE_ADMIN
```

---

## 3. Tokens d'API

### 3.1 JWT (per usuaris)

```
POST /api/auth/login
Body: { "email": "user@vora.es", "password": "..." }
Response: { "token": "eyJhbGciOiJSUzI1Ni..." }
```

- Caduca en 1 hora (configurable a `lexik_jwt_authentication.yaml`)
- Es renova fent login de nou
- Ús: admin, usuaris amb sessió

### 3.2 apiToken (per frontends estáticos)

```
GET /api/auth/me
Authorization: Bearer <jwt>
Response: { "data": { "apiToken": "UJIv45gTpMGckBdJjDg3UmkuqZzOWqHV", ... } }
```

- Token fix de 32 chars, no expira
- Cada usuari en té un de únic
- Ús: frontends estáticos (Victòria Taylor, VoraStudio web)

### 3.3 apiTokens per usuari (producció)

| Usuari | apiToken |
|--------|----------|
| Vora Studio | `UJIv45gTpMGckBdJjDg3UmkuqZzOWqHV` |
| Xavi (Global Brands) | `7Y0zI9cG1wRiy9Dw1kwSOgFd5YB12rUL` |
| Aula Gastronòmica | `JIfJnaAQXCCtPehFFYBiM9UDagZEjZ3H` |

---

## 4. Domain Guard (protecció per origen)

L'`ApiDomainGuardSubscriber` verifica que l'origen de la petició (`Origin` header)
estigui permès per l'usuari autenticat.

```
Usuari sense allowedDomains → tot permès
Usuari amb allowedDomains   → només Origins que coincideixin
Sense Origin header         → permès (dev, curl, etc.)
Origin no permès            → 403 "Domain not allowed: ..."
```

`allowedDomains` és un array JSON guardat a la columna `users.allowed_domains`:

```json
["vorastudio.cat", "voracms.voradata.cat"]
```

---

## 5. Access Control (regles per ruta)

| Ordre | Patro | Mètode | Rol |
|-------|-------|--------|-----|
| 1 | `/api/auth` | TOT | PUBLIC_ACCESS |
| 2 | `/api` | OPTIONS | PUBLIC_ACCESS (preflight CORS) |
| 3 | `/api` | TOT | IS_AUTHENTICATED_FULLY |
| 4 | `/admin/login` | TOT | PUBLIC_ACCESS |
| 5 | `/admin` | TOT | ROLE_USUARIO |

---

## 6. Endpoints API

### 6.1 Autenticació

| Mètode | Ruta | Descripció | Auth |
|--------|------|-----------|------|
| POST | `/api/auth/login` | Obté JWT (email + password) | ❌ Públic |
| GET | `/api/auth/me` | Dades usuari + apiToken + allowedDomains | ✅ JWT |

### 6.2 Contingut

| Mètode | Ruta | Descripció | Auth |
|--------|------|-----------|------|
| GET | `/api/public/{project}/{type}` | Entrades publicades per projecte + content type | ✅ Token |
| GET | `/api/public/{project}/{type}/{id}` | Entrada individual | ✅ Token |
| GET | `/api/public/artistes` | Artistes (format compat Victoria Taylor) | ✅ Token |
| GET | `/api/{slug}` | Entrades per content type (scoped a usuari) | ✅ Token |
| GET | `/api/{slug}/{id}` | Entrada individual (scoped a usuari) | ✅ Token |
| GET | `/api/sections` | Content types de l'usuari | ✅ Token |

### 6.3 Visites

| Mètode | Ruta | Descripció | Auth |
|--------|------|-----------|------|
| POST | `/api/visit` | Registra una visita | ✅ Token |

### 6.4 Exemple d'ús (frontend estátic)

```javascript
const API_TOKEN = 'UJIv45gTpMGckBdJjDg3UmkuqZzOWqHV';

fetch('https://voracms.voradata.cat/api/public/victoria-taylor/noticia', {
  headers: { 'Authorization': 'Bearer ' + API_TOKEN }
});
```

---

## 7. CORS

La configuració CORS es fa des de `PublicController`:

```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization
```

Per a entorns productius, es pot restringir l'origen des de `.htaccess`:

```apache
SetEnvIf Origin "https://(www\.)?vorastudio\.cat" ORIGIN_ALLOWED=$0
Header set Access-Control-Allow-Origin "%{ORIGIN_ALLOWED}e" env=ORIGIN_ALLOWED
```

---

## 8. .htaccess (producció)

**`public/.htaccess`:**

```apache
# 👇 CRÍTIC: Forward Authorization header per PHP-FPM
SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1

DirectoryIndex index.php
FallbackResource /index.php
```

Sense `SetEnvIf Authorization`, Apache amb PHP-FPM no passa el header
`Authorization` a PHP → el `ApiTokenAuthenticator` rep null → 401.

---

## 9. Migracions i Deploy

CDMON té `proc_open()` deshabilitat. Els comandos `bin/console` que el necessiten
fallen silenciosament al GitHub Actions (perquè té `|| true`).

**Solución:** usar `deploy.php` que fa cache clear físic + migrations via
Doctrine directament sense el CLI de Symfony.

---

## 10. Resum de regles

**ROLE_ADMIN**
- `/admin/*` → ✅
- `/api/auth/login` → ✅
- `/api/auth/me` → ✅ (via JWT)
- `/api/public/*` → ✅ (via JWT o apiToken)

**ROLE_USUARIO**
- `/admin/*` → ❌
- `/api/auth/login` → ✅
- `/api/auth/me` → ✅ (via JWT)
- `/api/public/*` → ✅ (via JWT o apiToken)
