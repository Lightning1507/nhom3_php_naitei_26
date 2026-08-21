# Phase 0 Research: F07 - Admin Management & Search

**Feature**: `98888-admin-management-search`  
**Date**: 2026-08-21  
**Inputs reviewed**: F07 specification, project constitution, business analysis, development plan, current Laravel routes/controllers/models/policies/migrations, Admin Blade UI, and existing F01-F06 tests.

## Decision 1: Use one canonical Application visibility scope

**Decision**: `Application::visibleTo(User $actor)` remains the single source of truth for the Application set visible to an internal user. It MUST be applied before search, filters, pagination, dashboard aggregation, and filter-option discovery. `ApplicationPolicy::view()` and `ApplicationDocumentPolicy::download()` MUST delegate to the same rule; an out-of-scope direct request is denied as not found.

The scope is made deny-by-default: active Super Admin sees all non-archived applications, Manager sees applications whose Service Type belongs to a Department they currently lead, Staff sees applications whose current `assigned_staff_id` is their own ID, and every other actor sees none. Citizen ownership behavior in the existing API remains separate and unchanged.

**Rationale**: The current list already calls `visibleTo()`, but the current `ApplicationPolicy::view()` and document policy allow every internal user. That lets Staff or Manager bypass the list scope with a direct URL. One scope also keeps dashboard counts and filter choices from leaking metadata.

**Alternatives considered**:

- Role checks in each controller were rejected because list, detail, dashboard, and download would drift.
- Global scoped route binding was rejected because it could change Citizen API and F05 mutation behavior.
- Client-side filtering or hiding Blade links was rejected because neither is an authorization boundary.

## Decision 2: Keep the query Laravel-first and bounded

**Decision**: Add `ListApplicationsRequest` to normalize and validate query parameters, then compose focused Eloquent scopes for literal keyword search, status groups, submitted-date range, and overdue status. The controller starts from `visibleTo($actor)`, applies validated scopes, eager-loads the list relationships, uses a whitelisted sort mode with an ID tie-breaker, and paginates 20 rows with the query string preserved.

Keyword search groups its `OR` predicates inside one closure and matches application code, Citizen name, Citizen ID, and Service Type name with PostgreSQL `ILIKE`. `%`, `_`, and `\` are escaped so user input is literal. The inclusive end date is implemented as `< start-of-next-day` in the application timezone. `completed` is a virtual filter that maps to `approved OR rejected`; it does not add an Application status.

**Rationale**: This follows the existing F03 Form Request and escaped-search pattern, keeps controllers thin enough, and avoids introducing a repository/interface or a general-purpose query framework for a single feature.

**Alternatives considered**:

- Raw request reads were rejected because enum, ID, date, and page validation would be inconsistent.
- A Repository/DDD layer was rejected as unnecessary for the current Laravel application and ten-day sprint.
- Cursor pagination was rejected because the requirement needs numbered pages.
- Raw column/direction input was rejected; sort values are mapped from a whitelist.

## Decision 3: Derive filter options from the authorized dataset

**Decision**: Service, Department, and assigned-Staff choices are derived with distinct foreign keys from the complete canonical visible Application query before user-selected filters are applied. Labels are then loaded separately, including archived or inactive related records referenced by visible history. An ID absent from those scoped choices receives one generic validation error without exposing a protected label or count.

**Rationale**: Loading all master data leaks organizational metadata to Staff and Manager, while deriving options from only the current page makes valid choices disappear between pages.

**Alternatives considered**:

- Global Service/Department/User dropdowns were rejected because they violate FR-012.
- Options derived from paginated results were rejected because the filter contract would be unstable.
- A JSON autocomplete endpoint was rejected because the bounded server-rendered selects are sufficient at the target scale.

## Decision 4: Extend the existing Admin SSR screens

**Decision**: Keep `Admin\Applications\ApplicationController`, `DashboardController`, and the existing Admin layout. Enhance the current application index/show pages; add `Admin\Users\UserController` with index/show plus a status mutation; add Applications and policy-gated Users navigation. Use Blade, existing `x-admin.*` components, Tailwind utilities, and Alpine only for local confirmation-dialog behavior.

F05 mutation routes and Actions remain their owner. F07 may render those controls through `@can`, but it does not redefine assign, claim, process, supplement, approve, reject, or result-download rules. The F05 unassigned claim queue is not added to Staff's F07 primary list.

**Rationale**: The codebase already has the Admin shell, application views, form/dialog components, and F05 controller actions. Extending those paths avoids duplicate resources and follows the constitution's Admin Blade SSR boundary.

**Alternatives considered**:

- A second Application search controller and duplicate view set were rejected.
- React/API Admin implementation was rejected because it violates the constitution.
- A client-side data-grid/chart package was rejected because it adds no necessary capability.

## Decision 5: Preserve historical labels through soft deletion

**Decision**: Historical read relations used by Application detail are made soft-delete-aware where the related model supports soft deletes: Citizen, assigned Staff, Service Type, responsible Department, assignment actors/staff, document uploader, and status-change actors. Detail rendering labels inactive/archived records instead of treating the missing default relation as missing history. Documents remain private and require Application visibility on every request.

**Rationale**: F07 must keep old applications intelligible after a user, service, or department is archived. Current relations do not consistently use `withTrashed()`, so valid history can disappear from the detail view.

**Alternatives considered**:

- Copying names into new snapshot columns was rejected because existing foreign-key history is sufficient for this feature.
- Restoring archived master data merely to view history was rejected because it changes business state.

## Decision 6: Treat account status changes as an audited transaction

**Decision**: Extend `UserPolicy` without widening F01 profile updates: `viewAny` is active Super Admin-only; `view` permits active Super Admin or preserves the existing active Citizen self-view; existing Citizen self-`update` remains unchanged; and a separate `changeStatus` ability is active Super Admin-only. Add a focused `SetUserActiveStatus` Action. It locks and reloads the target User inside a database transaction, re-authorizes, revalidates all blockers, changes only `is_active`, and creates an `ActivityLog` record in the same transaction with actor, subject, event, before/after status, timestamp, IP, and user agent. Repeating the current state is an idempotent no-op and does not create a duplicate audit event.

Deactivation is rejected for the acting account, the last active Super Admin, a Manager leading an active Department, and Staff/Manager assigned to a non-terminal Application. For last-admin protection, active Super Admin rows are locked in deterministic ID order before counting. Existing F05 assign/claim Actions must lock and revalidate the candidate User row before assignment so assignment cannot race with deactivation.

**Rationale**: Form validation alone cannot make the last-admin and active-assignment invariants atomic. Reusing `is_active` preserves all historical records and existing protected-request middleware makes the change effective on the next request.

**Alternatives considered**:

- Soft-deleting User was rejected because deactivation must be reversible.
- Controller-only checks were rejected because they are race-prone and hard to audit atomically.
- Database triggers were rejected because cross-entity business errors are clearer and more testable in an Action.

## Decision 7: Build dashboard metrics from the same scoped query

**Decision**: Dashboard displays total, received, processing, supplement required, completed, and overdue counts from one conditional aggregate over `Application::visibleTo($actor)`. Completed means approved plus rejected. Overdue means a non-terminal Application whose submitted time plus the Service Type processing duration is before now. Each card links to the matching application-list query (`status=...`, `status=completed`, or `overdue=1`).

**Rationale**: A shared definition guarantees the card count equals its drill-down list and a conditional aggregate avoids one full query per card.

**Alternatives considered**:

- Cached counters/materialized views were rejected because they add invalidation complexity at the 10,000-row target.
- Charts were rejected as outside the approved basic-dashboard scope.
- Separate overdue formulas in dashboard and list were rejected because they can diverge.

## Decision 8: Prove performance before adding schema or packages

**Decision**: No schema migration, extension, or package is planned initially. Existing foreign-key/status/date indexes are retained, eager loading and bounded aggregate/query counts are required, and an isolated PostgreSQL benchmark with at least 10,000 Applications and 10,000 Users measures the two-second acceptance target. Only if that benchmark fails will implementation add a targeted, explained index migration based on `EXPLAIN (ANALYZE, BUFFERS)` evidence.

Likely candidates, only if measured, are composite partial indexes matching visibility plus `submitted_at/id` ordering and PostgreSQL trigram indexes for contains-search. Denormalized deadlines, caches, and materialized views are not part of the first design.

**Rationale**: The current schema already indexes the principal foreign keys and statuses. Adding several write-costly indexes and `pg_trgm` without evidence conflicts with Laravel-first simplicity, while the benchmark preserves the measurable F07 performance gate.

**Alternatives considered**:

- Adding all speculative B-tree/GIN indexes immediately was rejected because current query plans have not been measured.
- Timing assertions in the normal Feature suite were rejected as environment-dependent and flaky.
- Loading and filtering 10,000 rows in PHP was rejected for memory, performance, and authorization reasons.

## Decision 9: Validate with risk-focused Feature tests

**Decision**: Add Admin Feature tests for application search/scope/detail, user management, dashboard/drill-down, and a separately invoked PostgreSQL performance benchmark. Regression coverage includes F05 workflow actions, Admin authentication boundaries, Citizen Application/document authorization, and F03 leadership relationships. Manual browser QA covers responsive overflow, keyboard focus, dialog confirmation, and non-color-only status meaning; no browser-test dependency is added.

Tests MUST run only against an isolated local disposable PostgreSQL database. The currently tracked PHPUnit configuration contains a remote database credential and MUST NOT be used for destructive tests; that credential must be removed from version control and rotated without copying it into any feature artifact.

**Rationale**: Authorization and transactional blockers have higher risk than pure presentation. Feature tests exercise middleware, policies, Eloquent, Blade, pagination, and audit behavior together, while a separate benchmark avoids slowing and destabilizing every test run.

**Alternatives considered**:

- Unit tests alone were rejected because they do not prove route/policy/UI boundaries.
- Running the current test suite against its configured remote database was rejected because many tests use `RefreshDatabase`.
- Adding Dusk/Playwright was rejected because the Admin interactions are progressive and can be manually verified without a new dependency.

## Related dependency defect

The existing F08 User-import authorization currently appears broader than F07's Super Admin-only User-management boundary. F07 will keep import routes/navigation separate and record this as an F08/F01 integration defect rather than silently expanding F07 into CSV import behavior.
