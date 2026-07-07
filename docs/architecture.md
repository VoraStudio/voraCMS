# VoraCMS — Documento de Arquitectura

## 1. Objetivo de la Aplicacion

VoraCMS es un **headless CMS multi-cliente** construido con Symfony 7.2.
Su proposito es servir contenido estructurado a traves de una API REST
para que una sola instalacion pueda dar servicio a multiples clientes
independientes, cada uno con sus propios usuarios, tipos de contenido,
entradas y archivos multimedia, completamente aislados entre si.

El panel de administracion permite a los super-admins de Vora Studio
gestionar todos los clientes desde un unico lugar, mientras que cada
cliente solo ve y gestiona su propio contenido.

---

## 2. Stack Tecnologico

| Capa | Tecnologia |
|------|-----------|
| Lenguaje | PHP 8.2+ |
| Framework | Symfony 7.2 (FrameworkBundle) |
| ORM | Doctrine ORM 3.6 |
| Base de datos | SQLite (desarrollo) / MySQL (produccion) |
| Autenticacion API | JWT (lexik/jwt-authentication-bundle) |
| Autenticacion Admin | form_login + sesion Symfony |
| Motor de templates | Twig 3.x |
| Frontend admin | Bootstrap 5 + CSS personalizado (tema oscuro glassmorphism) |

---

## 3. Modelo de Datos

### 3.1 Entidades Core

```
Client (1) ──── (N) User
  │
  ├── (N) ContentType ──── (N) FieldDefinition
  │
  ├── (N) Entry ──── (N) FieldValue
  │
  └── (N) Media
```

### 3.2 Entidad Client

Eje central del multi-tenencia. Cada fila representa un cliente independiente.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | integer (PK) | Identificador unico |
| name | string(255) | Nombre del cliente |
| slug | string(100) | Identificador unico para URL |
| logo | string(255) nullable | URL del logo |
| active | boolean | Si el cliente esta activo |
| createdAt | datetime | Fecha de creacion |

**Restricciones:** UNIQUE(slug), UNIQUE(name)

### 3.3 Entidad User

Usuario del sistema, siempre pertenece a un cliente.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | integer (PK) | Identificador unico |
| email | string(180) | Email de login (UNIQUE por cliente) |
| name | string(255) | Nombre completo |
| roles | json | Roles de seguridad |
| password | string(255) | Hash bcrypt |
| active | boolean | Si el usuario esta activo |
| locale | string(5) | Idioma preferido |
| client | ManyToOne(Client) | Cliente al que pertenece |

**Restricciones:** UNIQUE(email, client_id) — un email solo puede existir
una vez por cliente.

### 3.4 Entidad ContentType

Define un tipo de contenido (plantilla de entrada). Pertenece a un cliente.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | integer (PK) | Identificador unico |
| name | string(255) | Nombre visible |
| slug | string(100) | Identificador para URL de la API |
| description | text nullable | Descripcion |
| active | boolean | Si esta activo |
| base | boolean | Si es creado por defecto al provisionar |
| client | ManyToOne(Client) | Cliente propietario |

**Restricciones:** UNIQUE(client_id, slug) — cada cliente tiene sus
propios slugs de content type.

### 3.5 Entidad FieldDefinition

Define un campo dentro de un ContentType.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | integer (PK) | Identificador unico |
| name | string(255) | Nombre visible |
| slug | string(100) | Identificador para la API |
| fieldType | string(50) | Tipo de campo (text, richtext, image, date, url, etc.) |
| required | boolean | Si es obligatorio |
| translatable | boolean | Si se puede traducir |
| helpText | text nullable | Texto de ayuda |
| sortOrder | integer | Orden de visualizacion |
| contentType | ManyToOne(ContentType) | ContentType al que pertenece |

### 3.6 Entidad Entry

Una entrada de contenido. Pertenece a un cliente y a un ContentType.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | integer (PK) | Identificador unico |
| status | string(20) | draft / published / archived |
| locale | string(5) | Idioma de la entrada |
| createdAt | datetime | Fecha de creacion |
| updatedAt | datetime nullable | Ultima modificacion |
| publishedAt | date nullable | Fecha de publicacion |
| contentType | ManyToOne(ContentType) | Tipo de contenido |
| author | ManyToOne(User) | Autor de la entrada |
| client | ManyToOne(Client) | Cliente propietario |

**Indices compuestos:** (content_type_id, status), (content_type_id, locale)

### 3.7 Entidad FieldValue

Valor concreto de un campo para una entrada.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | integer (PK) | Identificador unico |
| value | text nullable | Valor del campo |
| entry | ManyToOne(Entry) | Entrada a la que pertenece |
| fieldDefinition | ManyToOne(FieldDefinition) | Definicion del campo |

### 3.8 Entidad Media

Archivo multimedia subido. Pertenece a un cliente.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| id | integer (PK) | Identificador unico |
| filename | string(255) | Nombre interno del archivo |
| originalFilename | string(255) | Nombre original subido |
| extension | string(10) | Extension del archivo |
| mimeType | string(50) | Tipo MIME |
| path | string(255) | Ruta relativa del archivo |
| thumbnailPath | string(255) nullable | Ruta del thumbnail |
| fileSize | integer | Tamano en bytes |
| altText | text nullable | Texto alternativo |
| uploadedBy | ManyToOne(User) | Usuario que lo subio |
| client | ManyToOne(Client) | Cliente propietario |

---

## 4. Modelo de Multi-Tenencia

### 4.1 Estrategia: Base de datos compartida con discriminador (Shared DB)

Todos los clientes comparten la misma base de datos y las mismas tablas.
Cada fila tiene una columna `client_id` que la asocia a su cliente.

### 4.2 Filtro Doctrine (ClientIdFilter)

Filtro SQL que se activa automaticamente en cada request y anade
`WHERE client_id = :current_client_id` a todas las queries de entidades
scoped. Si no hay cliente seleccionado (ej: super-admin en vista global),
el filtro se desactiva y no aplica restriccion.

### 4.3 ClientScope

Servicio que mantiene el estado del cliente actual durante el request.
Se usa en tres contextos:

1. **JWT** — El subscriber JwtClientIdSubscriber extrae `client_id` del
   token y llama a `ClientScope::setClient()`.
2. **Sesion admin** — El cliente se asigna en el login segun el usuario.
3. **API publica** — El cliente se lee del query parameter `?client={slug}`
   (endpoints GET publicos sin JWT).

### 4.4 Jerarquia de Roles

```
ROLE_SUPER_ADMIN ───> ROLE_ADMIN ───> ROLE_USER
```

- **ROLE_SUPER_ADMIN**: Ve todos los clientes, puede gestionarlos.
- **ROLE_ADMIN**: Administrador de un cliente concreto.
- **ROLE_USER**: Usuario basico de un cliente.

### 4.5 Aislamiento Cross-Client

Garantizado por:
1. Columna `client_id` en todas las entidades scoped
2. Filtro Doctrine activado por ClientScope
3. Repositorios que inyectan `clientScope->getClientId()` en queries
   personalizadas
4. MediaService que aísla uploads en `/public/uploads/{clientId}/`
5. Claves UNIQUE compuestas que evitan conflictos entre clientes

---

## 5. API REST

### 5.1 Endpoints Publicos

| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| GET | `/api/{slug}?client={slug}` | Lista entradas publicadas de un content type |
| GET | `/api/{slug}/{id}?client={slug}` | Detalle de una entrada |

Los GET publicos requieren el parametro `?client={slug}` para identificar
el cliente. Sin el, devuelven 400.

### 5.2 Endpoints Autenticados

| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| POST | `/api/auth/login` | Login JWT (email + password) |
| GET | `/api/auth/me` | Datos del usuario autenticado + cliente |

### 5.3 Formato de Respuesta

Todas las respuestas siguen el formato:

```json
{
  "data": { ... }
}
```

Coincide con el formato de Strapi v5 para compatibilidad con frontends
existentes.

---

## 6. Autenticacion

### 6.1 Panel Admin

- Login via formulario (`/admin/login`)
- Sesion Symfony con almacenamiento en cookie
- Roles: ROLE_SUPER_ADMIN puede ver todos los clientes;
  ROLE_ADMIN solo ve su cliente

### 6.2 API

- JWT con algoritmo RS256
- Claims: `username` (email), `roles`, `client_id`, `client_slug`
- Token TTL: 1 hora

---

## 7. Componentes Clave del Sistema

### ClientScope (App\Service\ClientScope)

Servicio central que resuelve el cliente actual:

```php
$clientScope->getClient();      // Devuelve Client o null
$clientScope->getClientId();     // Devuelve int o null
$clientScope->setClient($c);     // Activa un cliente
$clientScope->isSuperAdmin();    // true si el usuario es super-admin
```

### ClientIdFilter (App\Filter\ClientIdFilter)

Filtro Doctrine que se anade automaticamente a todas las queries.
Se activa/desactiva globalmente desde ClientFilterSubscriber.

### ClientProvisioner (App\Service\ClientProvisioner)

Crea los ContentTypes base (Noticies, Events) con sus FieldDefinitions
al crear un nuevo cliente. Se invoca automaticamente desde el CLI
`voracms:client:create` y desde el formulario de creacion en el admin.

### JwtClientIdSubscriber (App\EventSubscriber\JwtClientIdSubscriber)

Escucha el evento de autenticacion JWT y anade `client_id` y
`client_slug` a los claims del token. Tambien valida que usuarios
legacy (sin client_id) funcionen correctamente.

### ClientFilterSubscriber (App\EventSubscriber\ClientFilterSubscriber)

Activa o desactiva el filtro Doctrine en cada request segun el contexto
(super-admin necesita poder ver todos los clientes).

### MediaService (App\Service\MediaService)

Gestiona la subida de archivos. Los uploads se aislan por cliente:
`/public/uploads/{clientId}/{filename}`.

---

## 8. CLI

| Comando | Descripcion |
|---------|-------------|
| `voracms:client:create <slug> [<name>]` | Crea un cliente nuevo y lo provisiona con ContentTypes base |
| `voracms:client:provision <slug>` | Provisiona ContentTypes base para un cliente existente |

---

## 9. Panel Admin

Rutas bajo `/admin/`. Diseno oscuro glassmorphism con sidebar que muestra
el contexto del cliente actual.

| Ruta | Descripcion |
|------|-------------|
| `/admin/` | Dashboard con estadisticas scoped al cliente |
| `/admin/client/` | Gestion de clientes (solo super-admin) |
| `/admin/content-type/` | Gestion de tipos de contenido |
| `/admin/entry/type/{slug}` | Entradas de un content type |
| `/admin/media/` | Gestor de archivos multimedia |

---

## 10. Seguridad

- JWT firmado con RSA-256 (par de llaves publica/privada)
- CSRF protection en formularios admin
- Honeypot + reCAPTCHA en formulario de contacto
- Contrasenas hasheadas con bcrypt (cost 13)
- Aislamiento de datos garantizado por client_id en todas las entidades
- Roles con jerarquia: super-admin puede gestionar clientes,
  admin de cliente solo ve su contenido
