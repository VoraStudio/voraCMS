# Verification Report: jwt-only-auth

## Metadata

| Field | Value |
|-------|-------|
| **Change** | `jwt-only-auth` |
| **Mode** | `hybrid` (openspec files + Engram) |
| **Strict TDD** | Inactive (no test infrastructure) |
| **Executor** | `sdd-verify` |
| **Date** | 2026-07-10 |
| **Verdict** | `PASS WITH WARNINGS` |

---

## Executive Summary

The JWT-only authentication migration has been implemented correctly. All 17 tasks are marked complete. All spec requirements are satisfied. Zero references to `apiToken`, `ApiTokenAuthenticator`, `ApiDomainGuardSubscriber`, or related symbols remain in `src/`, `config/`, or `templates/`. Both deleted files are confirmed removed. The migration correctly drops the `users.api_token` column.

**One non-blocking issue**: `src/Service/TokenGenerator.php` — the class that generated random apiTokens — is now dead code with zero consumers. It was not listed in the task list for deletion but no longer serves any purpose.

---

## Artifacts Examined

### Specs
- `openspec/changes/jwt-only-auth/proposal.md`
- `openspec/changes/jwt-only-auth/specs/api-auth/spec.md`
- `openspec/changes/jwt-only-auth/specs/admin-users/spec.md`
- `openspec/changes/jwt-only-auth/specs/api-docs/spec.md`

### Design
- `openspec/changes/jwt-only-auth/design.md`

### Tasks
- `openspec/changes/jwt-only-auth/tasks.md`

### Implementation (config)
- `config/packages/lexik_jwt_authentication.yaml`
- `config/packages/security.yaml`
- `config/services.yaml`

### Implementation (src)
- `src/Entity/User.php`
- `src/Repository/UserRepository.php`
- `src/EventSubscriber/JwtClientIdSubscriber.php`
- `src/Controller/Admin/UserController.php`
- `src/Controller/Api/AuthController.php`
- `src/Controller/Api/EntryController.php`
- `src/Command/CreateUserCommand.php`
- `src/DataFixtures/AppFixtures.php`
- `src/Service/DomainService.php`
- `src/Service/TokenGenerator.php`

### Implementation (templates)
- `templates/admin/api-jwt.html.twig`
- `templates/admin/api-guide.html.twig`
- `templates/admin/api-security.html.twig`
- `templates/admin/components/_help_faq.html.twig`

### Implementation (migrations)
- `migrations/Version20260710090916.php`
- `migrations/Version20260701082931.php` (historic — contains api_token column creation)

---

## Completeness Table

| Task | Status | Notes |
|------|--------|-------|
| 1.1 — `token_ttl: 604800` in lexik_jwt_authentication.yaml | ✅ Done | Verified at line 5 |
| 1.2 — Firewall `api` uses `jwt: ~` | ✅ Done | Lines 29, 36 in security.yaml |
| 1.3 — Remove ApiTokenAuthenticator from services.yaml | ✅ Done | No references remain |
| 1.4 — Remove `$apiToken` from User entity | ✅ Done | Property, getter, setter removed |
| 1.5 — Remove `findByApiToken()` from UserRepository | ✅ Done | Method absent |
| 2.1 — `allowed_domains` injection in onJwtCreated | ✅ Done | Lines 34-41 in JwtClientIdSubscriber |
| 2.2 — `domain` validation in onJwtDecoded | ✅ Done | Lines 47-75 in JwtClientIdSubscriber |
| 2.3 — `/api/auth/me` returns `data.token`, no apiToken | ✅ Done | Lines 40-54 in AuthController |
| 2.4 — Remove TokenGenerator from UserController | ✅ Done | No TokenGenerator in constructor |
| 3.1 — Delete ApiTokenAuthenticator.php | ✅ Done | File confirmed deleted |
| 3.2 — Delete ApiDomainGuardSubscriber.php | ✅ Done | File confirmed deleted |
| 3.3 — Remove TokenGenerator from CreateUserCommand | ✅ Done | No TokenGenerator in constructor |
| 3.4 — Remove setApiToken() from fixtures | ✅ Done | No apiToken calls |
| 3.5 — Generate Doctrine migration | ✅ Done | Version20260710090916.php verified |
| 4.1 — Update api-jwt.html.twig (TTL, no comparison) | ✅ Done | Lines 27, 66-67 |
| 4.2 — Update api-guide.html.twig (JWT-only, token echo) | ✅ Done | Line 97: `data.token` documented |
| 4.3 — Update api-security.html.twig (domain validation flow) | ✅ Done | Steps 3-4 show JWT domain validation |
| 4.4 — Update _help_faq.html.twig (JWT-only FAQ) | ✅ Done | Lines 331-336 reference JWT only |

**Total**: 17/17 tasks complete ✅

---

## Spec Compliance Matrix

### api-auth Compliance

| Scenario | Status | Evidence |
|----------|--------|----------|
| JWT-only: `/api/auth/login` returns JWT | ✅ PASS | `security.yaml` — `api_login` uses `json_login` with `lexik_jwt_authentication.handler.authentication_success` |
| Firewall uses `jwt: ~` | ✅ PASS | `api` firewall: `jwt: ~` at line 36; `api_login` firewall: `jwt: ~` at line 29 |
| Request with valid JWT is authenticated | ✅ PASS | Firewall chain: `jwt: ~` validates bearer token, `access_control` requires `IS_AUTHENTICATED_FULLY` for `/api` |
| Request with legacy apiToken is rejected | ✅ PASS | `ApiTokenAuthenticator.php` deleted; only `jwt: ~` configured; `custom_authenticators` absent |
| Domain validation on `onJwtCreated` | ✅ PASS | `JwtClientIdSubscriber::onJwtCreated` injects `allowed_domains` from user entity |
| Domain validation on `onJwtDecoded` | ✅ PASS | `onJwtDecoded` validates `domain` claim against `allowed_domains`, calls `markAsInvalid()` on mismatch |
| 7-day TTL (exp = iat + 604800) | ✅ PASS | `config/packages/lexik_jwt_authentication.yaml` — `token_ttl: 604800` |
| `/api/auth/me` returns `data.token` | ✅ PASS | `AuthController::me()` extracts Bearer token and returns as `data.token` |
| `/api/auth/me` omits `apiToken` | ✅ PASS | Response keys: `slug`, `token`, `company`, `email`, `name`, `allowedDomains` — no `apiToken` |

### admin-users Compliance

| Scenario | Status | Evidence |
|----------|--------|----------|
| User entity has no `$apiToken` property | ✅ PASS | `User.php` — no `$apiToken`, getter, setter, or ORM mapping |
| Database has no `api_token` column | ✅ PASS | Migration `Version20260710090916.php` drops column; `up()`: `ALTER TABLE users DROP api_token` |
| Admin user creation without apiToken | ✅ PASS | `UserController::new()` — no `TokenGenerator`, no `setApiToken()` |
| No apiToken in form generation | ✅ PASS | `CreateUserCommand` — no `TokenGenerator`, no `setApiToken()` |
| No apiToken in fixtures | ✅ PASS | `AppFixtures::load()` — no `setApiToken()` calls |

### api-docs Compliance

| Scenario | Status | Evidence |
|----------|--------|----------|
| api-guide references only JWT | ✅ PASS | Lines 39-52: Bearer token with JWT, no apiToken mention |
| api-jwt documents 7-day TTL | ✅ PASS | Line 27 tag "Caducitat 7 dies", lines 66-67 explain expiry |
| api-jwt has no comparison table | ✅ PASS | No apiToken/JWT comparison table present |
| /api/auth/me example shows `token` | ✅ PASS | Line 104: `"token": "eyJhbGciOiJSUzI1NiIs..."` |
| /api/auth/me example omits `apiToken` | ✅ PASS | Response object has `slug`, `token`, `company`, `email`, `name`, `allowedDomains` only |
| Authorization examples use JWT placeholder | ✅ PASS | Line 47: `<JWT>`, line 89: `<span class="api-code--highlight">&lt;JWT&gt;</span>` |
| api-security flow diagram uses JWT | ✅ PASS | Steps 3-4: "Autenticació JWT" + "JWT Domain Validation" |
| FAQ references JWT only | ✅ PASS | Lines 331-336: "L'autenticació API es fa amb **JWT**" |

---

## Design Coherence

| Design Decision | Implementation | Status |
|-----------------|---------------|--------|
| Token TTL: 7 days (604800s) | `lexik_jwt_authentication.yaml: token_ttl: 604800` | ✅ Match |
| Domain validation: encode `allowed_domains` in JWT | `JwtClientIdSubscriber::onJwtCreated` + `onJwtDecoded` | ✅ Match |
| Firewall: `jwt: ~` only | `security.yaml` — both `api_login` and `api` use `jwt: ~` | ✅ Match |
| Data migration: drop `api_token` column | `Version20260710090916.php` — `ALTER TABLE users DROP api_token` | ✅ Match |
| Remove `$apiToken` from User entity | `User.php` — no `$apiToken` | ✅ Match |
| Remove `findByApiToken()` | `UserRepository.php` — method absent | ✅ Match |
| Deleted files | Both `ApiTokenAuthenticator.php` and `ApiDomainGuardSubscriber.php` confirmed deleted | ✅ Match |
| `/api/auth/me` payload | Returns `data.token`, no `apiToken` | ✅ Match |

---

## Issues

### CRITICAL (blocking)
None.

### WARNING
None.

### SUGGESTION

| ID | Severity | Description | File |
|----|----------|-------------|------|
| S1 | SUGGESTION | `TokenGenerator.php` is dead code — zero consumers remain after removing apiToken from UserController and CreateUserCommand. The class is annotated with `#[Autoconfigure]`, meaning the DI container will still instantiate it unnecessarily. Should be deleted. | `src/Service/TokenGenerator.php` |

---

## Risks

| Risk | Assessment | Notes |
|------|------------|-------|
| Existing clients using apiToken break | Accepted (Medium/Low) | Proposal defines immediate cutover; all internal clients already use JWT |
| DB migration drops column with active tokens | Accepted (Low) | Column confirmed unused in production |
| Domain validation regression (Origin vs JWT payload) | Mitigated (Low) | `onJwtDecoded` replicates same `domainService->normalize()` logic; allowed_domains sourced identically |
| TokenGenerator.php dead code | Low risk | No functional impact; unused service wastes negligible DI container resources |

---

## Next Recommended Step

**`sdd-archive`** — This change is ready for archival. All tasks are complete, all spec scenarios pass manual verification, and no blocking issues remain. The `TokenGenerator.php` dead file can be removed as a minor cleanup either before or after archival.

---

## Skill Resolution

- **Skill**: `sdd-verify` (executor mode, loaded via `skill()`)
- **References**: `references/report-format.md` used for report structure
- **Mode**: Standard (non-TDD) — verified via source inspection and grep/Select-String analysis
