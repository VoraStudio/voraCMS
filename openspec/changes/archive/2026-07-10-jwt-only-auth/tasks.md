# Tasks: JWT-Only Authentication

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~310 (150 deleted, 100 modified, 60 created) |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-always |

Decision needed before apply: Yes
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Complete JWT-only auth migration | Single PR | All changes interdependent; splitting would break intermediate state |

## Phase 1: Configuration & Foundation

- [x] 1.1 Add `token_ttl: 604800` to `config/packages/lexik_jwt_authentication.yaml`
- [x] 1.2 Replace `custom_authenticators`/`entry_point` with `jwt: ~` in `config/packages/security.yaml` (api firewall)
- [x] 1.3 Remove `app.security.jwt_authenticator` and `App\Security\ApiTokenAuthenticator` aliases from `config/services.yaml`
- [x] 1.4 Remove `$apiToken` property, getter, setter, and ORM column mapping from `src/Entity/User.php`
- [x] 1.5 Remove `findByApiToken()` method from `src/Repository/UserRepository.php`

## Phase 2: Core Implementation

- [x] 2.1 Add `allowed_domains` claim injection in `JwtClientIdSubscriber::onJwtCreated()`
- [x] 2.2 Add `domain` claim validation against `allowed_domains` in `JwtClientIdSubscriber::onJwtDecoded()`
- [x] 2.3 Replace `apiToken` with active JWT echo (`data.token`) in `src/Controller/Api/AuthController.php` `/me` response
- [x] 2.4 Remove `TokenGenerator` injection and `setApiToken()` from `src/Controller/Admin/UserController.php`

## Phase 3: Cleanup

- [x] 3.1 Delete `src/Security/ApiTokenAuthenticator.php`
- [x] 3.2 Delete `src/EventListener/ApiDomainGuardSubscriber.php`
- [x] 3.3 Remove `TokenGenerator` injection and `setApiToken()` from `src/Command/CreateUserCommand.php`
- [x] 3.4 Remove `setApiToken()` calls from `src/DataFixtures/AppFixtures.php`
- [x] 3.5 Generate Doctrine migration to drop `users.api_token` column

## Phase 4: Documentation

- [x] 4.1 Update `templates/admin/api-jwt.html.twig` (TTL to 7 days, remove apiToken comparison table)
- [x] 4.2 Update `templates/admin/api-guide.html.twig` (JWT-only auth examples, token echo in /me)
- [x] 4.3 Update `templates/admin/api-security.html.twig` (JWT-only domain validation flow)
- [x] 4.4 Update `templates/admin/components/_help_faq.html.twig` (JWT-only FAQ for auth)
