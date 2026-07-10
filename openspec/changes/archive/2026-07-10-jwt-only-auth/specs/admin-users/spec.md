# Delta Spec — admin-users

## ADDED Requirements

### Requirement: User Entity Without apiToken

The `User` entity MUST NOT expose an `apiToken` property, getter, setter, or database column.

#### Scenario: Creating a new user

- GIVEN the admin creates a user through the admin form
- WHEN the user is persisted
- THEN the user has no `apiToken` value
- AND the database `users` table does not contain an `api_token` column

### Requirement: Admin User Creation Form Without apiToken

The admin user creation flow MUST not generate, display, or store an API token.

#### Scenario: Submit new user form

- GIVEN an admin fills the new user form
- WHEN the form is submitted
- THEN the system creates the user without an `apiToken`
- AND no token field is visible in the form or confirmation

## MODIFIED Requirements

### Requirement: Admin User Form Fields

The admin user form MUST omit any `apiToken` input or read-only display.

(Previously: The form or user record exposed an API token.)

#### Scenario: Admin edits a user

- GIVEN an admin opens the edit user page
- WHEN the page renders
- THEN no apiToken field is shown

## REMOVED Requirements

### Requirement: Auto-Generate apiToken for New Users

(Reason: JWT authentication replaces apiToken; no static token is needed.)

(Migration: New users obtain a JWT through `POST /api/auth/login` after activation.)

### Requirement: apiToken Field in User Database Schema

(Reason: The `api_token` column is dropped by the migration.)

(Migration: Existing apiToken values are discarded; tokens cannot be recovered.)

### Requirement: apiToken Generation in Fixtures and CLI

(Reason: `AppFixtures` and `CreateUserCommand` no longer set apiToken.)

(Migration: Generated test users and CLI-created users authenticate with JWT.)
