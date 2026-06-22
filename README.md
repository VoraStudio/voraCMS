# VoraCMS — API para Frontend

VoraCMS es un **headless CMS multi-cliente**. Esto significa que no tiene frontend propio — solo expone una API REST para que cualquier frontend (Astro, Next.js, Nuxt, HTML vanilla, React, etc.) consuma el contenido.

Cada **cliente** (tenant) tiene sus propios tipos de contenido, entradas, usuarios y archivos, completamente aislados.

---

## Índice

- [Autenticación](#autenticación)
- [Endpoints Públicos](#endpoints-públicos)
- [Endpoints Autenticados](#endpoints-autenticados)
- [Formato de Respuesta](#formato-de-respuesta)
- [Ejemplos por Framework](#ejemplos-por-framework)
  - [Fetch nativo](#vanilla-js-fetch)
  - [Astro](#astro)
  - [Next.js](#nextjs)
- [Flujo para el Frontend](#flujo-completo-para-el-frontend)
- [Gestión de Errores](#gestión-de-errores)
- [Consideraciones Técnicas](#consideraciones-técnicas)

---

## Autenticación

La API usa **JWT** (JSON Web Token). Los endpoints de lectura (`GET`) son públicos y no requieren token. Para cualquier otra operación necesitas autenticarte.

### Obtener un token

```
POST /api/auth/login
Content-Type: application/json
```

```json
{
  "email": "usuari@client.com",
  "password": "la-contrasenya"
}
```

**Respuesta exitosa (200):**

```json
{
  "token": "eyJhbGciOiJSUzI1NiJ9..."
}
```

**Respuesta fallida (401):**

```json
{
  "code": 401,
  "message": "Invalid credentials."
}
```

> Los mensajes de error están en catalán gracias a las traducciones integradas.

### Usar el token

Incluye el token en cada request como `Bearer` en la cabecera `Authorization`:

```
Authorization: Bearer eyJhbGciOiJSUzI1NiJ9...
```

El token tiene una validez configurable. Cuando expire, el frontend debe redirigir al login o pedir credenciales de nuevo.

---

## Endpoints Públicos

No requieren autenticación. Solo devuelven entradas con estado `published`.

### Parámetro obligatorio: `?client={slug}`

VoraCMS es multi-cliente. **Siempre** debes indicar qué cliente pides mediante el query parameter `?client`.

```
/api/noticia?client=victoria-taylor
/api/noticia/1?client=victoria-taylor
```

Si lo omites, recibirás un error `400`:

```json
{
  "error": "Client slug is required. Use ?client={slug}"
}
```

### Listar entradas

```
GET /api/{slug}?client={slug}
GET /api/{slug}?client={slug}&locale=ca
```

| Parámetro | Tipo | Obligatorio | Descripción |
|-----------|------|-------------|-------------|
| `slug` | string | ✅ | Slug del content type (ej: `noticia`, `event`, `producte`) |
| `client` | string | ✅ | Slug del cliente (ej: `victoria-taylor`, `vorastudio`) |
| `locale` | string | ❌ | Filtrar por idioma (`ca`, `es`, `en`). Si se omite, devuelve todos. |

**Ejemplo:**

```
GET /api/noticia?client=victoria-taylor&locale=ca
```

**Respuesta:**

```json
{
  "data": [
    {
      "id": 1,
      "status": "published",
      "locale": "ca",
      "author": "Admin",
      "publishedAt": "2026-06-10T12:00:00+00:00",
      "titul": "Nova botiga a Girona",
      "descripcio": "Hem obert una nova botiga al centre de Girona",
      "imatge": "/uploads/botiga.jpg",
      "contingut": "<p>...</p>",
      "data": "2026-06-10"
    }
  ]
}
```

> Los campos dinámicos (`titul`, `descripcio`, `imatge`, etc.) dependen de cómo se haya definido el Content Type. El nombre del campo en la respuesta es el slug del campo.

### Detalle de entrada

```
GET /api/{slug}/{id}?client={slug}
```

| Parámetro | Tipo | Obligatorio | Descripción |
|-----------|------|-------------|-------------|
| `slug` | string | ✅ | Slug del content type |
| `id` | integer | ✅ | ID de la entrada |
| `client` | string | ✅ | Slug del cliente |

**Ejemplo:**

```
GET /api/noticia/1?client=victoria-taylor
```

**Respuesta:**

```json
{
  "data": {
    "id": 1,
    "status": "published",
    "locale": "ca",
    "author": "Admin",
    "publishedAt": "2026-06-10T12:00:00+00:00",
    "titul": "Nova botiga a Girona",
    "descripcio": "Hem obert una nova botiga al centre de Girona",
    "imatge": "/uploads/botiga.jpg",
    "contingut": "<p>...</p>"
  }
}
```

---

## Endpoints Autenticados

Requieren el token JWT en la cabecera `Authorization`.

### Información del usuario actual

```
GET /api/auth/me
Authorization: Bearer <token>
```

**Respuesta:**

```json
{
  "data": {
    "id": 3,
    "email": "usuari@client.com",
    "name": "Usuari Example",
    "roles": ["ROLE_USUARIO"],
    "client": {
      "id": 1,
      "name": "Victoria Taylor",
      "slug": "victoria-taylor"
    }
  }
}
```

El objeto `client` dentro de la respuesta indica a qué tenant pertenece el usuario. El frontend puede usar `client.slug` para construir las URLs de la API sin necesidad de pedirlo al usuario.

---

## Formato de Respuesta

Todas las respuestas siguen un formato compatible con **Strapi v5**:

```json
{
  "data": { ... }        // objeto único
  "data": [ ... ]        // o array de objetos
}
```

### Tipos de campo y su representación

| Tipo de campo | Representación en JSON |
|--------------|------------------------|
| `text` | string |
| `textarea` | string (con saltos de línea) |
| `richtext` | string (HTML) |
| `image` | string (ruta: `/uploads/logo.png`) |
| `gallery` | array de strings (rutas) |
| `date` | string (`YYYY-MM-DD`) |
| `datetime` | string ISO 8601 |
| `boolean` | boolean |
| `number` | float/int |
| `url` | string |
| `email` | string |
| `location` | string |
| `color` | string hex |
| `youtube` | string (URL o ID) |

### Errores

```json
{
  "error": "Descripción del error"
}
```

Códigos HTTP:
- `200` — Éxito
- `400` — Error de cliente (parámetros incorrectos)
- `401` — No autenticado (token inválido o expirado)
- `404` — No encontrado (slug o ID incorrectos)
- `403` — Sin permisos (rol insuficiente)

---

## Ejemplos por Framework

### Vanilla JS (fetch)

```js
const API_BASE = 'https://el-teu-domini.com';
const CLIENT_SLUG = 'victoria-taylor';

// Leer contenido público
async function getEntries(contentType, locale) {
  const params = new URLSearchParams({ client: CLIENT_SLUG });
  if (locale) params.set('locale', locale);

  const res = await fetch(`${API_BASE}/api/${contentType}?${params}`);
  const json = await res.json();

  if (!res.ok) throw new Error(json.error);
  return json.data;
}

// Login JWT
async function login(email, password) {
  const res = await fetch(`${API_BASE}/api/auth/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password })
  });

  if (!res.ok) throw new Error('Credencials invàlides');

  const { token } = await res.json();
  localStorage.setItem('voracms_token', token);
  return token;
}

// Request autenticado
async function fetchMe() {
  const token = localStorage.getItem('voracms_token');
  if (!token) return null;

  const res = await fetch(`${API_BASE}/api/auth/me`, {
    headers: { 'Authorization': `Bearer ${token}` }
  });

  if (res.status === 401) {
    localStorage.removeItem('voracms_token');
    return null; // Token expirado, redirigir a login
  }

  return (await res.json()).data;
}
```

### Astro

```astro
---
// src/pages/noticies.astro
// En Astro, el fetch se hace en build time (o server-side con SSR)

const API = 'https://el-teu-domini.com';
const client = 'victoria-taylor';

const res = await fetch(`${API}/api/noticia?client=${client}&locale=ca`);
const entries = res.ok ? (await res.json()).data : [];
---

<h1>Notícies</h1>

<ul>
  {entries.map(entry => (
    <li>
      {entry.imatge && (
        <img src={API + entry.imatge} alt={entry.titul} width="800" height="400" loading="lazy" />
      )}
      <h2>{entry.titul}</h2>
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

```tsx
// app/api/voracms.ts — Cliente API reutilizable
const API_BASE = process.env.NEXT_PUBLIC_VORACMS_URL!;
const CLIENT_SLUG = process.env.NEXT_PUBLIC_VORACMS_CLIENT!;

export async function getEntries(slug: string, locale?: string) {
  const params = new URLSearchParams({ client: CLIENT_SLUG });
  if (locale) params.set('locale', locale);

  const res = await fetch(`${API_BASE}/api/${slug}?${params}`, {
    next: { revalidate: 60 } // ISR cada 60s
  });

  if (!res.ok) return [];
  return (await res.json()).data;
}

export async function getEntry(slug: string, id: number) {
  const res = await fetch(
    `${API_BASE}/api/${slug}/${id}?client=${CLIENT_SLUG}`,
    { next: { revalidate: 60 } }
  );

  if (!res.ok) return null;
  return (await res.json()).data;
}
```

```tsx
// app/noticies/page.tsx
import { getEntries } from '@/api/voracms';

export default async function NoticiesPage() {
  const entries = await getEntries('noticia', 'ca');

  return (
    <div>
      {entries.map(entry => (
        <article key={entry.id}>
          {entry.imatge && (
            <img src={entry.imatge} alt={entry.titul} width="800" height="400" />
          )}
          <h2>{entry.titul}</h2>
          <p>{entry.descripcio}</p>
        </article>
      ))}
    </div>
  );
}
```

---

## Flujo Completo para el Frontend

```
Frontend                              VoraCMS API
   │                                      │
   │  GET /api/noticia?client=xxx         │
   │─────────────────────────────────────>│
   │                                      │── ClientScope: filtra per client
   │                                      │── EntryRepository: published + scoped
   │  { data: [...] }                     │
   │<─────────────────────────────────────│
   │                                      │
   │  POST /api/auth/login                │
   │  { email, password }                 │
   │─────────────────────────────────────>│
   │                                      │── lexik/jwt: valida credenciales
   │  { token: "eyJ..." }                 │
   │<─────────────────────────────────────│
   │                                      │
   │  GET /api/auth/me                    │
   │  Authorization: Bearer eyJ...        │
   │─────────────────────────────────────>│
   │                                      │── JWT decode: extrae user + client
   │  { data: { id, email, roles,        │
   │      client: { slug: "xxx" } } }     │
   │<─────────────────────────────────────│
   │                                      │
   │  A partir de aquí, el frontend sabe  │
   │  qué cliente es y puede construir    │
   │  las URLs sin pedir ?client a mano   │
```

### Resumen del flujo para el frontend

1. **Página pública** → llamar a `GET /api/{slug}?client={slug}`. No necesita token.
2. **Login de usuario** → `POST /api/auth/login` → guardar token en localStorage.
3. **Obtener perfil** → `GET /api/auth/me` con el token → extraer `client.slug`.
4. **Construir URLs** → usar `client.slug` de `/me` como parámetro `?client=` en adelante.

---

## Gestión de Errores

El frontend debe contemplar estos casos:

| Situación | Código | Acción del frontend |
|-----------|--------|---------------------|
| Token expirado | `401` | Borrar token, redirigir a login |
| Cliente no encontrado | `404` | Mostrar error: slug incorrecto |
| Content type no encontrado | `404` | Mostrar página 404 |
| Sin `?client=` | `400` | Error de integración (revisar código) |
| Error de red | — | Mostrar estado offline, reintentar |

### Ejemplo de helper con errores

```js
async function apiFetch(path, options = {}) {
  const token = localStorage.getItem('voracms_token');

  const res = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      ...(token && { 'Authorization': `Bearer ${token}` }),
      ...options.headers
    }
  });

  if (res.status === 401) {
    localStorage.removeItem('voracms_token');
    window.location.href = '/login';
    return null;
  }

  if (!res.ok) {
    const err = await res.json().catch(() => ({ error: 'Error desconegut' }));
    throw new Error(err.error || `HTTP ${res.status}`);
  }

  return res.json();
}

// Uso:
// const data = await apiFetch('/api/noticia?client=victoria-taylor');
```

---

## Consideraciones Técnicas

### CORS

El API tiene CORS abierto (`Allow-Origin: *`) para los endpoints `/api/*`, así que puedes consumirla desde cualquier dominio sin configuración adicional.

### Imágenes

Las imágenes se sirven desde el propio CMS. La ruta que devuelve la API es relativa:

```json
{ "imatge": "/uploads/botiga.jpg" }
```

El frontend debe anteponer la URL base del CMS:

```js
const imageUrl = `${API_BASE}${entry.imatge}`;
```

### Caché

Los endpoints públicos pueden cachearse sin problema. En Astro (SSG) se cachean en build time. En Next.js puedes usar ISR con `revalidate`. En fetch nativo, añade `Cache-Control` si es necesario.

### Multi-idioma

Usa el parámetro `?locale=ca|es|en` para filtrar entradas por idioma. Las entradas pueden tener el mismo slug en distintos idiomas.

### Roles de usuario

| Rol | Acceso |
|-----|--------|
| `ROLE_USUARIO` | Leer entradas propias del cliente |
| `ROLE_MOD` | Gestionar contenidos de su cliente |
| `ROLE_ADMIN` | Acceso total (multi-cliente) |

El frontend puede leer el rol desde `GET /api/auth/me` para condicionar la UI (mostrar/ocultar botones de edición, etc.).

---

## URLs de Referencia Rápida

```
# Públicas
GET  /api/{slug}?client={slug}
GET  /api/{slug}/{id}?client={slug}

# Autenticación
POST /api/auth/login        → { "email": "...", "password": "..." }
GET  /api/auth/me           → Authorization: Bearer <token>
```

> Para cualquier duda, revisa la documentación técnica en `docs/architecture.md` o `docs/database.md`.
