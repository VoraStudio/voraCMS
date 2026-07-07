# Tasks: Date Range Field Type + Remove datetime

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~81 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | single-pr |
| Chain strategy | size-exception |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: size-exception
400-line budget risk: Low

## Phase 1: Foundation (Backend types & helpers)

- [ ] 1.1 `src/Entity/FieldDefinition.php` — Delete `TYPE_DATETIME` (line 21), add `TYPE_DATE_RANGE = 'date_range'` after `TYPE_COLOR`, replace `self::TYPE_DATETIME` with `self::TYPE_DATE_RANGE` in `getTypes()`.
- [ ] 1.2 `src/Twig/AdminExtension.php` — Add `use Twig\TwigFilter;`, implement `getFilters()` returning `json_decode` filter, add `decodeJson(?string $value): ?array` method.

## Phase 2: Core Implementation (Serialization & Controller)

- [ ] 2.1 `src/Service/EntrySerializer.php` — Replace `FieldDefinition::TYPE_DATETIME => $this->serializeDate($value)` with `FieldDefinition::TYPE_DATE_RANGE => $this->serializeDateRange($value)`. Add `serializeDateRange(?string $value): ?array` that `json_decode`s the value and calls `$this->serializeDate()` on `start`/`end`.
- [ ] 2.2 `src/Controller/Admin/EntryController.php` — In `resolveFieldValue()`, before the final `return $raw;`, add a block for `FieldDefinition::TYPE_DATE_RANGE` that reads `field_{id}_start` / `field_{id}_end` from POST, `json_encode`s them, and returns the encoded JSON.

## Phase 3: Templates (CRUD forms & display)

- [ ] 3.1 `templates/admin/entry/new.html.twig` — Delete the `datetime` elseif block (lines 53-54). Add `date_range` elseif with `row g-2` > `col-md-6`*2 containing two `datetime-local` inputs (Inici / Fi).
- [ ] 3.2 `templates/admin/entry/edit.html.twig` — Delete the `datetime` elseif block (lines 70-71). Add `date_range` elseif, decode `val|json_decode` into `range`, pre-fill both `datetime-local` inputs from `range.start`/`range.end`.
- [ ] 3.3 `templates/admin/entry/show.html.twig` — Delete the `datetime` elseif block (lines 113-114). Add `date_range` elseif decoding `raw|json_decode` into `range`, display "Inici: {start} · Fi: {end}".
- [ ] 3.4 `templates/admin/entry/preview_generic.html.twig` — Delete the `datetime` elseif block (lines 512-513). Add `date_range` elseif rendering "Inici: {value.start} · Fi: {value.end}".

## Phase 4: Verification

- [ ] 4.1 Manual verification against spec scenarios: create Content Type with `date_range`, submit new/edit forms, inspect DB JSON, check show/preview renders, verify `date` fields still work, confirm `datetime` is gone from `getTypes()`.
