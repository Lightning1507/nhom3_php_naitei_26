# Implementation Plan: Public Service Catalog Management

**Branch**: `[002-service-catalog]` | **Date**: 2026-08-14 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/002-service-catalog/spec.md`

**Note**: This template is filled in by the `$speckit-plan` command; its definition describes the execution workflow.

## Summary

This feature implements the core catalog of public services, allowing Admins to manage Service Categories and ServiceTypes via the Blade SSR interface. Citizens will be able to browse, search, and filter active services through the React SPA via a new versioned REST API endpoint.

## Technical Context

**Language/Version**: PHP 8.5

**Primary Dependencies**: Laravel 13, React, Alpine.js, Tailwind CSS

**Storage**: PostgreSQL, Eloquent

**Testing**: PHPUnit, Laravel Feature Tests

**Target Platform**: Web (React SPA for Citizen, Blade SSR for Admin)

**Project Type**: Web Application

**Performance Goals**: < 1s load time for Citizen catalog API responses

**Constraints**: Strict separation between Citizen API routes (`routes/api.php`) and Admin SSR routes (`routes/web.php`). Use Form Requests for validation and Policies for authorization.

**Scale/Scope**: Admin CRUD interfaces for 2 models (`ServiceCategory` and `ServiceType`); Citizen listing/detail API endpoints.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Laravel-First Backend & Simplicity**: Adheres to standard Controller/Model/Policy pattern. No custom repository layers.
- **III. Application-Centric Domain**: Does not yet touch Applications, but soft deletes for ServiceTypes ensure historical integrity when applications are built.
- **IV. Authorization & Data Protection**: Admin routes protected by session auth and policies. Citizen API is public/read-only for the catalog.
- **V. Database Integrity & Auditability**: Foreign keys, soft deletes, and transactions will be used.
- **VI. Citizen React SPA & Admin Blade SSR**: API provided for Citizen SPA, Blade forms used for Admin CRUD.

## Project Structure

### Documentation (this feature)

```text
specs/002-service-catalog/
├── plan.md              # This file ($speckit-plan command output)
├── research.md          # Phase 0 output ($speckit-plan command)
├── data-model.md        # Phase 1 output ($speckit-plan command)
├── quickstart.md        # Phase 1 output ($speckit-plan command)
├── contracts/           # Phase 1 output ($speckit-plan command)
└── tasks.md             # Phase 2 output ($speckit-tasks command - NOT created by $speckit-plan)
```

### Source Code (repository root)

```text
# Web application
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── ServiceCategoryController.php
│   │   │   └── ServiceTypeController.php
│   │   └── Api/
│   │       └── V1/
│   │           └── ServiceCatalogController.php
│   └── Requests/
│       └── Admin/
├── Models/
│   ├── ServiceCategory.php
│   └── ServiceType.php
└── Policies/

database/
└── migrations/

resources/
├── js/
│   └── citizen/
│       └── pages/
│           ├── ServiceCatalog.jsx
│           └── ServiceDetail.jsx
└── views/
    └── admin/
        └── service_types/

tests/
├── Feature/
│   ├── Admin/
│   └── Api/
│       └── V1/
```

**Structure Decision**: Standard Laravel web application structure adhering to the separation of Citizen and Admin components as mandated by the constitution.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

*(No violations. Keeping implementation as simple as possible per Constitution Principle I.)*
