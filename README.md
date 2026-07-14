# VoraCMS — Guia de l'API per al Frontend

VoraCMS és un **headless CMS multi-projecte i multi-client**. No disposa de frontend propi — exposa una API REST dissenyada perquè qualsevol frontend (Astro, Next.js, Nuxt, React, HTML vanilla, etc.) consumeixi el contingut de manera senzilla i segura.

Totes les dades estan completament aïllades per client i projecte.

---

## Índex

- [Mètodes d'Accés i Autenticació](#mètodes-daccés-i-autenticació)
  - [1. Accés Públic (Recomanat per a webs estàtiques)](#1-accés-públic-recomanat-per-a-webs-estàtiques)
  - [2. Accés Protegit amb JWT (Privat / Dashboard)](#2-accés-protegit-amb-jwt-privat--dashboard)
  - [3. Accés Públic Dinàmic amb Master Token](#3-accés-públic-dinàmic-amb-master-token)
- [Endpoints de l'API](#endpoints-de-lapi)
  - [API Pública (`/api/public/*`)](#api-pública-apipublic)
  - [API Autenticada (`/api/*`)](#api-autenticada-api)
- [Formats dels Camps](#formats-dels-camps)
- [Codis d'Error](#codis-derror)
- [Exemples d'Integració per Framework](#exemples-dintegració-per-framework)
  - [Fetch Natiu (Vanilla JS)](#fetch-natiu-vanilla-js)
  - [Astro (Pàgines Estàtiques)](#astro-pàgines-estàtiques)
  - [Next.js (App Router amb Master Token)](#nextjs-app-router-amb-master-token)

---

## Mètodes d'Accés i Autenticació

VoraCMS disposa de tres fluxos clau per consumir el contingut segons les necessitats del teu frontend:

### 1. Accés Públic (Recomanat per a webs estàtiques)
No requereix cap tipus de token ni capçalera. És el mètode ideal per a llocs web públics o generadors de llocs estàtics (SSG).
* **URL:** `/api/public/{project_slug}/{content_type_slug}`
* **Exemple:** `https://voracms.voradata.cat/api/public/web/vorastudio-projects?locale=ca`

### 2. Accés Protegit amb JWT (Privat / Dashboard)
Requereix un JSON Web Token (JWT) vàlid que s'obté mitjançant credencials d'usuari. Ideal per a aplicacions privades, intranets o integracions del client.
* **Header HTTP requerit:**
  ```http
  Authorization: Bearer <JWT_TOKEN>
  ```
* **Obtenció del token:** Fent una petició `POST /api/auth/login` amb les credencials de l'usuari. Té una caducitat d'**1 hora**.

### 3. Accés Públic Dinàmic amb Master Token
Si el teu frontend fa peticions dinàmiques des del servidor (SSR) i necessita accedir als endpoints autenticats `/api/*` sense demanar credencials manualment, pot auto-generar un JWT mitjançant l'endpoint de Master Token.
* **Funcionament:** El frontend demana un token a `GET /api/public/token`.
* **Seguretat:** El servidor valida que el domini (`Origin` / `Host`) i la IP sol·licitants estiguin explícitament autoritzats a la fitxa d'usuari del CMS. Si es valida, el CMS retorna un JWT vàlid per una hora.

---

## Endpoints de l'API

### API Pública (`/api/public/*`)

Aquests endpoints tenen els CORS oberts (`Access-Control-Allow-Origin: *`) i es poden cridar directament des del navegador.

#### Llistar entrades d'un projecte i secció
```http
GET /api/public/{project_slug}/{content_type_slug}
```
* **Query Params:**
  * `locale` (opcional): Filtra per idioma (`ca`, `es`, `en`). Si no es passa, retorna tots els idiomes.

#### Detall d'una entrada
```http
GET /api/public/{project_slug}/{content_type_slug}/{id}
```

---

### API Autenticada (`/api/*`)

Requereixen incloure la capçalera `Authorization: Bearer <JWT>`.

#### Obtenir JWT (Login)
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "usuari@vorastudio.cat",
  "password": "el-teu-password"
}
```
**Resposta (200 OK):**
```json
{
  "token": "eyJhbGciOiJSUzI1Ni..."
}
```

#### Perfil de l'usuari
```http
GET /api/auth/me
```
Retorna informació de l'usuari i els seus dominis autoritzats.

#### Llistar seccions (Content Types) del projecte
```http
GET /api/sections
```

#### Llistar entrades d'una secció
```http
GET /api/{content_type_slug}
```

#### Registrar visita (Analytics)
```http
POST /api/visit
Content-Type: application/json

{
  "entry_id": 42,
  "path": "/projectes/el-meu-projecte"
}
```

---

## Formats dels Camps

Les dades es retornen en format JSON estructurat compatible amb l'estàndard de Strapi v5:

| Tipus de camp | Representació JSON |
|--------------|-------------------|
| `text` | string |
| `textarea` | string (amb salts de línia `\n`) |
| `richtext` | string (HTML apte per a renderitzat) |
| `image` / `gallery` | Llistat de fitxers `[{ id, filename, url, mimeType, fileSize, altText }]` |
| `date` / `datetime` | Format ISO 8601 (`2026-07-14T12:00:00+02:00`) |
| `boolean` | `true` / `false` |
| `number` | float / integer |

> [!IMPORTANT]
> Les URLs de les imatges i fitxers adjunts retornades són **relatives** (ex: `/uploads/12/imatge.png`).
> Al frontend s'ha d'afegir com a prefix la URL base del CMS: `const urlCompleta = CMS_URL + file.url;`.

---

## Codis d'Error

| Codi | Missatge | Causa |
|------|----------|-------|
| **400** | `Bad Request` | Peticions mal estructurades o paràmetres absents. |
| **401** | `Authentication required` | No s'ha enviat cap token de seguretat o ha caducat. |
| **403** | `Domain not allowed: ...` | El domini des d'on fas la crida no està permès al teu perfil del CMS. |
| **404** | `Not Found` | El projecte, tipus de contingut o entrada especificat no existeix. |

---

## Exemples d'Integració per Framework

### Fetch Natiu (Vanilla JS)
Per carregar projectes públics en un carousel des de la pròpia web corporativa de Vora Studio de forma pública i sense tokens:

```js
const CMS_URL = 'https://voracms.voradata.cat';

async function carregarProjectes() {
  try {
    const res = await fetch(`${CMS_URL}/api/public/web/vorastudio-projects?locale=ca`);
    if (!res.ok) throw new Error('Error al carregar el CMS');
    
    const { data } = await res.json();
    // 'data' és un array amb els projectes de Vora Studio
    console.log(data);
  } catch (error) {
    console.error(error);
  }
}
```

### Astro (Pàgines Estàtiques)
Astro realitza la petició durant la fase de build:

```astro
---
// src/pages/projectes.astro
const CMS_URL = 'https://voracms.voradata.cat';

const res = await fetch(`${CMS_URL}/api/public/web/vorastudio-projects?locale=ca`);
const projects = res.ok ? (await res.json()).data : [];
---

<h1>Els nostres projectes</h1>
<div class="grid">
  {projects.map((project) => (
    <article>
      {project.logo?.[0] && <img src={CMS_URL + project.logo[0].url} alt={project.titol} />}
      <h2>{project.titol}</h2>
      <p>{project.descripcio}</p>
    </article>
  ))}
</div>
```

### Next.js (App Router amb Master Token)
Exemple per a una aplicació Next.js amb renderitzat en el servidor (SSR) que s'autentica utilitzant el Master Token basat en la IP/Domini del servidor node:

```ts
// lib/cms.ts
const CMS_URL = 'https://voracms.voradata.cat';

async function getJwtToken() {
  const res = await fetch(`${CMS_URL}/api/public/token`, {
    headers: {
      // El host ha de coincidir amb un dels dominis autoritzats de l'usuari
      'Host': 'el-teu-domini-autoritzat.cat'
    },
    next: { revalidate: 3000 } // Fem cache del token (dura 1 hora)
  });
  if (!res.ok) throw new Error('No s\'ha pogut obtenir el Master Token');
  const { token } = await res.json();
  return token;
}

export async function getCmsData(endpoint: string) {
  const token = await getJwtToken();
  const res = await fetch(`${CMS_URL}${endpoint}`, {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  return res.json();
}
```
