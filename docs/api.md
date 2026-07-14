# VoraCMS — Guia de l'API REST

> **Base URL (producció):** `https://voracms.voradata.cat`  
> **Base URL (local):** `http://127.0.0.1:8000`

---

## Índex

1. [Autenticació](#1-autenticació)
2. [Master Token (públic/SSR)](#2-master-token-públicssr)
3. [Endpoints](#3-endpoints)
4. [Exemples d'ús](#4-exemples-dús)
5. [CORS](#5-cors)
6. [Domain Guard (Bloqueig de Domini)](#6-domain-guard-bloqueig-de-domini)
7. [Errors](#7-errors)
8. [Projectes i Slugs](#8-projectes-i-slugs)

---

## 1. Autenticació

Tots els endpoints sota `/api/*` requereixen autenticació excepte:
* `/api/auth/login` (login manual)
* `/api/public/token` (obtenció de Master Token)
* `/api/public/*` (endpoints públics lliures de token)

L'autenticació es realitza mitjançant **JSON Web Token (JWT)** amb l'algorisme de signatura RSA asimètrica (RS256).

### 1.1 Header Requerit
S'ha d'enviar el JWT a cada sol·licitud a la capçalera `Authorization`:
```http
Authorization: Bearer <JWT>
```

### 1.2 Login (obtenir JWT manualment)
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "usuari@vorastudio.cat",
  "password": "..."
}
```

**Resposta correcta (200 OK):**
```json
{
  "token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Error de credencials (401):**
```json
{
  "code": 401,
  "message": "Invalid credentials."
}
```

### 1.3 Obtenir dades de l'usuari (requereix JWT)
```http
GET /api/auth/me
Authorization: Bearer <JWT>
```

---

## 2. Master Token (públic/SSR)

Per a aplicacions que renderitzen en servidor (SSR) i necessiten consultar dades dinàmiques privades sense demanar credencials a l'usuari final, s'ofereix l'endpoint de Master Token.

```http
GET /api/public/token
```

### 2.1 Flux de funcionament
1. El frontend (ex. servidor Next.js) fa una crida a `GET /api/public/token`.
2. El servidor PHP llegeix el domini solicitant des del header `Host` o `Origin`.
3. Es verifica si algun usuari del CMS té aquest domini a la seva llista d'`allowedDomains` i, si té IP configurada, es valida amb `allowedIps`.
4. Si és vàlid, el CMS retorna un JWT signat pel perfil d'aquell client actiu, amb una validesa d'**1 hora**.

---

## 3. Endpoints

### 3.1 Contingut Públic (sense token, per a SSG)
Accés lliure per a webs estàtiques o de consum directe. Retorna les entrades **publicades** d'un projecte i tipus de contingut.

```http
GET /api/public/{project_slug}/{content_type_slug}
GET /api/public/{project_slug}/{content_type_slug}/{id}
```

* **Query Params:**
  * `locale`: Filtra per idioma (`ca`, `es`, `en`). Si no s'especifica, retorna tots els idiomes.

### 3.2 Artistes (Format Victoria Taylor)
Retorna els artistes en un format pla optimitzat per a la integració amb la galeria de Victoria Taylor.
```http
GET /api/public/artistes
```

### 3.3 Contingut Scoped (requereix JWT)
Retorna el contingut del client resolt a partir de l'usuari autenticat (mitjançant el filtre de Doctrine `user_id_filter`).
* `GET /api/sections` — Llistar els tipus de contingut (taules/seccions)
* `GET /api/{slug}` — Llistar entrades d'un tipus de contingut
* `GET /api/{slug}/{id}` — Entrada individual

### 3.4 Registre de Visites
Registra una impressió/visita d'una entrada per a mètriques d'auditoria.
```http
POST /api/visit
Content-Type: application/json
Authorization: Bearer <JWT>

{
  "entry_id": 28,
  "path": "/projectes/aurex-immobles"
}
```

---

## 4. Exemples d'ús

### 4.1 Carregar dades de forma pública (Vanilla JS)
```javascript
const CMS_BASE = 'https://voracms.voradata.cat';

async function loadProjects() {
  const res = await fetch(`${CMS_BASE}/api/public/web/vorastudio-projects?locale=ca`);
  const json = await res.json();
  console.log(json.data); // Llista de projectes publicats
}
```

### 4.2 Crida provinent de Servidor (SSR) amb Master Token (Node/Next.js)
```javascript
const CMS_BASE = 'https://voracms.voradata.cat';

async function fetchFromSSR(endpoint) {
  // 1. Obtenir Master Token
  const tokenRes = await fetch(`${CMS_BASE}/api/public/token`, {
    headers: {
      'Host': 'vorastudio.cat' // Coincident amb allowedDomains
    }
  });
  const { token } = await tokenRes.json();

  // 2. Cridar l'endpoint privat amb el JWT
  const dataRes = await fetch(`${CMS_BASE}${endpoint}`, {
    headers: {
      'Authorization': 'Bearer ' + token
    }
  });
  return dataRes.json();
}
```

---

## 5. CORS

Tots els endpoints sota `/api/public/*` retornen les capçaleres de CORS següents:
```http
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization
```

Les peticions pre-vuelo (OPTIONS) no requereixen cap tipus d'autenticació.

---

## 6. Domain Guard (Bloqueig de Domini)

L'`ApiDomainGuardSubscriber` verifica que l'origen de la petició estigui explícitament autoritzat per a l'usuari corresponent si aquest té la llista `allowedDomains` definida a la base de dades.

* Si `allowedDomains` està buit: Es permeten tots els orígens.
* Si no s'envia capçalera `Origin` (p. ex., des de curl o script de dev): Es permet l'accés.
* Si el domini de la capçalera `Origin` no és a la llista: Retorna `403 Forbidden`.

---

## 7. Errors

| Codi | Missatge | Causa |
|------|----------|-------|
| **400** | `Bad Request` | Manca de paràmetres obligatoris a la consulta. |
| **401** | `Authentication required` | No s'ha enviat cap JWT o el format és incorrecte. |
| **403** | `Domain not allowed: ...` | L'origen del frontend està bloquejat pel Domain Guard. |
| **404** | `Project not found` | El slug del projecte no existeix. |
| **404** | `Content type not found` | El tipus de contingut (secció) no existeix. |

---

## 8. Projectes i Slugs

Llistat de projectes actius configurats al sistema per a la crida pública `/api/public/{project_slug}/...`:

| Projecte | Slug | Propietari |
|----------|------|------------|
| Web (VoraStudio) | `web` | Vora Studio |
| Victoria Taylor | `victoria-taylor` | Xavi |
| Palmito House | `palmito-house` | Xavi |
| Aula Gastronòmica | `web-principal` | Aula Gastronòmica |
| Wiar | `wiar` | Xavi |
