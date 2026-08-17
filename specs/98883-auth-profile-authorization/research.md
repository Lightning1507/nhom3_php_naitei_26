# Research: F01 - Authentication, User Profile & Authorization

## Decision: Use Laravel Sanctum cookie/session authentication for Citizen SPA

**Rationale**: The constitution requires first-party Citizen SPA auth with secure cookie/session authentication and CSRF protection. Laravel Sanctum is already installed and fits the existing Laravel/Vite/React setup without adding a new identity provider.

**Alternatives considered**: Personal access tokens stored in browser storage were rejected because the constitution forbids first-party token storage in `localStorage`. JWT was rejected because it adds complexity and does not improve this same-origin SPA use case.

## Decision: Use Laravel web session authentication for Admin

**Rationale**: Admin is a Blade SSR area under `/admin`, so Laravel's session guard, CSRF protection, and redirect-based login flow are the simplest fit.

**Alternatives considered**: A React Admin SPA was rejected because the constitution requires Admin Blade SSR. Sharing the Citizen API login flow with Admin was rejected because the two interfaces must stay separated.

## Decision: Keep identity and Citizen profile fields on `users`

**Rationale**: The foundation migration already stores role, citizen identifier, date of birth, phone, address, active status, and soft deletes in `users`. Reusing this model minimizes schema churn and matches the current database design.

**Alternatives considered**: A separate `citizen_profiles` table was rejected for F01 because it would duplicate existing foundation fields and add migration risk to a shared Supabase database. It can be reconsidered only if a later feature needs profile history or complex profile ownership.

## Decision: Enforce uniqueness with validation plus database constraints

**Rationale**: Email and CCCD uniqueness must survive concurrent registration. Form Request validation gives user-friendly errors; database unique constraints provide the final integrity guarantee.

**Alternatives considered**: Application-only duplicate checks were rejected because concurrent requests can bypass them. PostgreSQL advisory locks were rejected because unique constraints are simpler and sufficient.

## Decision: Implement role and active-account checks with middleware plus policies/gates

**Rationale**: Middleware cleanly separates Citizen and Admin route areas, while policies/gates cover resource ownership and denied-by-default behavior.

**Alternatives considered**: UI-only checks were rejected by the constitution. A permission package was rejected because four fixed roles do not justify extra dependency or configuration overhead.

## Decision: Record important auth/security events in `activity_logs`

**Rationale**: The database foundation already includes `activity_logs`; F01 needs traceability for login, logout, and important access denial events.

**Alternatives considered**: Log-file-only audit trails were rejected because they are harder to query in Admin features and do not align with the database design.
