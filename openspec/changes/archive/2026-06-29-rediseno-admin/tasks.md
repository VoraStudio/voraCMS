# Tasks: rediseno-admin

## Review Workload Forecast
| Field | Value |
|-------|-------|
| Estimated changed lines | ~350 (7 files) |
| 400-line budget risk | Low |
| Chained PRs recommended | No |

## Tasks

1. [x] **admin.css: Rediseñar action buttons** — Reemplazar glassmorphism/neón por estilo minimalista corporate (dark + light mode)
2. [x] **admin.css: Aumentar visibilidad filas inactivas** — Opacidad 0.55, bg rojo rgba(239,68,68,0.10), celdas opacity 0.20
3. [x] **admin.css: Reducir glow botón Projectes** — box-shadow reducido
4. [x] **styles.md: Actualizar guía de estilo** — Separar icon buttons / text buttons, nueva paleta corporate
5. [x] **ProjectRepository: Añadir findAllOrderedByUser** — LEFT JOIN user + ORDER BY user.name, p.name
6. [x] **ProjectController: Usar findAllOrderedByUser** — Cambiar findActive por nuevo método
7. [x] **project/index.html.twig: Agrupar por cliente** — Tabla con headers de usuario, filas clickeables
8. [x] **DashboardController: Pasar últimos registros** — latestUsers, latestProjects, latestContentTypes
9. [x] **dashboard.html.twig: Añadir 3 columnas últimos registros** — Cards con listas clickeables

## Pendientes (no planificados)
- Convertir toggle de proyectos a AJAX (como el de usuarios)
- Añadir animaciones GSAP a las nuevas secciones del dashboard
