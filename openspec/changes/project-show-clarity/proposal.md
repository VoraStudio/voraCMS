# Proposal: Project Show UI/UX Clarity & Content Type Hierarchy

## 1. Problem Statement
A la vista de detall d'un projecte (`/admin/project/{id}`, `templates/admin/project/show.html.twig`), els conceptes de **Client**, **Projecte** i **Tipus de Contingut / Secció** es confonen perquè tenen noms idèntics o molt similars (ex: Client "Aula Gastronomica", Projecte "Aula Gastronomica", Secció "Activitats Aula Gastronomica"). A més:
- La capçalera de cada bloc de tipus de contingut és plana i no transmet clarament que es tracta d'una "Secció" o "Model de dades".
- La taula conté una columna "CLIENT" a cada fila d'entrada que és redundant i confusa dins del context del projecte.
- No hi ha badges de metadades que indiquin el nombre d'entrades ni l'slug del tipus de contingut.

## 2. Proposed Solution
1. **Targeta d'Encapçalament de Secció / Tipus de Contingut**:
   - Badge identificador visual: `SECCIÓ / TIPUS DE CONTINGUT` amb icona `bi-layers-fill`.
   - Nom del tipus de contingut destacat en gran.
   - Pills de metadades: Badge amb el recompte d'entrades (ex: `3 entrades`) i slug del model (ex: `activitats-aula-gastronomica`).
   - Descripció de la secció clara i ben espaiada.
2. **Neteja i Optimització de la Taula d'Entrades**:
   - Eliminar la columna redundant "CLIENT" de la taula del projecte.
   - Donar més amplada i visibilitat al Títol, ID, Estat (Publicat/Esborrany/Arxivat), Actiu (Toggle) i Data.
3. **Resum visual de Seccions del Projecte**:
   - Indicador de quantes seccions té el projecte i accés ràpid a cadascuna.

## 3. Impact
- Millora dràstica de la claredat i usabilitat (UI/UX) per als gestors i clients del CMS.
- Eliminació d'elements redundants a les taules.
