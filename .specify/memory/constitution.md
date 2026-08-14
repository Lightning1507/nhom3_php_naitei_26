<!--
Sync Impact Report
- Version change: 1.0.0 -> 1.1.0
- Modified principles:
  - I. Laravel-First & Simplicity -> I. Laravel-First Backend & Simplicity
  - IV. Authorization & Data Protection (clarified Citizen/Admin interface boundaries)
  - VI. API & Code Consistency -> VI. Citizen React SPA & Admin Blade SSR
- Added sections: none
- Removed sections: none
- Follow-up TODOs: none
-->
# Public Service Management System Constitution

## Core Principles

### I. Laravel-First Backend & Simplicity
The backend MUST use PHP 8.5 and Laravel 13 conventions and favor the simplest architecture that
clearly meets the requirement. The standard backend request flow MUST be Route -> Controller ->
Form Request -> Policy/Gate -> Service/Action when needed -> Eloquent. Controllers MUST remain
thin. Repositories, interfaces, DDD, Clean Architecture, microservices, and similar abstractions
MUST NOT be introduced without a documented, concrete need. Design decisions SHOULD optimize
delivery and maintainability for a four-person team working in a ten-day sprint. This keeps the
codebase understandable and prevents architectural overhead from displacing public-service value.

### II. Feature-Driven Development
All business functionality MUST be delivered as a Spec Kit feature through Specify -> Plan -> Tasks
-> Implement -> Test. Unspecified business functionality MUST NOT be implemented. Every feature
MUST define a bounded scope and testable acceptance criteria before implementation. Redmine MUST
remain the source of truth for ownership, estimates, deadlines, and status; GitHub MUST remain the
source of truth for branches, pull requests, reviews, and merges. This provides traceability from
an approved need to reviewed code without duplicating project-management responsibilities.

### III. Application-Centric Domain
`Application` MUST remain the core business entity, and every affected feature MUST preserve the
public-service application lifecycle. The base statuses are `received`, `processing`,
`supplement_required`, `approved`, and `rejected`. Status transitions MUST be defined by explicit,
testable business rules and MUST NOT be added, removed, reordered, or bypassed arbitrarily. This
protects the meaning and integrity of the system's central workflow.

### IV. Authorization & Data Protection
The authorization model MUST recognize Citizen, Staff, Manager, and Super Admin roles. Citizens
MUST use the React Citizen area through the REST API; Staff, Manager, and Super Admin MUST use the
server-rendered Blade Admin area. Every protected operation MUST enforce server-side authorization
with middleware, policies, or gates. Citizens MUST access only their own applications and documents
unless an explicit business rule grants additional access. Citizen-uploaded documents MUST use
private storage. React or Blade visibility MUST NOT be treated as an authorization control. These
rules prevent disclosure or modification of sensitive public-service data.

### V. Database Integrity & Auditability
The data model MUST use foreign keys, unique constraints, indexes, transactions, and soft deletes
where they protect valid relationships, concurrency, performance, or recoverability. Important
historical records MUST be preserved. Current state MUST be modeled separately from history; for
example, `Application.status` versus `StatusHistory`, and `assigned_staff_id` versus
`AssignmentHistory`. Important workflow actions MUST identify what changed, when it changed, and
the responsible actor. This ensures operational decisions can be reconstructed and audited.

### VI. Citizen React SPA & Admin Blade SSR
The Citizen Site MUST be a React.js SPA styled with Tailwind CSS and MUST consume versioned Laravel
REST endpoints under `/api/v1/...`. Citizen business screens MUST be implemented in
`resources/js/citizen`; Blade MAY provide only the initial HTML shell and React mount point.
Citizen screens MUST NOT use Blade rendering or Alpine.js for business UI behavior.

The Admin Site MUST use Laravel Blade server-side rendering under `/admin/...`, Tailwind CSS for
styling, and Alpine.js only for progressive, local UI interactions. Admin features MUST NOT be
implemented as a React SPA unless this constitution is amended first. React and Alpine MUST NOT
control the same DOM subtree.

Citizen APIs MUST follow one documented response structure. Validation MUST reside in Form
Requests, response transformation MUST use API Resources where applicable, authorization MUST
reside in policies or gates, and non-trivial business logic shared by Citizen and Admin MUST reside
in focused services or actions. These boundaries make the rendering model and ownership of every
interface immediately recognizable and prevent duplicate business logic.

### VII. Quality & Definition of Done
A feature is Done only when its approved scope is implemented, required validation and
authorization are present, relevant tests pass, and the code has been reviewed and merged. Tests
MUST cover applicable core workflow transitions, authorization boundaries, data-integrity rules,
and other critical business rules. Security and data-integrity defects MUST receive high priority.
Additional tests SHOULD be proportional to business risk and regression impact. This definition
prevents schedule pressure from converting incomplete or unsafe work into completed work.

## Engineering Constraints

- PHP 8.5, Laravel 13, PostgreSQL, and Eloquent MUST remain the backend and persistence baseline.
- Citizen frontend dependencies MUST use the dedicated React/Vite entry under
  `resources/js/citizen`; Admin JavaScript MUST use the dedicated Alpine/Vite entry under
  `resources/js/admin`.
- Tailwind CSS MUST be the shared styling system, but Citizen React components and Admin Blade
  templates MUST remain structurally separate.
- `routes/api.php` MUST own Citizen JSON endpoints. `routes/web.php` MUST own the Citizen SPA shell,
  client-route fallback, and Admin Blade routes without exposing Citizen business operations as
  Blade form handlers.
- Laravel Sanctum MUST provide first-party Citizen SPA authentication using secure cookie/session
  authentication and CSRF protection. First-party Citizen access tokens MUST NOT be stored in
  browser `localStorage`.
- Laravel session authentication MUST protect Admin web routes. Both authentication paths MUST
  enforce authorization on the server.
- PHPUnit and Laravel Feature Tests MUST cover backend behavior. API tests MUST live under
  `tests/Feature/Api`, and Admin SSR tests MUST live under `tests/Feature/Admin`.
- New architectural layers or dependencies MUST include a concrete use case and a simpler-option
  assessment in the feature plan or pull request.
- Protected data MUST be denied by default unless a policy, gate, or middleware rule allows access.
- Workflow mutations that span multiple related writes MUST use a database transaction.
- API formats, route areas, status values, and historical models MUST remain consistent with the
  principles above unless a constitution amendment explicitly changes them.

## Delivery Workflow and Quality Gates

- Each business change MUST begin with a Spec Kit specification containing scope and acceptance
  criteria, followed by a plan and dependency-ordered tasks before implementation.
- Each task MUST be traceable to its Spec Kit feature and its Redmine ownership record.
- Pull requests MUST identify the implemented acceptance criteria, relevant tests, authorization
  impact, data-integrity impact, and any justified architectural exception.
- Reviewers MUST verify constitution compliance before approval. Any MUST-rule exception requires
  a constitution amendment; a SHOULD-rule exception requires written rationale in the plan or PR.
- Required tests MUST pass before merge. Review and merge MUST occur before a feature is marked
  Done in Redmine.

## Governance

This constitution supersedes conflicting project conventions and informal practices. Amendments
MUST be proposed in writing, explain the rationale and operational impact, include a migration plan
when existing code or data is affected, and receive team review before merge. Versions MUST follow
semantic versioning: MAJOR for incompatible governance changes or principle removals, MINOR for new
principles or materially expanded obligations, and PATCH for clarifications that do not change
obligations. Every feature plan and pull request review MUST include a constitution compliance
check. Complexity and exceptions MUST be justified in the relevant plan or pull request, and
unresolved violations MUST block merge.

**Version**: 1.1.0 | **Ratified**: 2026-08-11 | **Last Amended**: 2026-08-13
