# Tasks: Entry Form UX & Multilingual Refinement

- [x] 1. Millora d'Estats a Nova Entrada
  - [x] 1.1 Deshabilitar el toggle "Arxivat" a `templates/admin/entry/new.html.twig`.
  - [x] 1.2 Corregir el text inicial del toggle publicat perquè digui "Publicat" quan està actiu.

- [x] 2. Filtre Twig i Diccionari de Traduccions
  - [x] 2.1 Afegir filtre Twig `trans_field` a `src/Twig/AdminExtension.php` amb suport per a títol, descripció, imatge, categoria, data, places (capacity), preu i ordre.
  - [x] 2.2 Afegir les claus de traducció a `messages.ca.yaml`, `messages.es.yaml` i `messages.en.yaml`.

- [x] 3. Adaptació de Plantilles Twig
  - [x] 3.1 Aplicar `|trans_field` als camps individuals, bento grid i media pairs a `new.html.twig` i `edit.html.twig`.
  - [x] 3.2 Traduir breadcrumbs, botons inferiors (*Cancel·lar*, *Crear entrada*, *Guardar canvis*) i botó de tornada.
  - [x] 3.3 Traduir el component de pujada i mediateca a `_gallery_field.html.twig`.

- [x] 4. Sincronització JavaScript
  - [x] 4.1 Iniciar l'objecte `labels` en JS amb textos traduïts via Twig a `new.html.twig` i `edit.html.twig`.
