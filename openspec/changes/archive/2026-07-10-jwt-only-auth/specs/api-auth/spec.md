# Delta Spec — api-auth

## ADDED Requirements

### Requirement: JWT Token Echo in /api/auth/me

The system MUST include the active JWT bearer token in the `GET /api/auth/me` response under `data.token`.

#### Scenario: Authenticated consumer requests profile

- GIVEN a request to `GET /api/auth/me` with a valid `Authorization: Bearer <jwt>` header
- WHEN the controller builds the response
- THEN `data.token` equals the JWT from the Authorization header
- AND the response does not contain `apiToken`

### Requirement: Allowed Domains Claim in JWT Payload

The system MUST include the authenticated user's `allowed_domains` list in the JWT payload under the `allowed_domains` claim.

#### Scenario: Successful login

- GIVEN a user with configured allowed domains
- WHEN the system issues a JWT
- THEN the payload contains `allowed_domains` with those values

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

## MODIFIED Requirements

### Requirement: API Firewall Authentication Method

The API firewall MUST authenticate all requests under `/api/*` using only a valid JWT Bearer token.

(Previously: The firewall accepted either an `apiToken` or a JWT in the `Authorization: Bearer` header.)

#### Scenario: Request with valid JWT

- GIVEN a request to `/api/*` with a valid `Authorization: Bearer <jwt>` header
- WHEN the request reaches the firewall
- THEN the request is authenticated as the corresponding user

#### Scenario: Request with legacy apiToken is rejected

- GIVEN a request to `/api/*` with an `Authorization: Bearer <apiToken>` header
- WHEN the request reaches the firewall
- THEN the response status is 401 Unauthorized

## REMOVED Requirements

### Requirement: Authenticate API Requests via apiToken

(Reason: The apiToken authenticator is removed in favor of JWT-only authentication.)

(Migration: Consumers must obtain a JWT from `POST /api/auth/login` and send it as `Authorization: Bearer <jwt>`.)

### Requirement: Authenticate Users by apiToken Lookup

(Reason: `UserRepository::findByApiToken()` is deleted because apiToken authentication is removed.)

(Migration: Identity resolution now relies on the JWT payload; no repository lookup by apiToken is needed.)
