VoraCMS - Flux de l'Aplicacio


1. Introduccio

Aquest document descriu el recorregut d'una sol.licitud desde que
entra al servidor fins que se'n torna una resposta. Cada punt
d'entrada (API publica, API autenticada, panell admin) te el seu
propi flux.


2. Punt d'entrada unic (index.php)

Totes les sol.licituds arriben a public/index.php. Aquest fitxer
carrega l'autoloader de Composer i crea el kernel de Symfony.

index.php
    |
    v
Kernel::handle($request)
    |
    v
Router -> matcheja URL amb Controller i metode


3. El cicle de vida d'una sol.licitud

Symfony organitza cada sol.licitud en events. Els mes importants
son:

KernelEvents::REQUEST
    Es el primer event. Aqui es fan checkjos globals.
    ClientFilterSubscriber escolta aquest event per decidir
    si activa o desactiva el filtre Doctrine.

Router
    El router analitza la URL i determina quin controller
    i metode s'ha d'executar.

KernelEvents::CONTROLLER
    El controller ha estat seleccionat. Es poden modificar
    arguments abans de cridar-lo.

Controller
    S'executa el metode del controller. Aqui es fa la logica
    principal (consultar BD, serialitzar, etc.).

KernelEvents::VIEW
    Si el controller retorna un array o objecte (no un Response),
    aqui es converteix en Response.

KernelEvents::RESPONSE
    La resposta ja esta preparada. Es poden afegir headers,
    modificar el cos, etc.

KernelEvents::TERMINATE
    La resposta s'ha enviat al client. Neteja final.

Diagrama:

REQUEST -> [REQUEST event] -> Router -> [CONTROLLER event] -> Controller
    -> [VIEW event] -> [RESPONSE event] -> Resposta -> [TERMINATE event]


4. Flux A: API publica (GET /api/{slug}?client={slug})

Es el cas mes simple. No hi ha autenticacio. El client
s'identifica amb el query parameter ?client={slug}.

Pas a pas:

1. El router detecta que la URL coincideix amb
   EntryController::list()

2. ClientFilterSubscriber rep l'event REQUEST.
   Comprova: l'usuari esta autenticat? NO.
   No activa el filtre encara (no sabem quin client es).

3. Symfony crida EntryController::list($slug, $ctRepo, $entryRepo, $request)

4. Dins del metode, es crida resolveClientFromQuery($request):
   a. Llegeix $_GET['client']
   b. Si no existeix -> retorna JsonResponse 400
   c. Busca Client per slug a la BD
   d. Si no el troba -> retorna JsonResponse 404
   e. Crida ClientScope::setClient($client)
      Aixo activa el filtre Doctrine amb client_id

5. Es consulta ContentTypeRepository::findBySlug($slug)
   La query porta WHERE client_id = X (gracies al filtre)

6. Es consulta EntryRepository::findPublishedByType($slug)
   La query tambe porta WHERE client_id = X

7. Es serialitzen les entries i es retorna JSON

Diagrama:

GET /api/noticia?client=victoria-taylor
    |
    v
Router -> EntryController::list()
    |
    v
resolveClientFromQuery()
    |
    +-- ?client existeix? NO -> 400
    |
    +-- client existeix? NO -> 404
    |
    +-- SI -> ClientScope::setClient($client)
               |
               v
        Filtre Doctrine activat (client_id = 4)
    |
    v
ContentTypeRepository::findBySlug('noticia')
    -> SELECT * FROM content_types
       WHERE slug = 'noticia'
       AND client_id = 4
    |
    v
EntryRepository::findPublishedByType('noticia')
    -> SELECT * FROM entries
       WHERE content_type_id = ?
       AND status = 'published'
       AND client_id = 4
    |
    v
Serializer -> JsonResponse


5. Flux B: API autenticada (POST /api/auth/login)

5.1 Login

POST /api/auth/login { email, password }
    |
    v
Firewall api_login (stateless)
    |
    +-- Es json_login configurat a security.yaml
    |
    v
Proveedor d'usuaris (entity: User, property: email)
    |
    +-- Busca User per email a la BD
    +-- Verifica password amb bcrypt
    |
    v
Login OK?
    |
    +-- NO -> AuthenticationFailureHandler
    |         retorna 401 "Invalid credentials"
    |
    +-- SI -> AuthenticationSuccessHandler
              |
              v
        JwtClientIdSubscriber
              |
              +-- Llegeix el User autenticat
              +-- Extreu User->getClient()
              +-- Afegeix al JWT:
              |   - client_id
              |   - client_slug
              |   - roles
              |   - username (email)
              |
              v
        LexikJWTBundle genera token
              |
              v
        Resposta: { token: "eyJ..." }

5.2 /api/auth/me

GET /api/auth/me
Header: Authorization: Bearer eyJ...
    |
    v
Firewall api (stateless)
    |
    +-- Extreu el JWT del header
    +-- Valida la signatura (RSA256)
    +-- Valida exp (no ha caducat)
    |
    v
JwtClientIdSubscriber (a REQUEST)
    |
    +-- Llegeix client_id del JWT
    +-- Crida ClientScope::setClient()
    +-- Activa filtre Doctrine
    |
    v
AuthController::me()
    |
    +-- $this->getUser() -> User
    +-- Construeix array amb id, email, name, roles
    +-- Afegeix client: { id, name, slug }
    |
    v
Resposta: { data: { id, email, name, roles, client } }


6. Flux C: Panell admin

6.1 Login

GET /admin/login
    |
    v
Firewall admin (stateful)
    |
    +-- Mostra formulari de login
    |
    v
POST /admin/login { _username, _password }
    |
    v
Proveedor d'usuaris busca per email
    |
    v
Login OK?
    |
    +-- NO -> Torna al formulari amb error
    |
    +-- SI -> Symfony crea sessio
              |
              v
        Redirect a /admin/ (dashboard)

6.2 Dashboard

GET /admin/
    |
    v
Firewall admin (stateful)
    |
    +-- Llegeix sessio
    +-- L'usuari te ROLE_ADMIN? SI -> continua
    |
    v
DashboardController::index()
    |
    +-- ClientScope::getClient()
    |   +-- Si ROLE_ADMIN -> retorna el seu Client
    |   +-- Si ROLE_SUPER_ADMIN -> retorna null
    |
    +-- ClientScope::isSuperAdmin()
    |
    +-- ContentTypeRepository::findActive()
    |   +-- Si te client -> WHERE client_id = X
    |   +-- Si es super-admin -> no filtre (veu tots)
    |
    +-- Per cada content type, conta entries
    |
    +-- Si es super-admin:
    |   ClientRepository llista tots els clients
    |
    v
Renderitza admin/dashboard.html.twig
    |
    +-- currentClient -> header amb nom i logo
    +-- stats -> targetes amb tipus i numeros
    +-- clients (super-admin) -> graella de clients


7. Flux D: Upload de media

POST /admin/media/upload (multipart)
    |
    v
Formulari autenticat (ROLE_ADMIN)
    |
    v
MediaController::upload(Request)
    |
    +-- Llegeix el fitxer del request
    +-- Agafa client_id de ClientScope
    +-- Crida MediaService::upload($file, $clientId)
    |
    v
MediaService::upload($file, $clientId)
    |
    +-- Genera nom unic (uuid + extensio)
    +-- Construeix path: /public/uploads/{clientId}/{filename}
    +-- Crea directori si no existeix
    +-- Mou el fitxer
    +-- Crea entitat Media amb:
    |   - client_id
    |   - uploaded_by_id (usuari actual)
    |   - filename, path, mime_type, file_size
    |
    v
$em->persist($media)
$em->flush()
    |
    v
Resposta: redirect o JSON amb dades del media


8. Flux E: Creacio d'un client (provisioning)

8.1 Via CLI

bin/console voracms:client:create victoria-taylor "Victoria Taylor"
    |
    v
ProvisionClientCommand::execute()
    |
    +-- $isCreateMode = true (perque el nom existeix)
    |
    v
handleCreate('Victoria Taylor', 'victoria-taylor')
    |
    +-- Crea Client
    +-- $em->persist($client)
    +-- $em->flush()   (el client te ID ara)
    |
    v
ClientProvisioner::provision($client)
    |
    +-- createNoticies($client)
    |   +-- ContentType: Noticies (slug: noticia, base: true)
    |   +-- FieldDefinitions: titul, descripcio, imatge, data, contingut
    |   +-- $em->persist($noticies)
    |
    +-- createEvents($client)
    |   +-- ContentType: Events (slug: event, base: true)
    |   +-- FieldDefinitions: titul, descripcio, imatge, data_event, hora, ubicacio, enllac
    |   +-- $em->persist($events)
    |
    +-- $em->flush()
    |
    v
Output: Client "Victoria Taylor" creat amb exit (ID: 4)

8.2 Via panell admin

POST /admin/client/new { name, slug, logo, active }
    |
    v
ClientController::new(Request)
    |
    +-- denyAccessUnlessGranted('ROLE_SUPER_ADMIN')
    |
    +-- Crea Client
    +-- $em->persist($client)
    |
    +-- ClientProvisioner::provision($client)
    |   (mateix proces que via CLI)
    |
    +-- $em->flush()
    |
    v
Flash: "Client creat correctament"
Redirect: /admin/client/


9. Flux F: Error handling

L'aplicacio retorna errors HTTP estandard:

400 Bad Request
    Quan: ?client= no s'ha passat a l'API publica
    Resposta: { error: "Client slug is required. Use ?client={slug}" }

401 Unauthorized
    Quan: Credencials incorrectes al login
    Resposta: { code: 401, message: "Invalid credentials." }

404 Not Found
    Quan: Client slug no existeix
    Quan: Content type slug no existeix
    Quan: Entry ID no existeix
    Resposta: { error: "Not found" } o { error: "Client 'xxx' not found" }

500 Internal Server Error
    Quan: Error inesperat (TypeError, PDOException, etc.)
    En entorn dev: Pagina Symfony amb stack trace
    En entorn prod: Pagina d'error generica


10. Resum de fluxos

Flux    | Autenticacio  | Client scope         | Cache | Tipus
A       | Cap           | ?client={slug}       | Si    | API public GET
B       | JWT           | JWT claim            | No    | API auth
C       | Sessio        | Sessio usuari        | No    | Admin
D       | Sessio        | ClientScope          | No    | Admin upload
E       | CLI/Sessio    | Explicit             | No    | Admin/CLI

Tots els fluxos comparteixen el mateix ClientScope i el mateix
filtre Doctrine. La diferencia es com s'activa el client.
