---

description: "Dependency-ordered implementation tasks for F07 Admin Management & Search"
---

# Tasks: F07 - Admin Management & Search

**Input**: Design documents from `/specs/98888-admin-management-search/`

**Prerequisites**: `plan.md`, `spec.md`, `research.md`, `data-model.md`, `contracts/admin-management.md`, `quickstart.md`

**Tests**: Required by the specification and constitution. Write the story-specific Feature tests first, confirm they fail for the intended missing behavior, then implement until they pass.

**Organization**: Tasks are grouped by user story so each increment can be implemented and validated independently. All test commands must target an isolated local disposable PostgreSQL database.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel after its phase prerequisites because it changes different files and does not depend on an incomplete task in the same group.
- **[Story]**: Maps the task to User Story 1-4 from `spec.md`.
- Every task names the exact repository path it changes or validates.

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Make the existing Laravel test environment safe before any `RefreshDatabase` test is executed. No new dependency is required.

- [X] T001 Replace every tracked remote database value with isolated local test defaults in `phpunit.xml`, align them with `.env.testing`, and verify no credential remains in either file

---

## Phase 2: Foundational (Blocking Prerequisite)

**Purpose**: Enforce a fail-fast safety boundary shared by every F07 Feature and performance test.

**CRITICAL**: Do not start or run user-story tests until this phase is complete and the exposed remote database credential has been rotated outside the repository.

- [X] T002 Add a testing-database safety guard in `tests/TestCase.php` that aborts destructive tests unless the environment is `testing`, the PostgreSQL host is local/approved-isolated, and the database name is explicitly dedicated to tests

**Checkpoint**: PHPUnit can only reach the disposable local testing database; user-story work may begin.

---

## Phase 3: User Story 1 - Tra cứu danh sách hồ sơ đúng phạm vi (Priority: P1) MVP

**Goal**: Staff, Manager, and Super Admin receive only authorized Applications and can safely search, filter, sort, and paginate 20 rows while scoped filter choices and empty states reveal no protected data.

**Independent Test**: Seed Applications across multiple Citizens, Services, Departments, Staff, and statuses; sign in as each internal role; verify role scope, four-field literal search, all individual and combined filters, inclusive dates, stable sorting/pagination, retained query parameters, scoped choices, and both empty-state variants.

### Tests for User Story 1

- [X] T003 [P] [US1] Write failing role-scope and scoped-filter-option tests for Staff, Manager, Super Admin, inactive/internal-invalid actors, multi-Department Managers, and duplicate-free results in `tests/Feature/Admin/ApplicationSearchTest.php`
- [X] T004 [P] [US1] Write failing keyword, wildcard-escaping, status-group, Service, Department, Staff, overdue, inclusive-date, combined-AND, and invalid/out-of-scope query tests in `tests/Feature/Admin/ApplicationFilterValidationTest.php`
- [X] T005 [P] [US1] Write failing 20-row stable-sort, query-retaining pagination, invalid/out-of-range page, empty-scope versus no-match, reset-filter, bounded-query-count, and GET non-mutation tests in `tests/Feature/Admin/ApplicationPaginationTest.php`

### Implementation for User Story 1

- [X] T006 [P] [US1] Implement query trimming/defaults, enum/virtual-status/date/boolean/page/sort validation, inclusive date rules, and generic scoped-ID validation in `app/Http/Requests/Admin/Applications/ListApplicationsRequest.php`
- [X] T007 [P] [US1] Add read-only label, badge, terminal-status, completed-group, and whitelisted sort helpers without adding a lifecycle state in `app/Enums/ApplicationStatus.php`
- [X] T008 [US1] Harden deny-by-default `visibleTo()` and add escaped multi-relation search, status-group, scoped ID, inclusive submitted-date, overdue, and deterministic sort scopes in `app/Models/Application.php`
- [X] T009 [US1] Refactor `index()` to consume `ListApplicationsRequest`, apply the canonical scope before every criterion, derive distinct Service/Department/Staff options from the unfiltered authorized set, eager-load row data, redirect an obsolete page safely, and paginate 20 rows in `app/Http/Controllers/Admin/Applications/ApplicationController.php`
- [X] T010 [US1] Implement the complete responsive search/filter/sort form, required table columns, inactive/archived option labels, pagination links, reset action, and distinct empty states in `resources/views/admin/applications/index.blade.php`
- [X] T011 [P] [US1] Add the policy-safe “Hồ sơ” navigation item for all active internal roles without changing Citizen or F08 import navigation in `resources/views/admin/layouts/app.blade.php`

**Checkpoint**: User Story 1 is independently demoable as the MVP; all US1 tests pass against local PostgreSQL.

---

## Phase 4: User Story 2 - Xem đầy đủ chi tiết hồ sơ được phép (Priority: P2)

**Goal**: The detail and private-document paths use exactly the list visibility scope, mask out-of-scope existence, preserve archived historical labels, and show the full read model while leaving every F05 workflow mutation authoritative.

**Independent Test**: For each internal role, open visible and out-of-scope Applications by link and direct URL; verify masked denial, authorized private download, complete form/result/history data, deterministic timelines, archived/inactive labels, current-scope recheck after reassignment, and no GET mutation.

### Tests for User Story 2

- [X] T012 [P] [US2] Write failing detail authorization, 404-masking, reassignment recheck, complete read-model, deterministic history, archived relation, terminal result/rejection, and GET non-mutation tests in `tests/Feature/Admin/ApplicationDetailTest.php`
- [X] T013 [P] [US2] Update failing Citizen/internal document tests so parent Application visibility is required while Citizen ownership behavior remains unchanged in `tests/Feature/Api/V1/ApplicationAuthorizationTest.php`
- [X] T014 [P] [US2] Add regression assertions that F05 action visibility and assign/claim/process/supplement/approve/reject behavior still use existing policies and Actions in `tests/Feature/Admin/ApplicationProcessingTest.php`

### Implementation for User Story 2

- [X] T015 [P] [US2] Make internal `view` authorization query `Application::visibleTo($user)` and return `denyAsNotFound()` outside scope while preserving Citizen ownership in `app/Policies/ApplicationPolicy.php`
- [X] T016 [P] [US2] Require parent-Application visibility and document ownership/association for private downloads instead of allowing every internal actor in `app/Policies/ApplicationDocumentPolicy.php`
- [X] T017 [P] [US2] Add explicit soft-delete-aware historical read relations for Citizen, assigned Staff, Service Type, and responsible Department without widening operational F05 eligibility relations in `app/Models/Application.php` and `app/Models/ServiceType.php`
- [X] T018 [P] [US2] Make assignment Staff/actor, status-change actor, and document uploader historical relations soft-delete-aware with deterministic timestamp-plus-ID ordering in `app/Models/ApplicationAssignment.php`, `app/Models/ApplicationStatusHistory.php`, and `app/Models/ApplicationDocument.php`
- [X] T019 [US2] Refactor `show()` and document download to authorize the canonical scope on every request, eager-load the full historical read model, verify the document belongs to the bound Application, and avoid workflow mutations in `app/Http/Controllers/Admin/Applications/ApplicationController.php`
- [X] T020 [US2] Render Citizen, Service, Department, current Staff, form data, documents, assignments, status history, all timestamps, result/rejection, archived badges, and existing `@can`-guarded F05 actions in `resources/views/admin/applications/show.blade.php`
- [ ] T021 [US2] Run and resolve the focused US2 and F05 regression suite in `tests/Feature/Admin/ApplicationDetailTest.php`, `tests/Feature/Admin/ApplicationAuthorizationTest.php`, `tests/Feature/Admin/ApplicationProcessingTest.php`, and `tests/Feature/Api/V1/ApplicationAuthorizationTest.php`

**Checkpoint**: User Story 2 can be validated from direct URLs independently; authorized history remains readable and protected data remains undisclosed.

---

## Phase 5: User Story 3 - Quản lý trạng thái người dùng (Priority: P3)

**Goal**: Super Admin alone can search/view safe User data and atomically activate/deactivate eligible accounts without changing roles, identity, organization, assignments, or history.

**Independent Test**: As Super Admin, search/filter/page Users, inspect a safe detail, deactivate and reactivate eligible Citizen/internal accounts, confirm next-request access changes and audit evidence; verify every blocker, concurrency case, secret omission, preservation rule, and non-Super-Admin denial.

### Tests for User Story 3

- [x] T022 [P] [US3] Write failing guest/role authorization, literal name-email-Citizen-ID search, role/status intersection, newest stable pagination, safe detail, archived organization label, empty state, and secret-omission tests in `tests/Feature/Admin/UserManagementTest.php`
- [x] T023 [P] [US3] Write failing activate/deactivate, next-protected-request denial, self/last-admin/active-leader/unfinished-assignment guards, terminal-or-archived dependency exceptions, idempotent no-op, preservation, atomic audit, and audit-rollback tests in `tests/Feature/Admin/UserStatusManagementTest.php`
- [x] T024 [P] [US3] Write PostgreSQL concurrency/stale-model tests for simultaneous last-admin deactivation and assign/claim versus deactivation serialization in `tests/Feature/Admin/UserStatusConcurrencyTest.php`

### Implementation for User Story 3

- [x] T025 [P] [US3] Implement normalized literal search plus UserRole/status/page validation for the Super Admin list in `app/Http/Requests/Admin/Users/ListUsersRequest.php`
- [x] T026 [P] [US3] Validate only the desired boolean `is_active` state and authorize the distinct status ability in `app/Http/Requests/Admin/Users/UpdateUserStatusRequest.php`
- [x] T027 [P] [US3] Add active-Super-Admin `viewAny`, administrative `view`, and separate `changeStatus` abilities while preserving active-Citizen self-view/self-update behavior in `app/Policies/UserPolicy.php`
- [x] T028 [US3] Implement `SetUserActiveStatus` with deterministic Super Admin row locking, fresh actor/target authorization, all deactivation guards, idempotent no-op, `is_active`-only mutation, and atomic `user.activated`/`user.deactivated` ActivityLog metadata in `app/Actions/User/SetUserActiveStatus.php`
- [x] T029 [P] [US3] Lock and revalidate a fresh active Staff User before locking/writing an Application so assign/claim cannot race account deactivation, without changing F05 transitions, in `app/Actions/Application/AssignApplicationAction.php` and `app/Actions/Application/ClaimApplicationAction.php`
- [x] T030 [US3] Implement Super Admin User index/show/status endpoints with safe selected columns, escaped query composition, bounded organization/Application summaries, archived organization labels, 20-row pagination, policy calls, Action delegation, and PRG flash/errors in `app/Http/Controllers/Admin/Users/UserController.php`
- [x] T031 [US3] Register GET index/show and PATCH status routes with numeric `{user}` constraints after the existing static import routes in `routes/web.php`
- [x] T032 [P] [US3] Complete the responsive Super Admin User list with search, role/status filters, safe columns, deterministic pagination, reset and empty states in `resources/views/admin/users/index.blade.php`
- [x] T033 [P] [US3] Create the safe User detail, organization/Application summaries, inactive/archive labels, guarded activate/deactivate forms, and keyboard-accessible confirmation dialog in `resources/views/admin/users/show.blade.php`
- [x] T034 [US3] Add the `UserPolicy::viewAny`-gated “Người dùng” navigation item while keeping F08 Import separate in `resources/views/admin/layouts/app.blade.php`
- [ ] T035 [US3] Run and resolve User-management plus F01 inactive-account/profile regressions in `tests/Feature/Admin/UserManagementTest.php`, `tests/Feature/Admin/UserStatusManagementTest.php`, `tests/Feature/Admin/UserStatusConcurrencyTest.php`, and `tests/Feature/Api/V1/Auth/InactiveAccountAccessTest.php`

**Checkpoint**: User Story 3 is independently usable by Super Admin; account state, audit, concurrency, and F01/F03/F05 boundaries are proven.

---

## Phase 6: User Story 4 - Theo dõi tổng quan vận hành (Priority: P4)

**Goal**: Each internal role sees six metrics calculated from its canonical Application scope and can drill into a list whose total uses the identical status/overdue definition.

**Independent Test**: Seed all statuses and deadlines across roles/Departments; compare total, received, processing, supplement-required, completed, and overdue cards with expected scoped counts and with each linked Application list, including zero-scope roles.

### Tests for User Story 4

- [ ] T036 [P] [US4] Replace placeholder coverage with failing Staff/Manager/Super Admin scope, six-metric definition, zero-state, drill-down equality, bounded-query-count, and GET non-mutation tests in `tests/Feature/Admin/DashboardTest.php`

### Implementation for User Story 4

- [ ] T037 [US4] Build one conditional aggregate from `Application::visibleTo($actor)` using the shared completed and overdue definitions and expose matching drill-down queries in `app/Http/Controllers/Admin/DashboardController.php`
- [ ] T038 [P] [US4] Replace the placeholder with responsive metric cards, zero values, explanatory status text, and accessible links to the corresponding filtered Application list in `resources/views/admin/dashboard.blade.php`
- [ ] T039 [US4] Run and resolve dashboard/list consistency tests in `tests/Feature/Admin/DashboardTest.php` and `tests/Feature/Admin/ApplicationSearchTest.php`

**Checkpoint**: All four user stories are independently functional; dashboard figures equal their authorized drill-down lists.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Prove scale, close regressions, and validate the complete Admin experience without expanding F07 into F05/F08.

- [ ] T040 [P] Create an explicitly grouped PostgreSQL benchmark with at least 10,000 Applications and 10,000 Users covering list/search/filter/page/dashboard/User-list p95 timing and bounded query counts in `tests/Feature/Admin/AdminManagementQueryPerformanceTest.php`
- [ ] T041 Run the benchmark from `tests/Feature/Admin/AdminManagementQueryPerformanceTest.php`, record timings/query plans and the SC-002/SC-006 decision in `specs/98888-admin-management-search/performance-results.md`, and add only evidence-backed indexes when required in `database/migrations/2026_08_21_120000_optimize_admin_management_queries.php`
- [ ] T042 Run the full safe-local backend suite plus `composer run lint`, `npm run lint`, and `npm run build`; fix only F07 regressions in `app/`, `resources/views/admin/`, `resources/css/admin.css`, `resources/js/admin.js`, and `tests/Feature/`
- [ ] T043 Execute every authorization, search/filter, detail, dashboard, User-status, keyboard/dialog, 1089px desktop, and 375px mobile scenario in `specs/98888-admin-management-search/quickstart.md` and update that file only if the executable validation instructions proved inaccurate

---

## Dependencies & Execution Order

### Phase dependencies

```text
Phase 1 Setup
    |
    v
Phase 2 Safe Test Foundation
    |
    +---------------------------+
    |                           |
    v                           v
US1 Application List (MVP)    US3 User Management backend/tests
    |                           |
    +-------------+             |
    |             |             |
    v             v             |
US2 Detail      US4 Dashboard   |
    |             |             |
    +-------------+-------------+
                  |
                  v
        Phase 7 Polish/Performance
```

- **Phase 1** has no dependency and removes the unsafe tracked test target.
- **Phase 2** depends on Phase 1 and blocks every Feature test.
- **US1** depends on Phase 2 and establishes the canonical visibility/query semantics.
- **US2** depends on US1 because direct detail/document authorization must reuse the US1 scope.
- **US3** depends on Phase 2; most work can run parallel to US1/US2, but T029 and T034 must be integrated after checking the current F05 Actions and shared Admin layout.
- **US4** depends on US1 because dashboard aggregation and drill-down reuse its status/overdue scopes.
- **Phase 7** depends on every story selected for the release.

### Within each user story

1. Write the listed tests first and confirm they fail for the intended missing behavior.
2. Implement model/policy/Form Request primitives before controller composition.
3. Implement routes/controller view models before final Blade integration.
4. Run the focused tests and regressions at the story checkpoint.
5. Do not use F07 tasks to rewrite F05 transitions or F08 import/export behavior.

## Parallel Opportunities

- After T002, US1 test files T003-T005 can be written in parallel.
- After the US1 failing tests exist, T006 and T007 can proceed in parallel; T011 is isolated from backend query work.
- In US2, T012-T014 can proceed in parallel; T015-T018 modify separate policy/model files and can be split across developers before T019 integration.
- US3 list/detail tests T022, mutation tests T023, and concurrency tests T024 are independent files; T025-T027 can then proceed in parallel, and T029 is isolated from User-controller/view work.
- US4 view T038 can be built from the contract while T037 implements the aggregate after T036 defines the expected behavior.
- US3 can run alongside US1/US2 after the safe test foundation, with coordination only on `resources/views/admin/layouts/app.blade.php` and the two F05 Actions.
- T040 can be prepared while manual UI review begins, but T041-T043 require the selected stories to be complete.

## Parallel Example: User Story 1

```text
Developer A: T003 -> T008 -> T009
Developer B: T004 -> T006 -> T010
Developer C: T005 -> T007 -> T011
Integrate: run the complete US1 checkpoint suite
```

## Parallel Example: User Story 2

```text
Developer A: T012 -> T015 -> T019
Developer B: T013 -> T016 -> T018
Developer C: T014 -> T017 -> T020
Integrate: T021
```

## Parallel Example: User Story 3

```text
Developer A: T022 -> T025 -> T030 -> T031
Developer B: T023 -> T026 -> T027 -> T028
Developer C: T024 -> T029 -> T032 -> T033
Integrate shared navigation: T034
Validate: T035
```

## Parallel Example: User Story 4

```text
Developer A: T036 -> T037
Developer B: T038
Integrate and validate: T039
```

## Implementation Strategy

### MVP first

1. Complete T001-T002 and confirm the test database is isolated locally.
2. Complete T003-T011 for User Story 1.
3. Stop and validate every US1 acceptance scenario.
4. Demo the role-scoped Application worklist as the F07 MVP.

### Incremental delivery

1. **MVP**: US1 provides secure Application discovery and paging.
2. **Increment 2**: US2 closes direct-access/document gaps and completes historical detail.
3. **Increment 3**: US3 adds safe Super Admin account operations and audit/concurrency protection.
4. **Increment 4**: US4 adds scoped dashboard metrics and drill-down.
5. **Release gate**: performance, full regression, lint/build, and manual quickstart validation.

### Scope controls

- Do not create/edit passwords, roles, Citizen identity, Department memberships/leaders, or physical User deletion.
- Do not add a sixth Application status; `completed` is a read-only query group.
- Do not add F05 workflow mutation paths; retain its Actions and policies.
- Do not absorb F08 CSV import/export/API documentation; the broader current import authorization remains a recorded F08/F01 dependency defect.
- Do not add indexes, `pg_trgm`, cache, materialized views, a repository, React Admin code, or a new package without measured evidence and plan-level justification.

## Notes

- `[P]` means separate files and no dependency on another incomplete task in the same parallel set.
- All tests using `RefreshDatabase` are forbidden until T001-T002 pass and the external credential is rotated.
- Commit after each task or small logical group; each pull request must cite F07 acceptance criteria and authorization/data-integrity impact.
- Stop at each checkpoint to validate the story independently before continuing to the next priority.
