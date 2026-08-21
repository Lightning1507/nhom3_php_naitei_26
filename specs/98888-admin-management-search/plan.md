# Implementation Plan: F07 - Admin Management & Search

**Branch**: `[98888-admin-management-search]` | **Date**: 2026-08-21 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/98888-admin-management-search/spec.md`

## Summary

Complete the existing Blade Admin workspace so active Staff, Manager, and Super Admin can search, filter, paginate, and inspect Applications strictly within one canonical authorization scope; Super Admin can search accounts and safely activate/deactivate eligible Users; and every internal role gets scope-correct operational metrics. The implementation extends current F01-F06 models, policies, controllers, Actions, and Blade views. It uses validated GET Form Requests, reusable Eloquent scopes, policy-enforced direct access, a transactional audited User-status Action, and focused Feature/performance tests. It does not create a new domain model, duplicate F05 workflow rules, introduce a repository, convert Admin to React, or add a package.

## Technical Context

**Language/Version**: PHP 8.5.9 (project baseline PHP 8.5), JavaScript via Vite; Laravel application currently reports 13.24.0  
**Primary Dependencies**: Laravel 13, Eloquent ORM, Blade SSR, Tailwind CSS, Alpine.js, Laravel session authentication  
**Storage**: PostgreSQL; existing private filesystem storage for Application documents  
**Testing**: PHPUnit/Laravel Feature Tests; Composer and npm lint scripts; separate PostgreSQL performance group  
**Target Platform**: Server-rendered web application on the project's PHP/PostgreSQL deployment platform; modern desktop/mobile browsers for Admin UI  
**Project Type**: Monolithic Laravel web application with Citizen React SPA and Admin Blade SSR areas; F07 modifies only the Admin SSR area and shared backend domain rules  
**Performance Goals**: At least 95% of Application and User list/search/filter/page requests complete within 2 seconds with 10,000 records; bounded query counts with no N+1; direct known-Application lookup remains operationally immediate  
**Constraints**: 20 rows per page; authorization must be identical across rows, options, detail, documents, dashboard, and drill-down; protected direct access must not reveal existence; GET views cannot mutate data; status change is reversible, transactional, and audited; existing F05 workflow remains authoritative  
**Scale/Scope**: 10,000+ Applications and 10,000+ Users; four Admin page groups (dashboard, Application list/detail, User list/detail); three internal visibility roles; six dashboard metrics; no new domain entity or initial schema migration

## Constitution Check

*GATE: Passed before Phase 0 research and re-checked after Phase 1 design.*

| Principle / constraint | Pre-research result | Post-design result |
|---|---|---|
| I. Laravel-First Backend & Simplicity | PASS — plan starts from current controllers/models and requires no speculative layer. | PASS — Form Request -> policy/scope -> focused Action/Eloquent; no repository, interface, package, cache, or new service boundary without need. |
| II. Feature-Driven Development | PASS — approved F07 spec defines four prioritized stories and scope exclusions. | PASS — plan, research, data model, contract, and quickstart are feature-local; Tasks and implementation remain later Spec Kit phases. |
| III. Application-Centric Domain | PASS — F07 reads the existing five lifecycle statuses and does not own transitions. | PASS — `completed` is a read-only filter group, not a sixth status; all F05 mutations remain unchanged in ownership. |
| IV. Authorization & Data Protection | PASS — role-scoped list/detail/document/user-management requirements are explicit. | PASS — one deny-by-default Application scope, 404-masked detail, private document authorization, and Super Admin User policies enforce server-side protection. |
| V. Database Integrity & Auditability | PASS — reversible User state and historical preservation are specified. | PASS — transaction, row locking, atomic ActivityLog, soft-delete-aware relations, deterministic history, and assignment/deactivation race hardening are designed. |
| VI. Citizen React SPA & Admin Blade SSR | PASS — F07 is an Admin feature. | PASS — routes remain in `web.php`; UI remains Blade/Tailwind with local Alpine dialogs; Citizen React/API behavior is not replaced. |
| VII. Quality & Definition of Done | PASS — acceptance and measurable performance/security criteria exist. | PASS — Feature/regression tests, isolated PostgreSQL benchmark, `composer run lint`, `npm run lint`, build, and manual responsive/accessibility QA are defined. |
| Engineering: PostgreSQL/Eloquent, protected-by-default, transactions | PASS. | PASS — no alternate datastore/ORM; no public documents; User/audit multi-write is transactional. |

No constitutional exception or complexity waiver is required.

## Project Structure

### Documentation (this feature)

```text
specs/98888-admin-management-search/
|-- plan.md
|-- research.md
|-- data-model.md
|-- quickstart.md
|-- contracts/
|   `-- admin-management.md
|-- checklists/
|   `-- requirements.md
`-- tasks.md                    # Created later by $speckit-tasks, not this command
```

### Source Code (repository root)

```text
app/
|-- Actions/
|   |-- Application/
|   |   |-- AssignApplicationAction.php       # lock/revalidate candidate integration
|   |   `-- ClaimApplicationAction.php        # lock/revalidate actor integration
|   `-- User/
|       `-- SetUserActiveStatus.php            # new transactional audited mutation
|-- Http/
|   |-- Controllers/Admin/
|   |   |-- Applications/ApplicationController.php
|   |   |-- DashboardController.php
|   |   `-- Users/UserController.php
|   `-- Requests/Admin/
|       |-- Applications/ListApplicationsRequest.php
|       `-- Users/
|           |-- ListUsersRequest.php
|           `-- UpdateUserStatusRequest.php
|-- Models/
|   |-- Application.php                       # visibility/search/date/overdue scopes
|   |-- ApplicationAssignment.php             # historical relations/order
|   |-- ApplicationDocument.php               # historical uploader relation
|   |-- ApplicationStatusHistory.php          # historical relations/order
|   |-- ServiceType.php                       # archived Department read
|   `-- User.php
`-- Policies/
    |-- ApplicationPolicy.php
    |-- ApplicationDocumentPolicy.php
    `-- UserPolicy.php

routes/
`-- web.php

resources/
|-- views/admin/
|   |-- layouts/app.blade.php
|   |-- dashboard.blade.php
|   |-- applications/
|   |   |-- index.blade.php
|   |   `-- show.blade.php
|   `-- users/
|       |-- index.blade.php
|       `-- show.blade.php
|-- css/admin.css                              # only if a missing compact style is needed
`-- js/admin.js                                # reuse existing local dialog behavior

tests/Feature/Admin/
|-- ApplicationSearchTest.php
|-- ApplicationDetailTest.php
|-- ApplicationAuthorizationTest.php          # regression/update existing expectations
|-- ApplicationProcessingTest.php             # F05 regression
|-- UserManagementTest.php
|-- DashboardTest.php                         # replace placeholder assertions
`-- AdminManagementQueryPerformanceTest.php   # separately grouped PostgreSQL benchmark

tests/Feature/Api/V1/
`-- ApplicationAuthorizationTest.php          # document/Citizen regression
```

**Structure Decision**: Keep the existing single Laravel application and its established Admin namespaces. Application retrieval stays in the current Admin Application controller plus reusable Eloquent scopes; the only new business Action is the transactional User status change. Existing Application/User/Department/Service models are reused, and no parallel frontend or domain layer is introduced.

## Design and integration boundaries

- **F01** owns authentication, credentials, roles, and general account access. F07 reads those rules and changes only `is_active` through a guarded Admin operation.
- **F02/F03** own Service Type, Department, leader, and membership data. F07 reads them for scope, labels, filters, and deactivation blockers.
- **F04** owns Application submission/form data/documents. F07 presents these read-only and preserves private storage.
- **F05** owns assignment and workflow mutations. F07 reuses its policy-controlled UI actions and only strengthens candidate row locking needed for safe concurrent deactivation.
- **F06** owns history/audit infrastructure and full activity-log UI. F07 writes the required account-state audit through the existing ActivityLog model but does not build the F06 log browser.
- **F08** owns CSV import/export and API documentation. Existing User import navigation/routes stay separate; its broader current authorization is recorded as a dependency defect, not silently changed by this plan.

Implementation must deliver P1 Application list/scope before P2 detail hardening, then P3 User management, and finally P4 dashboard. This order establishes the canonical visibility/query semantics before any consumer reuses them.

## Data and migration strategy

No new entity or initial migration is planned. Current indexes are the baseline. The separate 10,000-row PostgreSQL benchmark is a design gate: if a measured path misses SC-002/SC-006, use its query plan to add only the necessary index in a migration and document why the existing index is insufficient. Caching, materialized views, denormalized deadlines, and `pg_trgm` are deferred unless measurements justify them.

## Validation strategy

Automated tests cover authorization symmetry, scoped option non-disclosure, literal multi-field search, each filter plus combined intersection, inclusive dates, deterministic 20-row pagination, historical soft-delete labels, private documents, all User deactivation guards, transaction/audit rollback, concurrency with F05 assignment, dashboard/drill-down equality, and GET non-mutation. Run performance validation separately on isolated local PostgreSQL and complete manual responsive/keyboard/dialog QA. The current tracked remote testing credential must be removed and rotated before any destructive `RefreshDatabase` test execution.
