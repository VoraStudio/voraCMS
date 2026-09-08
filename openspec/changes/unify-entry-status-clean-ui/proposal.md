# Proposal: Unificar l'estat d'entrades i eliminar el camp redundant Actiu/Inactiu a la UI

## 1. Context i Declaració del Problema
Actualment a les taules i vistes d'entrades de contingut (`Entry`) conviuen dos conceptes d'estat que se superposen:
1. **L'estat editorial (`status`)**: `draft` (Esborrany), `published` (Publicat), `scheduled` (Programat) i `archived` (Arxivat). Aquesta és la font de la veritat del cicle de vida del contingut a la web.
2. **El camp actiu (`active`)**: Booleà binari (`true`/`false`) que es va introduir històricament per simetria amb `User` i `Project`.

Aquest camp `active` a les entrades genera greus problemes de disseny i usabilitat:
- **Redundància conceptual**: Genera una matriu confusa d'estats sense sentit de negoci (què és un *Esborrany Actiu* o un *Publicat Inactiu*?).
- **Desconnexió amb el Frontend**: L'API pública mai ha utilitzat `e.active` per filtrar contingut; només consulta `e.status = 'published'`.
- **Sobrecàrrega visual a la interfície (UI/UX)**:
  - Ocupa una columna sencera («ACTIU») a les taules d'entrades.
  - Afegeix un botó de commutador (*toggle*) a la columna d'accions que competeix visualment amb l'estat real.
  - Introdueix filtres innecessaris («Actius», «Inactius») i regles CSS complexes d'inactivitat que van arribar a enfosquir les files fins a fer-les il·legibles.

## 2. Solució Proposada
Eliminar completament la presència del concepte **Actiu / Inactiu** a totes les taules, components i accions de les **entrades (`Entry`)**, mantenint-lo exclusivament a les entitats on sí que té sentit de domini (**Usuaris** per al control d'accés i **Projectes** per a la congelació de projectes).

1. **Retirar la columna «ACTIU»**:
   - Eliminar la capçalera i la cel·la de la columna d'actiu a [templates/admin/project/show.html.twig](file:///d:/webs/VoraDataCMS/templates/admin/project/show.html.twig) i [templates/admin/entry/index.html.twig](file:///d:/webs/VoraDataCMS/templates/admin/entry/index.html.twig).
2. **Retirar el botó Toggle de les entrades**:
   - Treure el botó de commutador `_toggle_btn.html.twig` del grup d'accions (`.cyber-actions`) de les files d'entrades.
3. **Reassignació de l'espai a la taula**:
   - Reassignar l'espai alliberat a les columnes de Títol, Descripció i Estat perquè la informació respiri millor i tingui màxima llegibilitat.
4. **Conservació a Usuaris i Projectes**:
   - `User.active` i `Project.active` es mantenen intactes amb els seus toggles i indicadors visuals.

## 3. Impacte
- **UI/UX més neta, elegant i professional**: S'elimina el soroll visual i la redundància de les taules d'entrades.
- **Model mental simplificat**: Una única font de la veritat per a la visibilitat del contingut: l'estat editorial (`status`).
- **Sense risc de regressió**: L'API externa ja no utilitzava aquest camp, de manera que el comportament de cara a les webs públiques no es veu alterat.
