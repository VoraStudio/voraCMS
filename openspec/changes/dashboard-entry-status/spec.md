# Specification: Dashboard Recent Entries Status Indicator

## 1. User Stories
- **Com a gestor/administrador**, vull veure directament al Dashboard quin és l'estat de les meves últimes entrades per saber si estan visibles, programades o pendents de publicació.

## 2. Requirements & UI/UX

### Req 1: Badge d'Estat a la Cantonada Superior Dreta
- Posicionament absolut `top: 10px; right: 12px;` dins de `.d2-news-card` (amb `position: relative;`).
- Distinció semàntica dels estats:
  1. **Si `not entry.active`**: Badge `.d2-corner-badge--inactive` amb icona `bi-pause-circle-fill` ("Inactiu").
  2. **Si `entry.status == 'published'`**: Badge `.d2-corner-badge--published` amb icona `bi-check-circle-fill` ("Activa").
  3. **Si `entry.status == 'scheduled'`**: Badge `.d2-corner-badge--scheduled` amb icona `bi-clock-fill` ("Programada").
  4. **Si `entry.status == 'archived'`**: Badge `.d2-corner-badge--archived` amb icona `bi-archive-fill` ("Arxivada").
  5. **Si `entry.status == 'draft'`**: Badge `.d2-corner-badge--draft` amb icona `bi-pencil-fill` ("Esborrany").

### Req 2: Backend Mapping a DashboardController
- El mètode `index()` de `DashboardController` ha de transmetre el booleà `'active' => $entry->isActive()` tant a la consulta principal com al fallback d'entrades recents.
