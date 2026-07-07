# Spec: Date Range Field Type

## Functional Requirements

### FR1: Remove `datetime` field type
- `FieldDefinition::TYPE_DATETIME` constant is deleted
- `FieldDefinition::getTypes()` no longer returns `'datetime'`
- All template branches for `'datetime'` are removed
- Existing DB rows with `fieldType = 'datetime'` are NOT migrated (out of scope)

### FR2: Add `date_range` field type
- `FieldDefinition::TYPE_DATE_RANGE = 'date_range'` is added
- `FieldDefinition::getTypes()` includes `'date_range'`
- Value is stored as JSON in `FieldValue.value` (TEXT column)

### FR3: date_range storage format
- JSON string: `{"start":"2026-01-15T10:00","end":"2026-02-20T18:00"}`
- Both `start` and `end` use `datetime-local` format: `YYYY-MM-DDTHH:mm`
- If one of the values is empty, it is stored as `""` (empty string), e.g. `{"start":"2026-01-15T10:00","end":""}`

### FR4: date_range UI (new/edit forms)
- Two `<input type="datetime-local">` fields side by side
- Label shows field name, start input labeled "Inici", end input labeled "Fi"
- Both inputs respect `field.required` — if required, both are required
- Each input is named `field_{id}_start` and `field_{id}_end`

### FR5: date_range in resolveFieldValue
- `EntryController::resolveFieldValue()` reads `field_{id}_start` and `field_{id}_end` from POST
- Encodes them as JSON string before persisting

### FR6: date_range serialization
- `EntrySerializer::serializeValue()` decodes JSON and returns `{start: string, end: string}`
- Malformed JSON returns `null`

### FR7: date_range display (show/preview)
- Show template: displays "Inici: {start} · Fi: {end}" with formatted dates
- Preview template: same format, respecting frontend design

## Scenarios

### Happy path
1. Admin creates Content Type with `date_range` field
2. Admin creates new entry, fills start=2026-01-15T10:00 and end=2026-02-20T18:00
3. Value stored as `{"start":"2026-01-15T10:00","end":"2026-02-20T18:00"}`
4. Edit form pre-fills both inputs correctly
5. API returns `{start: "2026-01-15T10:00:00", end: "2026-02-20T18:00:00"}`
6. Show/preview displays both dates

### Edge cases
1. **Partial range**: Only start filled → stored as `{"start":"2026-01-15T10:00","end":""}`
2. **Malformed JSON in DB**: serializeDateRange returns `null`
3. **Empty value**: date_range with no value → stored as `{"start":"","end":""}` → displays empty
4. **Existing datetime fields**: Not migrated, but Choice validator only fires on new inserts/updates — no crash on read
5. **Required date_range**: Both inputs get `required` attribute, browser prevents partial submission

## Validation Rules

| Rule | Enforcement |
|---|---|
| date_range value must be valid JSON | Controller always writes valid JSON; serializer handles malformed gracefully |
| start/end format must be `YYYY-MM-DDTHH:mm` | Native `datetime-local` input enforces format at browser level |
| start ≤ end | Not validated (user responsibility — same as before with separate date fields) |

## Business Rules

- `date` field type is UNCHANGED — still renders as `<input type="datetime-local">`
- Existing entries with `datetime` fields remain readable (the Choice constraint on `setFieldType()` only fires on write operations)
- No migration script — admins must manually recreate Content Types that used `datetime`

## Acceptance Criteria

- [ ] AC1: `FieldDefinition::getTypes()` no longer includes `'datetime'`
- [ ] AC2: `FieldDefinition::getTypes()` includes `'date_range'`
- [ ] AC3: New entry form for Content Type with `date_range` shows two datetime-local inputs
- [ ] AC4: Submitting form with both dates persists valid JSON
- [ ] AC5: Edit form pre-fills both inputs from stored JSON
- [ ] AC6: EntrySerializer returns `{start, end}` object for date_range fields
- [ ] AC7: Show template renders both dates
- [ ] AC8: Existing `date` fields work identically before and after
- [ ] AC9: Preview template shows date_range values
