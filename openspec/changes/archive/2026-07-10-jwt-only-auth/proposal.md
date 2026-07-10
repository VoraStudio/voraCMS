# Proposal: JWT-Only Authentication

## Intent

Eliminate dual auth (apiToken + JWT). JWT is more secure (RSA-signed, no DB lookup per request, self-expiring tokens). This simplifies the codebase, removes a deprecated auth path, and moves domain validation into the signed JWT payload.

## Scope

### In Scope
- Configure JWT TTL to 7 days, set firewall to jwt-only, remove service aliases
- Delete ApiTokenAuthenticator, ApiDomainGuardSubscriber, findByApiToken()
- Remove `$apiToken` property from User entity + migration to drop column
- Move domain validation from Origin header to JWT payload (JwtClientIdSubscriber)
- Strip apiToken generation from UserController, CreateUserCommand, AppFixtures
- Remove apiToken from `/api/auth/me` response
- Update 4 documentation Twig templates

### Out of Scope
- Grace period for apiToken users (cutover is immediate)
- Session-based admin panel login (unchanged, uses `form_login`)
- API version bump

## Capabilities

### New Capabilities
None — no new external-facing capability is introduced.

### Modified Capabilities
None — pure implementation refactor. No spec-level behavior changes. Auth flows are identical from the API consumer's perspective.

## Approach

1. Configure TTL 604800 in `lexik_jwt_authentication.yaml`. Set firewall `api` to `jwt: ~`. Remove ApiTokenAuthenticator references from `security.yaml` and `services.yaml`.
2. Delete `ApiTokenAuthenticator.php`, `ApiDomainGuardSubscriber.php`, `findByApiToken()` from UserRepository.
3. Remove `$apiToken` property from User entity. Generate Doctrine migration to drop the column.
4. Rewire `JwtClientIdSubscriber`: onJwtCreated injects `allowed_domains` into token; onJwtDecoded validates `domain` claim against `allowed_domains`.
5. Strip apiToken from `UserController::new()`, `CreateUserCommand`, `AppFixtures`.
6. Remove `apiToken` from `/api/auth/me` response in AuthController.
7. Update 4 Twig documentation templates to JWT-only flow.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `config/packages/lexik_jwt_authentication.yaml` | Modified | TTL → 604800 |
| `config/packages/security.yaml` | Modified | Firewall api → JWT only |
| `config/services.yaml` | Modified | Remove ApiTokenAuthenticator alias |
| `src/Security/ApiTokenAuthenticator.php` | Removed | Delete file |
| `src/EventListener/ApiDomainGuardSubscriber.php` | Removed | Delete file |
| `src/Entity/User.php` | Modified | Remove `$apiToken` + getter/setter |
| `src/Repository/UserRepository.php` | Modified | Remove `findByApiToken()` |
| `src/EventSubscriber/JwtClientIdSubscriber.php` | Modified | Add allowed_domains to payload |
| `src/Controller/Admin/UserController.php` | Modified | Remove apiToken from new() |
| `src/Controller/Api/AuthController.php` | Modified | Remove apiToken from /me |
| `src/Command/CreateUserCommand.php` | Modified | Remove apiToken generation |
| `src/DataFixtures/AppFixtures.php` | Modified | Remove apiToken generation |
| `migrations/` | New | Drop user.api_token column |
| `templates/admin/api-jwt.html.twig` | Modified | JWT-only flow docs |
| `templates/admin/api-guide.html.twig` | Modified | JWT-only examples |
| `templates/admin/api-security.html.twig` | Modified | JWT-only security |
| `templates/admin/_help_faq.html.twig` | Modified | Update auth FAQ |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Existing clients using apiToken break | Medium | All internal clients already use JWT; external consumers notified; documented in release notes |
| DB migration drops column with active tokens | Low | apiToken is unused in production; no active sessions depend on it |
| Domain validation regression (Origin vs JWT payload) | Low | onJwtDecoded subscriber replicates same logic; allowed_domains sourced identically at token creation |

## Rollback Plan

Revert all changed files: restore `$apiToken` property + migration rollback, restore deleted authenticator/subscriber, revert config files (`security.yaml`, `services.yaml`, `lexik_jwt_authentication.yaml`), restore apiToken generation in controllers/command/fixtures. Deploy as a single revert commit.

## Dependencies

- None (self-contained — all changes within this project)

## Success Criteria

- [ ] `POST /api/auth/login` returns JWT without `apiToken` in body
- [ ] `GET /api/auth/me` returns user data without `apiToken` field
- [ ] All API endpoints authenticate via JWT Bearer token only
- [ ] Domain validation passes for allowed domains, rejects unauthorized
- [ ] User entity has no `$apiToken` property; DB column dropped
- [ ] Zero references to `apiToken` remain in config, src, or fixtures
- [ ] 4 Twig documentation templates updated to JWT-only flow
