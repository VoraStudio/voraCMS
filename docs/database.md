# VoraCMS — Base de Dades

## 1. Estratègia Multi-Client

L'arquitectura utilitza una **Base de Dades Compartida** amb columnes discriminadores. Tots els inquilins (clients) comparteixen el mateix esquema i les mateixes taules.
* **Usuaris/Clients (`users`)**: Cada usuari actua com a propietari de dades (client o inquilí).
* **Projectes (`projects`)**: Cada usuari pot crear múltiples projectes (per exemple, per a diferents webs com vorastudio.cat, palmitohouse.com, etc.).
* **Aïllament**: Totes les consultes de dades filtren automàticament per `user_id` o `project_id` a través de filtres del Doctrine ORM (`user_id_filter`), excepte per a l'administrador global (`ROLE_ADMIN`).

---

## 2. Diagrama de Taules i Relacions

```
users (1) ─── (N) projects
  │
  ├─── (N) content_types (1) ─── (N) field_definitions
  │
  ├─── (N) entries (1) ─── (N) field_values
  │
  └─── (N) media
```

---

## 3. Detall de les Taules

### 3.1 Taula `users`
Emmagatzema els administradors i clients del CMS.

| Columna | Tipus | Restriccions | Descripció |
|---------|-------|--------------|------------|
| `id` | INTEGER | PK, AUTO_INCREMENT | Identificador únic. |
| `email` | VARCHAR(180) | UNIQUE, NOT NULL | Correu per a iniciar sessió. |
| `name` | VARCHAR(255) | NOT NULL | Nom complet o de l'empresa. |
| `slug` | VARCHAR(100) | UNIQUE, NOT NULL | Identificador textual per a directoris. |
| `roles` | JSON | NOT NULL | Llista de rols (`ROLE_ADMIN`, `ROLE_USUARIO`). |
| `password` | VARCHAR(255) | NOT NULL | Hash de la contrasenya (Bcrypt). |
| `active` | BOOLEAN | DEFAULT true | Estat del compte. |
| `locale` | VARCHAR(5) | DEFAULT 'ca' | Idioma del panell de control. |
| `allowed_domains` | JSON | NULL | Dominis admesos per a fer peticions CORS. |
| `allowed_ips` | JSON | NULL | IPs del servidor SSR admeses per a Master Token. |
| `created_at` | DATETIME | NOT NULL | Data de registre. |

### 3.2 Taula `projects`
Un usuari client pot configurar diferents fronts o webs de consum.

| Columna | Tipus | Restriccions | Descripció |
|---------|-------|--------------|------------|
| `id` | INTEGER | PK, AUTO_INCREMENT | Identificador únic. |
| `user_id` | INTEGER | FK (users.id) | Propietari del projecte. |
| `name` | VARCHAR(255) | NOT NULL | Nom del projecte. |
| `slug` | VARCHAR(100) | NOT NULL | Slug utilitzat a les rutes de l'API pública. |
| `color` | VARCHAR(7) | DEFAULT '#4945FF' | Color identificatiu a la interfície. |
| `active` | BOOLEAN | DEFAULT true | Estat del projecte. |

> **Índex Únic:** `UNIQUE(slug, user_id)` — el mateix slug de projecte no es pot repetir per a un mateix usuari.

### 3.3 Taula `content_types`
Esquemes o plantilles de contingut definides dins d'un projecte.

| Columna | Tipus | Restriccions | Descripció |
|---------|-------|--------------|------------|
| `id` | INTEGER | PK, AUTO_INCREMENT | Identificador únic. |
| `project_id` | INTEGER | FK (projects.id) | Projecte al qual pertany. |
| `name` | VARCHAR(255) | NOT NULL | Nom descriptiu (ex: "Notícies"). |
| `slug` | VARCHAR(100) | NOT NULL | Slug per a l'endpoint (ex: "noticies"). |
| `description` | TEXT | NULL | Descripció opcional de l'estructura. |
| `active` | BOOLEAN | DEFAULT true | Si és editable. |
| `base` | BOOLEAN | DEFAULT false | Indica si és una plantilla global auto-clonable. |

### 3.4 Taula `field_definitions`
Camps que composen un determinat tipus de contingut.

| Columna | Tipus | Restriccions | Descripció |
|---------|-------|--------------|------------|
| `id` | INTEGER | PK, AUTO_INCREMENT | Identificador únic. |
| `content_type_id` | INTEGER | FK (content_types.id) | Tipus de contingut pare. |
| `name` | VARCHAR(255) | NOT NULL | Label visible. |
| `slug` | VARCHAR(100) | NOT NULL | Clau JSON resultant. |
| `field_type` | VARCHAR(50) | NOT NULL | Tipus (`text`, `richtext`, `image`, `color`, etc.). |
| `required` | BOOLEAN | DEFAULT false | Valida si és obligatori. |
| `sort_order` | INTEGER | DEFAULT 0 | Ordre de posició al formulari. |

### 3.5 Taula `entries`
Entrades reals introduïdes pels redactors.

| Columna | Tipus | Restriccions | Descripció |
|---------|-------|--------------|------------|
| `id` | INTEGER | PK, AUTO_INCREMENT | Identificador únic. |
| `content_type_id` | INTEGER | FK (content_types.id) | Tipus de contingut associat. |
| `user_id` | INTEGER | FK (users.id) | Creador de l'entrada. |
| `status` | VARCHAR(20) | DEFAULT 'draft' | `draft`, `published`, `archived`. |
| `locale` | VARCHAR(5) | DEFAULT 'ca' | Idioma específic d'aquesta entrada. |
| `created_at` | DATETIME | NOT NULL | Data de creació. |
| `updated_at` | DATETIME | NULL | Data d'actualització. |

### 3.6 Taula `field_values`
Valors individuals per a cada camp d'una entrada concreta.

| Columna | Tipus | Restriccions | Descripció |
|---------|-------|--------------|------------|
| `id` | INTEGER | PK, AUTO_INCREMENT | Identificador únic. |
| `entry_id` | INTEGER | FK (entries.id) | Entrada a la qual pertany el valor. |
| `field_definition_id` | INTEGER | FK (field_definitions.id) | Camp del qual recull la definició. |
| `value` | TEXT | NULL | Contingut de dades desat com a cadena plana o JSON. |

### 3.7 Taula `media`
Arxius adjunts o imatges penjats pels usuaris.

| Columna | Tipus | Restriccions | Descripció |
|---------|-------|--------------|------------|
| `id` | INTEGER | PK, AUTO_INCREMENT | Identificador de l'arxiu. |
| `project_id` | INTEGER | FK (projects.id) | Projecte opcional on s'associa. |
| `user_id` | INTEGER | FK (users.id) | Usuari que l'ha pujat. |
| `filename` | VARCHAR(255) | NOT NULL | Nom final desat a disc. |
| `original_filename` | VARCHAR(255) | NOT NULL | Nom del fitxer en el moment de la pujada. |
| `extension` | VARCHAR(10) | NOT NULL | Extensió del fitxer (`png`, `webp`, etc.). |
| `mime_type` | VARCHAR(50) | NOT NULL | Tipus MIME. |
| `path` | VARCHAR(255) | NOT NULL | Ruta de l'arxiu física. |
| `file_size` | INTEGER | NOT NULL | Mida en bytes del fitxer. |
| `alt_text` | TEXT | NULL | Text accessible per a la imatge. |
