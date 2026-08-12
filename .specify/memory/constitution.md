<!--
Sync Impact Report
- Version change: unratified scaffold -> 1.0.0
- Modified principles: template placeholders -> seven project-specific principles
- Added sections: Engineering Constraints; Delivery Workflow and Quality Gates
- Removed sections: none
- Follow-up TODOs: none
-->
# Public Service Management System Constitution

## Core Principles

### I. Laravel-First & Simplicity
The system MUST follow Laravel 13 conventions and favor the simplest architecture that clearly
meets the requirement. The standard request flow MUST be Route -> Controller -> Form Request ->
Policy/Gate -> Service/Action when needed -> Eloquent. Controllers MUST remain thin. Repositories,
interfaces, DDD, Clean Architecture, microservices, and similar abstractions MUST NOT be introduced
without a documented, concrete need. Design decisions SHOULD optimize delivery and maintainability
for a four-person team working in a ten-day sprint. This keeps the codebase understandable and
prevents architectural overhead from displacing public-service value.

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
MUST use the Citizen/API area; Staff, Manager, and Super Admin MUST use the Internal/Admin area.
Every protected operation MUST enforce server-side authorization with middleware, policies, or
gates. Citizens MUST access only their own applications and documents unless an explicit business
rule grants additional access. Citizen-uploaded documents MUST use private storage. Frontend
visibility MUST NOT be treated as an authorization control. These rules prevent disclosure or
modification of sensitive public-service data.

### V. Database Integrity & Auditability
The data model MUST use foreign keys, unique constraints, indexes, transactions, and soft deletes
where they protect valid relationships, concurrency, performance, or recoverability. Important
historical records MUST be preserved. Current state MUST be modeled separately from history; for
example, `Application.status` versus `StatusHistory`, and `assigned_staff_id` versus
`AssignmentHistory`. Important workflow actions MUST identify what changed, when it changed, and
the responsible actor. This ensures operational decisions can be reconstructed and audited.

### VI. API & Code Consistency
Citizen REST endpoints MUST use the `/api/v1/...` prefix, while Internal/Admin server-rendered
routes MUST use `/admin/...`. Citizen APIs MUST follow one documented response structure.
Validation MUST reside in Form Requests, authorization MUST reside in policies or gates, and
non-trivial business logic MUST reside in focused services or actions. Implementations SHOULD
prefer clear, Laravel-native code over clever or speculative abstractions. Consistency reduces
review time, defects, and onboarding cost.

### VII. Quality & Definition of Done
A feature is Done only when its approved scope is implemented, required validation and
authorization are present, relevant tests pass, and the code has been reviewed and merged. Tests
MUST cover applicable core workflow transitions, authorization boundaries, data-integrity rules,
and other critical business rules. Security and data-integrity defects MUST receive high priority.
Additional tests SHOULD be proportional to business risk and regression impact. This definition
prevents schedule pressure from converting incomplete or unsafe work into completed work.

## Engineering Constraints

- Laravel 13 conventions and framework-native capabilities MUST be the default technical choice.
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

**Version**: 1.0.0 | **Ratified**: 2026-08-11 | **Last Amended**: 2026-08-11
