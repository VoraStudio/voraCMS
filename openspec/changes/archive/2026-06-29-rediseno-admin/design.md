# Design: rediseno-admin

## Technical Approach

### CSS Architecture
- All action button styles live in admin.css under `/* ─── Action Buttons ─── Minimalist Corporate ─── */` (dark) and `[data-theme="light"] ...` (light)
- Row inactive styles at `/* ─── Active/inactive row distinction ─── */`
- Projects glow reduced in `.cyber-btn--projects` and `.cyber-btn--projects:hover` blocks

### Template Architecture
- **project/index.html.twig**: Iterates projects sorted by user name + project name. Uses `{% set currentUserId %}` to detect user changes and render group headers. Table rows have `onclick` for navigation + `event.stopPropagation()` on actions cell.
- **dashboard.html.twig**: 3 cyber-card columns with thin lists. Each item is an `<a>` with hover background transition. Uses `findBy([], ['createdAt' => 'DESC'], 5)` for each entity type.

### Controller Changes
- DashboardController: passes `latestUsers`, `latestProjects`, `latestContentTypes` (each via `findBy` with limit)
- ProjectController: uses `findAllOrderedByUser()` instead of `findActive()`
- ProjectRepository: new `findAllOrderedByUser()` with LEFT JOIN user + ORDER BY user.name, p.name

### Visual Decision: Toggle Active
Toggle active button keeps green tint rgba(34,197,94,0.75) by default to visually indicate ON state without being flashy. On hover: background rgba(34,197,94,0.08) + solid green #22c55e.

### Open Questions
- Should projects toggle use AJAX like users toggle? (Currently form submit with reload)
