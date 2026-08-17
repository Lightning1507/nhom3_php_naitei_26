# Tasks: F03 - Department & Staff Management

**Input**: Design documents from `specs/98884-department-staff-management/`

**Prerequisites**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md), [data-model.md](./data-model.md), [contracts/admin-departments.md](./contracts/admin-departments.md), [quickstart.md](./quickstart.md), [design-context.md](./design-context.md)

**Tests**: Test tasks are included because the specification defines independent tests and measurable outcomes, while the constitution requires coverage for authorization, data integrity, transactions and auditability. Within each user story, write the listed tests first and confirm they fail for the expected reason before implementation.

**Organization**: Tasks are grouped by user story so each story can be implemented, tested and demonstrated as an incremental deliverable after the shared foundation is complete.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel with other marked tasks in the same stage because files do not conflict and prerequisites are already satisfied.
- **[Story]**: Maps the task to a user story in `spec.md`.
- Every executable task includes an exact file path or command/config target.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Prepare reusable test data and Admin presentation primitives without adding new dependencies.

- [X] T001 Add F03 factory states for active/inactive Staff, Manager, led Department and archived Department in database/factories/UserFactory.php and database/factories/DepartmentFactory.php
- [X] T002 [P] Add compact Admin-only button, input, table, card and destructive variants without changing Citizen component sizing in resources/css/app.css
- [X] T003 [P] Extend the shared Admin navigation shell with Department navigation, flash-message slots and responsive container behavior in resources/views/admin/layouts/app.blade.php

**Checkpoint**: Test fixtures and Admin shell conventions are ready for the shared foundation.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Establish the database, model, authorization, concurrency, audit and Blade component contracts required by every story.

**CRITICAL**: No user-story implementation should begin until this phase is complete.

- [X] T004 Create the PostgreSQL migration that preflights canonical code collisions, normalizes valid codes, adds the code check constraint, `lock_version`, `departments.leader_id` index and `department_user.user_id` index in database/migrations/2026_08_17_000000_harden_departments_for_management.php
- [X] T005 [P] Add `lock_version`, scoped active/archived queries, historical leader/member relationships, count helpers and soft-deleted route resolution to app/Models/Department.php
- [X] T006 [P] Add reusable active Staff/Manager membership and active Manager leadership query scopes while preserving existing F01 role helpers in app/Models/User.php
- [X] T007 [P] Create the stale-version domain exception and render its actionable Blade `409` response in app/Exceptions/StaleDepartmentVersion.php and bootstrap/app.php
- [X] T008 [P] Create the focused Department audit writer with actor/request context and before/after snapshots in app/Support/Department/DepartmentActivityLogger.php
- [X] T009 Implement deny-by-default Department abilities and explicit policy registration in app/Policies/DepartmentPolicy.php and app/Providers/AppServiceProvider.php
- [X] T010 [P] Create reusable accessible Admin button, badge and native-dialog Blade components in resources/views/components/admin/button.blade.php, resources/views/components/admin/badge.blade.php and resources/views/components/admin/dialog.blade.php

**Checkpoint**: Database integrity, scoped access, audit, optimistic concurrency and shared Admin components are ready; user-story work may begin.

---

## Phase 3: User Story 1 - Quản lý thông tin phòng ban (Priority: P1) MVP

**Goal**: Super Admin can create, view and update Department identity data with canonical unique codes and field-level validation; other roles cannot mutate Department identity.

**Independent Test**: Log in as Super Admin, create a Department without a leader, edit its name/address, open list/detail and verify persisted values; then verify a case-variant duplicate code and all non-Super-Admin mutations are rejected without partial writes.

### Tests for User Story 1

- [ ] T011 [P] [US1] Add guest, Citizen, Staff, Manager and Super Admin create/update route-boundary tests in tests/Feature/Admin/Departments/DepartmentAuthorizationTest.php
- [ ] T012 [P] [US1] Add Department create/read/update, canonical code, invalid fields, concurrent duplicate and stale-version tests in tests/Feature/Admin/Departments/DepartmentManagementTest.php
- [ ] T013 [P] [US1] Add `department.created` and `department.updated` actor/snapshot/rollback assertions in tests/Feature/Admin/Departments/DepartmentAuditTest.php

### Implementation for User Story 1

- [ ] T014 [P] [US1] Implement name/code/address normalization, validation and archived-code uniqueness for create in app/Http/Requests/Admin/Departments/StoreDepartmentRequest.php
- [ ] T015 [P] [US1] Implement update validation, self-ignore uniqueness and required version token in app/Http/Requests/Admin/Departments/UpdateDepartmentRequest.php
- [ ] T016 [P] [US1] Implement transactional Department creation, unique-constraint error mapping and create audit in app/Actions/Department/CreateDepartment.php
- [ ] T017 [P] [US1] Implement locked version comparison, identity update, version increment, duplicate error mapping and update audit in app/Actions/Department/UpdateDepartment.php
- [ ] T018 [US1] Implement the basic active Department list, create/store, show, edit/update and policy authorization flow in app/Http/Controllers/Admin/Departments/DepartmentController.php
- [ ] T019 [P] [US1] Create the shared Department identity form and dedicated create/edit pages with field errors and version token in resources/views/admin/departments/partials/form.blade.php, resources/views/admin/departments/create.blade.php and resources/views/admin/departments/edit.blade.php
- [ ] T020 [P] [US1] Create the initial active Department list/detail pages with missing-leader and conflict states in resources/views/admin/departments/index.blade.php and resources/views/admin/departments/show.blade.php
- [ ] T021 [US1] Register named Admin Department list/create/store/show/edit/update routes under the existing `auth` and `internal` group in routes/web.php

**Checkpoint**: US1 is independently functional and is the suggested MVP: Super Admin can safely create, inspect and edit Department identity data.

---

## Phase 4: User Story 2 - Thiết lập Manager và thành viên phòng ban (Priority: P1)

**Goal**: Super Admin can set/unset a valid Manager leader and manage Staff/Manager memberships; a Manager can manage only Staff in Department they lead; leader and duplicate invariants always hold.

**Independent Test**: Set Manager A as leader, add Staff B and C, verify A is automatically a member exactly once, remove C, and confirm invalid roles, inactive accounts, duplicates, cross-Department Manager requests and removing the current leader are rejected.

### Tests for User Story 2

- [ ] T022 [P] [US2] Add leader eligibility, automatic membership, duplicate membership, Manager scope and current-leader removal tests in tests/Feature/Admin/Departments/DepartmentMembershipTest.php
- [ ] T023 [P] [US2] Add bounded manager/member candidate search, role/status filtering, exclusion and anti-enumeration tests in tests/Feature/Admin/Departments/DepartmentCandidateTest.php
- [ ] T024 [P] [US2] Extend leader/add/remove audit and rollback assertions in tests/Feature/Admin/Departments/DepartmentAuditTest.php

### Implementation for User Story 2

- [ ] T025 [P] [US2] Implement nullable active-Manager leader and version validation in app/Http/Requests/Admin/Departments/ChangeDepartmentLeaderRequest.php
- [ ] T026 [P] [US2] Implement add/remove member ID, eligibility and version validation in app/Http/Requests/Admin/Departments/StoreDepartmentMemberRequest.php and app/Http/Requests/Admin/Departments/DestroyDepartmentMemberRequest.php
- [ ] T027 [P] [US2] Implement locked leader change/unset, automatic non-duplicating membership, version increment and audit transaction in app/Actions/Department/ChangeDepartmentLeader.php
- [ ] T028 [P] [US2] Implement scoped active Staff/Manager membership insertion, database duplicate mapping, version increment and audit in app/Actions/Department/AddDepartmentMember.php
- [ ] T029 [P] [US2] Implement scoped member removal with current-leader guard, version increment, preserved User/history and audit in app/Actions/Department/RemoveDepartmentMember.php
- [ ] T030 [P] [US2] Implement the leader update endpoint with policy authorization and PRG feedback in app/Http/Controllers/Admin/Departments/DepartmentLeaderController.php
- [ ] T031 [P] [US2] Implement add/remove member endpoints with scoped membership resolution and PRG feedback in app/Http/Controllers/Admin/Departments/DepartmentMemberController.php
- [ ] T032 [US2] Implement authorized, minimum-two-character, maximum-20-result manager/member candidate responses in app/Http/Controllers/Admin/Departments/DepartmentCandidateController.php
- [ ] T033 [US2] Extend Department creation to accept an optional valid leader and atomically attach leader membership in app/Http/Requests/Admin/Departments/StoreDepartmentRequest.php and app/Actions/Department/CreateDepartment.php
- [ ] T034 [US2] Add leader combobox, leader warning, member table and add/remove confirmations to resources/views/admin/departments/partials/form.blade.php and resources/views/admin/departments/show.blade.php
- [ ] T035 [US2] Implement accessible native-dialog and bounded candidate-combobox progressive behavior in resources/js/admin/app.js
- [ ] T036 [US2] Register leader, member and manager/member candidate routes with correct ordering and method verbs in routes/web.php

**Checkpoint**: US2 is independently verifiable with a Department fixture; leader/member invariants and scoped Manager operations are enforced server-side.

---

## Phase 5: User Story 3 - Tra cứu cơ cấu tổ chức (Priority: P2)

**Goal**: Super Admin can search/filter all Department records while Manager sees only led Department; list/detail present leader, member counts and linked Services read-only with pagination and accessible states.

**Independent Test**: Seed multiple Department records, search by partial name/code, filter manager/status, paginate, open a result and verify leader, members and read-only Services; repeat as Manager and confirm only led Department data and scoped statistics are visible.

### Tests for User Story 3

- [ ] T037 [P] [US3] Add scoped search, escaped wildcard, manager/status filter, stable pagination, query preservation, count and Service read-only tests in tests/Feature/Admin/Departments/DepartmentQueryTest.php
- [ ] T038 [P] [US3] Extend collection/detail 403/404 masking and permission-driven action rendering tests in tests/Feature/Admin/Departments/DepartmentAuthorizationTest.php
- [ ] T039 [P] [US3] Add a PostgreSQL-only SC-004 performance group with 1,000 Department and 10,000 membership fixtures, query-count guard and separate timing report in tests/Feature/Admin/Departments/DepartmentQueryPerformanceTest.php and phpunit.xml

### Implementation for User Story 3

- [ ] T040 [P] [US3] Implement validated `search`, `manager_id`, `status` and `page` query inputs in app/Http/Requests/Admin/Departments/ListDepartmentsRequest.php
- [ ] T041 [US3] Implement actor-scoped stats, escaped case-insensitive search, filters, eager leader/count queries, stable `paginate(15)` and historical detail loading in app/Http/Controllers/Admin/Departments/DepartmentController.php
- [ ] T042 [P] [US3] Complete summary cards, responsive filter toolbar, table columns/actions, pagination and empty/no-result states in resources/views/admin/departments/index.blade.php
- [ ] T043 [P] [US3] Complete leader/member status presentation and linked Service Type read-only section without Service mutation controls in resources/views/admin/departments/show.blade.php
- [ ] T044 [US3] Add accessible loading/error/filter-reset behavior and mobile overflow handling for Department queries in resources/views/admin/departments/index.blade.php and resources/js/admin/app.js

**Checkpoint**: US3 list and detail queries are scoped, paginated, read-only where required and independently testable at normal and benchmark scale.

---

## Phase 6: User Story 4 - Chuyển và gỡ thành viên an toàn (Priority: P2)

**Goal**: Staff membership can be removed or atomically transferred without deleting User accounts or historical Application data; Manager transfer requires authority over both source and target.

**Independent Test**: Transfer Staff B from Department A to B and verify B disappears from A, appears once in B and historical assignments remain; then force invalid/duplicate/archived/stale target cases and confirm both Department memberships remain unchanged.

### Tests for User Story 4

- [ ] T045 [P] [US4] Add atomic transfer, deterministic lock order, invalid/duplicate/archived/stale target, Manager dual-scope and history-preservation tests in tests/Feature/Admin/Departments/DepartmentTransferTest.php
- [ ] T046 [P] [US4] Extend transfer audit metadata and full rollback assertions in tests/Feature/Admin/Departments/DepartmentAuditTest.php

### Implementation for User Story 4

- [ ] T047 [P] [US4] Implement target Department, source/target version and generic unavailable-target validation in app/Http/Requests/Admin/Departments/TransferDepartmentMemberRequest.php
- [ ] T048 [US4] Implement deterministic source/target row locks, source membership lock, eligibility recheck, attach-before-detach, two-version increment and single audit transaction in app/Actions/Department/TransferDepartmentMember.php
- [ ] T049 [US4] Implement the policy-authorized transfer endpoint and redirect to target detail in app/Http/Controllers/Admin/Departments/TransferDepartmentMemberController.php
- [ ] T050 [US4] Add authorized transfer-target candidate lookup that excludes source, archived, duplicate and Manager-out-of-scope targets in app/Http/Controllers/Admin/Departments/DepartmentCandidateController.php
- [ ] T051 [US4] Add the Staff transfer dialog, source/target/version context and success/error feedback in resources/views/admin/departments/show.blade.php and resources/js/admin/app.js
- [ ] T052 [US4] Register transfer and transfer-target candidate routes with nested member identifiers in routes/web.php

**Checkpoint**: US4 transfer is all-or-nothing, authorization-scoped and leaves User/Application history untouched on success or failure.

---

## Phase 7: User Story 5 - Lưu trữ phòng ban không còn hoạt động (Priority: P3)

**Goal**: Super Admin can soft-archive Department records while preserving leader, membership, Service and historical references; archived Department remains authorized read-only data and cannot receive structural changes.

**Independent Test**: Archive a Department containing members and linked Services, verify it disappears from active lists/candidates but remains visible through the archived filter with all relations intact, and confirm every mutation is rejected.

### Tests for User Story 5

- [ ] T053 [P] [US5] Add soft-archive, active-list/candidate exclusion, archived-detail visibility, code reservation, relation preservation and mutation-denial tests in tests/Feature/Admin/Departments/DepartmentArchiveTest.php
- [ ] T054 [P] [US5] Extend archive actor/snapshot and failed-archive rollback assertions in tests/Feature/Admin/Departments/DepartmentAuditTest.php

### Implementation for User Story 5

- [ ] T055 [P] [US5] Implement archive confirmation and version validation in app/Http/Requests/Admin/Departments/ArchiveDepartmentRequest.php
- [ ] T056 [US5] Implement locked version-aware soft archive with preserved relationships and same-transaction audit in app/Actions/Department/ArchiveDepartment.php
- [ ] T057 [US5] Implement the Super-Admin-only destroy-as-archive endpoint and archived read-only loading in app/Http/Controllers/Admin/Departments/DepartmentController.php
- [ ] T058 [US5] Register the Department DELETE route as archive-only with no restore or force-delete route in routes/web.php
- [ ] T059 [US5] Add archive confirmation copy, archived badges/filter results and complete read-only action suppression in resources/views/admin/departments/index.blade.php and resources/views/admin/departments/show.blade.php

**Checkpoint**: US5 preserves all historical data, removes archived Department from active operations and exposes no hard-delete path.

---

## Phase 8: Polish and Cross-Cutting Concerns

**Purpose**: Validate the integrated feature, update living documentation and enforce all quality/scope gates.

- [ ] T060 [P] Document F03 implementation status, schema enhancement and Admin-only dependencies in docs/technology-stack.md
- [ ] T061 [P] Update F03 screen mapping and implemented Admin Blade/Alpine components without claiming User Management scope in docs/ui-guidelines.md
- [ ] T062 Execute and record all end-to-end, responsive 1089px/375px, keyboard/dialog and scope-boundary checks from specs/98884-department-staff-management/quickstart.md
- [ ] T063 Run the targeted F03 suite with `php artisan test --testsuite=Feature --filter=Department` using phpunit.xml
- [ ] T064 Run the separate SC-004 PostgreSQL performance group and record fixture size, query count and timing against tests/Feature/Admin/Departments/DepartmentQueryPerformanceTest.php
- [ ] T065 Run the full backend regression suite with `php artisan test` using phpunit.xml
- [ ] T066 Run backend formatting validation with `composer run lint` using composer.json and fix all touched PHP files
- [ ] T067 Run frontend/security lint validation with `npm run lint` using package.json and fix all touched Blade/JS/CSS files
- [ ] T068 Run the production asset build with `npm run build` using vite.config.js
- [ ] T069 Review routes/web.php, app/Policies/DepartmentPolicy.php and resources/views/admin/departments/ for forbidden account management, Service mutation or Application workflow scope before PR handoff

**Checkpoint**: All selected stories, regression tests, linters, build, quickstart checks and scope review pass.

---

## Dependencies and Execution Order

### Phase Dependencies

- **Phase 1 - Setup**: No dependencies; starts immediately.
- **Phase 2 - Foundational**: Depends on Phase 1 and blocks all user-story integration.
- **Phase 3 - US1**: Depends on Phase 2; produces the first demoable Department CRUD increment.
- **Phase 4 - US2**: Domain tests/actions may start after Phase 2, but final controller/form/detail integration depends on US1.
- **Phase 5 - US3**: Query/request work may start after Phase 2; final list/detail integration depends on US1 and uses US2 membership presentation.
- **Phase 6 - US4**: Domain tests/action may start after Phase 2; final transfer UI and candidate integration depend on US2.
- **Phase 7 - US5**: Archive domain work may start after Phase 2; final archived list/detail experience depends on US1 and US3.
- **Phase 8 - Polish**: Depends on every story selected for the release.

### User Story Dependency Graph

```text
Setup -> Foundation -> US1 (MVP)
                    |-> US2 -> US4
                    |    `-> US3
                    `-> US1 + US3 -> US5

US3 query work and US4/US5 domain tests can begin after Foundation;
the arrows above identify final UI/integration dependencies.
```

### Within Each User Story

1. Write tests and confirm expected failures.
2. Implement Form Requests and domain Actions.
3. Implement controllers and scoped queries.
4. Register routes only after controller contracts exist.
5. Build/update Blade and Alpine presentation.
6. Run the story-specific tests and validate the independent test criterion.

### Parallel Opportunities

- T002 and T003 can run in parallel after T001 starts.
- T005, T006, T007, T008 and T010 can run in parallel after the migration contract in T004 is agreed.
- Test files within each user story are parallelizable.
- Requests/actions marked `[P]` use different files and can be developed concurrently after that story's failing tests exist.
- US3 query tests/request, US4 domain tests/request and US5 domain tests/request can start in parallel after Phase 2, while final UI integration follows the dependency graph.
- Documentation tasks T060 and T061 can run in parallel.

---

## Parallel Example: User Story 1

```text
Task: "T011 [P] [US1] Add route-boundary tests in tests/Feature/Admin/Departments/DepartmentAuthorizationTest.php"
Task: "T012 [P] [US1] Add management/integrity tests in tests/Feature/Admin/Departments/DepartmentManagementTest.php"
Task: "T013 [P] [US1] Add create/update audit tests in tests/Feature/Admin/Departments/DepartmentAuditTest.php"
```

## Parallel Example: User Story 2

```text
Task: "T025 [P] [US2] Create app/Http/Requests/Admin/Departments/ChangeDepartmentLeaderRequest.php"
Task: "T027 [P] [US2] Create app/Actions/Department/ChangeDepartmentLeader.php"
Task: "T028 [P] [US2] Create app/Actions/Department/AddDepartmentMember.php"
Task: "T029 [P] [US2] Create app/Actions/Department/RemoveDepartmentMember.php"
```

## Parallel Example: User Story 3

```text
Task: "T037 [P] [US3] Add query behavior tests in tests/Feature/Admin/Departments/DepartmentQueryTest.php"
Task: "T038 [P] [US3] Add scoped rendering tests in tests/Feature/Admin/Departments/DepartmentAuthorizationTest.php"
Task: "T039 [P] [US3] Add the separate performance group in tests/Feature/Admin/Departments/DepartmentQueryPerformanceTest.php"
```

## Parallel Example: User Story 4

```text
Task: "T045 [P] [US4] Add transfer integrity tests in tests/Feature/Admin/Departments/DepartmentTransferTest.php"
Task: "T046 [P] [US4] Add transfer audit tests in tests/Feature/Admin/Departments/DepartmentAuditTest.php"
Task: "T047 [P] [US4] Create app/Http/Requests/Admin/Departments/TransferDepartmentMemberRequest.php"
```

## Parallel Example: User Story 5

```text
Task: "T053 [P] [US5] Add archive lifecycle tests in tests/Feature/Admin/Departments/DepartmentArchiveTest.php"
Task: "T054 [P] [US5] Add archive audit tests in tests/Feature/Admin/Departments/DepartmentAuditTest.php"
Task: "T055 [P] [US5] Create app/Http/Requests/Admin/Departments/ArchiveDepartmentRequest.php"
```

---

## Implementation Strategy

### MVP First - User Story 1

1. Complete Phase 1 Setup.
2. Complete Phase 2 Foundational.
3. Complete Phase 3 US1.
4. Stop and run `DepartmentAuthorizationTest`, `DepartmentManagementTest` and the create/update audit assertions.
5. Demo create -> list/detail -> edit -> duplicate/stale rejection before starting membership work.

### Incremental Delivery

1. **US1**: Safe Department identity CRUD.
2. **US2**: Leader and member organization with scoped Manager rights.
3. **US3**: Complete scoped discovery, filters, read-only Services and scale behavior.
4. **US4**: Atomic Staff transfer and history preservation.
5. **US5**: Soft archive and historical read-only lifecycle.
6. Run Phase 8 before PR review and Redmine completion updates.

### Parallel Team Strategy

After Foundation is stable:

- Developer A completes US1 and owns shared `DepartmentController` integration.
- Developer B starts US2 domain/actions/tests, then integrates membership UI after US1 detail exists.
- Developer C starts US3 queries/performance and US5 archive domain tests.
- Developer D starts US4 transfer domain/action tests and integrates after US2 membership contracts stabilize.

Avoid simultaneous uncoordinated edits to `routes/web.php`, `DepartmentController.php`, `show.blade.php`, `index.blade.php` and `DepartmentAuditTest.php`; use the task order above for final integration.

### Redmine Split Suggestion

- Create separate Redmine subtasks for Phase 1–2 foundation and each US1–US5 phase.
- Split backend/test work from Blade/Alpine work when any phase estimate would exceed 8 hours.
- Link every implementation PR to its Redmine issue and list the completed task IDs/acceptance scenarios.

## Format Validation

All executable task lines use the required unchecked checkbox, sequential `T001`–`T069` ID, optional `[P]`, mandatory `[USn]` label inside user-story phases, and an explicit file path or command/config target.
