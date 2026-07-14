# VoraCMS — Flux de l'Aplicació

Aquest document detalla el cicle de vida de les peticions des de la seva arribada al servidor fins al retorn de la resposta, distingint els diferents fluxos: API Pública, API Autenticada, Master Token i l'Administració.

---

## 1. Cicle de Vida de la Petició (Kernel de Symfony)

Totes les peticions HTTP són centralitzades al fitxer `public/index.php`.

```
index.php ──> Kernel::handle($request) ──> Router (Troba Controller) ──> Controller Event ──> Execució ──> Response
```

### Events Clau
1. **`KernelEvents::REQUEST`**: Es fan les validacions inicials del sistema.
2. **`KernelEvents::CONTROLLER`**: S'activa el filtre de Doctrine per a les entitats d'acord amb el rol de l'usuari autenticat.
   * L'`UserFilterSubscriber` activa el filtre `user_id_filter` i inyecta el `user_id` de l'usuari actiu (aïllant les seves dades).
   * Si l'usuari té el rol `ROLE_ADMIN`, el filtre es desactiva perquè pugui visualitzar el contingut de tots els clients globalment.
3. **Controller**: Execució del mètode de control corresponent.
4. **`KernelEvents::RESPONSE`**: S'apliquen capçaleres HTTP CORS mitjançant `CorsSubscriber`.

---

## 2. Fluxos Principals d'API

### Flux A: API Pública (`GET /api/public/{project}/{type}`)
Utilitzada per frontends estàtics per recollir entrades publicades d'un tipus i projecte determinat de forma oberta (sense token).

```
Petició GET /api/public/web/noticia
  │
  ├───> [Router] ───> PublicController::content()
  │
  ├───> [UserFilterSubscriber] ───> Bypasseja la petició (les rutes públiques no apliquen filtre de BD)
  │
  ├───> PublicController llegeix el projecte pel slug 'web'
  │
  ├───> Busca el Content Type 'noticia' pertanyent a aquest projecte
  │
  ├───> Consulta les entrades amb estat 'published' per a aquest Content Type
  │
  └───> Retorna la col·lecció en format JSON amb capçaleres CORS obertes (`Access-Control-Allow-Origin: *`)
```

### Flux B: Master Token (`GET /api/public/token`)
Permet a servidors SSR obtenir un token JWT dinàmic basat en el seu domini i IP origen sense haver d'enviar credencials a l'aplicació client.

```
Frontend (SSR) demana: GET /api/public/token
  │
  ├───> [TokenMasterService] llegeix la capçalera Host (ex: 'vorastudio.cat') i IP demanant
  │
  ├───> Busca a BD l'usuari propietari del domini
  │
  ├───> Valida que la IP estigui a la llista 'allowedIps' (si està configurada)
  │
  ├───> Genera un JWT signat amb clau RSA256 per al client trobat (caduca en 1 hora)
  │
  └───> Resposta: { "token": "eyJhbGci..." }
```

### Flux C: API Autenticada (`GET /api/{slug}`)
Cridada amb un JWT vàlid per a obtenir dades protegides del CMS o fer accions com demanar seccions o registrar visites.

```
Petició GET /api/noticia amb Header: Authorization: Bearer <JWT>
  │
  ├───> [Firewall API] ───> Valida signatura asimètrica del JWT (clau pública RSA)
  │
  ├───> [UserFilterSubscriber] ───> Llegeix l'ID d'usuari contingut al JWT
  │          └───> Activa el Doctrine filter: user_id = X
  │
  ├───> [Controller] ───> El repositori executa la query, inyectant automàticament el filtre
  │          └───> SELECT * FROM entries WHERE content_type_id = ? AND user_id = X
  │
  └───> Retorna la informació de l'inquilí degudament aïllada
```

---

## 3. Flux d'Upload i Seguretat de Fitxers (Mediateca)

```
POST /admin/media/upload (Fitxer + project_id opcional)
  │
  ├───> [CsrfTokenValidation] ───> Valida token de formulari
  │
  ├───> [MediaController] ───> Verifica propietats:
  │          └───> Si project_id s'envia i l'usuari no és ROLE_ADMIN:
  │                Verifica que el projecte pertanyi a l'usuari autenticat.
  │                Si no pertany ──> Retorna 403 Forbidden (Prevenció d'IDOR)
  │
  ├───> [MediaService] ───> Valida l'arxiu:
  │          ├───> Tamany inferior a 3MB
  │          └───> Extensió permesa: 'jpg', 'jpeg', 'png', 'webp', 'avif' (svg no permès per XSS)
  │
  ├───> [MediaService] ───> Desa el fitxer al directori físic: `/public/uploads/{userId}/{filename}`
  │
  └───> Guarda el registre a la base de dades i retorna redirect/JSON
```
