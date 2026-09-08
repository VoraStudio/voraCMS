# Specification: Capçalera Contextual Adaptativa de Projectes i Entrades

## Requisits Funcionals

### REQ-1: Mètriques de Projecte vs Entrades a la Vista Global Admin
- A `/admin/projects`, l'usuari amb `ROLE_ADMIN` ha de veure les mètriques dividides en Projectes (Total, Actius, Inactius) i Entrades (Total Entrades, Publicats, No Publicats).
- El filtre de selecció d'estat ha de permetre filtrar per projectes actius i inactius.

### REQ-2: Mètriques Exclusives d'Entrades en Context de Projecte o Secció
- A `/admin/project/{id}` o a `/admin/content/{slug}`, les mètriques han d'estar circumscrites exclusivament a les entrades del projecte o secció actiu.
- No s'ha de mostrar cap referència a projectes totals ni actius/inactius.
- Les 6 targetes han de ser: Total Entrades, Publicats, No Publicats, Arxivats, Programats i Activitat.
- El filtre d'estats ha de permetre filtrar les files de la taula per `published`, `draft`, `scheduled` i `archived`.

### REQ-3: Vista Client
- Un usuari client (sense `ROLE_ADMIN`) ha de veure exclusivament la capçalera d'entrades (Mode B), calculada sobre el conjunt d'entrades del seu projecte assignat.

### REQ-4: Formularis
- Els formularis de creació i edició de projecte no han d'incloure la barra de mètriques `_project_header_bar.html.twig`.
