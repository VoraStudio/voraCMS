# Proposal: Dashboard Recent Entries Status Indicator

## 1. Problem Statement
Al Dashboard de client/administrador (`/admin`, `templates/admin/dashboard.html.twig`), el bloc d'Últimes Entrades (`latestEntries` / `.d2-news-card`) mostra el títol, la descripció, la data i el projecte, però no indicava de manera clara i immediata l'estat de publicació ni d'activació de l'entrada (si és activa, esborrany, programada o arxivada).

## 2. Proposed Solution
- Afegir un indicador d'estat tipus badge / icona a la cantonada superior dreta de cada targeta de contingut recent (`.d2-news-card__status-corner`).
- Mostrar l'estat amb codi de colors i icones Bootstrap Icons coherents:
  - **Activa / Publicada**: Icona `bi-check-circle-fill` en verd.
  - **Programada**: Icona `bi-clock-fill` en blau.
  - **Arxivada**: Icona `bi-archive-fill` en gris/neutre.
  - **Esborrany / No publicat**: Icona `bi-pencil-fill` en taronja.
  - **Inactiva**: Icona `bi-pause-circle-fill` en vermell.
- Actualitzar `DashboardController.php` per proporcionar l'estat d'activació (`active`) a l'array de `latestEntries`.

## 3. Impact
- Comprensió immediata de l'estat de cada entrada des del Dashboard sense haver d'entrar a la fitxa d'edició o a la vista de projecte.
