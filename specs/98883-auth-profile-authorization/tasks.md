# Tasks: F01 - Authentication, User Profile & Authorization

**Input**: Design documents from `specs/98883-auth-profile-authorization/`

**Prerequisites**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md), [data-model.md](./data-model.md), [contracts/v1-auth-profile.openapi.yaml](./contracts/v1-auth-profile.openapi.yaml), [quickstart.md](./quickstart.md)

**Tests**: Tests are included because the constitution requires relevant API/Admin feature tests and the feature has security, authorization, and data-integrity risk.

**Organization**: Tasks are grouped by user story so each story can be implemented and tested independently after the shared foundation is complete.

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Confirm the existing Laravel/Vite foundation is ready for F01 work.

- [X] T001 Verify PHP, Composer, npm, and PostgreSQL extension setup in docs/technology-stack.md
- [X] T002 Verify `.env.example` contains safe auth/session/database defaults for F01 in .env.example
- [X] T003 [P] Create F01 API auth test directory in tests/Feature/Api/V1/Auth/.gitkeep
- [X] T004 [P] Create F01 profile test directory in tests/Feature/Api/V1/Profile/.gitkeep
- [X] T005 [P] Create F01 Admin auth test directory in tests/Feature/Admin/Auth/.gitkeep

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Shared auth, role, response, and audit primitives required by every user story.

**CRITICAL**: No user story work should begin until this phase is complete.

- [X] T006 Add role helper methods to app/Models/User.php
- [X] T007 Add active-account helper methods to app/Models/User.php
- [X] T008 [P] Create authentication event logger in app/Support/Auth/AuthEventLogger.php
- [X] T009 [P] Create Citizen role middleware in app/Http/Middleware/EnsureCitizen.php
- [X] T010 [P] Create internal user role middleware in app/Http/Middleware/EnsureInternalUser.php
- [X] T011 Register F01 middleware aliases in bootstrap/app.php
- [X] T012 [P] Create reusable auth route comments and route groups in routes/api.php
- [X] T013 [P] Create reusable Admin auth route comments and route groups in routes/web.php
- [X] T014 Add F01 response helper coverage in tests/Feature/Api/V1/Auth/AuthResponseEnvelopeTest.php

**Checkpoint**: Shared role, active-account, middleware, and audit primitives are ready.

---

## Phase 3: User Story 1 - Citizen Registration, Login, and Logout (Priority: P1) MVP

**Goal**: A Citizen can register with valid identity information, log in to the Citizen area, access protected Citizen resources, and log out.

**Independent Test**: Register a new Citizen, log in, call a protected Citizen endpoint, log out, then confirm protected access is rejected.

### Tests for User Story 1

- [X] T015 [P] [US1] Add Citizen registration feature tests in tests/Feature/Api/V1/Auth/CitizenRegistrationTest.php
- [X] T016 [P] [US1] Add Citizen login/logout feature tests in tests/Feature/Api/V1/Auth/CitizenLoginTest.php
- [X] T017 [P] [US1] Add duplicate email and CCCD concurrency-oriented tests in tests/Feature/Api/V1/Auth/CitizenRegistrationUniquenessTest.php

### Implementation for User Story 1

- [X] T018 [P] [US1] Create Citizen registration request in app/Http/Requests/Api/V1/Auth/RegisterCitizenRequest.php
- [X] T019 [P] [US1] Create Citizen login request in app/Http/Requests/Api/V1/Auth/LoginCitizenRequest.php
- [X] T020 [P] [US1] Create user API resource in app/Http/Resources/Api/V1/UserResource.php
- [X] T021 [US1] Create Citizen registration action in app/Actions/Auth/RegisterCitizen.php
- [X] T022 [US1] Create Citizen auth controller in app/Http/Controllers/Api/V1/Auth/CitizenAuthController.php
- [X] T023 [US1] Add Citizen auth API routes in routes/api.php
- [X] T024 [US1] Add login throttling for Citizen login in app/Http/Requests/Api/V1/Auth/LoginCitizenRequest.php
- [X] T025 [US1] Record Citizen register, login, failed login, and logout events in app/Http/Controllers/Api/V1/Auth/CitizenAuthController.php
- [X] T026 [US1] Add Citizen auth API client functions in resources/js/citizen/api/auth.js
- [X] T027 [US1] Add Citizen login page in resources/js/citizen/pages/LoginPage.jsx
- [X] T028 [US1] Add Citizen register page in resources/js/citizen/pages/RegisterPage.jsx
- [X] T029 [US1] Wire Citizen auth routes in resources/js/citizen/App.jsx

**Checkpoint**: US1 is fully functional and independently testable.

---

## Phase 4: User Story 2 - Internal User Login Boundary (Priority: P1)

**Goal**: Staff, Manager, and Super Admin users can log in to Admin, while Citizens cannot use or access the Admin area.

**Independent Test**: Log in with each internal role and confirm Admin access; try with a Citizen account and confirm denial.

### Tests for User Story 2

- [X] T030 [P] [US2] Add Admin login feature tests in tests/Feature/Admin/Auth/AdminLoginTest.php
- [X] T031 [P] [US2] Add Admin role-boundary tests in tests/Feature/Admin/Auth/AdminRoleBoundaryTest.php

### Implementation for User Story 2

- [X] T032 [P] [US2] Create Admin login request in app/Http/Requests/Admin/Auth/AdminLoginRequest.php
- [X] T033 [US2] Create Admin auth controller in app/Http/Controllers/Admin/Auth/AdminAuthController.php
- [X] T034 [US2] Create Admin login Blade view in resources/views/admin/auth/login.blade.php
- [X] T035 [US2] Protect Admin dashboard routes with internal user middleware in routes/web.php
- [X] T036 [US2] Add Admin login and logout routes in routes/web.php
- [X] T037 [US2] Record Admin login, failed login, and logout events in app/Http/Controllers/Admin/Auth/AdminAuthController.php

**Checkpoint**: US2 is fully functional and independently testable.

---

## Phase 5: User Story 4 - Authorization and Ownership Boundaries (Priority: P1)

**Goal**: Protected operations enforce current role, active-account status, and owner-only Citizen access on the server.

**Independent Test**: Use two Citizens and internal users from each role to confirm wrong-role, inactive, unauthenticated, and cross-owner access is denied.

### Tests for User Story 4

- [X] T038 [P] [US4] Add Citizen protected-route authorization tests in tests/Feature/Api/V1/Auth/CitizenAuthorizationBoundaryTest.php
- [X] T039 [P] [US4] Add inactive-account access tests in tests/Feature/Api/V1/Auth/InactiveAccountAccessTest.php
- [X] T040 [P] [US4] Add access denial audit tests in tests/Feature/Api/V1/Auth/AccessDeniedAuditTest.php

### Implementation for User Story 4

- [X] T041 [US4] Enforce active-account checks in app/Http/Middleware/EnsureCitizen.php
- [X] T042 [US4] Enforce active-account checks in app/Http/Middleware/EnsureInternalUser.php
- [X] T043 [P] [US4] Create user policy for self-access rules in app/Policies/UserPolicy.php
- [X] T044 [US4] Register user policy in app/Providers/AppServiceProvider.php
- [X] T045 [US4] Record access-denied events from F01 middleware in app/Support/Auth/AuthEventLogger.php
- [X] T046 [US4] Apply Citizen middleware to protected Citizen API routes in routes/api.php
- [X] T047 [US4] Apply internal user middleware to protected Admin routes in routes/web.php

**Checkpoint**: US4 is fully functional and independently testable.

---

## Phase 6: User Story 3 - Citizen Profile Management (Priority: P2)

**Goal**: An authenticated Citizen can view their own profile and update allowed profile fields while CCCD, email, role, password, and active status remain protected.

**Independent Test**: View the current Citizen profile, update allowed fields, attempt forbidden field changes, and confirm only allowed changes persist.

### Tests for User Story 3

- [ ] T048 [P] [US3] Add Citizen profile read tests in tests/Feature/Api/V1/Profile/CitizenProfileReadTest.php
- [ ] T049 [P] [US3] Add Citizen profile update tests in tests/Feature/Api/V1/Profile/CitizenProfileUpdateTest.php
- [ ] T050 [P] [US3] Add forbidden profile field tests in tests/Feature/Api/V1/Profile/CitizenProfileForbiddenFieldsTest.php

### Implementation for User Story 3

- [ ] T051 [P] [US3] Create Citizen profile update request in app/Http/Requests/Api/V1/Profile/UpdateCitizenProfileRequest.php
- [ ] T052 [US3] Create Citizen profile controller in app/Http/Controllers/Api/V1/ProfileController.php
- [ ] T053 [US3] Add current Citizen profile API routes in routes/api.php
- [ ] T054 [US3] Record profile update events in app/Http/Controllers/Api/V1/ProfileController.php
- [ ] T055 [US3] Add Citizen profile API client functions in resources/js/citizen/api/profile.js
- [ ] T056 [US3] Add Citizen profile page in resources/js/citizen/pages/ProfilePage.jsx
- [ ] T057 [US3] Wire Citizen profile route in resources/js/citizen/App.jsx

**Checkpoint**: US3 is fully functional and independently testable.

---

## Phase 7: Polish and Cross-Cutting Concerns

**Purpose**: Final verification, documentation, and cleanup across all user stories.

- [ ] T058 [P] Update F01 implementation notes in docs/technology-stack.md
- [ ] T059 [P] Update F01 UI status in docs/ui-guidelines.md
- [ ] T060 Run Laravel Pint for touched PHP files with vendor/bin/pint
- [ ] T061 Run Vite build for touched Citizen assets with npm run build
- [ ] T062 Run F01 targeted tests with php artisan test --filter=Auth
- [ ] T063 Run F01 targeted tests with php artisan test --filter=Profile
- [ ] T064 Run Admin auth tests with php artisan test --filter=Admin
- [ ] T065 Run full backend test suite with php artisan test
- [ ] T066 Validate F01 quickstart scenarios in specs/98883-auth-profile-authorization/quickstart.md

---

## Dependencies and Execution Order

### Phase Dependencies

- **Phase 1 Setup**: No dependencies.
- **Phase 2 Foundational**: Depends on Phase 1 and blocks all user stories.
- **Phase 3 US1**: Depends on Phase 2 and is the MVP.
- **Phase 4 US2**: Depends on Phase 2; can run in parallel with US1 after middleware/helper contracts stabilize.
- **Phase 5 US4**: Depends on Phase 2 and integrates with US1/US2 protected routes.
- **Phase 6 US3**: Depends on Phase 2 and benefits from US4 owner-access policy.
- **Phase 7 Polish**: Depends on selected user stories being complete.

### User Story Dependencies

- **US1 Citizen registration/login/logout**: No dependency on other user stories after Phase 2.
- **US2 internal login boundary**: No dependency on US1 after Phase 2.
- **US4 authorization and ownership boundaries**: Can start after Phase 2, but final route application should be reviewed with US1 and US2 endpoints.
- **US3 Citizen profile management**: Can start after Phase 2, but final owner-only behavior depends on US4 policy/middleware decisions.

### Within Each User Story

- Write tests before implementation.
- Requests/resources before controllers.
- Middleware/policies before protecting routes.
- API client functions before React page wiring.
- Complete each story checkpoint before marking related Redmine subtasks done.

## Parallel Opportunities

- T003, T004, and T005 can run in parallel.
- T008, T009, T010, T012, and T013 can run in parallel after T006 and T007 decisions are clear.
- US1 tests T015, T016, and T017 can run in parallel.
- US2 tests T030 and T031 can run in parallel.
- US4 tests T038, T039, and T040 can run in parallel.
- US3 tests T048, T049, and T050 can run in parallel.
- Documentation updates T058 and T059 can run in parallel.

## Parallel Example: User Story 1

```text
Task: "T015 [P] [US1] Add Citizen registration feature tests in tests/Feature/Api/V1/Auth/CitizenRegistrationTest.php"
Task: "T016 [P] [US1] Add Citizen login/logout feature tests in tests/Feature/Api/V1/Auth/CitizenLoginTest.php"
Task: "T017 [P] [US1] Add duplicate email and CCCD concurrency-oriented tests in tests/Feature/Api/V1/Auth/CitizenRegistrationUniquenessTest.php"
```

## Parallel Example: User Story 3

```text
Task: "T048 [P] [US3] Add Citizen profile read tests in tests/Feature/Api/V1/Profile/CitizenProfileReadTest.php"
Task: "T049 [P] [US3] Add Citizen profile update tests in tests/Feature/Api/V1/Profile/CitizenProfileUpdateTest.php"
Task: "T050 [P] [US3] Add forbidden profile field tests in tests/Feature/Api/V1/Profile/CitizenProfileForbiddenFieldsTest.php"
```

## Implementation Strategy

### MVP First

1. Complete Phase 1.
2. Complete Phase 2.
3. Complete Phase 3 for Citizen registration/login/logout.
4. Stop and validate US1 independently with the Citizen auth tests and quickstart scenario.

### Incremental Delivery

1. Deliver US1 as the first demoable Citizen auth increment.
2. Deliver US2 to unlock protected Admin work.
3. Deliver US4 to harden all protected route boundaries.
4. Deliver US3 to complete Citizen profile management.
5. Run Phase 7 validation before PR review and Redmine status changes.

### Redmine Split Suggestion

- Group Phase 1 and Phase 2 into a foundation subtask if estimate stays within 8 hours.
- Create separate Redmine subtasks for US1, US2, US4, and US3 if each estimate stays within the team limit.
- Split React UI work from backend/API work if any story exceeds 8 hours.

## Format Validation

All executable task lines use the required checkbox, sequential task ID, optional `[P]`, story label for user-story phases, and an explicit file path or command target.
