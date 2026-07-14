# Design: SSR Security Hardening

## Technical Approach

Replace wildcard CORS with a config-driven resolver, tighten public API authentication so public reads are anonymous but public writes require a restricted `ROLE_MOD` token, seed a dedicated `vorastudio` user for that token, and move all JWT handling from the VoraStudio browser to the server-side PHP entry point using cURL (CDMON disables `proc_open` and likely `file_get_contents`).

## Architecture Decisions

| Decision | Options | Tradeoff | Choice |
|---|---|---|---|
| CORS origin resolution | Hard-coded array in subscriber vs. resolver interface + config | Interface adds one file but keeps subscriber testable and origins configurable per environment | `CorsOriginResolverInterface` + `ConfigCorsOriginResolver` reading `%cors_allowed_origins%` |
| Public read auth | Require JWT for everything vs. allow safe methods on `/api/public/*` | Safe methods public enable SSR without exposing credentials to browsers; writes still need JWT | `security.yaml` allows `GET/HEAD/OPTIONS` on `/api/public/*` publicly; other methods require JWT |
| User-filter bypass | Role-based (ROLE_ADMIN) vs. path-based (`/api/public/*`) | Path-based matches the public-route contract and avoids giving a non-admin public user implicit admin visibility | `UserFilterSubscriber` skips `user_id_filter` when path starts with `/api/public/` |
| Token identity for public frontend | Reuse admin user vs. seed dedicated `vorastudio` ROLE_MOD user | Dedicated user limits blast radius if token leaks and satisfies the no-admin requirement | Seed `vorastudio` user in `AppFixtures` with `ROLE_MOD` and allowed domains |
| SSR HTTP client | `file_get_contents()` vs. `curl_*` | `file_get_contents` is simpler but likely disabled on CDMON; cURL is reliably available | Pure `curl_*` implementation in a helper class |
| Visit IP source | Always trust `Request::getClientIp()` vs. accept `client_ip` from trusted SSR only | Accepting body IP allows SSR to proxy real visitor IPs while rejecting direct browser calls | `VisitController` accepts `client_ip`/`user_agent` from JSON only when source IP is in `%trusted_frontend_ips%`; otherwise 403 |
| Token cache storage | Server filesystem vs. APCu/Redis | Filesystem works on shared hosting without extra extensions; TTL is checked via file mtime | `.jwt_cache` file with 5-minute TTL and 401 retry logic |

## Data Flow

```
Browser ──► VoraStudio/index.php
                │
                ├─ curl GET /api/public/token ──► VoraCMS
                │        Origin: https://vorastudio.cat
                │        CorsSubscriber checks ConfigCorsOriginResolver
                │        TokenMasterService finds vorastudio user → JWT
                │
                ├─ cache JWT in .jwt_cache (5 min)
                │
                ├─ curl GET /api/public/{project}/{type}
                │        Authorization: Bearer <JWT>
                │        Origin: https://vorastudio.cat
                │
                ├─ render HTML via echo
                │
                └─ (page load) curl POST /api/visit
                         Authorization: Bearer <JWT>
                         body: { entry_id, path, client_ip, user_agent }
                         VisitController checks trusted source IP
```

## File Changes

| File | Action | Description |
|---|---|---|
| `src/Contract/CorsOriginResolverInterface.php` | Create | `resolve(Request $request): array` contract |
| `src/Service/ConfigCorsOriginResolver.php` | Create | Reads `%cors_allowed_origins%` parameter |
| `src/EventSubscriber/CorsSubscriber.php` | Modify | Inject resolver; mirror allowed origin; 403 on disallowed origin; keep preflight handling |
| `config/packages/cors.yaml` | Create | `%cors_allowed_origins%` list and framework parameter |
| `.env` / `.env.local` | Modify | Add `TRUSTED_FRONTEND_IPS=` and `CORS_ALLOWED_ORIGINS=` (fallback for local) |
| `src/EventSubscriber/UserFilterSubscriber.php` | Modify | Skip filter for paths starting with `/api/public/` |
| `config/packages/security.yaml` | Modify | Allow `GET/HEAD/OPTIONS` on `/api/public/*` publicly; keep `/api/public/token` public; all other `/api/*` requires JWT |
| `src/DataFixtures/AppFixtures.php` | Modify | Seed `vorastudio` user (`ROLE_MOD`, allowed domains) |
| `src/Controller/Api/VisitController.php` | Modify | Accept `client_ip` and `user_agent` from JSON when source IP is trusted; 403 otherwise |
| `config/services.yaml` | Modify | Bind `%trusted_frontend_ips%` from env |
| `VoraStudio/includes/CmsClient.php` | Create | cURL-based token fetch/cache/retry and content fetch helper |
| `VoraStudio/index.php` | Modify | Include `CmsClient`; fetch content server-side; remove `<script src="js/api.js">` |
| `VoraStudio/js/api.js` | Delete | No longer needed; tokens never stored client-side |

## Interfaces / Contracts

```php
namespace App\Contract;

use Symfony\Component\HttpFoundation\Request;

interface CorsOriginResolverInterface
{
    /** @return string[] */
    public function resolve(Request $request): array;
}
```

```php
// VoraStudio/includes/CmsClient.php
class CmsClient
{
    public function getToken(): ?string;          // fetch or return cached JWT
    public function fetch(string $path): ?array;  // GET with Authorization + Origin
    public function post(string $path, array $body): bool;
}
```

## Testing Strategy

The project has no automated test runner configured (`strict_tdd: false`). Verification will be manual:

| Layer | What to Test | Approach |
|---|---|---|
| Unit | `ConfigCorsOriginResolver`, `CmsClient` cache TTL, domain normalization | Ad-hoc PHP scripts or direct code review |
| Integration | Firewall public-read/public-write rules, CORS 403/allow, token issuance for vorastudio | `curl` commands against local VoraCMS |
| E2E | VoraStudio page renders without `api.js`, no JWT in HTML, visit records real visitor IP/UA | Browser dev tools + database inspection |

## Migration / Rollout

1. Run `php bin/console doctrine:fixtures:load --append` (or a new migration) to add the `vorastudio` user in production.
2. Add `CORS_ALLOWED_ORIGINS` and `TRUSTED_FRONTEND_IPS` to production `.env.local` before deployment.
3. Deploy VoraCMS first, then VoraStudio frontend.
4. After rollout, rotate JWT keys if any legacy browser token may have been exposed.

## Resolved Questions

- **Routes**: Use existing `/api/public/{project}/{type}` and `/api/visit`. No new routes needed. Spec shorthand clarified.
- **Production SSR IP**: Resolved as `134.0.10.83` (vorastudio.cat A record). Should be confirmed by running `curl https://api.ipify.org` from the production server.
- **SSR scope**: VoraStudio `index.php` remains static. The SSR infrastructure (`CmsClient`, JWT caching) is prepared as a reusable layer for future client projects.
- **JWT TTL**: Keep `token_ttl: 3600` (1 hour). The SSR cache layer handles automatic renewal. The 7-day value in the main api-auth spec is superseded by this decision and should be updated.
