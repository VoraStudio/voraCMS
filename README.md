# VoraCMS — API per al Frontend

VoraCMS és un **headless CMS multi-client**. No té frontend propi — exposa una API REST perquè qualsevol frontend (Astro, Next.js, Nuxt, HTML vanilla, React, etc.) consumeixi el contingut.

Cada **client** (tenant) té els seus propis tipus de contingut, entrades, usuaris i arxius, completament aïllats.

---

## Índex

- [Autenticació](#autenticació)
- [Endpoints](#endpoints)
  - [`GET /api/auth/me` — Perfil de l'usuari](#get-apiauthme)
  - [`GET /api/sections` — Seccions (tipus de contingut)](#get-apisections)
  - [`GET /api/{slug}` — Llistat d'entrades](#get-apislug)
  - [`GET /api/{slug}/{id}` — Entrada individual](#get-apislugid)
  - [`POST /api/visit` — Registrar visita](#post-apivisit)
- [Formats dels camps](#formats-dels-camps)
- [Codis d'error](#codis-derror)
- [Exemples per framework](#exemples-per-framework)
  - [Fetch natiu (vanilla JS)](#vanilla-js-fetch)
  - [Astro](#astro)
  - [Next.js](#nextjs)
- [Flujo complet](#flujo-complet-per-al-frontend)
- [Referència ràpida](#referència-ràpida)

---

## Autenticació

L'API utilitza **Bearer token** amb l'`apiToken` de l'usuari.

### Obtenir el token

L'`apiToken` es genera automàticament al crear l'usuari. El pots trobar a:

1. **Admin → Usuaris** → editar usuari → camp **API Token**
2. O via endpoint `GET /api/auth/me` un cop autenticat (catch-22: necessites el token per obtenir-lo)

> Durant el desenvolupament, copia l'`apiToken` des de l'admin.

### Usar el token

Inclou-lo a cada request com a `Bearer` al header `Authorization`:

```
Authorization: Bearer <apiToken>
```

### Scoping automàtic

No cal passar `?client={slug}` ni cap paràmetre de tenant. L'`ApiTokenAuthenticator` detecta l'usuari a partir del token, i automàticament:

- Filtra les entrades **només de l'usuari autenticat**
- Filtra les seccions **només de l'usuari autenticat**
- Aïlla completament les dades entre clients

---

## Endpoints

### `GET /api/auth/me`

Retorna les dades de l'usuari autenticat, inclòs el seu `apiToken`.

```
GET /api/auth/me
Authorization: Bearer <apiToken>
```

**Resposta:**

```json
{
  "data": {
    "slug": "victoria-taylor",
    "apiToken": "a1b2c3d4e5f6...",
    "company": "Victoria Taylor Studio",
    "email": "victoria@taylor.com",
    "name": "Victoria Taylor"
  }
}
```

> El camp `slug` és l'identificador únic del client. El pots usar com a ruta al frontend (ex: `victoria-taylor.com`).

---

### `GET /api/sections`

Retorna les seccions (tipus de contingut) de l'usuari autenticat.

```
GET /api/sections?active=true
Authorization: Bearer <apiToken>
```

| Paràmetre | Tipus | Per defecte | Descripció |
|-----------|-------|-------------|------------|
| `active`  | bool  | `true`      | Filtra només seccions actives |

**Resposta:**

```json
{
  "data": [
    {
      "id": 1,
      "name": "Notícies",
      "slug": "noticies",
      "description": "Secció de notícies del restaurant",
      "isActive": true
    },
    {
      "id": 2,
      "name": "Events",
      "slug": "events",
      "description": "Esdeveniments i promocions",
      "isActive": true
    }
  ]
}
```

**Ús al frontend:** Aquest endpoint et permet **descobrir** quines seccions té el client i construir la navegació dinàmicament.

---

### `GET /api/{slug}`

Retorna totes les entrades **publicades** d'una secció.

```
GET /api/noticies?locale=ca
Authorization: Bearer <apiToken>
```

| Paràmetre | Tipus | Obligatori | Descripció |
|-----------|-------|------------|------------|
| `slug`    | string | ✅ | Slug del content type (ex: `noticies`, `events`) |
| `locale`  | string | ❌ | Filtrar per idioma (`ca`, `es`, `en`). Sense filtre torna totes. |

**Resposta:**

```json
{
  "data": [
    {
      "id": 1,
      "status": "published",
      "locale": "ca",
      "createdAt": "2026-06-10T12:00:00+00:00",
      "updatedAt": "2026-06-10T12:00:00+00:00",
      "publishedAt": "2026-06-10T12:00:00+00:00",
      "titol": "Nova botiga a Girona",
      "descripcio": "Hem obert una nova ubicació al centre",
      "imatge": [
        {
          "id": 3,
          "name": "botiga-nova.jpg",
          "url": "/uploads/botiga-nova.jpg",
          "formats": {
            "small": { "url": "/uploads/botiga-nova.jpg" },
            "thumbnail": { "url": "/uploads/botiga-nova.jpg" }
          }
        }
      ],
      "contingut": "<p>Text llarg amb format HTML</p>",
      "data_esdeveniment": "2026-07-15T18:00:00"
    }
  ]
}
```

> Els noms dels camps dinàmics (`titol`, `descripcio`, `imatge`, etc.) són els **slugs dels FieldDefinition** del content type. No són fixes.

---

### `GET /api/{slug}/{id}`

Retorna una entrada concreta pel seu ID.

```
GET /api/noticies/3
Authorization: Bearer <apiToken>
```

**Resposta:**

```json
{
  "data": {
    "id": 3,
    "status": "published",
    "locale": "ca",
    "createdAt": "2026-06-10T12:00:00+00:00",
    "updatedAt": "2026-06-10T12:00:00+00:00",
    "publishedAt": "2026-06-10T12:00:00+00:00",
    "titol": "Nova botiga a Girona",
    "imatge": [
      {
        "id": 3,
        "name": "botiga-nova.jpg",
        "url": "/uploads/botiga-nova.jpg",
        "formats": {
          "small": { "url": "/uploads/botiga-nova.jpg" },
          "thumbnail": { "url": "/uploads/botiga-nova.jpg" }
        }
      }
    ],
    "contingut": "<p>Text llarg...</p>"
  }
}
```

---

### `POST /api/visit`

Registra una visita a una entrada (per analytics).

```
POST /api/visit
Content-Type: application/json

{
  "entry_id": 3,
  "path": "/noticies/nova-botiga"
}
```

**Resposta:**

```json
{
  "ok": true
}
```

---

## Formats dels camps

| Tipus de camp | Representació JSON |
|--------------|-------------------|
| `text` | string |
| `textarea` | string (amb salts de línia) |
| `richtext` | string (HTML) |
| `image` | `[{ id, name, url, formats: { small, thumbnail } }]` |
| `gallery` | `[{ id, name, url, formats }]` |
| `date` | string ISO (`2026-06-30T14:30:00`) |
| `datetime` | string ISO |
| `boolean` | `true` / `false` |
| `number` | float / int |
| `url` | string |
| `email` | string |
| `color` | string hex (`#FF5733`) |
| `youtube` | `{ id, url, embed }` |

### Imatges

Les rutes que retorna l'API són relatives:

```json
"url": "/uploads/botiga.jpg"
```

El frontend ha d'anteposar la URL base del CMS:

```js
const imageUrl = `${API_BASE}${entry.imatge[0].url}`;
```

---

## Codis d'error

| Codi | Significat | Acció del frontend |
|------|-----------|-------------------|
| `200` | Èxit | Processar `data` |
| `400` | Paràmetres incorrectes | Revisar la crida |
| `401` | Token invàlid o no enviat | Redirigir a login o demanar token |
| `404` | Slug o ID no trobat | Mostrar 404 |
| `500` | Error intern del servidor | Reintentar més tard |

Format d'error:

```json
{
  "error": "Descripció de l'error"
}
```

---

## Exemples per framework

### Vanilla JS (fetch)

```js
const API_BASE = 'https://cms.domini.com';
const TOKEN = 'el_api_token_de_l_usuari';

const headers = {
  Authorization: `Bearer ${TOKEN}`
};

/* Descobrir seccions */
async function getSections() {
  const res = await fetch(`${API_BASE}/api/sections`, { headers });
  if (!res.ok) return [];
  return (await res.json()).data;
}

/* Llistar entrades d'una secció */
async function getEntries(slug, locale) {
  const params = new URLSearchParams();
  if (locale) params.set('locale', locale);
  const qs = params.toString() ? `?${params}` : '';

  const res = await fetch(`${API_BASE}/api/${slug}${qs}`, { headers });
  if (!res.ok) return [];
  return (await res.json()).data;
}

/* Entrada individual */
async function getEntry(slug, id) {
  const res = await fetch(`${API_BASE}/api/${slug}/${id}`, { headers });
  if (!res.ok) return null;
  return (await res.json()).data;
}

/* Ús */
const seccions = await getSections();
const noticies = await getEntries('noticies', 'ca');
const entrada = await getEntry('noticies', 3);
```

### Astro

```astro
---
// src/pages/noticies.astro
const API = 'https://cms.domini.com';
const TOKEN = 'el_api_token';

const res = await fetch(`${API}/api/noticies?locale=ca`, {
  headers: { Authorization: `Bearer ${TOKEN}` }
});
const entries = res.ok ? (await res.json()).data : [];
---

<h1>Notícies</h1>
<ul>
  {entries.map(entry => (
    <li>
      {entry.imatge?.[0] && (
        <img src={API + entry.imatge[0].url} alt={entry.titol} width="800" height="400" loading="lazy" />
      )}
      <h2>{entry.titol}</h2>
      <time datetime={entry.publishedAt}>
        {new Date(entry.publishedAt).toLocaleDateString('ca')}
      </time>
      <p>{entry.descripcio}</p>
      <a href={`/noticia/${entry.id}`}>Llegir més</a>
    </li>
  ))}
</ul>
```

### Next.js

```ts
// lib/voracms.ts
const API_BASE = process.env.NEXT_PUBLIC_VORACMS_URL!;
const TOKEN = process.env.NEXT_PUBLIC_VORACMS_TOKEN!;

const headers = { Authorization: `Bearer ${TOKEN}` };

export async function getSections() {
  const res = await fetch(`${API_BASE}/api/sections`, { headers, next: { revalidate: 300 } });
  if (!res.ok) return [];
  return (await res.json()).data;
}

export async function getEntries(slug: string, locale?: string) {
  const params = new URLSearchParams();
  if (locale) params.set('locale', locale);
  const qs = params.toString() ? `?${params}` : '';

  const res = await fetch(`${API_BASE}/api/${slug}${qs}`, {
    headers,
    next: { revalidate: 60 }
  });
  if (!res.ok) return [];
  return (await res.json()).data;
}

export async function getEntry(slug: string, id: number) {
  const res = await fetch(`${API_BASE}/api/${slug}/${id}`, {
    headers,
    next: { revalidate: 60 }
  });
  if (!res.ok) return null;
  return (await res.json()).data;
}
```

```tsx
// app/noticies/page.tsx
import { getEntries } from '@/lib/voracms';

export default async function NoticiesPage() {
  const entries = await getEntries('noticies', 'ca');

  return (
    <div>
      {entries.map(entry => (
        <article key={entry.id}>
          {entry.imatge?.[0] && (
            <img src={entry.imatge[0].url} alt={entry.titol} width="800" height="400" />
          )}
          <h2>{entry.titol}</h2>
          <p>{entry.descripcio}</p>
        </article>
      ))}
    </div>
  );
}
```

---

## Flux complet per al frontend

```
Frontend                          VoraCMS API
   │                                  │
   │  GET /api/auth/me                │
   │  Authorization: Bearer <token>   │
   │─────────────────────────────────>│
   │                                  │── ApiTokenAuthenticator: resol usuari
   │  { data: { slug, name, ... } }  │
   │<─────────────────────────────────│
   │                                  │
   │  GET /api/sections               │
   │  Authorization: Bearer <token>   │
   │─────────────────────────────────>│
   │                                  │── Filtra per usuari autenticat
   │  { data: [{ slug, name }] }     │
   │<─────────────────────────────────│
   │                                  │
   │  Per cada secció:                │
   │  GET /api/{slug}?locale=ca       │
   │  Authorization: Bearer <token>   │
   │─────────────────────────────────>│
   │                                  │── EntryRepository: published + scoped
   │  { data: [{ id, titol, ... }] } │
   │<─────────────────────────────────│
   │                                  │
   │  GET /api/{slug}/{id}            │
   │  Authorization: Bearer <token>   │
   │─────────────────────────────────>│
   │  { data: { id, titol, ... } }   │
   │<─────────────────────────────────│
```

### Resum del flux

1. Obtenir `apiToken` des de l'admin d'usuaris
2. **Descobrir seccions** → `GET /api/sections` → obtens els slugs disponibles
3. **Llistar entrades** → `GET /api/noticies?locale=ca` → entries d'una secció
4. **Detall** → `GET /api/noticies/3` → entrada individual
5. **Tracking** → `POST /api/visit` → registrar visita (opcional)

---

## Referència ràpida

```
GET  /api/auth/me                    → Perfil usuari (token necessari per obtenir-lo)
GET  /api/sections?active=true       → Llistat de seccions
GET  /api/{slug}?locale=ca           → Llistat d'entrades publicades
GET  /api/{slug}/{id}                → Entrada individual
POST /api/visit                      → Registrar visita

Headers:
  Authorization: Bearer <apiToken>
```

> Per a qualsevol dubte, consulta l'admin del CMS o obre un issue al repositori.
