# Specification: Login Multilingual & Legal Terms

## 1. User Stories
- **Com a usuari visitant**, vull poder seleccionar l'idioma a la pantalla d'inici de sessió per interactuar amb la plataforma en la meva llengua des del primer moment.
- **Com a usuari/client**, vull poder consultar les condicions legals i la política de privacitat directament des de la pantalla de login per entendre com es tracten les meves dades.

## 2. Requirements

### Req 1: Selector d'idioma a Login
- Selector `s-lang-switcher` a la cantonada superior dreta (`.s-login-topbar`) de `templates/admin/login.html.twig`.
- Enllaços directes a `admin_switch_locale` per als idiomes `ca`, `es` i `en`.

### Req 2: Enllaç i Modal Legal
- Enllaç a `templates/admin/login.html.twig` amb els textos traduïts `auth.legal_terms` & `auth.privacy_policy`.
- Enllaç a la landing comercial (`https://cms.voradata.cat/`) a través del logo de VoraData i de l'acció traduïda `auth.discover_voradata` (*Descobreix VoraData* / *Descubre VoraData* / *Discover VoraData*).
- Modal amb tres seccions essencials:
  1. Relació corporativa directa entre VoraCMS, VoraData i VoraStudio.
  2. Tractament de dades amb propòsit únic de donar servei a les landing pages del client.
  3. Declaració explícita de no utilització de cookies de seguiment de tercers ni IA de tercers externa.
- Botó de tancament `legal.close_btn` (*Entesos* / *Entendido* / *Understood*).

### Req 3: Traduccions
- Claus afegides a `translations/messages.ca.yaml`, `messages.es.yaml` i `messages.en.yaml` sota els dominis `auth.*` i `legal.*`.
