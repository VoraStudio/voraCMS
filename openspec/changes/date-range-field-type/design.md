# Design: Date Range Field Type + Remove datetime

## Technical Approach

Reuse the existing `FieldValue.value` TEXT column to store `date_range` as JSON. Remove the redundant `TYPE_DATETIME` constant and all Twig branches that referenced it. Add a small `json_decode` Twig filter so edit/show templates can read the stored JSON without passing decoded maps through controllers. `date` keeps its current behavior exactly.

## Architecture Decisions

| Decision | Options | Tradeoffs | Rationale |
|---|---|---|---|
| Store `date_range` as JSON in TEXT | JSON column vs TEXT | No schema change; aligns with existing `FieldValue.value` design and binding decision. |
| Remove `TYPE_DATETIME` completely | Deprecate vs delete | Deletion is cleaner and matches proposal; orphan `datetime` rows remain readable but fail Choice validation on write. |
| Add `json_decode` Twig filter | Controller-decoded map vs inline string parsing | Filter keeps controller change limited to `resolveFieldValue()` and avoids fragile regex parsing in templates. |
| Normalize `start`/`end` with existing `serializeDate()` | Reuse vs new formatter | Keeps `:00` seconds normalization consistent with `date` fields. |

## Data Flow

```
new/edit form
  field_{id}_start ──┐
  field_{id}_end  ───┼→ resolveFieldValue() ──json_encode()──→ FieldValue.value (TEXT)
                     │
API consumer ←── serializeDateRange() ←──json_decode()──┘
```

## File Changes

| File | Action | Description |
|---|---|---|
| `src/Entity/FieldDefinition.php` | Modify | Remove `TYPE_DATETIME`, add `TYPE_DATE_RANGE`, update `getTypes()` |
| `src/Service/EntrySerializer.php` | Modify | Replace `TYPE_DATETIME` case with `TYPE_DATE_RANGE`; add `serializeDateRange()` |
| `src/Controller/Admin/EntryController.php` | Modify | Encode start/end POST values as JSON in `resolveFieldValue()` |
| `templates/admin/entry/new.html.twig` | Modify | Remove `datetime` block, add `date_range` side-by-side inputs |
| `templates/admin/entry/edit.html.twig` | Modify | Same, pre-filling from stored JSON |
| `templates/admin/entry/show.html.twig` | Modify | Remove `datetime` block, render `date_range` as "Inici · Fi" |
| `templates/admin/entry/preview_generic.html.twig` | Modify | Same, using already-decoded API value |
| `src/Twig/AdminExtension.php` | Modify (supporting) | Add `json_decode` filter for Twig templates |

### `src/Entity/FieldDefinition.php`

- Delete line 21: `public const TYPE_DATETIME = 'datetime';`
- Add after line 27: `public const TYPE_DATE_RANGE = 'date_range';`
- Update `getTypes()` (line 35): replace `self::TYPE_DATETIME` with `self::TYPE_DATE_RANGE`.

```php
public static function getTypes(): array
{
    return [
        self::TYPE_TEXT, self::TYPE_TEXTAREA, self::TYPE_RICHTEXT,
        self::TYPE_IMAGE, self::TYPE_GALLERY,
        self::TYPE_DATE, self::TYPE_DATE_RANGE,
        self::TYPE_LOCATION, self::TYPE_BOOLEAN,
        self::TYPE_NUMBER, self::TYPE_URL, self::TYPE_EMAIL, self::TYPE_COLOR,
        self::TYPE_YOUTUBE,
    ];
}
```

### `src/Service/EntrySerializer.php`

- Delete line 83: `FieldDefinition::TYPE_DATETIME => $this->serializeDate($value),`
- Add after line 82: `FieldDefinition::TYPE_DATE_RANGE => $this->serializeDateRange($value),`
- Add private method after `serializeDate()`:

```php
private function serializeDateRange(?string $value): ?array
{
    if (!$value) return null;

    $decoded = json_decode($value, true);
    if (!is_array($decoded)) return null;

    return [
        'start' => $this->serializeDate($decoded['start'] ?? null),
        'end' => $this->serializeDate($decoded['end'] ?? null),
    ];
}
```

### `src/Controller/Admin/EntryController.php`

Add inside `resolveFieldValue()` before the final `return $raw;` (line 472):

```php
if ($fieldDef->getFieldType() === FieldDefinition::TYPE_DATE_RANGE) {
    $start = $request->request->get('field_' . $fieldId . '_start', '');
    $end = $request->request->get('field_' . $fieldId . '_end', '');
    $encoded = json_encode(['start' => $start, 'end' => $end]);
    return $encoded !== false ? $encoded : '{"start":"","end":""}';
}
```

### `src/Twig/AdminExtension.php`

Add filter to support JSON parsing in templates:

```php
use Twig\TwigFilter;

public function getFilters(): array
{
    return [
        new TwigFilter('json_decode', [$this, 'decodeJson']),
    ];
}

public function decodeJson(?string $value): ?array
{
    if (!$value) return null;
    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : null;
}
```

### `templates/admin/entry/new.html.twig`

- Delete lines 53-54 (`datetime` block).
- Insert after the `date` block (line 51):

```twig
{% elseif field.fieldType == 'date_range' %}
<div class="row g-2">
    <div class="col-md-6">
        <label class="form-label small">Inici</label>
        <input type="datetime-local" name="field_{{ field.id }}_start" class="form-control" {% if field.required %}required{% endif %}>
    </div>
    <div class="col-md-6">
        <label class="form-label small">Fi</label>
        <input type="datetime-local" name="field_{{ field.id }}_end" class="form-control" {% if field.required %}required{% endif %}>
    </div>
</div>
```

### `templates/admin/entry/edit.html.twig`

- Delete lines 70-71 (`datetime` block).
- Insert after the `date` block (line 68):

```twig
{% elseif field.fieldType == 'date_range' %}
{% set range = val ? val|json_decode : null %}
<div class="row g-2">
    <div class="col-md-6">
        <label class="form-label small">Inici</label>
        <input type="datetime-local" name="field_{{ field.id }}_start" class="form-control" value="{{ range.start ?? '' }}" {% if field.required %}required{% endif %}>
    </div>
    <div class="col-md-6">
        <label class="form-label small">Fi</label>
        <input type="datetime-local" name="field_{{ field.id }}_end" class="form-control" value="{{ range.end ?? '' }}" {% if field.required %}required{% endif %}>
    </div>
</div>
```

### `templates/admin/entry/show.html.twig`

- Delete lines 113-114 (`datetime` block).
- Insert after the `date` block (line 111):

```twig
{% elseif def.fieldType == 'date_range' %}
{% set range = raw|json_decode %}
<div class="s-field-value">Inici: {{ range.start ?? '—' }} · Fi: {{ range.end ?? '—' }}</div>
```

### `templates/admin/entry/preview_generic.html.twig`

- Delete lines 512-513 (`datetime` block).
- Insert after the `date` block (line 510):

```twig
{% elseif field.fieldType == 'date_range' %}
    Inici: {{ value.start ?? '—' }} · Fi: {{ value.end ?? '—' }}
```

## Interfaces / Contracts

- `FieldDefinition::getTypes()` returns the canonical enum; any new consumer MUST use `TYPE_DATE_RANGE` and never `TYPE_DATETIME`.
- `date_range` stored value contract: JSON object with string keys `start` and `end`, each either `YYYY-MM-DDTHH:mm` or `""`.
- API output contract: `{start: ?string, end: ?string}` with seconds normalized to `:00` when present.

## Testing Strategy

| Layer | What to Test | Approach |
|---|---|---|
| Unit | `EntrySerializer::serializeDateRange()` | Add `tests/Service/EntrySerializerTest.php` if PHPUnit is enabled; otherwise manual verification. |
| Integration | Form POST → stored JSON → API response | Create a Content Type with `date_range`, submit new/edit forms, inspect DB and `/api/entries`. |
| Manual | Show/preview render, `date` unchanged, `datetime` branches gone | Visual check in admin show, preview, and API guide. |

No existing test suite is present, so the immediate verification is manual.

## Migration / Rollout

No database migration. Existing `datetime` field rows become read-only; the Choice constraint on `FieldDefinition::setFieldType()` only fires on writes. Rollback is a straight revert of the modified files.

## Open Questions

- Should `api-guide.html.twig` be updated during apply even though it is excluded from this design?
