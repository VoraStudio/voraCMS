# Design: Roles & Permissions

## 1. Technical Approach

**Approach 1 (chosen):** A join entity `UserProject` carrying a single boolean permission (`canManageContentTypes`) + a centralized `ProjectVoter` (subject = `Project`).

**Why not the alternatives**

| Option | Tradeoff | Verdict |
|---|---|---|
| Symfony ACL (`AccessDecisionManager` + ACE rows) | Full ACL granularity, but heavy: schema bloat, query cost, and we have only one boolean to model | Rejected: overkill |
| Roles as JSON inside `User` | No referential integrity, no way to revoke a single project's permission cleanly | Rejected: unmaintainable |
| Groups per project | One indirection per project; still need a boolean per (user, project) | Rejected: same cost, more code |
| **Join entity + Voter** | One row per (user, project), Voter returns a decision per attribute (`MANAGE_CT`, `VIEW`), hierarchy handles role inheritance | **Chosen** |

The Voter is the single source of truth for "can this user do X on this project". Controllers stay thin (`isGranted(...)`), templates render conditionally (`is_granted(...)`), and role hierarchy gives us free composition (ADMIN > MOD > USUARIO).

## 2. Schema (Doctrine)

### New entity: `UserProject`

| Field | Mapping | Notes |
|---|---|---|
| `id` | `int` autoincrement | PK |
| `user` | `ManyToOne(User)` `nullable=false` `inversedBy=projectPermissions` | owning side, FK index |
| `project` | `ManyToOne(Project)` `nullable=false` `inversedBy=userPermissions` | owning side, FK index |
| `canManageContentTypes` | `boolean` `default=false` `options={'default': false}` | the only business permission we need today |
| unique | `UniqueConstraint(name=user_project_unique, columns=[user_id, project_id])` | prevents duplicates |

Constructor initializes the `Collection` inverse side. `addUserProject()` / `removeUserProject()` follow the existing pattern from `Client::addUser()`.

### Inverse side additions

- `User::projectPermissions` — `OneToMany(targetEntity=UserProject, mappedBy=user, cascade=[remove])`
- `Project::userPermissions` — `OneToMany(targetEntity=UserProject, mappedBy=project, cascade=[remove])`
- `Client::projects` — `OneToMany(targetEntity=Project, mappedBy=client)` (currently only the owning side exists on `Project`)

## 3. `ProjectVoter` (subject = `Project`)

Attributes: `MANAGE_CT`, `VIEW`.

| Role | `VIEW` | `MANAGE_CT` |
|---|---|---|
| `ROLE_ADMIN` | grant (bypass) | grant (bypass) |
| `ROLE_MOD` + `UserProject.canManageContentTypes=true` | grant | grant |
| `ROLE_MOD` (no row or `false`) | grant (default) | deny |
| `ROLE_USUARIO` + `UserProject.canManageContentTypes=true` | grant | grant |
| `ROLE_USUARIO` (no row) | **deny** | deny |

Decision rules:

1. If user has `ROLE_ADMIN` → grant both, return.
2. Look up `UserProject` for `(user, project)`. If none exists → deny `MANAGE_CT`; for `VIEW` deny for `ROLE_USUARIO`, grant for `ROLE_MOD`.
3. If `UserProject` exists → grant `MANAGE_CT` iff `canManageContentTypes===true`; grant `VIEW` always (a non-ADMIN user only sees a project they have a row for, by construction).
4. Return `ACCESS_DENIED` as safe default.

Repository method on `UserProjectRepository`: `findOneByUserAndProject(User $u, Project $p): ?UserProject`.

## 4. Controller changes

### `ContentTypeController`

| Method | Today | After |
|---|---|---|
| `index()` | `denyAccessUnlessGranted('ROLE_MOD')` | `denyAccessUnlessGranted('MANAGE_CT', $ct->getProject())` per CT in the loop — or `isGranted('VIEW', $project)` to list + per-item `MANAGE_CT` for new/edit/delete buttons |
| `new()` | `denyAccessUnlessGranted('ROLE_ADMIN')` | `isGranted('MANAGE_CT', $project)` |
| `edit()` / `delete()` | role check + `verifyOwnership` | `isGranted('MANAGE_CT', $contentType->getProject())` + keep `verifyOwnership()` for tenant isolation (defense in depth) |

`new()` already assigns `$ct->setProject($project)` — no change there.

### `EntryController`

Add at the top of every action: `denyAccessUnlessGranted('ROLE_USUARIO')`. Additionally, `byType` and `new` add `isGranted('VIEW', $contentType->getProject())` and `edit`/`delete` add `isGranted('VIEW', $entry->getContentType()->getProject())`. Keep the existing `verifyEntryOwnership` and `verifyContentTypeOwnership` for tenant isolation.

### `ProjectController`

`index`/`new`/`edit`/`delete` get `denyAccessUnlessGranted('ROLE_ADMIN')` at the top. The `edit` action, in addition, accepts a `users[][user_id]` + `users[][can_manage_content_types]` POST payload and upserts `UserProject` rows scoped to the project's client. CSRF token: `isCsrfTokenValid('user-permissions-' . $project->getId(), ...)`.

## 5. Template changes

### `layout.html.twig`

| Section | Condition |
|---|---|
| "Tipus de contingut" | `is_granted('MANAGE_CT', activeProject)` |
| "Clients" (already shown) | `is_granted('ROLE_ADMIN')` (was `ROLE_SUPER_ADMIN`) |
| "Projectes" sublist | new Twig function `admin_visible_projects()` that returns projects where `is_granted('VIEW', p)`; if user is `ROLE_ADMIN`, returns all client projects, else joins from `user.projectPermissions` |
| "Usuaris" link | `is_granted('ROLE_ADMIN')` (new menu entry pointing to a future `admin_user_index` — for R7 only the project-scoped assignment is in scope) |

### `project/form.html.twig` (renamed conceptually to `edit.html.twig` per the brief)

Add a `Permisos d'usuaris` section after the existing fields: list the project's client users, each with a checkbox `can_manage_content_types[user_id]`. Single POST to the same action reuses CSRF.

### New partial: `templates/admin/project/_users.html.twig`

Iterates `$client->getUsers()`, renders the checkbox grid, posts the array. Reused by `edit` and (later) by a per-user screen.

## 6. Security config

`config/packages/security.yaml`:

```yaml
role_hierarchy:
    ROLE_ADMIN:    [ROLE_MOD, ROLE_USUARIO]
    ROLE_MOD:      ROLE_USUARIO

access_control:
    - { path: ^/admin/login, roles: PUBLIC_ACCESS }
    - { path: ^/admin,       roles: ROLE_USUARIO }
```

`ROLE_SUPER_ADMIN` is removed from the hierarchy; existing rows in DB are rewritten by the migration.

## 7. Migration plan

Single migration `VersionXXXX_roles_permissions.php`:

1. `CREATE TABLE user_project` (id, user_id FK, project_id FK, can_manage_content_types BOOLEAN DEFAULT 0, UNIQUE(user_id, project_id), indexes on both FKs).
2. `UPDATE users SET roles = REPLACE(...)` to substitute `ROLE_SUPER_ADMIN` → `ROLE_ADMIN` and `ROLE_USER` → `ROLE_USUARIO` in the stored JSON. For rows holding both `ROLE_USER` and `ROLE_ADMIN`, normalize to `["ROLE_ADMIN"]` (one of the few times we touch JSON in SQL — do it via a small PHP script in the migration if the DB lacks JSON functions; SQLite has `json_replace`).
3. `down()` drops `user_project`.

Code changes (no migration needed, just deploy with the migration):

- `User::getRoles()`: replace the hard-coded `'ROLE_USER'` with `'ROLE_USUARIO'`.
- `ClientScope::isSuperAdmin()`: check `ROLE_ADMIN`. Keep the method name (or add `isAdmin()` as alias) — it is called in 6 places; renaming is mechanical but optional.

`AppFixtures`: change the seed user to `['ROLE_ADMIN']` and add a `ROLE_USUARIO` example user with one `UserProject` row pointing at the seed project so the "Can manage CT" toggle is observable on first boot.

## 8. Files affected

**New**

- `src/Entity/UserProject.php`
- `src/Repository/UserProjectRepository.php`
- `src/Security/Voter/ProjectVoter.php`
- `templates/admin/project/_users.html.twig`
- `migrations/VersionXXXX_roles_permissions.php`

**Modified**

- `src/Entity/User.php` — add `projectPermissions`, change `getRoles()` sentinel.
- `src/Entity/Project.php` — add `userPermissions`.
- `src/Entity/Client.php` — add `projects` inverse.
- `src/Controller/Admin/ContentTypeController.php` — replace role gates with Voter calls.
- `src/Controller/Admin/EntryController.php` — add `ROLE_USUARIO` gate + `VIEW` checks.
- `src/Controller/Admin/ProjectController.php` — add `ROLE_ADMIN` gate + users permission handler.
- `src/Service/ClientScope.php` — update `isSuperAdmin()` semantics (now means "has ROLE_ADMIN").
- `src/Twig/AdminExtension.php` — add `admin_visible_projects()`.
- `templates/admin/layout.html.twig` — conditional sidebar items.
- `templates/admin/project/form.html.twig` (or `edit.html.twig`) — embed `_users.html.twig`.
- `src/DataFixtures/AppFixtures.php` — update seed roles + add `UserProject` example.
- `config/packages/security.yaml` — new hierarchy, new access_control.
- `docs/security.md` — note the role model change (low priority).

## 9. Risks & open questions

| # | Risk / question | Mitigation |
|---|---|---|
| R1 | `User::getRoles()` silently auto-appends a role. If we forget to swap `'ROLE_USER'` → `'ROLE_USUARIO'`, migrated users with only `ROLE_USUARIO` stored will be granted `ROLE_USER` by `getRoles()` and the new access_control will still pass (because USUARIO is in the hierarchy for MOD/ADMIN) — but raw `is_granted('ROLE_USUARIO')` will fail for ADMIN users because their stored role is `ROLE_ADMIN` (and hierarchy grants USUARIO, so it works) | Triple-check this change in the PR. Add a unit-style smoke check in fixtures: after seeding, verify `getRoles()` contains `ROLE_USUARIO`. |
| R2 | Stale `_project_id` in session: a non-ADMIN user with a stale session pointing at a project they no longer have VIEW on can still see the page until Voter denies | Voter must be called on every action that loads the active project. Don't trust session state. |
| R3 | `ContentType.base` is the existing field for "visible in all projects". The brief says base CTs are visible everywhere. The current `admin_content_types()` filters by `project_id` — base CTs are not surfaced. | Extend `ContentTypeRepository::findActive($projectId)` to also return CTs where `base=true` and `client_id=...`. Cheap and contained. |
| R4 | The "Clients" menu entry in `layout.html.twig` uses `is_granted('ROLE_SUPER_ADMIN')`. After the change it should be `ROLE_ADMIN`. Same for the comment in `ClientController`. | Sweep `grep ROLE_SUPER_ADMIN src/` and replace. |
| R5 | `UserProject` exposes a way for an admin to grant a `ROLE_MOD` user `canManageContentTypes=true`. The brief's table says MOD can manage CTs in the project by default if no row exists. Confirm the desired UX: empty row = "no override, role default" or "deny". | Per the brief, deny-on-empty for `USUARIO` and grant-on-empty for `MOD` — implemented in Voter step 2. If the product wants deny-on-empty for MOD too, the Voter flips in one place. |
| R6 | Entry `client_id` direct reference (defense in depth) stays. But the Voter needs the `Project`, not the `Entry`. Chain: `Entry -> ContentType -> Project`. | Documented in design; controllers do `$entry->getContentType()->getProject()`. |
| R7 | No PHPUnit. Verification will be manual (load fixtures, log in as each role, exercise CRUD). | Acceptable for now; flagged in `sdd-verify` later. |
| Q1 | Should `ROLE_MOD` users be able to see *all* projects of the client, or only the ones they're assigned to? The brief says "Solo asignados" for both MOD and USUARIO in the matrix, but `MOD` has more latitude. | Confirm with Pau before `sdd-tasks` if not in the spec. |

## 10. Next step

Ready for `sdd-tasks`. The work decomposes naturally into 5 tasks: (1) entity + migration, (2) Voter, (3) controllers wiring, (4) sidebar + project edit form, (5) fixtures + security.yaml + smoke check.
