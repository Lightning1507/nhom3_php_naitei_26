# Implementation Plan: F01 - Authentication, User Profile & Authorization

**Branch**: `feature/98883-authentication-user-profile-authorization` | **Date**: 2026-08-14 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `specs/98883-auth-profile-authorization/spec.md`

## Summary

Implement the shared identity foundation for citizens and internal users. Citizen users register, log in, log out, view their profile, and update allowed profile fields through the React SPA and versioned Laravel API. Staff, Manager, and Super Admin users log in through the Admin Blade area. Server-side authorization enforces role boundaries, active-account checks, owner-only citizen access, and audit logging for important authentication and authorization events.

## Technical Context

**Language/Version**: PHP 8.5, Laravel 13, JavaScript with React 19 and Alpine.js

**Primary Dependencies**: Laravel Sanctum, Laravel session authentication, Eloquent, Blade, React Router, Axios, Tailwind CSS, Vite

**Storage**: PostgreSQL on Supabase for shared development/demo, using existing `users`, `sessions`, `personal_access_tokens`, and `activity_logs` foundations

**Testing**: PHPUnit and Laravel Feature Tests; Citizen API tests under `tests/Feature/Api`; Admin SSR tests under `tests/Feature/Admin`

**Target Platform**: Web application with Citizen React SPA and Admin Blade SSR served by Laravel

**Project Type**: Hybrid Laravel web application with JSON API, React SPA frontend, and server-rendered Admin area

**Performance Goals**: Auth, logout, profile read, and profile update flows return within 2 seconds under normal development/demo load; registration and first login can be completed by a valid citizen user within 3 minutes

**Constraints**: Use Laravel-first conventions; no repository layer or broad architecture rewrite; Citizen business UI stays in `resources/js/citizen`; Admin stays in Blade under `/admin`; protected operations are denied by default unless middleware/policy/gate allows them; first-party Citizen auth must not store tokens in `localStorage`

**Scale/Scope**: Four roles, Citizen self-registration, internal login, Citizen profile read/update, role/ownership authorization helpers, and audit events for auth and important access denials

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **Laravel-first backend**: PASS. Plan uses Route -> Controller -> Form Request -> Policy/Gate -> focused Action/Service only where useful -> Eloquent.
- **Feature-driven development**: PASS. Scope is bounded by F01 spec and artifacts in this directory.
- **Application-centric domain**: PASS. This feature creates auth/authorization foundation only and does not alter the Application status lifecycle.
- **Authorization and data protection**: PASS. Citizen, Staff, Manager, and Super Admin boundaries are enforced server-side; Citizen profile access is owner-only.
- **Database integrity and auditability**: PASS. Existing unique constraints and `activity_logs` are used; concurrent registration relies on database uniqueness plus validation.
- **Citizen React SPA and Admin Blade SSR**: PASS. Citizen auth/profile API lives under `/api/v1`; Admin login uses Blade routes under `/admin`.
- **Quality and definition of done**: PASS. Plan requires relevant API/Admin feature tests and authorization-boundary tests.

## Project Structure

### Documentation (this feature)

```text
specs/98883-auth-profile-authorization/
|-- plan.md
|-- research.md
|-- data-model.md
|-- quickstart.md
|-- contracts/
|   `-- v1-auth-profile.openapi.yaml
`-- tasks.md
```

### Source Code (repository root)

```text
app/
|-- Actions/Auth/
|-- Http/Controllers/Api/V1/Auth/
|-- Http/Controllers/Api/V1/ProfileController.php
|-- Http/Controllers/Admin/Auth/
|-- Http/Middleware/
|-- Http/Requests/Api/V1/Auth/
|-- Http/Requests/Api/V1/Profile/
|-- Http/Resources/Api/V1/
|-- Policies/
|-- Support/Auth/
`-- Models/User.php

resources/
|-- js/citizen/api/
|-- js/citizen/pages/
|-- js/citizen/components/
`-- views/admin/auth/

routes/
|-- api.php
`-- web.php

tests/Feature/
|-- Api/V1/Auth/
|-- Api/V1/Profile/
`-- Admin/Auth/
```

**Structure Decision**: Keep the existing single Laravel application. Citizen JSON endpoints are versioned in `routes/api.php`; Citizen screens and API client code stay under `resources/js/citizen`; Admin login and protected Admin pages stay in Blade under `/admin`.

## Phase 0: Research

See [research.md](./research.md). All technical unknowns are resolved without requiring changes to the approved scope.

## Phase 1: Design

- Data model and validation rules: [data-model.md](./data-model.md)
- API contract: [contracts/v1-auth-profile.openapi.yaml](./contracts/v1-auth-profile.openapi.yaml)
- End-to-end validation guide: [quickstart.md](./quickstart.md)

## Post-Design Constitution Check

- **No architecture violations introduced**: PASS. No repositories, DDD, microservices, or extra auth packages are required.
- **Rendering boundaries remain intact**: PASS. Citizen remains React SPA plus `/api/v1`; Admin remains Blade SSR plus web session.
- **Security controls are server-side**: PASS. Middleware, Form Requests, policies/gates, and active-account checks own authorization.
- **Data protection and auditability are covered**: PASS. CCCD uniqueness, immutable Citizen CCCD updates, owner-only profile access, and audit logging are represented in design artifacts.

## Complexity Tracking

No constitution violations require justification.
