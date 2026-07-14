# Proposal: SSR + Security Hardening

## Intent

Three confirmed vulnerabilities — Origin spoofing (JWT delivered without real credentials), info disclosure (admin email, roles, allowed_domains in sessionStorage), and ROLE_ADMIN bypass (UserFilterSubscriber skips multi-tenant filter) — demand moving VoraStudio frontend from client-side JWT to server-side rendering, creating a restricted ROLE_MOD user for public tokens, and hardening CORS.

## Scope

### In Scope
- Create "vorastudio" user with ROLE_MOD for public JWT tokens (not ROLE_ADMIN)
- Refactor CorsSubscriber → injectable CorsOriginResolver service, replace wildcard
- Modify UserFilterSubscriber: skip user_id_filter on `/api/public/*` routes, not on role
- VoraStudio/index.php: server-side curl calls to CMS with JWT, render HTML, cache JWT
- Remove api.js from VoraStudio frontend (no client-side JWT)
- VisitController: accept client_ip/user_agent from JSON body, validate by trusted server IPs

### Out of Scope
- Other client frontends (Raymel, Victoria Taylor, etc.) — unchanged
- Full API authentication overhaul
- Admin panel login changes
- PHPUnit tests (manual verification only)

## Capabilities

### New Capabilities
- `ssr-frontend`: Server-side rendering pipeline for VoraStudio, caching JWT, rendering API data into Twig/HTML
- `cors-origin-service`: Configurable service that resolves allowed origins per client/domain

### Modified Capabilities
- `api-auth`: JWT payload roles change from ROLE_ADMIN to ROLE_MOD for public tokens; new skip-user-filter rule per route prefix
- `admin-users`: New "vorastudio" seed user with ROLE_MOD, no admin privileges

## Approach

1. **vorastudio user**: Symfony seed command + migration. ROLE_MOD, allowed_domains = [vorastudio.cat, localhost]. Kept in fixtures.
2. **CorsOriginResolver**: Interface + implementation returning origins from config (yaml/env). CorsSubscriber injects it, resolves per Origin header. Default: empty list (no CORS).
3. **UserFilterSubscriber**: Add route prefix check — if path starts with `/api/public/`, skip filter. Published content must be readable regardless of user.
4. **VoraStudio SSR**: index.php cURL to `/api/public/token` with correct Origin → gets JWT (5-min cache). Server-side cURL to content endpoints with JWT. Renders full HTML. `api.js` removed.
5. **VisitController**: Accept `client_ip`, `user_agent` in JSON body. Validate request IP against `TRUSTED_FRONTEND_IPS` env array. Fall back to current behavior for direct browser requests.
6. **Cleanup**: Remove api.js, update CSP headers, remove sessionStorage references.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `voracms/src/EventSubscriber/CorsSubscriber.php` | Modified | Inject CorsOriginResolver |
| `voracms/src/Service/CorsOriginResolver.php` | New | Configurable origin resolution |
| `voracms/src/EventSubscriber/UserFilterSubscriber.php` | Modified | Skip filter on `/api/public/*` |
| `voracms/src/Controller/Api/VisitController.php` | Modified | Accept body IP/UA |
| `voracms/src/DataFixtures/AppFixtures.php` | Modified | Seed vorastudio user |
| `voracms/config/packages/security.yaml` | Modified | Optional: add ROLE_API if needed |
| `VoraStudio/index.php` | Modified | Add SSR curl + render logic |
| `VoraStudio/js/api.js` | Removed | Remove client-side JWT |
| `voracms/.env` | Modified | TRUSTED_FRONTEND_IPS, CORS origins |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| UserFilterSubscriber change breaks other client frontends | Low | Only `/api/public/*` routes affected; other client tokens are ROLE_ADMIN and bypass anyway |
| VoraStudio SSR JWT expired mid-session (5-min cache) | Low | Graceful fallback: return 401, re-fetch token server-side, retry |
| TRUSTED_FRONTEND_IPS misconfigured blocks real visits | Medium | Validate in staging before prod; log rejections |

## Rollback Plan

1. Revert UserFilterSubscriber to role-only check
2. Restore api.js in VoraStudio frontend
3. Revert CorsSubscriber to wildcard
4. Restore VisitController to getClientIp()
5. Deploy as single revert commit

## Dependencies

- None (self-contained)

## Success Criteria

- [ ] `GET /api/public/token` with spoofed Origin returns 403
- [ ] JWT payload does NOT contain ROLE_ADMIN when obtained via public token
- [ ] VoraStudio page loads without api.js; content rendered server-side
- [ ] Visit records show correct IP/UA from SSR requests
- [ ] `Access-Control-Allow-Origin: *` absent from API responses
