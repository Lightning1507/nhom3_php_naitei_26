# Tasks: F04 - Application Submission & Document Management

**Input**: Design documents from `specs/98886-application-submission-and-document-management/`

**Prerequisites**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md), [data-model.md](./data-model.md), [contracts/application-documents.md](./contracts/application-documents.md), [quickstart.md](./quickstart.md)

**Tests**: Test tasks are included because the constitution requires relevant API feature tests and the feature handles data-integrity, authorization, and file-upload risk. Within each user story, write the listed tests first and confirm they fail for the expected reason before implementation.

**Organization**: Tasks are grouped by user story so each story can be implemented, tested and demonstrated as an incremental deliverable after the shared foundation is complete.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel with other marked tasks in the same stage because files do not conflict and prerequisites are already satisfied.
- **[Story]**: Maps the task to a user story in `spec.md`.
- Every executable task includes an exact file path or command/config target.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Confirm the environment and existing prerequisites are ready for F04 work.

- [X] T001 Verify Docker Postgres test container (`psm-pg`) is running and `.env.testing` is configured
- [X] T002 Verify prerequisites: `applications`/`application_documents`/`application_code_sequences` migrations, `Application`/`ApplicationDocument` models, `ApplicationStatus`/`DocumentKind`/`UserRole` enums, Sanctum auth, `ServiceType` factory
- [X] T003 Create test files tests/Feature/Api/V1/ApplicationSubmissionTest.php and tests/Feature/Api/V1/ApplicationDocumentTest.php

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Routes, storage and authorization primitives shared by all user stories.

**CRITICAL**: No user story work should begin until this phase is complete.

- [X] T004 Create `application_code_sequences` migration in database/migrations/2026_08_15_112629_create_application_code_sequences_table.php (sequence_date + last_sequence, unique date)
- [X] T005 [P] Create app/Services/ApplicationCodeService.php — generate `HS-YYYYMMDD-xxxxx` inside a transaction using `lockForUpdate` on the day's sequence row
- [X] T006 Add document API routes to routes/api.php under `auth:sanctum`, with `scopeBindings()` on `{application}/documents/{document}`
- [X] T007 Add `uploadDocument` ability to app/Policies/ApplicationPolicy.php (owner-only)
- [X] T008 [P] Register policy usage via authorize() calls in controllers (server-side authorization)

**Checkpoint**: Code generation, routes and authorization boundary are ready.

---

## Phase 3: User Story 1 - Citizen nộp hồ sơ dịch vụ công (Priority: P1) MVP

**Goal**: A logged-in Citizen can submit an application for an active service with `form_data` validated against the service's `form_schema`; the system issues a unique `HS-YYYYMMDD-xxxxx` code and records the initial `received` history.

**Independent Test**: Submit an application for an active service with complete required fields and confirm the code format and `received` status; submit missing required fields, an inactive service, and as Staff and confirm each is rejected.

### Tests for User Story 1

- [X] T009 [P] [US1] Add application submission success test (code format `HS-YYYYMMDD-xxxxx`, status `received`, history written) in tests/Feature/Api/V1/ApplicationSubmissionTest.php
- [X] T010 [P] [US1] Add missing-required-field 422 and optional-field-omitted tests in tests/Feature/Api/V1/ApplicationSubmissionTest.php
- [X] T011 [P] [US1] Add inactive-service and missing-service-type 422 tests in tests/Feature/Api/V1/ApplicationSubmissionTest.php
- [X] T012 [P] [US1] Add concurrent-submission unique-code and DB-level duplicate-code tests in tests/Feature/Api/V1/ApplicationSubmissionTest.php
- [X] T013 [P] [US1] Add Staff-submission 403 test in tests/Feature/Api/V1/ApplicationSubmissionTest.php

### Implementation for User Story 1

- [X] T014 [P] [US1] Create app/Http/Requests/Api/V1/StoreApplicationRequest.php (service_type_id exists/active/not-trashed + form_data per form_schema, type-aware rules)
- [X] T015 [P] [US1] Create app/Actions/Application/CreateApplicationAction.php (transaction: code -> application `received` -> status history)
- [X] T016 [P] [US1] Create app/Http/Resources/Api/V1/ApplicationResource.php
- [X] T017 [US1] Implement `store` in app/Http/Controllers/Api/V1/ApplicationController.php (authorize create, 201 envelope)

**Checkpoint**: US1 is fully functional and independently testable.

---

## Phase 4: User Story 2 - Citizen xem danh sách và chi tiết hồ sơ của mình (Priority: P1)

**Goal**: A Citizen can list and view only their own applications, newest first; other citizens' applications are denied.

**Independent Test**: Create applications for two citizens, list as the first citizen (only own shown), view a second citizen's application detail (403).

### Tests for User Story 2

- [X] T018 [P] [US2] Add index-returns-only-calling-citizen test in tests/Feature/Api/V1/ApplicationSubmissionTest.php
- [X] T019 [P] [US2] Add cross-citizen show 403 test in tests/Feature/Api/V1/ApplicationSubmissionTest.php
- [X] T020 [P] [US2] Add show-returns-requested-application test in tests/Feature/Api/V1/ApplicationSubmissionTest.php

### Implementation for User Story 2

- [X] T021 [P] [US2] Implement `index` in app/Http/Controllers/Api/V1/ApplicationController.php (scoped to citizen, paginated, latest first)
- [X] T022 [US2] Implement `show` in app/Http/Controllers/Api/V1/ApplicationController.php (authorize `view`, load serviceType)

**Checkpoint**: US2 is fully functional and independently testable.

---

## Phase 5: User Story 3 - Citizen upload tài liệu đính kèm hồ sơ (Priority: P1)

**Goal**: A logged-in Citizen can upload a valid PDF/image to their own application; invalid or oversized files are rejected.

**Independent Test**: Upload a valid PDF to an owned application and confirm metadata is stored; upload an `.exe` and an oversized file and confirm rejection with no record created.

### Tests for User Story 3

- [X] T023 [P] [US3] Add PDF upload success test in tests/Feature/Api/V1/ApplicationDocumentTest.php
- [X] T024 [P] [US3] Add image upload success test in tests/Feature/Api/V1/ApplicationDocumentTest.php
- [X] T025 [P] [US3] Add non-PDF/image rejection test in tests/Feature/Api/V1/ApplicationDocumentTest.php
- [X] T026 [P] [US3] Add oversized file rejection test in tests/Feature/Api/V1/ApplicationDocumentTest.php
- [X] T027 [P] [US3] Add cross-citizen upload 403 test in tests/Feature/Api/V1/ApplicationDocumentTest.php
- [X] T028 [P] [US3] Add staff upload 403 test in tests/Feature/Api/V1/ApplicationDocumentTest.php

### Implementation for User Story 3

- [X] T029 [P] [US3] Create app/Http/Requests/Api/V1/StoreApplicationDocumentRequest.php (mimes:pdf,jpg,jpeg,png + max 10MB)
- [X] T030 [P] [US3] Create app/Actions/Application/StoreApplicationDocumentAction.php (store private disk + metadata)
- [X] T031 [P] [US3] Create app/Http/Resources/Api/V1/ApplicationDocumentResource.php
- [X] T032 [US3] Implement `store` in app/Http/Controllers/Api/V1/ApplicationDocumentController.php (authorize uploadDocument, 201 envelope)

**Checkpoint**: US3 is fully functional and independently testable.

---

## Phase 6: User Story 4 - Tải xuống tài liệu của hồ sơ (Priority: P1)

**Goal**: The application owner and Staff/Manager/Super Admin can download a document; other citizens and unauthenticated users are rejected.

**Independent Test**: Upload a document, download as the owner (success), as Staff/Manager (success), as another citizen (403), and unauthenticated (401).

### Tests for User Story 4

- [X] T033 [P] [US4] Add owner download success test in tests/Feature/Api/V1/ApplicationDocumentTest.php
- [ ] T034 [P] [US4] Add staff/manager download success test in tests/Feature/Api/V1/ApplicationDocumentTest.php
- [X] T035 [P] [US4] Add other-citizen download 403 test in tests/Feature/Api/V1/ApplicationDocumentTest.php
- [X] T036 [P] [US4] Add unauthenticated download 401 test in tests/Feature/Api/V1/ApplicationDocumentTest.php
- [X] T037 [P] [US4] Add soft-deleted document 404 test in tests/Feature/Api/V1/ApplicationDocumentTest.php
- [X] T038 [P] [US4] Add cross-application document 404 test in tests/Feature/Api/V1/ApplicationDocumentTest.php

### Implementation for User Story 4

- [X] T039 [P] [US4] Add `download` ability to app/Policies/ApplicationDocumentPolicy.php (owner + staff/manager/superadmin)
- [X] T040 [US4] Implement `download` in app/Http/Controllers/Api/V1/ApplicationDocumentController.php (stream via Storage)

**Checkpoint**: US4 is fully functional and independently testable.

---

## Phase 7: User Story 5 - Citizen xóa tài liệu khi hồ sơ chưa được nộp xong (Priority: P2)

**Goal**: The owner can soft-delete a document while the application is in `received` status; deletion is blocked once processing starts or for non-owners.

**Independent Test**: Upload a document, delete it while `received` (soft-deleted), attempt download (404); attempt delete after status becomes `processing` (403).

### Tests for User Story 5

- [X] T041 [P] [US5] Add owner soft-delete success test in tests/Feature/Api/V1/ApplicationDocumentTest.php
- [X] T042 [P] [US5] Add delete-after-processing 403 test in tests/Feature/Api/V1/ApplicationDocumentTest.php
- [X] T043 [P] [US5] Add cross-citizen delete 403 test in tests/Feature/Api/V1/ApplicationDocumentTest.php

### Implementation for User Story 5

- [X] T044 [P] [US5] Add `delete` ability to app/Policies/ApplicationDocumentPolicy.php (owner + status `received`)
- [X] T045 [US5] Implement `destroy` in app/Http/Controllers/Api/V1/ApplicationDocumentController.php (soft delete, 200 envelope)

**Checkpoint**: US5 is fully functional and independently testable.

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: Edge cases and quality gates affecting multiple user stories.

- [ ] T046 [US4] Handle missing binary file in `download`: check `Storage::exists()` and return clear 404 instead of 500; add test
- [X] T047 Run `php artisan test --env=testing --filter ApplicationSubmissionTest` and confirm all pass
- [X] T048 Run `php artisan test --env=testing --filter ApplicationDocumentTest` and confirm all pass
- [ ] T049 Run `composer run lint` (Pint) and `npm run lint` and fix issues
- [ ] T050 Run full suite `php artisan test --env=testing` and confirm all pass

---

---

## Phase 9: Increment 2 - Dynamic per-service document requirements, per-requirement upload & locking

**Purpose**: Ràng buộc tài liệu với từng requirement của service (`code/label/required/type`),
thêm `requirement_code` cho `application_documents`, lock upload/delete theo trạng thái nghiệp vụ,
và soft validation (thiếu tài liệu bắt buộc → cảnh báo đỏ, không chặn nộp). UI admin xử lý hồ sơ
ngoài phạm vi (chỉ chuẩn bị hạ tầng supplement).

### Phase 9A - Backend data & contract

- [ ] T051 Create migration `add_requirement_code_to_application_documents` (`requirement_code` string nullable + index)
- [ ] T052 Create idempotent backfill command normalizing `service_types.document_requirements` to `{code,label,required,type}` (code=slug(label), unique via suffix, type=mixed default, accept legacy `{name,is_required}`)
- [ ] T053 Create `app/Support/ServiceSchema.php` with `normalizeDocumentRequirements` and `normalizeFormSchema` (shared by admin actions + resources; skip `file` type in form_schema)
- [ ] T054 Update `StoreApplicationDocumentRequest`: `requirement_code` nullable|string; `required` when service has ≥1 requirement; must be `in:` service codes
- [ ] T055 Update `StoreApplicationDocumentAction`: persist `requirement_code`, set `document_kind` from status (`received`→submission, `supplement_required`→supplement), server-side validate code belongs to service
- [ ] T056 Update `ApplicationDocumentResource`: expose `requirement_code` + `requirement_label` (from service requirements)
- [ ] T057 Update `ApplicationResource`: compute + return `missing_required_documents` (`[{code,label}]`) from service requirements vs documents (when `received`/`supplement_required`)
- [ ] T058 Soft validation: `store` still returns 201 when required documents missing; response includes `missing_required_documents`

### Phase 9B - Lock policies

- [ ] T059 Update `ApplicationPolicy::uploadDocument`: owner + status ∈ {`received`, `supplement_required`}; when `supplement_required` only supplement-kind uploads
- [ ] T060 Update `ApplicationDocumentPolicy::delete`: owner + status `received` + `assigned_staff_id === null`

### Phase 9C - Admin service-type editor

- [ ] T061 Update `StoreServiceTypeRequest`/`UpdateServiceTypeRequest`: `document_requirements.*.type` in:pdf,image,mixed (keep `name`, `is_required`)
- [ ] T062 Update `CreateServiceType`/`UpdateServiceType` actions: normalize via `ServiceSchema` → auto `code`, default `type=mixed`, store canonical shape
- [ ] T063 Update `resources/views/admin/service-types/create.blade.php` + `edit.blade.php`: per-requirement `type` select (PDF/Ảnh/Cả hai) + code preview; remove `file` option from `form_schema` type select

### Phase 9D - Citizen SPA per-requirement upload

- [ ] T064 Update `resources/js/citizen/utils/schema.js`: add `normalizeDocumentRequirements` + `requirementAccept(requirement)` (mime list per type)
- [ ] T065 Refactor `DocumentUploader.jsx` to per-requirement slots: files bound to `requirement_code`, label/`*`/type hint, red warning when required slot empty, validate mime/size per slot type
- [ ] T066 Update `ApplyPage.jsx`: Step 2 renders requirement slots (free dropzone fallback when no requirements); Step 3 red "Thiếu N tài liệu bắt buộc" but allow submit; upload each file with `requirement_code`
- [ ] T067 Update `MyApplicationDetailPage.jsx`: group documents by requirement label/code, red banner "Thiếu tài liệu bắt buộc" when `received`, "Tải thêm" shows only missing requirement slots

### Phase 9E - Tests & quality gates

- [ ] T068 Add upload tests (valid code 201, missing code 422, wrong code 422, no-requirement service free upload, processing/approved/rejected upload 403, supplement_required upload → kind=supplement) in `ApplicationDocumentTest`
- [ ] T069 Add delete test (received + assigned_staff → 403) in `ApplicationDocumentTest`
- [ ] T070 Add soft-validation tests (store missing required docs → 201 + `missing_required_documents`; show returns correct list) in `ApplicationSubmissionTest`
- [ ] T071 Add admin service-type test (store/update with `type`, auto code, invalid type 422) under `tests/Feature/Admin`
- [ ] T072 Update `CitizenSpaTest` route renders after slot UI change
- [ ] T073 Run backfill on dev (Supabase) + test DB; run `composer run lint`, `npm run lint`, `npm run build`, full suite `php artisan test --env=testing`

---

## Dependencies & Execution Order

- **Setup (Phase 1)** → **Foundational (Phase 2)** blocks all user stories.
- **US1 (P1)** → **US2 (P1)**; **US3 (P1)** → **US4 (P1)** → **US5 (P2)**, each independently testable.
- **Polish (Phase 8)** depends on all user stories.

### Within each user story

- Tests first (fail before implementation), then model/policy/service, then controller/route.
- Parallel `[P]` tasks touch different files.

### Parallel Opportunities

- All `[P]` test tasks in a phase can run together.
- US1/US2 share `ApplicationSubmissionTest.php`; US3/US4/US5 share `ApplicationDocumentTest.php`,
  so tests are written sequentially per story but run together at the end.

## Notes

- Use FormRequest, ApiResponse envelope, Actions for logic, Policies for authorization (SunLint
  S005/S025/S028, LV002/LV005).
- Task #99000 không phải feature riêng; artifact đã được gộp vào feature F04 tại
  `specs/98886-application-submission-and-document-management/`.
- Do not commit `.env`, secrets, or unrelated changes (e.g. `.specify/memory/constitution.md`).