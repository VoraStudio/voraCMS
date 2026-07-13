# Design: JWT-Only Authentication

## Technical Approach

Replace the dual API authenticator with LexikJWT-only authentication. Configure the JWT TTL to 7 days, move domain validation from the request `Origin` header into the signed JWT payload, drop the `api_token` database column, and update all code paths and documentation that referenced `apiToken`.

This is a pure refactor: external API consumers still send `Authorization: Bearer <token>`, but only JWTs are accepted. Domain authorization is now verified at token decode time instead of on every request via `ApiDomainGuardSubscriber`.

## Architecture Decisions

### Decision: Token TTL

| Option | Tradeoff | Decision |
|--------|----------|----------|
| 1 hour (current) | Frequent re-login for long-lived frontends | Rejected |
| 7 days (604800s) | Reasonable UX for SPAs without refresh tokens | **Chosen** |
| 30 days + refresh tokens | Better UX but adds refresh-token storage/complexity | Rejected |

**Rationale**: 7 days balances security and convenience. No refresh-token mechanism is introduced because the project does not want to manage refresh-token storage or rotation. Consumers re-login after expiry.

### Decision: Domain Validation Location

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Keep `ApiDomainGuardSubscriber` checking `Origin` per request | Works, but runs after firewall auth and duplicates user lookup | Rejected |
| Encode `allowed_domains` in JWT and validate on `JWTDecodedEvent` | Self-contained, no per-request DB lookup, works for stateless JWT | **Chosen** |
| Validate inside each controller | Error-prone, easy to miss endpoints | Rejected |

**Rationale**: Moving domain validation into the JWT payload keeps the API fully stateless, removes one subscriber, and binds the allowed-domain check to the token itself.

### Decision: Firewall Authentication

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Keep `custom_authenticators` with `ApiTokenAuthenticator` | Supports both token types; adds maintenance and security surface | Rejected |
| Use `jwt: ~` only | Simple, JWT-only, aligns with the change goal | **Chosen** |

**Rationale**: `jwt: ~` is the standard LexikJWT authenticator. Removing `ApiTokenAuthenticator` eliminates the deprecated path and its service alias wiring.

### Decision: Data Migration

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Generate Doctrine migration to drop `api_token` column | Clean schema; irreversible data loss | **Chosen** |
| Keep column but stop using it | Leaves dead data, complicates future refactors | Rejected |

**Rationale**: The column is confirmed unused in production. Dropping it enforces the JWT-only contract.

### Decision: Grace Period

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Immediate cutover | Simple implementation; existing apiToken clients break | **Chosen** |
| Grace period accepting both tokens | Safer rollout but prolongs dual-auth maintenance | Rejected |

**Rationale**: All internal clients already use JWT. The proposal explicitly excludes a grace period.

## Data Flow

### Before (dual auth)

```
Client
  │ Authorization: Bearer <apiToken|jwt>
  ▼
RequestEvent ──► ApiDomainGuardSubscriber ──► validates Origin vs allowed_domains
  │
  ▼
ApiTokenAuthenticator
  │─ is JWT? ──► LexikJWTAuthenticator (validates signature/exp/user)
  └─ is apiToken? ──► UserRepository::findByApiToken() lookup
  │
  ▼
Controller
```

### After (JWT only)

```
Client
  │ Authorization: Bearer <jwt>
  ▼
JWT firewall (jwt: ~)
  │
  ▼
JwtClientIdSubscriber::onJwtDecoded
  │─ signature/exp valid? (LexikJWT)
  └─ domain claim in allowed_domains claim? (new validation)
  │
  ▼
Controller
```

On login, `JwtClientIdSubscriber::onJwtCreated` injects `allowed_domains` and `domain` into the payload. On every subsequent request, `onJwtDecoded` rejects tokens whose `domain` claim is not in `allowed_domains`.

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `config/packages/lexik_jwt_authentication.yaml` | Modify | Add `token_ttl: 604800` |
| `config/packages/security.yaml` | Modify | Replace `api` firewall `custom_authenticators`/`entry_point` with `jwt: ~` |
| `config/services.yaml` | Modify | Remove `app.security.jwt_authenticator` alias and `App\Security\ApiTokenAuthenticator` service definition |
| `src/Security/ApiTokenAuthenticator.php` | Delete | No longer needed |
| `src/EventListener/ApiDomainGuardSubscriber.php` | Delete | Domain validation moves to JWT payload |
| `src/Entity/User.php` | Modify | Remove `$apiToken` property, getter, setter, and ORM mapping |
| `src/Repository/UserRepository.php` | Modify | Remove `findByApiToken()` |
| `src/EventSubscriber/JwtClientIdSubscriber.php` | Modify | Add `allowed_domains` on created; validate `domain` claim on decoded |
| `src/Controller/Admin/UserController.php` | Modify | Remove `TokenGenerator` injection and `setApiToken()` call in `new()` |
| `src/Controller/Api/AuthController.php` | Modify | Remove `apiToken` from `/api/auth/me`; echo active JWT in `data.token` |
| `src/Command/CreateUserCommand.php` | Modify | Remove `TokenGenerator` injection and `setApiToken()` call |
| `src/DataFixtures/AppFixtures.php` | Modify | Remove `setApiToken()` calls |
| `migrations/Version{timestamp}.php` | Create | Drop `users.api_token` column |
| `templates/admin/api-jwt.html.twig` | Modify | Update TTL to 7 days, remove apiToken comparison table |
| `templates/admin/api-guide.html.twig` | Modify | JWT-only auth examples, token echo documentation |
| `templates/admin/api-security.html.twig` | Modify | Update flow diagram to JWT-only domain validation |
| `templates/admin/components/_help_faq.html.twig` | Modify | Update user-form FAQ to JWT-only |

## Interfaces / Contracts

### JWT Payload Contract

```json
{
  "iat": 1752000000,
  "exp": 1752604800,
  "username": "user@example.com",
  "user_id": 42,
  "user_slug": "user-slug",
  "domain": "example.com",
  "allowed_domains": ["example.com", "www.example.com"]
}
```

- `exp` MUST equal `iat + 604800`.
- `domain` is the normalized host used at login.
- `allowed_domains` contains the user's configured domains at login time.

### `/api/auth/me` Response Contract

```json
{
  "data": {
    "slug": "user-slug",
    "company": "Company",
    "email": "user@example.com",
    "name": "User Name",
    "allowedDomains": ["example.com"],
    "token": "<active-jwt>"
  }
}
```

`apiToken` is removed; `token` echoes the active JWT from the `Authorization` header.

## Testing Strategy

No test infrastructure exists yet (`openspec/config.yaml` marks all layers unavailable). Verification will be manual:

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | `DomainService::normalize()` edge cases | Manual ad-hoc checks |
| Integration | `POST /api/auth/login` returns JWT with correct `exp`, `domain`, `allowed_domains` | HTTP client / curl |
| Integration | `GET /api/auth/me` returns `data.token` and no `apiToken` | HTTP client / curl |
| Integration | Legacy `apiToken` in `Authorization: Bearer` returns 401 | HTTP client / curl |
| Integration | Request from unauthorized domain returns 401 | HTTP client / curl with forged `domain` claim |
| Migration | `users.api_token` column dropped, `doctrine:schema:validate` passes | Run migration and validate schema |

When test infrastructure is added later, these scenarios should become automated regression tests.

## Migration / Rollout

1. Run the generated Doctrine migration to drop `users.api_token`.
2. Deploy code changes in a single commit.
3. Clear Symfony cache (`cache:clear`).
4. Verify `POST /api/auth/login`, `GET /api/auth/me`, and a protected `/api/*` endpoint.
5. Notify consumers that legacy apiTokens are no longer valid; direct them to obtain a JWT via login.

**Rollback**: Revert the deploy commit, run the migration's `down()` method to restore the column, and clear cache.

## Open Questions

- [ ] Should we expose a new admin UI field to edit `allowed_domains`? The entity already supports it; current templates may need verification.
- [ ] Are there any external clients still holding active apiTokens that need proactive notification before deploy?
