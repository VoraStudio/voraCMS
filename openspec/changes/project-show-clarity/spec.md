# Specification: Project Show UI/UX Clarity & Content Type Hierarchy

## 1. User Stories
- **Com a usuari o gestor d'un projecte**, vull identificar clarament quin Tipus de Contingut (Secció) estic visualitzant dins del projecte.
- **Com a usuari o gestor**, vull veure d'un cop d'ull quantes entrades conté cada secció i quines són les seves característiques.
- **Com a usuari**, vull una taula neta sense columnes redundants com "Client" a cada fila per tenir millor llegibilitat del contingut.

## 2. Requirements & UI/UX

### Req 1: Content Type Header (Panell de Secció)
- La capçalera de cada taula `.d2-panel-head` ha d'incloure:
  - Un badge de categoria `SECCIÓ` amb icona (`bi-layers-fill`).
  - El títol del Tipus de Contingut (`ct.name`).
  - Un pill de recompte: `{{ entries|length }} entrades`.
  - Un pill font-monospace amb l'slug: `#{{ ct.slug }}`.
  - La descripció de la secció en un subtítol ben diferenciat.
  - Botó d'acció a la dreta: `+ Nova` (s'elimina el botó redundant `Veure totes` per evitar viatges circulars).

### Req 2: Taula d'Entrades Neta i Accions Homogènies
- Eliminar la columna `Client` (`<th>Client</th>` i `<td>...</td>`).
- **Unificació del bloc d'Accions**: El botó "Veure" s'estandarditza com a icona pura (`.cyber-btn--icon .cyber-btn--view` amb icona `bi-eye`), aconseguint un grup d'accions homogeni, compacte i elegant:
  `[ 👁️ Veure ] [ 🟢 Toggle Actiu ] [ ✏️ Editar ] [ 🗑️ Eliminar ]`.
- Reorganitzar columnes:
  1. **Títol & ID** (amb thumbnail/icona).
  2. **Data de publicació/creació**.
  3. **Descripció / Extracte**.
  4. **Estat** (Badge Publicat / No publicat / Programat / Arxivat).
  5. **Actiu** (Switch toggle ràpid).
  6. **Accions** (Icones homogènies).

### Req 3: Resum de Seccions Disponibles al Projecte
- Sota el títol del projecte o abans de les seccions, mostrar un mini selector/índex de les seccions disponibles (si n'hi ha més d'una) per navegar ràpidament.
