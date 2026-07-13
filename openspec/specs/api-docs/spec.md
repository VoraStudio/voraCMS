# Spec — api-docs

## Requirements

### Requirement: JWT-Only Authentication Documentation

The API documentation templates MUST describe authentication using only JWT Bearer tokens.

#### Scenario: Consumer reads authentication guide

- GIVEN a user opens `api-guide.html.twig`
- WHEN the authentication section is rendered
- THEN the instructions reference only JWT
- AND `apiToken` is not mentioned

### Requirement: Document JWT TTL

The JWT documentation MUST state that access tokens expire after 7 days.

#### Scenario: Consumer reads JWT guide

- GIVEN a user opens `api-jwt.html.twig`
- WHEN the token lifetime section is rendered
- THEN the documented TTL is 7 days (604800 seconds)

### Requirement: Document /api/auth/me Token Echo

The API guide MUST describe that `GET /api/auth/me` returns the active JWT in `data.token`.

#### Scenario: Consumer reads endpoint reference

- GIVEN a user views the `/api/auth/me` documentation
- WHEN the response example is rendered
- THEN the example contains `token` and omits `apiToken`

### Requirement: Authentication Header Examples

All `Authorization: Bearer` examples in the documentation MUST use a JWT placeholder.

#### Scenario: Copy-to-clipboard header

- GIVEN a documentation template shows the Authorization header
- WHEN the consumer copies the example
- THEN the placeholder is `<JWT>` or a sample JWT string
- AND no `apiToken` placeholder remains
