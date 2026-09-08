# Proposal: Capçalera Contextual Adaptativa de Projectes i Entrades

## 1. Context i Declaració del Problema
A la capçalera de resum del CMS (`_project_header_bar.html.twig`) hi havia una barreja confusa de mètriques i rols:
- Al llistat general d'administrador (`/admin/projects`), es mostrava «Total Projectes» barrejat amb estats d'entrades globals sense diferenciar la salut del catàleg de projectes (actius vs inactius).
- En entrar al detall d'un projecte (`/admin/project/{id}`) o d'una secció (`/admin/content/{slug}`), es continuaven mostrant mètriques globals o «Total Projectes», quan l'operativa només concerneix les entrades d'aquell projecte o secció.
- Al perfil de client (usuari no `ROLE_ADMIN`), que només opera sobre el seu propi projecte, es mostrava «1 Total Projectes» en comptes de donar la informació rellevant del seu contingut: total d'entrades, publicades, esborranys, arxivades, etc.

## 2. Solució Acordada
Dissenyar una capçalera contextual intel·ligent de dos modes:

1. **Gestió Admin Global (`/admin/projects`)**:
   - Informació de projectes: **Total Projectes**, **Projectes Actius**, **Projectes Inactius**.
   - Informació d'entrades globals: **Total Entrades**, **Publicats**, **No Publicats**.
   - Filtre d'estats enfocat a projectes (Tots, Actius, Inactius).

2. **Dins d'un Projecte / Secció o Perfil de Client**:
   - Informació exclusivament d'entrades (abast contextual al projecte o secció):
     - **Total Entrades** (recompte real d'entrades d'aquell àmbit).
     - **Publicats** (entrades publicades).
     - **No Publicats** (esborranys).
     - **Arxivats** (entrades arxivades).
     - **Programats** (entrades programades).
     - **Activitat** (% / tendència de noves entrades).
   - Filtre d'estats enfocat a entrades (Tots, Publicats, No Publicats, Programats, Arxivats) filtrant en temps real les files de les taules.

3. **Formularis d'edició/creació**:
   - Exclosos de la capçalera de mètriques per mantenir l'atenció en el formulari.
