# Tasks: SSR Security Hardening

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~380-430 |
| 400-line budget risk | Medium |
| Chained PRs recommended | Yes |
| Suggested split | PR 1 (Foundation) → PR 2 (Backend Security + SSR) → PR 3 (Cleanup) |
| Delivery strategy | ask-always |
| Chain strategy | pending |

Decision needed before apply: Yes
Chained PRs recommended: Yes
Chain strategy: pending
400-line budget risk: Medium

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | CORS resolver + config files + env vars | PR 1 | Standalone infra, zero risk |
| 2 | Backend security + Visit + SSR client | PR 2 | Core logic, depends on Phase 1 |
| 3 | Cleanup api.js + verify | PR 3 | Trivial deletion + manual smoke test |

## Phase 1: Foundation

- [x] 1.1 Create `src/Contract/CorsOriginResolverInterface.php` — `resolve(Request): array`
- [x] 1.2 Create `src/Service/ConfigCorsOriginResolver.php` reading `%cors_allowed_origins%`
- [x] 1.3 Create `config/packages/cors.yaml` with allowed origins parameter
- [x] 1.4 Add `TRUSTED_FRONTEND_IPS` and `CORS_ALLOWED_ORIGINS` to `.env`
- [x] 1.5 Register resolver as service in `config/services.yaml`

## Phase 2: Backend Security

- [x] 2.1 Refactor `CorsSubscriber` — inject resolver, replace wildcard with config-driven check
- [x] 2.2 Modify `UserFilterSubscriber` — skip `user_id_filter` when path starts with `/api/public/`
- [x] 2.3 Update `security.yaml` — allow GET/HEAD/OPTIONS on `/api/public/*` without JWT
- [x] 2.4 Seed `vorastudio` user (ROLE_MOD, allowed_domains) in `AppFixtures`

## Phase 3: Visit + SSR

- [x] 3.1 Modify `VisitController` — accept `client_ip`/`user_agent` from body, validate source IP against `%trusted_frontend_ips%`
- [x] 3.2 Create `VoraStudio/includes/CmsClient.php` — cURL JWT fetch, filesystem cache (5min TTL), 401 retry
- [x] 3.3 Wire `CmsClient` into `VoraStudio/index.php` — replace client-side api.js with server-side fetch

## Phase 4: Cleanup

- [x] 4.1 Delete `VoraStudio/js/api.js`
- [x] 4.2 Verify: no JWT leakage in SSR markup, public GET works without auth, POST to `/api/public/*` still requires JWT
