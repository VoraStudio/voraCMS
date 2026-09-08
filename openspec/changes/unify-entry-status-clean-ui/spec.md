# Specification: Unificar l'estat d'entrades i eliminar el camp redundant Actiu/Inactiu a la UI

## Requisits

### Requirement: Eliminació del camp Actiu a les taules d'entrades
Les taules d'entrades (tant a la vista de projecte `/admin/project/{id}` com al llistat general d'entrades `/admin/content/{slug}`) **NO HAN DE MOSTRAR** cap columna ni indicador de valor booleà «Actiu / Inactiu».
- L'únic indicador d'estat a les entrades ha de ser el cicle de vida editorial (`status`): Publicat, Esborrany, Programat, Arxivat.

### Requirement: Retirada del botó Toggle d'actiu a les entrades
El grup d'accions (`.cyber-actions`) de les entrades **NO HA D'INCLOURE** el botó de commutador (*toggle switch*).
- Les accions permeses per fila d'entrada són exclusivament: Veure (ull), Editar (llapis) i Eliminar (paperera).

### Requirement: Preservació d'Actiu/Inactiu a Usuaris i Projectes
El camp `active`, les columnes de taula i els botons de commutador toggle **S'HAN DE MANTENIR SENSE CANVIS** a:
- La gestió d'usuaris (`/admin/users`, entitat `User`).
- El llistat i gestió de projectes (`/admin/projects`, entitat `Project`).

### Requirement: Ajust d'amplada i layout de taula
La taula `.cyber-table--project-show` ha de redistribuir el 100% de la seva amplada entre les 5 columnes resultants (Títol, Data, Descripció, Estat, Accions) mantenint el `table-layout: fixed` per a una alineació perfecta entre totes les seccions del projecte.
