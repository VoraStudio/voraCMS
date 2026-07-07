# Proposal: Date Range Field Type + Remove Redundant datetime

## Intent

`datetime` is a redundant field type — it renders and serializes identically to `date`. Removing it cleans up the type system. Replacing it with `date_range` adds a genuinely new capability: storing start/end pairs (e.g. event date ranges) as a single field, persisted as JSON in the existing TEXT column.

## Scope

### In Scope
- Remove `TYPE_DATETIME` constant and `'datetime'` from `FieldDefinition::getTypes()`
- Add `TYPE_DATE_RANGE = 'date_range'` constant and wire it into all enum consumers
- UI: two `datetime-local` inputs (start / end) side-by-side in new/edit forms
- Serialization: `date_range` returns parsed JSON object `{start, end}` in API; `date` unchanged
- Display: show both values in show.html.twig and preview_generic.html.twig
- Controller: `resolveFieldValue` encodes start/end POST values as JSON for persistence
- Update `api-guide.html.twig` examples (remove `datetime`, add `date_range`)

### Out of Scope
- Database migration for existing `datetime` field values (they become orphan; Content Types using it will need manual recreation)
- Backward-compat layer for API clients reading `datetime` fields
- Multi-day range picker (calendar widget) — uses native datetime-local only
- Reusable form component — inline in Twig templates

## Capabilities

### New Capabilities
- `field-type-date-range`: stores and renders start/end date pairs as a single field

### Modified Capabilities
- `admin`: field type enum shrinks (remove `datetime`), new `date_range` render paths in CRUD templates

## Approach

1. Edit `FieldDefinition.php`: delete `TYPE_DATETIME`, add `TYPE_DATE_RANGE`, update `getTypes()` array
2. Edit `EntrySerializer.php`: replace `TYPE_DATETIME` case with `TYPE_DATE_RANGE` — decode JSON and return parsed object
3. Edit `EntryController.php::resolveFieldValue()`: when `date_range`, read `field_X_start` + `field_X_end` from POST and encode as `{"start":"...","end":"..."}` JSON
4. Edit 4 Twig templates: in each, remove the `datetime` elseif block, add `date_range` block with two inputs side-by-side (new/edit) or formatted display (show/preview)
5. Edit `api-guide.html.twig`: remove `datetime` from example field arrays and field type table, add `date_range` entry

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `src/Entity/FieldDefinition.php` | Modified | Remove TYPE_DATETIME, add TYPE_DATE_RANGE |
| `src/Service/EntrySerializer.php` | Modified | Swap datetime case for date_range JSON decode |
| `src/Controller/Admin/EntryController.php` | Modified | Add date_range encoding in resolveFieldValue |
| `templates/admin/entry/new.html.twig` | Modified | Replace datetime block with date_range |
| `templates/admin/entry/edit.html.twig` | Modified | Same |
| `templates/admin/entry/show.html.twig` | Modified | Same |
| `templates/admin/entry/preview_generic.html.twig` | Modified | Same |
| `templates/admin/api-guide.html.twig` | Modified | Update type references |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Existing DB rows with `fieldType=datetime` become invalid for the Choice constraint | Medium | Add a note in the release: admins must recreate Content Types using `datetime` before deploying. No crash — the Choice constraint fires on new inserts/updates only |
| JSON stored as TEXT may be empty or malformed | Low | Controller always writes valid JSON; `serializeValue` does `json_decode` and falls back to null on failure |
| Side-by-side inputs break on narrow viewports | Low | Use `row` + `col-6` grid; they stack on mobile |

## Rollback Plan

Revert each file individually: restore `TYPE_DATETIME` constant, remove `TYPE_DATE_RANGE`, restore deleted Twig elseif blocks. No migration needed since TEXT column is unchanged. Deploy revert as a single commit.

## Dependencies

- None (self-contained change, no external packages)

## Success Criteria

- [ ] `FieldDefinition::getTypes()` no longer includes `'datetime'` and includes `'date_range'`
- [ ] New entry form shows two datetime-local inputs for `date_range` fields, persisted as JSON
- [ ] Edit form pre-fills both inputs from stored JSON value
- [ ] API returns `{start, end}` object for `date_range` fields via EntrySerializer
- [ ] API guide references `date_range` in examples and type table
- [ ] Existing `date` fields work identically before and after
