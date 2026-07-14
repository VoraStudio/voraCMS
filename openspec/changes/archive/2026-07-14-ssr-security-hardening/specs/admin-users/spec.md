# Delta for admin-users

## ADDED Requirements

### Requirement: vorastudio Seed User

The system MUST seed a user with username `vorastudio`, slug `vorastudio`, email `vorastudio@vora.es`, role `ROLE_MOD`, and `allowed_domains` containing `["https://vorastudio.cat", "vorastudio.cat", "localhost"]`. The user MUST NOT have admin privileges.

#### Scenario: Fixtures load the restricted user

- GIVEN the application fixtures are executed
- WHEN the `vorastudio` user is persisted
- THEN the user has role `ROLE_MOD`
- AND the user does not have role `ROLE_ADMIN`

#### Scenario: Public token uses the seed user

- GIVEN a request to `/api/public/token` from an allowed origin
- WHEN the system issues the JWT
- THEN the token represents the `vorastudio` user
- AND the token payload contains the seed user's `allowed_domains`

#### Scenario: Admin access is denied

- GIVEN the `vorastudio` user is authenticated
- WHEN the user attempts to access an admin-only route
- THEN the response status is 403 Forbidden
