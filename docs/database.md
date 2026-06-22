VoraCMS - Base de Dades

1. Estrategia

Base de dades compartida amb columna discriminadora. Tots els clients
comparteixen les mateixes taules. Cada fila porta un client_id que la
associa al seu client.

Taules:

- clients
- users
- content_types
- field_definitions
- entries
- field_values
- media

No hi ha una base de dades per client. Es una sola base amb una
columna client_id a cada taula que ho necessita.

2. Relacions entitat - relacio (detall)

2.1 client -> user

Tipus: OneToMany
Entitat origen: Client
Entitat desti: User
Cardinalitat: 1 client te N usuaris
FK: users.client_id -> clients.id
Codi Client: $client->getUsers() : Collection
Codi User: $user->getClient() : Client
Esborrat: RESTRICT (no cascada)

Un usuari sempre pertany a un sol client. Si s'elimina el client,
els usuaris NO s'eliminen automaticament (cal reassignar o
eliminar manualment).

2.2 client -> content_type

Tipus: OneToMany (cascade: persist, remove)
Entitat origen: Client
Entitat desti: ContentType
Cardinalitat: 1 client te N content types
FK: content_types.client_id -> clients.id
Codi Client: $client->getContentTypes() : Collection
Codi CT: $ct->getClient() : Client
Esborrat: CASCADE

Si s'elimina un client, s'eliminen tots els seus content types.

2.3 content_type -> field_definition

Tipus: OneToMany (cascade: persist, remove)
Entitat origen: ContentType
Entitat desti: FieldDefinition
Cardinalitat: 1 content type te N field definitions
FK: field_definitions.content_type_id -> content_types.id
Codi CT: $ct->getFields() : Collection
Codi FD: $fd->getContentType() : ContentType
Esborrat: CASCADE

No tenen client_id directe. El tenant scoping es fa a traves
de content_type -> client.

2.4 client -> entry

Tipus: OneToMany
Entitat origen: Client
Entitat desti: Entry
Cardinalitat: 1 client te N entries
FK: entries.client_id -> clients.id
Codi Client: $client->getEntries() : Collection
Codi Entry: $entry->getClient() : Client
Esborrat: CASCADE

2.5 content_type -> entry

Tipus: OneToMany
Entitat origen: ContentType
Entitat desti: Entry
Cardinalitat: 1 content type te N entries
FK: entries.content_type_id -> content_types.id
Codi CT: $ct->getEntries() : Collection
Codi Entry: $entry->getContentType() : ContentType
Esborrat: CASCADE

2.6 entry -> field_value

Tipus: OneToMany (cascade: persist, remove)
Entitat origen: Entry
Entitat desti: FieldValue
Cardinalitat: 1 entry te N field values
FK: field_values.entry_id -> entries.id
Codi Entry: $entry->getFieldValues() : Collection
Codi FV: $fv->getEntry() : Entry
Esborrat: CASCADE

No tenen client_id directe. El tenant scoping es fa a traves
de entry -> client.

2.7 field_definition -> field_value

Tipus: OneToMany
Entitat origen: FieldDefinition
Entitat desti: FieldValue
Cardinalitat: 1 field definition te N field values
FK: field_values.field_definition_id -> field_definitions.id
Codi FD: $fd->getFieldValues() : Collection
Codi FV: $fv->getFieldDefinition() : FieldDefinition
Esborrat: CASCADE

2.8 user -> entry

Tipus: OneToMany
Entitat origen: User
Entitat desti: Entry
Cardinalitat: 1 user es autor de N entries
FK: entries.author_id -> users.id
Codi User: $user->getEntries() : Collection
Codi Entry: $entry->getAuthor() : ?User
Esborrat: SET NULL (autor opcional)

2.9 user -> media

Tipus: OneToMany
Entitat origen: User
Entitat desti: Media
Cardinalitat: 1 user puja N media
FK: media.uploaded_by_id -> users.id
Codi User: $user->getMedia() : Collection
Codi Media: $media->getUploadedBy() : ?User
Esborrat: SET NULL

2.10 client -> media

Tipus: OneToMany
Entitat origen: Client
Entitat desti: Media
Cardinalitat: 1 client te N media
FK: media.client_id -> clients.id
Codi Client: $client->getMedia() : Collection
Codi Media: $media->getClient() : Client
Esborrat: CASCADE

3. La taula clients (eix central)

clients

Columna | Tipus | Descripcio
id | integer PK | Auto, clau primaria
name | varchar(255) | Nom del client
slug | varchar(100) | Identificador per URL, UNIQUE
logo | varchar(255) | URL del logo (nullable)
active | boolean | Client actiu (default 1)
created_at | datetime | Data de creacio

Restriccions:

- UNIQUE (slug)
- UNIQUE (name)

4. Taules dependents (scoped per client)

4.1 users

Columna | Tipus | Descripcio
id | integer PK | Auto
email | varchar(180) | Email de login
name | varchar(255) | Nom complet
roles | json | Array de rols
password | varchar(255) | Hash bcrypt
active | boolean | Usuari actiu (default 1)
locale | varchar(5) | Idioma (default 'ca')
created_at | datetime | Data de creacio
client_id | integer FK | clients.id

Restriccions:

- UNIQUE (email, client_id)

  4.2 content_types

Columna | Tipus | Descripcio
id | integer PK | Auto
name | varchar(255) | Nom, ex: "Noticies"
slug | varchar(100) | Per URL, ex: "noticia"
description | text | Descripcio (nullable)
active | boolean | Actiu (default 1)
base | boolean | Creat al provisionar (default 0)
created_at | datetime | Data de creacio
client_id | integer FK | clients.id

Restriccions:

- UNIQUE (client_id, slug)

  4.3 field_definitions

NO te client_id directe. Scoped via content_type -> client.

Columna | Tipus | Descripcio
id | integer PK | Auto
name | varchar(100) | Nom, ex: "Titol"
slug | varchar(100) | Per API, ex: "titul"
field_type | varchar(50) | text, richtext, image, date, url
required | boolean | Camp obligatori (default 0)
translatable | boolean | Traduible (default 1)
help_text | text | Text d'ajuda (nullable)
sort_order | integer | Ordre (default 0)
content_type_id | integer FK | content_types.id

4.4 entries

Columna | Tipus | Descripcio
id | integer PK | Auto
status | varchar(20) | draft, published, archived (default 'draft')
locale | varchar(5) | Idioma (default 'ca')
created_at | datetime | Data de creacio
updated_at | datetime | Ultima modificacio (nullable)
published_at | date | Data de publicacio (nullable)
content_type_id | integer FK | content_types.id
author_id | integer FK | users.id (nullable)
client_id | integer FK | clients.id

Indexs:

- INDEX (content_type_id, status)
- INDEX (content_type_id, locale)

  4.5 field_values

NO te client_id directe. Scoped via entry -> client.

Columna | Tipus | Descripcio
id | integer PK | Auto
value | text | Valor del camp (nullable)
entry_id | integer FK | entries.id
field_definition_id | integer FK | field_definitions.id

4.6 media

Columna | Tipus | Descripcio
id | integer PK | Auto
filename | varchar(255) | Nom intern
original_filename | varchar(255) | Nom original
extension | varchar(10) | Extensio
mime_type | varchar(50) | Tipus MIME
path | varchar(255) | Ruta relativa
thumbnail_path | varchar(255) | Ruta del thumbnail (nullable)
file_size | integer | Tamany en bytes
alt_text | text | Text alternatiu (nullable)
created_at | datetime | Data de pujada
uploaded_by_id | integer FK | users.id (nullable)
client_id | integer FK | clients.id

5. Claus uniques compostes

Son la clau perque el multi-tenencia funcioni sense conflictes.

Taula | Clau unica | Que evita
users | (email, client_id) | Dos usuaris amb el mateix email al mateix client
content_types | (client_id, slug) | Dos "noticia" al mateix client

El slug de clients es UNIQUE global (no compost) perque la ruta
?client={slug} el busca sense client_id.

6. Resum de claus foranes

10 claus foranes en total
5 apunten a clients.id (tenant scoping directe)
3 apunten a content_types o entries (tenant scoping indirecte)
2 apunten a users.id (autor)

Esborrat en cascada: 7 FK tenen CASCADE
Esborrat restrict: 1 FK (users.client_id)
Esborrat SET NULL: 2 FK (author_id, uploaded_by_id)

7. Indexos

Taula | Index | Tipus
users | (email, client_id) | UNIQUE (compost)
users | email | INDEX
content_types | (client_id, slug) | UNIQUE (compost)
content_types | slug | INDEX
entries | (content_type_id, status) | INDEX
entries | (content_type_id, locale) | INDEX
entries | author_id | INDEX
field_definitions | content_type_id | INDEX
field_values | entry_id | INDEX
field_values | field_definition_id | INDEX
media | uploaded_by_id | INDEX

8. El filtre SQL

El ClientIdFilter afegeix automaticament aquesta condicio a cada query:

WHERE client_id = :current_client_id

Ho fa a nivell de Doctrine, abans que la query arribi a la BD.
S'aplica a: users, content_types, entries, media.
NO s'aplica a: field_definitions, field_values (ja estan scoped via FK).

9. Resum

7 taules compartides per tots els clients
5 amb client_id directe
2 sense client_id directe (scoped via FK)
5 claus uniques (2 de compostes)
10 claus foranes
11 indexos
1 filtre SQL que afegeix WHERE client_id = ?
