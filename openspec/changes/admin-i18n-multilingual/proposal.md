# Proposal: Admin Interface Multilingual Support (CAT, ES, EN)

## 1. Problem Statement
El panell d'administració de VoraDataCMS actualment té la majoria de textos incrustats directament en català (`ca`). Per donar suport a usuaris i equips que operen en castellà (`es`) o anglès (`en`), cal habilitar un sistema de canvi d'idioma àgil per a tota la interfície del CMS (menús, botons, taules, missatges de confirmació i capçaleres).

## 2. Proposed Solution
1. **Infraestructura de Traducció de Symfony**:
   - Utilitzar el component natiu `symfony/translation` (ja instal·lat).
   - Crear catàlegs de traducció modulars a `translations/messages.ca.yaml`, `translations/messages.es.yaml` i `translations/messages.en.yaml`.
2. **Selector d'Idioma a la Capçalera (Topbar)**:
   - Afegir un selector elegant d'idioma (`CAT | ES | EN` o desplegable compacte amb icona de planeta / globe) al costat del botó de canvi de tema.
3. **Controlador de Canvi d'Idioma & Persistència**:
   - Crear una ruta `/admin/switch-locale/{locale}` que guardi el `_locale` a la sessió de l'usuari i redirigeixi a la pàgina d'origen.
   - Si l'usuari té camp de preferència o sessió activa, assignar el locale corresponent a cada petició.
4. **Traducció de Templates Twig Clau**:
   - Traduir `layout.html.twig`, `_user_card.html.twig`, `_project_header_bar.html.twig`, `dashboard.html.twig`, `project/show.html.twig`, etc., emprant el filtre `|trans`.

## 3. Impact
- Interfície 100% multilingüe per a administradors i clients en Català, Castellà i Anglès de forma transparent i immediata.
