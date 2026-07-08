# VoraCMS — API REST Guide

> **Base URL (producció):** `https://voracms.voradata.cat`  
> **Base URL (local):** `http://127.0.0.1:8000`

---

## Índex

1. [Autenticació](#1-autenticació)
2. [Tokens d'API](#2-tokens-dapi)
3. [Endpoints](#3-endpoints)
4. [Exemples d'ús](#4-exemples-dús)
5. [CORS](#5-cors)
6. [Domain Guard](#6-domain-guard)
7. [Errors](#7-errors)
8. [Slugs de projectes](#8-slugs-de-projectes)

---

## 1. Autenticació

Tots els endpoints `/api/*` requereixen autenticació excepte `/api/auth/login`.

### 1.1 Mètodes d'autenticació

| Mètode | Token | On s'obté | Caduca |
|--------|-------|-----------|--------|
| **JWT** | `Bearer eyJhbGciOi...` | `POST /api/auth/login` | 1 hora |
| **apiToken** | `Bearer UJIv45gT...` | `GET /api/auth/me` (cal JWT) | Mai |

### 1.2 Login (obtenir JWT)

```
POST /api/auth/login
Content-Type: application/json

{
  "email": "admin@vora.es",
  "password": "..."
}
```

**Resposta correcta (200):**
```json
{
  "token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Error (401):**
```json
{
  "code": 401,
  "message": "Invalid credentials."
}
```

### 1.3 Obtindre dades de l'usuari (requereix JWT)

```
GET /api/auth/me
Authorization: Bearer <jwt>
```

**Resposta (200):**
```json
{
  "data": {
    "slug": "vora-studio",
    "apiToken": "UJIv45gTpMGckBdJjDg3UmkuqZzOWqHV",
    "company": "Vora Studio",
    "email": "admin@vora.es",
    "name": "Vora Studio",
    "allowedDomains": ["vorastudio.cat", "voracms.voradata.cat"]
  }
}
```

> El camp `apiToken` és el token fix per a frontends estáticos.
> El camp `allowedDomains` indica quins origens CORS té permesos l'usuari.

---

## 2. Tokens d'API

### 2.1 Per a frontends estáticos (apiToken)

Els frontends (VoraStudio.cat, VictoriaTaylor.com, etc.) usen l'`apiToken` de l'usuari propietari del projecte.

**Tokens per usuari (producció):**

| Usuari | apiToken | Projectes |
|--------|----------|-----------|
| Vora Studio | `UJIv45gTpMGckBdJjDg3UmkuqZzOWqHV` | Web (vorastudio.cat) |
| Xavi (Global Brands) | `7Y0zI9cG1wRiy9Dw1kwSOgFd5YB12rUL` | Victoria Taylor, Palmito House |
| Aula Gastronòmica | `JIfJnaAQXCCtPehFFYBiM9UDagZEjZ3H` | Aula Gastronòmica |

### 2.2 Capçalera requerida

Totes les peticions a `/api/*` (excepte login) han d'incloure:

```
Authorization: Bearer <token>
```

### 2.3 Exemple amb fetch

```javascript
const API_TOKEN = 'UJIv45gTpMGckBdJjDg3UmkuqZzOWqHV';

fetch('https://voracms.voradata.cat/api/public/web/vorastudio-projects?locale=ca', {
  headers: {
    'Authorization': 'Bearer ' + API_TOKEN
  }
})
.then(r => r.json())
.then(data => console.log(data));
```

---

## 3. Endpoints

### 3.1 Contingut públic (per projecte + content type)

Obté les entrades publicades d'un projecte i content type específic.

```
GET /api/public/{project_slug}/{content_type_slug}
GET /api/public/{project_slug}/{content_type_slug}/{id}
```

**Paràmetres:**

| Paràmetre | Descripció | Exemple |
|-----------|-----------|---------|
| `project_slug` | Slug del projecte | `web`, `victoria-taylor` |
| `content_type_slug` | Slug del content type | `vorastudio-projects`, `noticia`, `event` |
| `id` (opcional) | ID de l'entrada | `28` |

**Query params:**

| Query | Descripció | Exemple |
|-------|-----------|---------|
| `locale` | Filtrar per idioma | `ca`, `es`, `en` |

**Exemples:**

```
GET /api/public/web/vorastudio-projects?locale=ca
GET /api/public/victoria-taylor/noticia?locale=ca
GET /api/public/victoria-taylor/noticia/42
GET /api/public/victoria-taylor/event?locale=ca
GET /api/public/victoria-taylor/artistes_victoria_taylor
```

**Resposta:**
```json
{
  "data": [
    {
      "id": 28,
      "status": "published",
      "locale": "ca",
      "createdAt": "2026-06-30T10:12:07+00:00",
      "titol": "Aurex Immobles",
      "descripcio": "Aurex neix amb la necessitat...",
      "logo": [{ "id": 31, "url": "/uploads/media/31.jpg", "formats": {...} }],
      "website": "www.aureximmobles.com",
      "tags": "Briefing - Marketing - Consulting",
      "repte": "Crear una marca forta...",
      "estrategia": "Posicionar Aurex...",
      "resultat": "Una imatge renovada...",
      "galeria": [{ "id": 32, "url": "/uploads/media/32.jpg", ... }],
      "slug_del_projecte": "aurex",
      "packs": "Pack Integral",
      "ordre": null
    }
  ]
}
```

### 3.2 Artistes (format Victoria Taylor)

```
GET /api/public/artistes
```

Endpoint específic per a la web de Victoria Taylor. Retorna els artistes amb format compatible amb `artistas.js`.

### 3.3 Entrades per content type (scoped a l'usuari)

```
GET /api/{slug}
GET /api/{slug}/{id}
GET /api/sections
```

Aquests endpoints estan scoped a l'usuari autenticat (`UserIdFilter` de Doctrine).
Un usuari només veu les seves pròpies entrades/projectes.

| Endpoint | Descripció |
|----------|-----------|
| `GET /api/{slug}` | Llistat d'entrades publicades per content type |
| `GET /api/{slug}/{id}` | Entrada individual |
| `GET /api/sections` | Llistat de content types de l'usuari |

### 3.4 Visites

```
POST /api/visit
Authorization: Bearer <token>
Content-Type: application/json

{
  "entry_id": 28,
  "path": "/projectes/aurex"
}
```

Registra una visita a una entrada. L'usuari s'obté del token.

---

## 4. Exemples d'ús

### 4.1 Frontend VoraStudio (projectes-scroll.js)

```javascript
const CMS_API_BASE = 'https://voracms.voradata.cat';
const CMS_API_TOKEN = 'UJIv45gTpMGckBdJjDg3UmkuqZzOWqHV';

async function loadCarouselFromCMS() {
  const res = await fetch(CMS_API_BASE + '/api/public/web/vorastudio-projects?locale=ca', {
    headers: { 'Authorization': 'Bearer ' + CMS_API_TOKEN }
  });
  const json = await res.json();
  // json.data → array de projectes
}
```

### 4.2 Frontend Victoria Taylor (cms.js)

```javascript
const CMS_URL = 'https://voracms.voradata.cat';
const CMS_API_TOKEN = 'UJIv45gTpMGckBdJjDg3UmkuqZzOWqHV';

async function getCMSData(url) {
  const response = await fetch(`${CMS_URL}${url}`, {
    headers: { 'Authorization': 'Bearer ' + CMS_API_TOKEN }
  });
  return response.json();
}
```

### 4.3 cURL (testing)

```bash
# Login
curl -X POST https://voracms.voradata.cat/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@vora.es","password":"..."}'

# Llistar projectes (amb JWT)
curl -H "Authorization: Bearer eyJhbGci..." \
  https://voracms.voradata.cat/api/public/web/vorastudio-projects?locale=ca

# Llistar projectes (amb apiToken)
curl -H "Authorization: Bearer UJIv45gTpMGckBdJjDg3UmkuqZzOWqHV" \
  https://voracms.voradata.cat/api/public/web/vorastudio-projects?locale=ca
```

---

## 5. CORS

Els endpoints `/api/public/*` retornen estos headers CORS:

```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization
```

Les peticions OPTIONS (preflight) són públiques (no requereixen token).

### 5.1 Configuració per a dominis específics (opcional)

Al `.htaccess` de producció:

```apache
SetEnvIf Origin "https://(www\.)?vorastudio\.cat" ORIGIN_ALLOWED=$0
Header always set Access-Control-Allow-Origin "%{ORIGIN_ALLOWED}e" env=ORIGIN_ALLOWED
Header always set Access-Control-Allow-Methods "GET, POST, OPTIONS"
Header always set Access-Control-Allow-Headers "Content-Type, Authorization"
```

---

## 6. Domain Guard

L'`ApiDomainGuardSubscriber` verifica que l'origen de la petició estigui permès.

```
Usuari sense allowedDomains → tot permès
Usuari amb allowedDomains   → només Origins que coincideixin
Sense Origin header         → permès (dev, curl, testing)
Origin no permès            → 403 "Domain not allowed: ..."
```

Els `allowedDomains` es configuren a la BD (columna `users.allowed_domains`, array JSON).

---

## 7. Errors

| Codi | Missatge | Causa |
|------|----------|-------|
| 401 | `Authentication required` | No s'ha enviat token Bearer |
| 401 | `Invalid API token` | Token no vàlid o inexistent |
| 403 | `Domain not allowed: ...` | L'origen no està permès per a aquest usuari |
| 404 | `Project not found` | El slug del projecte no existeix |
| 404 | `Content type not found` | El slug del content type no existeix |
| 404 | `Entry not found` | L'entrada no existeix o no pertany al content type |

### 7.1 Format d'error

```json
{
  "error": "Authentication required"
}
```

```json
{
  "error": "Domain not allowed: otherdomain.com"
}
```

---

## 8. Slugs de projectes

Slugs disponibles a producció per a `GET /api/public/{project_slug}/...`:

| Projecte | Slug | apiToken | Usuari propietari |
|----------|------|----------|-------------------|
| Web (VoraStudio) | `web` | `UJIv45g...` | Vora Studio |
| Victoria Taylor | `victoria-taylor` | `7Y0zI9c...` | Xavi |
| Palmito House | `palmito-house` | `7Y0zI9c...` | Xavi |
| Aula Gastronòmica | `web-principal` | `JIfJnaA...` | Aula Gastronòmica |
| Wiar | `wiar` | `7Y0zI9c...` | Xavi |

### 8.1 Projectes individuals (vorastudio-projects)

Slugs del camp `slug_del_projecte` per a la web de VoraStudio:

| Projecte (entry) | slug_del_projecte |
|-----------------|-------------------|
| Aurex Immobles (39) | `aurex` |
| Comercial Ros (28) | `cros` |
| Wiar (40) | `wiar` |
| InnovaFP (41) | *(buit)* |
| Guardavan (42) | *(buit)* |
| D'Tast (43) | `dtast` |
| cFood (44) | `cfood` |
| Spica (45) | `spica` |
| Raymel (46) | `raymel` |

> Aquests slugs es corresponen amb l'atribut `data-project` al HTML de vorastudio.cat.
> Exemple: `<body data-project="aurex">`
