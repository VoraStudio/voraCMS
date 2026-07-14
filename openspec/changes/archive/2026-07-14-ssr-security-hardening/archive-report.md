# Archive Report — ssr-security-hardening

## Change Summary

| Field | Value |
|-------|-------|
| **Change** | ssr-security-hardening |
| **Completed** | 2026-07-14 |
| **Branch** | `feature/ssr-security-hardening` |
| **Status** | ✅ ARCHIVED |
| **Mode** | Hybrid (openspec + engram) |

## Task Completion

| Phase | Tasks | Status |
|-------|-------|--------|
| Phase 1: Foundation | 1.1-1.5 | ✅ All complete |
| Phase 2: Backend Security | 2.1-2.4 | ✅ All complete |
| Phase 3: Visit + SSR | 3.1-3.3 | ✅ All complete |
| Phase 4: Cleanup | 4.1-4.2 | ✅ All complete |
| **Total** | **14/14** | ✅ |

## Verification Summary

| Result | Status |
|--------|--------|
| PHP syntax | ✅ PASS |
| Spec compliance — api-auth | ✅ 10/10 PASS |
| Spec compliance — admin-users | ✅ 4/4 PASS |
| Spec compliance — cors-origin-service | ✅ 7/7 PASS |
| Spec compliance — ssr-frontend | ✅ 9/9 PASS |
| Design coherence | ✅ 8/8 decisions aligned |
| Warnings | ✅ Both resolved (W1 fetch call, W2 csrf_token) |

## Specs Synced

| Domain | Action | Details |
|--------|--------|---------|
| `admin-users` | Updated | 1 ADDED requirement (vorastudio Seed User, 3 scenarios). 3 existing requirements preserved. |
| `api-auth` | Updated | 1 ADDED (Public Route Restriction) + 2 MODIFIED (API Firewall Authentication Method, Allowed Domains Claim in JWT Payload). 3 existing requirements preserved. |
| `cors-origin-service` | Created | New domain. Full spec with 4 requirements (CorsOriginResolver Contract, Configuration-Driven Origins, CORS Decision, Default Deny). |
| `ssr-frontend` | Created | New domain. Full spec with 6 requirements (Server-Side Token Fetch, Token Cache, Server-Side Content Fetch, Safe HTML Rendering, Visit Posting, Remove api.js). |

## Key Accomplishments

1. **Origin spoofing eliminated**: Wildcard CORS replaced with config-driven `CorsOriginResolver`; spoofed origins return 403.
2. **No ROLE_ADMIN in public tokens**: Dedicated `vorastudio` seed user with `ROLE_MOD` only; no client-side JWT exposure.
3. **User-filter bypass hardened**: `UserFilterSubscriber` skips on `/api/public/*` path prefix, not role-based.
4. **SSR pipeline operational**: `CmsClient` with cURL-based token fetch, filesystem cache (300s TTL), 401 retry logic.
5. **Visit integrity protected**: `client_ip`/`user_agent` accepted from JSON body only when source IP is in `TRUSTED_FRONTEND_IPS`.
6. **`api.js` removed**: No JWT or `sessionStorage` tokens in client-facing markup.

## Archive Contents

```
openspec/changes/archive/2026-07-14-ssr-security-hardening/
├── proposal.md          ✅
├── design.md            ✅
├── tasks.md             ✅ (14/14 complete)
├── verify-report.md     ✅
└── specs/
    ├── admin-users/spec.md
    ├── api-auth/spec.md
    ├── cors-origin-service/spec.md
    └── ssr-frontend/spec.md
```

## Source of Truth Updated

- `openspec/specs/admin-users/spec.md` — merged delta
- `openspec/specs/api-auth/spec.md` — merged delta
- `openspec/specs/cors-origin-service/spec.md` — new domain
- `openspec/specs/ssr-frontend/spec.md` — new domain

## Next Steps

None. SDD cycle complete for `ssr-security-hardening`. Ready for the next change.
