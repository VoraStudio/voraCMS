# Specification: Entry Form UX & Multilingual Refinement

## 1. User Stories
- **Com a gestor de contingut**, en crear una nova entrada vull tenir l'opció d'Arxivar bloquejada per evitar errors d'estat inicials.
- **Com a usuari**, vull veure l'estat del toggle reflectit en l'idioma seleccionat (ex: *Published* quan és verd en anglès, *Publicat* en català, *Publicado* en castellà).
- **Com a usuari multilingüe**, vull que els camps de formulari comuns (*Title, Description, Image, Category, Date, Price, Capacity, Order*) i les accions (*Create entry, Cancel*) es mostrin traduïts al meu idioma preferit.

## 2. Requirements

### Req 1: Bloqueig d'Arxivat en Nova Entrada
- A `templates/admin/entry/new.html.twig`, l'element `.s-toggle[data-value="archived"]` s'ha de deshabilitar visualment i funcionalment (`pointer-events: none`, `opacity: 0.45`, `tabindex="-1"`, `aria-disabled="true"`).

### Req 2: Toggle Switches Reactius i Multilingües
- Els interruptors d'estat han de mostrar el text corresponent a l'estat actiu o inactiu utilitzant les claus:
  - `status.published` / `status.not_published`
  - `status.archived` / `status.not_archived`
  - `status.scheduled` / `status.not_scheduled`
- L'objecte JavaScript `labels` a `new.html.twig` i `edit.html.twig` s'ha d'alimentar amb `|trans|e('js')`.

### Req 3: Traducció Dinàmica de Camps (`trans_field`)
- Filtre Twig `trans_field` a `AdminExtension.php` per mapejar automàticament noms de camps de base de dades a les claus `entry.*_field`.
- 'Places' es tradueix com a *Places* (CA), *Plazas* (ES) i *Capacity* (EN) per fer referència a persones/aforament.

### Req 4: Traducció d'Elements Estructurals
- Breadcrumbs (`nav.dashboard`, `nav.projects`, `entry.new_entry_title`, `entry.edit_entry_title`).
- Botó tornar (`action.back`).
- Dropzone de càrrega i mediateca a `_gallery_field.html.twig` (`action.drag_files_or_select`, `action.upload_hint`, `action.choose_from_media`).
- Botons d'acció inferiors (`action.cancel`, `action.create_entry`, `action.save_changes`).
