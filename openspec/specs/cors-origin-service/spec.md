# Specification — cors-origin-service

## Purpose

Provide a configurable service that resolves allowed CORS origins per request, replacing wildcard or hard-coded origin checks.

## Requirements

### Requirement: CorsOriginResolver Contract

The system MUST define a `CorsOriginResolver` interface or contract that returns the list of allowed origins for the current request.

#### Scenario: Resolver is injectable

- GIVEN a configured implementation of `CorsOriginResolver`
- WHEN `CorsSubscriber` is constructed
- THEN the resolver is injected by the service container

### Requirement: Configuration-Driven Origins

The resolver MUST load allowed origins from project configuration (YAML or environment variables).

#### Scenario: Origins configured

- GIVEN the configuration contains `["https://vorastudio.cat", "https://example.com"]`
- WHEN the resolver resolves for a request
- THEN it returns exactly those origins

#### Scenario: Empty configuration

- GIVEN no origins are configured
- WHEN the resolver resolves for a request
- THEN it returns an empty list

### Requirement: CORS Decision

`CorsSubscriber` MUST inject the resolver and use its returned origins to decide whether the request `Origin` header is allowed.

#### Scenario: Allowed origin

- GIVEN a request with `Origin: https://vorastudio.cat` and that origin is configured
- WHEN `CorsSubscriber` processes the request
- THEN the response includes `Access-Control-Allow-Origin: https://vorastudio.cat`

#### Scenario: Disallowed origin

- GIVEN a request with `Origin: https://attacker.com` and that origin is not configured
- WHEN `CorsSubscriber` processes the request
- THEN the response status is 403 Forbidden

### Requirement: Default Deny

When no origins are configured, the system MUST deny all cross-origin requests.

#### Scenario: No configured origins

- GIVEN an empty allowed origins list
- WHEN a cross-origin request arrives
- THEN the response status is 403 Forbidden
