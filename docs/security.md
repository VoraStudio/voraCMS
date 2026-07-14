# VoraCMS — Guia de Seguretat (Rols, Autenticació i Proteccions)

Aquest document descriu el model de seguretat del sistema, incloent la jerarquia de rols, la protecció de l'API REST, i els mecanismes actius contra vulnerabilitats (CSRF, IDOR, XSS, etc.).

---

## 1. Jerarquia de Rols

El sistema defineix una estructura jeràrquica de permisos a `config/packages/security.yaml`:

```
ROLE_ADMIN (Super Administrador de Vora Studio)
  ├── Accés total a totes les dades del panell d'administració (/admin/*)
  ├── Pot gestionar globalment tots els usuaris i projectes actius
  ├── Bypasseja el filtre de base de dades per veure dades creuades
  └── HEREA: ROLE_MOD ──> ROLE_USUARIO

ROLE_USUARIO (Client del CMS / Inquilí)
  ├── Accés exclusiu a la seva pròpia instància i projectes al panell d'administració
  ├── Autenticació obligatòria mitjançant JWT per a l'API privada
  └── Dades aïllades per a totes les operacions a través del filtre Doctrine `user_id_filter`
```

---

## 2. Seguretat en l'API REST

L'API del CMS utilitza **JSON Web Token (JWT)** amb signatura asimètrica RSA-256 (parell de claus en format PEM a `config/jwt/`).

* **Autenticació JWT manual**: Crida a `/api/auth/login` amb email/contrasenya. Retorna un token vàlid per una hora.
* **Master Token (API Pública)**: Petició a `/api/public/token`. El CMS resol l'usuari inquilí a partir del domini solicitant (`Host` / `Origin`) i valida la IP del servidor contra la llista d'admesos (`allowedIps`). Retorna un JWT signat al moment amb validesa d'**1 hora**.
* **Protecció d'Origen (Domain Guard)**: L'`ApiDomainGuardSubscriber` rebutja peticions de navegadors (mitjançant capçalera `Origin`) que no coincideixin amb el camp `allowedDomains` de l'usuari. Si no coincideix, es retorna un error `403 Forbidden`.

---

## 3. Proteccions Actives contra Vulnerabilitats

El sistema implementa de forma nativa regles estrictes per evitar els vectors d'atac més habituals:

### 3.1 Protecció contra CSRF (Cross-Site Request Forgery)
Tots els formularis de gestió i mutació de dades al panell d'administració inclouen validació de token de seguretat `_token`. Això impedeix que llocs de tercers puguin realitzar peticions malicioses en nom de l'usuari amb la sessió iniciada.
* **Mòduls assegurats**:
  * Creació i edició de tipus de contingut (`ContentTypeController`).
  * Creació i edició de plantilles globals (`BaseContentController`).
  * Creació i edició d'entrades de dades (`EntryController`).
  * Edició i creació d'usuaris (`UserController`).

### 3.2 Prevenció de IDOR (Insecure Direct Object Reference)
A la mediateca, per evitar que un client pugi un arxiu i l'intenti enllaçar mitjançant manipulació de paràmetres a un projecte que pertany a un altre usuari:
* Al mètode `upload` de `MediaController`, es valida que el `project_id` enviat pertanyi realment a l'usuari de la sessió. Si no coincideix, es denega l'operació al moment (`403 Forbidden`).

### 3.3 Mitigació de Stored XSS a través de Fileries
El format d'imatges SVG és vulnerable a Stored XSS (Cross-Site Scripting), ja que pot contenir scripts de Javascript (`<script>`) que s'executen quan la imatge es visualitza al navegador de forma aïllada.
* **Mitigació**: S'ha eliminat la tolerància d'arxius `.svg` a la llista d'extensions permeses a `MediaService`. Només s'accepten formats rasteritzats segurs (`.jpg`, `.jpeg`, `.png`, `.webp`, `.avif`).

---

## 4. Configuració a Servidor (CORS / `.htaccess`)

Perquè les capçaleres de seguretat i d'autenticació funcionin correctament sota Apache:
1. **Forward d'Authorization**:
   ```apache
   SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
   ```
   Això permet que Apache lliuri el token Bearer del header a PHP en entorns CGI/FPM.

2. **CORS per a Dominis Específics**:
   Al fitxer `.htaccess` es pot configurar un lock restrictiu complementari:
   ```apache
   SetEnvIf Origin "https://(www\.)?vorastudio\.cat" ORIGIN_ALLOWED=$0
   Header set Access-Control-Allow-Origin "%{ORIGIN_ALLOWED}e" env=ORIGIN_ALLOWED
   ```
