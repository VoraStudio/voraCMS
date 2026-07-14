# VoraCMS — Document d'Arquitectura

## 1. Objectiu de l'Aplicació

VoraCMS és un **headless CMS multi-projecte i multi-client** desenvolupat amb Symfony 7.2.
El seu objectiu principal és servir contingut estructurat mitjançant una API REST per tal que una única instal·lació pugui oferir servei a múltiples clients independents. Cada client disposa dels seus propis usuaris, projectes, tipus de contingut (taules), entrades i arxius multimèdia, completament aïllats entre si.

El panell d'administració permet als administradors de Vora Studio gestionar globalment tots els usuaris i projectes, mentre que els usuaris clients només veuen i gestionen les seves pròpies dades.

---

## 2. Stack Tecnològic

| Capa | Tecnologia |
|------|-----------|
| **Llenguatge** | PHP 8.2+ |
| **Framework** | Symfony 7.2 (FrameworkBundle) |
| **ORM** | Doctrine ORM 3.6 |
| **Base de dades** | SQLite (desenvolupament) / MySQL (producció) |
| **Autenticació API** | JWT amb signatura RSA asimètrica (RS256) |
| **Autenticació Admin** | `form_login` + sessió de Symfony |
| **Motor de plantilles** | Twig 3.x |
| **Frontend Admin** | Bootstrap 5 + CSS personalitzat (disseny fosc glassmorphism) |

---

## 3. Model de Dades

### 3.1 Entitats Core

```
User (1) ──── (N) Project
  │
  ├── (N) ContentType ──── (N) FieldDefinition
  │
  ├── (N) Entry ──── (N) FieldValue
  │
  └── (N) Media
```

### 3.2 Entitat User
Representa el client/inquilí (tenant) del sistema.
* `id` (int, PK): Identificador únic.
* `email` (string, UNIQUE): Correu de login.
* `name` (string): Nom complet.
* `slug` (string, UNIQUE): Identificador per a URL.
* `roles` (json): Rols de seguretat (`ROLE_ADMIN` per a administradors generals de Vora Studio, `ROLE_USUARIO` per a usuaris clients).
* `password` (string): Hash de contrasenya (bcrypt).
* `allowedDomains` (json, nullable): Llista de dominis autoritzats a fer crides des de frontend.
* `allowedIps` (json, nullable): Llista d'IPs de servidors autoritzats a emetre Master Tokens.

### 3.3 Entitat Project
Un usuari pot tenir un o més projectes actius (ex. per a diferenciar web corporativa, e-commerce, aplicació de marca).
* `id` (int, PK): Identificador únic.
* `name` (string): Nom de projecte.
* `slug` (string): Slug per a l'API pública.
* `user` (ManyToOne, User): Propietari del projecte.

### 3.4 Entitat ContentType (Secció)
Defineix l'esquema o estructura d'un conjunt d'entrades (ex. Notícies, Esdeveniments).
* `id` (int, PK): Identificador únic.
* `name` (string): Nom visible.
* `slug` (string): Identificador a la ruta d'API.
* `project` (ManyToOne, Project): Projecte on pertany.

### 3.5 Entitat FieldDefinition
Defineix les columnes/camps dins d'un `ContentType`.
* `id` (int, PK)
* `name` (string)
* `slug` (string): Identificador en el JSON final.
* `fieldType` (string): `text`, `richtext`, `image`, `date`, `color`, `youtube`, etc.
* `required` (boolean)

### 3.6 Entitat Entry
Representa una entrada concreta de dades.
* `id` (int, PK)
* `status` (string): `draft` / `published` / `archived`.
* `locale` (string): Idioma de l'entrada (`ca`, `es`, `en`).
* `contentType` (ManyToOne, ContentType)

### 3.7 Entitat FieldValue
Emmagatzema el valor real del camp de l'entrada.
* `id` (int, PK)
* `value` (text, nullable): Valor en format text/serialitzat.
* `entry` (ManyToOne, Entry)
* `fieldDefinition` (ManyToOne, FieldDefinition)

---

## 4. Multi-Tenancy (Multi-Client)

### 4.1 Estrategia de BD Compartida
Tots els clients comparteixen la mateixa base de dades i les mateixes taules. L'aïllament està garantit per codi mitjançant dues vies:

1. **Doctrine Filter (`user_id_filter`)**: S'aplica a totes les entitats de contingut. Afegeix automàticament `WHERE user_id = :user_id` a cada consulta que fa l'aplicació.
2. **UserFilterSubscriber**: Escolta l'esdeveniment `KernelEvents::CONTROLLER`. Si l'usuari té el rol `ROLE_USUARIO`, inyecta el seu `id` al filtre de Doctrine. Si té el rol `ROLE_ADMIN`, desactiva el filtre perquè pugui supervisar totes les dades globalment.

### 4.2 Aïllament de Fitxers Multimèdia
El servei `MediaService` organitza els fitxers físicament al disc separant-los per l'ID de l'usuari:
`/public/uploads/{userId}/{safe_filename}`.

---

## 5. Flux de Seguretat i Emissió de Tokens

El CMS no fa servir tokens fixos per a l'API. Tota connexió s'autentica mitjançant un JWT vàlid per una hora.

1. **Autenticació Manual (JWT)**: Login típic de client a `/api/auth/login`.
2. **Master Token Service**: Permet a servidors SSR (Astro, Next.js) demanar un token mitjançant `GET /api/public/token`. El servei `TokenMasterService` verifica la capçalera `Host` / `Origin` i l'adreça IP del servidor demanant contra la llista d'admesos (`allowedDomains` / `allowedIps`). Si es validen, es genera un JWT signat al moment amb clau RSA per a aquest inquilí.
