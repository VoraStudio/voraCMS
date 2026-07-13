# Archive Report: jwt-only-auth

## Metadata

| Field | Value |
|-------|-------|
| **Change** | `jwt-only-auth` |
| **Archive Date** | 2026-07-10 |
| **Verdict** | `PASS WITH WARNINGS` |
| **Mode** | `hybrid` (openspec files + Engram) |

## Executive Summary

The JWT-only authentication migration is complete. All 17 tasks implemented and verified. Delta specs from three domains (`api-auth`, `admin-users`, `api-docs`) were synced as new main specs (no pre-existing specs for these domains). The change folder was moved to the archive. The SDD cycle is closed.

## Specs Synced

| Domain | Action | Details |
|--------|--------|---------|
| `api-auth` | Created | 5 requirements synced (4 ADDED + 1 MODIFIED from delta; 2 REMOVED skipped) |
| `admin-users` | Created | 3 requirements synced (2 ADDED + 1 MODIFIED from delta; 3 REMOVED skipped) |
| `api-docs` | Created | 4 requirements synced (3 ADDED + 1 MODIFIED from delta; 3 REMOVED skipped) |

No existing main specs were overwritten. All three domains are net-new in the source of truth.

## Archive Contents

```
openspec/changes/archive/2026-07-10-jwt-only-auth/
├── proposal.md          ✅
├── design.md            ✅
├── tasks.md             ✅ (17/17 complete)
├── verify-report.md     ✅ (PASS WITH WARNINGS)
└── specs/
    ├── api-auth/spec.md ✅
    ├── admin-users/spec.md ✅
    └── api-docs/spec.md ✅
```

## Verification Notes

- **CRITICAL issues**: None
- **WARNINGS**: None
- **SUGGESTIONS (resolved)**: S1 — `src/Service/TokenGenerator.php` was dead code. Deleted after verification.
- **Pre-existing main specs overwritten**: None (all three domains were new)

## Source of Truth Updated

| Main Spec | Status |
|-----------|--------|
| `openspec/specs/api-auth/spec.md` | New |
| `openspec/specs/admin-users/spec.md` | New |
| `openspec/specs/api-docs/spec.md` | New |
| `openspec/specs/admin/spec.md` | Unchanged |

## Archive Reconciliation

- **Tasks gate**: All 17 checkboxes were `[x]` — no reconciliation needed.
- **Specs gate**: All 3 delta specs synced as full specs (no existing main specs to merge into).
- **Overrides**: None. Standard archive.

## Risk Assessment

| Risk | Post-Archive Status |
|------|-------------------|
| Existing clients using apiToken break | Accepted — notified |
| DB migration drops column with active tokens | Mitigated — confirmed unused |
| Domain validation regression | Mitigated — validated in verify-report |
| TokenGenerator dead code | Resolved — deleted |
