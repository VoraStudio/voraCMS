# Specification — ssr-frontend

## Purpose

Render VoraStudio pages server-side using a cached CMS JWT, removing client-side JWT handling and storage.

## Requirements

### Requirement: Server-Side Token Fetch

`VoraStudio/index.php` MUST request a JWT from `GET /api/public/token` using an `Origin` header that matches the configured allowed origin.

#### Scenario: Allowed origin

- GIVEN the SSR origin is in the allowed origins list
- WHEN `index.php` requests `/api/public/token`
- THEN it receives a valid JWT for the `vorastudio` user

#### Scenario: Disallowed origin

- GIVEN the SSR origin is not in the allowed origins list
- WHEN `index.php` requests `/api/public/token`
- THEN the response status is 403 Forbidden
- AND the page renders an error message instead of CMS content

### Requirement: Token Cache

The system MUST cache the fetched JWT on the server filesystem for a maximum of 5 minutes. If a content request returns 401 or 403, the system MUST re-fetch the token and retry the request once.

#### Scenario: Cache hit

- GIVEN a valid JWT was fetched within the last 5 minutes
- WHEN `index.php` renders a page
- THEN the cached token is reused

#### Scenario: Token rejected

- GIVEN a cached JWT is rejected with 401
- WHEN `index.php` renders a page
- THEN it re-fetches a token from `/api/public/token`
- AND retries the content request once

### Requirement: Server-Side Content Fetch

`index.php` MUST call CMS content endpoints server-side via cURL, passing the JWT in an `Authorization: Bearer` header.

#### Scenario: Content endpoint call

- GIVEN a valid server-side JWT
- WHEN `index.php` requests `/api/public/pages/home`
- THEN the response contains CMS content
- AND the request includes the JWT header

### Requirement: Safe HTML Rendering

The system MUST render full HTML from fetched CMS data and MUST NOT expose the JWT or any API token in the client-facing markup.

#### Scenario: No token leakage

- GIVEN CMS content was fetched successfully
- WHEN the page HTML is generated
- THEN the response body contains the rendered content
- AND the response body does not contain the JWT string

### Requirement: Visit Posting

`VisitController` MUST accept `client_ip` and `user_agent` from the JSON request body. The SSR frontend MUST send the visitor's real `client_ip` and `user_agent`, taken from `$_SERVER`.

#### Scenario: Trusted SSR posts visit

- GIVEN a visitor loads a VoraStudio page
- WHEN `index.php` posts to `/api/public/visits`
- THEN the JSON body contains the visitor's IP and user agent
- AND the request is accepted because the source IP is in `TRUSTED_FRONTEND_IPS`

#### Scenario: Untrusted source rejected

- GIVEN a request to `/api/public/visits` from an IP not in `TRUSTED_FRONTEND_IPS`
- WHEN the controller validates the source IP
- THEN the response status is 403 Forbidden

### Requirement: Remove api.js

The VoraStudio frontend MUST NOT load `js/api.js` or store authentication tokens in `sessionStorage`.

#### Scenario: Page loads without client API layer

- GIVEN the VoraStudio page is rendered
- WHEN the browser parses the markup and scripts
- THEN no request loads `VoraStudio/js/api.js`
- AND `sessionStorage` contains no JWT
