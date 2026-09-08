# Design: Capçalera Contextual Adaptativa de Projectes i Entrades

## 1. Detecció Contextual a Twig
A `templates/admin/project/_project_header_bar.html.twig`:
```twig
{% set isAdmin = is_granted('ROLE_ADMIN') %}
{% set isInsideProject = (project is defined and project is not null) or (contentType is defined and contentType is not null) or (not isAdmin) %}
{% set currentProject = project ?? (contentType is defined and contentType ? contentType.project : null) %}
{% set currentSection = contentType ?? null %}
```

## 2. Modes de Mètriques (KPIs)

### Mode A: Admin Global (`not isInsideProject`)
Es mostren 6 targetes dividides en dues dimensions clares:
1. **Total Projectes**: Nombre total de projectes a la plataforma.
2. **Projectes Actius**: Projectes amb `active == true`.
3. **Projectes Inactius**: Projectes amb `active == false`.
4. **Total Entrades**: Recompte global d'entrades de tots els tipus de contingut.
5. **Publicats**: Entrades amb estat `published`.
6. **No Publicats**: Entrades amb estat `draft`.

### Mode B: Dins Projecte / Secció o Client (`isInsideProject`)
Es mostren 6 targetes enfocades 100% a les entrades del context seleccionat:
1. **Total Entrades**: Nombre d'entrades de la secció, projecte o client.
2. **Publicats**: Entrades publicades.
3. **No Publicats**: Esborranys.
4. **Arxivats**: Entrades en arxiu històric.
5. **Programats**: Entrades amb publicació programada futura.
6. **Activitat**: Increment setmanal d'entrades creades (+N%).

## 3. Comportament dels Filtres
- **Mode A (Admin Global)**: El selector `#statusFilter` ofereix `[all, active, inactive]` per filtrar files de projecte (`.d2-project-row`).
- **Mode B (Contextual / Client)**: El selector `#statusFilter` ofereix `[all, published, draft, scheduled, archived]` i filtra files de taula (`tr.cyber-row[data-status]`).
- **Cercador unificat**: Filtra tant noms de projecte com el text de qualsevol fila d'entrada a les taules.

## 4. Eliminació de la Barra Redundant `.d2-section-bar`
S'elimina el bloc `<div class="d2-section-bar">`:
- Era redundant perquè el nom del projecte ja encapçala la pàgina (H1) i cada requadre/panell de contingut ja disposa del seu propi badge `SECCIÓ` identificatiu.
- Evita la confusió d'etiquetar el nom d'un projecte com a «Secció».

