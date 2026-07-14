# Spec — api-auth

## Requirements

### Requirement: JWT Token Echo in /api/auth/me

The system MUST include the active JWT bearer token in the `GET /api/auth/me` response under `data.token`.

#### Scenario: Authenticated consumer requests profile

- GIVEN a request to `GET /api/auth/me` with a valid `Authorization: Bearer <jwt>` header
- WHEN the controller builds the response
- THEN `data.token` equals the JWT from the Authorization header
- AND the response does not contain `apiToken`

### Requirement: Allowed Domains Claim in JWT Payload

The system MUST include the authenticated user's `allowed_domains` list in the JWT payload under the `allowed_domains` claim. When the token is issued through `/api/public/token`, the JWT payload MUST use the `vorastudio` user, MUST contain `ROLE_MOD`, and MUST NOT contain `ROLE_ADMIN`.

#### Scenario: Successful login

- GIVEN a user with configured allowed domains
- WHEN the system issues a JWT
- THEN the payload contains `allowed_domains` with those values

#### Scenario: Public token role restriction

- GIVEN a request to `/api/public/token` from an allowed origin
- WHEN the system issues the JWT
- THEN the payload roles contain `ROLE_MOD`
- AND the payload roles do not contain `ROLE_ADMIN`

#### Scenario: Spoofed origin denied

- GIVEN a request to `/api/public/token` with an `Origin` header not in the allowed origins list
- WHEN the system validates the origin
- THEN the response status is 403 Forbidden

### Requirement: Domain Claim Validation

The system MUST reject any API request whose JWT `domain` claim is not present in the user's `allowed_domains` list.

#### Scenario: Request from allowed domain

- GIVEN a JWT whose `domain` claim is included in `allowed_domains`
- WHEN the consumer calls a protected `/api/*` endpoint
- THEN the request is authenticated and processed

#### Scenario: Request from unauthorized domain

- GIVEN a JWT whose `domain` claim is not included in `allowed_domains`
- WHEN the consumer calls a protected `/api/*` endpoint
- THEN the response status is 401 Unauthorized

### Requirement: JWT Access Token TTL

The system MUST issue JWT access tokens with a time-to-live of 7 days (604800 seconds).

#### Scenario: Token expiration claim

- GIVEN a successful login
- WHEN the JWT payload is inspected
- THEN `exp` equals `iat` plus 604800 seconds

### Requirement: API Firewall Authentication Method

The API firewall MUST authenticate all requests under `/api/*` using only a valid JWT Bearer token, except that safe read requests (`GET`, `HEAD`, `OPTIONS`) to routes under `/api/public/*` do not require authentication. The route `/api/public/token` MUST remain accessible without authentication. All other requests under `/api/public/*` MUST still require a valid JWT Bearer token.

#### Scenario: Request with valid JWT

- GIVEN a request to `/api/admin/users` with a valid `Authorization: Bearer <jwt>` header
- WHEN the request reaches the firewall
- THEN the request is authenticated as the corresponding user

#### Scenario: Request with legacy apiToken is rejected

- GIVEN a request to `/api/admin/users` with an `Authorization: Bearer <apiToken>` header
- WHEN the request reaches the firewall
- THEN the response status is 401 Unauthorized

#### Scenario: Public read without JWT is allowed

- GIVEN a `GET` request to `/api/public/pages/home` with no `Authorization` header
- WHEN the request reaches the firewall
- THEN the request is allowed without authentication

#### Scenario: Public write without JWT is rejected

- GIVEN a `POST` request to `/api/public/visits` with no `Authorization` header
- WHEN the request reaches the firewall
- THEN the response status is 401 Unauthorized

#### Scenario: Public token endpoint remains public

- GIVEN a `GET` request to `/api/public/token` with no `Authorization` header
- WHEN the request reaches the firewall
- THEN the response status is 200 OK

### Requirement: Public Route Restriction

UserFilterSubscriber MUST NOT apply the `user_id_filter` on routes whose path starts with `/api/public/`.

#### Scenario: Public read bypasses user filter

- GIVEN a `GET` request to `/api/public/pages/home`
- WHEN UserFilterSubscriber processes the request
- THEN the `user_id_filter` is not applied

#### Scenario: Public write still bypasses user filter

- GIVEN a `POST` request to `/api/public/visits` with a valid JWT
- WHEN UserFilterSubscriber processes the request
- THEN the `user_id_filter` is not applied
- AND JWT authentication remains enforced
