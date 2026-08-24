# Specification: Admin Interface Multilingual Support (CAT, ES, EN)

## 1. User Stories
- **Com a usuari del panell**, vull poder canviar l'idioma de la interfície entre Català (CAT), Castellà (ES) i Anglès (EN) amb un sol clic.
- **Com a usuari**, vull que la meva preferència d'idioma es mantingui durant tota la meva navegació pel panell.

## 2. Requirements

### Req 1: Selector d'Idioma a la Topbar
- Afegir un menú desplegable compacte o toggle pill `.s-lang-toggle` al topbar (`templates/admin/layout.html.twig`), a l'esquerra del botó de canvi de tema.
- Opcions:
  - **CA** (Català)
  - **ES** (Español)
  - **EN** (English)
- L'idioma actiu ha d'estar visualment destacat.

### Req 2: Locale Switcher Controller & Session Listener
- Endpoint `/admin/switch-locale/{locale}` a `LocaleController.php` restringit a `['ca', 'es', 'en']`.
- Guardar `_locale` a `$request->getSession()->set('_locale', $locale)`.
- Event Subscriber / Event Listener `LocaleSubscriber` que sincronitza `$request->setLocale($session->get('_locale', 'ca'))` en cada request.

### Req 3: Catàlegs de Traducció YAML
- `translations/messages.ca.yaml` (Base Català)
- `translations/messages.es.yaml` (Castellà)
- `translations/messages.en.yaml` (Anglès)
- Cobertura de termes: Seccions de menú, botons d'acció, estats d'entrades, títols de dashboard, mètrics, fitxa de perfil, etc.

### Req 4: Persistència a la Base de Dades (User.locale)
- Quan l'usuari commuta l'idioma via `LocaleController`, s'actualitza `$user->setLocale($locale)` i es fa `flush()` a la taula `users`.
- Creat `LoginSuccessSubscriber` per carregar el `user.locale` guardat a la BDD cap a la sessió en fer login.
- La fitxa de perfil `templates/admin/user/profile.html.twig` mostra el valor de l'idioma preferit guardat i està 100% traduïda.
