# Tasks: F05 - Application Processing Workflow

**Input**: Design documents from `specs/98887-application-processing-workflow/`

**Prerequisites**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md),
[data-model.md](./data-model.md), [contracts/admin-workflow.md](./contracts/admin-workflow.md),
[contracts/citizen-supplement.md](./contracts/citizen-supplement.md), [quickstart.md](./quickstart.md)

**Tests**: Test tasks are included because the constitution requires relevant feature tests and the
feature handles data-integrity, authorization, and concurrent-transition risk. Within each user story,
write the listed tests first and confirm they fail for the expected reason before implementation.

**Organization**: Tasks are grouped by user story so each story can be implemented, tested and
demonstrated as an incremental deliverable after the shared foundation is complete. Ticket mapping
(per plan.md): T1 backend = #99475, T2 UI = #99476, T3 authz = #99477, T4 polish = #99478.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel with other marked tasks in the same stage because files do not conflict
  and prerequisites are already satisfied.
- **[Story]**: Maps the task to a user story in `spec.md`.
- Every executable task includes an exact file path or command/config target.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Confirm the environment and existing prerequisites are ready for F05 work.

- [X] T001 Verify Docker Postgres test container (`psm-pg`) is running and `.env.testing` is configured
- [X] T002 Verify prerequisites: F04 merged (requirement_code, document_kind, status locks,
  `ServiceSchema`, `ApplicationDocumentPolicy`), models `Application`/`ApplicationAssignment`/
  `ApplicationStatusHistory`, `Department::scopeVisibleTo`, `User` role scopes, `ApplicationStatus` enum
- [X] T003 Create test files tests/Feature/Admin/ApplicationProcessingTest.php and
  tests/Feature/Admin/ApplicationAuthorizationTest.php

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Transition map, factories and model helpers shared by all user stories.

**CRITICAL**: No user story work should begin until this phase is complete.

- [X] T004 Create app/Support/Application/ApplicationTransitionMap.php — allowed transitions
  `received→processing`, `processing→supplement_required`, `supplement_required→processing`,
  `processing→approved`, `processing→rejected`; `assertTransition(from,to)` throwing 422 on any other
  (incl. no-op and from terminal states)
- [X] T005 [P] Create database/factories/ApplicationFactory.php (status, service_type, citizen,
  submitted_at, optional assigned_staff_id/processing_started_at/completed_at/result_note/rejection_reason)
- [X] T006 [P] Create database/factories/ApplicationAssignmentFactory.php (active or ended, assigned_by,
  department_id) and database/factories/ApplicationStatusHistoryFactory.php (from/to, changed_by, note)
- [X] T007 Add model helpers to app/Models/Application.php: `activeAssignment()` (first `ended_at`
  null), `isOverdue()` (`completed_at` null && `submitted_at + processing_time_days < now`),
  `supplementNote()` (latest history with `to_status=supplement_required`)

**Checkpoint**: Transition rules, factories and model helpers are ready.

---

## Phase 3: User Story 1 - Manager phân công hồ sơ cho Staff trên Assignment Board (Priority: P1) MVP

**Goal**: A Manager (or Super Admin) can view applications in their department scope, assign/reassign
to an active Staff of the responsible department, see pending/overdue counts; unauthorized or
out-of-scope assignments are rejected.

**Independent Test**: Assign a `received` application of a service in Manager A's department to Staff S
of that department → `assigned_staff_id` updated, assignment row created, status unchanged; reassign to
S2 → old assignment `ended_at` set, new row created, pointer moved; Staff (non-Manager) assign → 403;
Manager of another department doesn't see/assign the application; assigning a Staff outside the
responsible department → 422; assigning a terminal application → 422.

### Tests for User Story 1

- [X] T008 [P] [US1] Add assign-success test (assigned_staff_id + application_assignments row + status
  unchanged + history untouched) in tests/Feature/Admin/ApplicationProcessingTest.php
- [X] T009 [P] [US1] Add reassign test (old assignment ended_at, new row, pointer moved) in tests/Feature/Admin/ApplicationProcessingTest.php
- [X] T010 [P] [US1] Add assign-terminal 422 test (approved/rejected) in tests/Feature/Admin/ApplicationProcessingTest.php
- [X] T011 [P] [US1] Add staff-assign 403 test in tests/Feature/Admin/ApplicationProcessingTest.php
- [X] T012 [P] [US1] Add wrong-department-staff 422 test in tests/Feature/Admin/ApplicationProcessingTest.php
- [X] T013 [P] [US1] Add inactive/trashed-staff 422 test in tests/Feature/Admin/ApplicationProcessingTest.php
- [X] T014 [P] [US1] Add manager-out-of-scope invisible test (list excludes other departments; Super Admin sees all) in tests/Feature/Admin/ApplicationProcessingTest.php

### Implementation for User Story 1

- [X] T015 [P] [US1] Create app/Actions/Application/AssignApplicationAction.php (transaction:
  lockForUpdate application, close active assignment if any (ended_at), create assignment row, update
  assigned_staff_id; reject terminal / wrong-scope / inactive staff)
- [X] T016 [P] [US1] Create app/Http/Requests/Admin/Applications/AssignApplicationRequest.php
  (`staff_id` required, exists active Staff, belongs to responsible department via `scopeEligibleDepartmentStaff`)
- [X] T017 [P] [US1] Add admin routes to routes/web.php in `admin` group (`auth`+`internal`):
  `admin.applications.index`, `admin.applications.show`, `admin.applications.assign`; wire
  app/Http/Controllers/Admin/Applications/ApplicationController.php (index/show) and
  ApplicationAssignmentController.php (store) with flash redirect
- [X] T018 [US1] Implement index query in ApplicationController: Staff → own assigned (+ claimable
  group), Manager/Super Admin → scope board with pending + overdue counts (FR-024) and filters
  (status, assigned_staff_id, overdue, q)

**Checkpoint**: US1 is fully functional and independently testable.

---

## Phase 4: User Story 2 - Staff tiếp nhận và xử lý hồ sơ (Priority: P1)

**Goal**: Assigned Staff can claim unassigned applications in scope, start processing, approve (with
optional result_note + result document) or reject (with required rejection_reason); invalid/unauthorized
transitions are rejected and concurrent transitions are serialized.

**Independent Test**: Staff S claims an unassigned app of their department → becomes handler; S starts
processing → `processing_started_at` set + history; S approves with result_note → `approved` +
`completed_at`; S rejects another without reason → 422; `received→approved` direct → 422; S2 (unassigned)
start/approve/reject → 403.

### Tests for User Story 2

- [X] T019 [P] [US2] Add claim-success test (assigned_staff_id + assigned_by=self, status unchanged,
  assignment row) in tests/Feature/Admin/ApplicationProcessingTest.php
- [X] T020 [P] [US2] Add claim-already-assigned 409 test in tests/Feature/Admin/ApplicationProcessingTest.php
- [X] T021 [P] [US2] Add start-processing-success test (processing_started_at set if null, history,
  keep if already set) in tests/Feature/Admin/ApplicationProcessingTest.php
- [X] T022 [P] [US2] Add approve-success test (approved, completed_at, result_note) in tests/Feature/Admin/ApplicationProcessingTest.php
- [X] T023 [P] [US2] Add reject-success + reject-missing-reason 422 tests in tests/Feature/Admin/ApplicationProcessingTest.php
- [X] T024 [P] [US2] Add invalid-transition 422 tests (received→approved, approved→processing,
  supplement_required→rejected, no-op processing→processing) in tests/Feature/Admin/ApplicationProcessingTest.php
- [X] T025 [P] [US2] Add unassigned-staff-action 403 test in tests/Feature/Admin/ApplicationProcessingTest.php
- [X] T026 [P] [US2] Add concurrent-transition test (two transitions same app, one succeeds, history
  not lost/duplicated) in tests/Feature/Admin/ApplicationProcessingTest.php

### Implementation for User Story 2

- [X] T027 [P] [US2] Create app/Actions/Application/ClaimApplicationAction.php (transaction +
  lockForUpdate: only unassigned `received`, staff in responsible department, assigned_by=self)
- [X] T028 [P] [US2] Create app/Actions/Application/StartProcessingAction.php (transition + set
  processing_started_at if null)
- [X] T029 [P] [US2] Create app/Actions/Application/ApproveApplicationAction.php (transition + completed_at
  + result_note) and app/Actions/Application/RejectApplicationAction.php (transition + completed_at +
  rejection_reason)
- [X] T030 [P] [US2] Create shared transition helper (transaction wrapper: lockForUpdate, assertTransition,
  update status, write application_status_histories) reused by all transition Actions
- [X] T031 [P] [US2] Create FormRequests: RequestSupplementRequest (note required), ApproveApplicationRequest
  (result_note optional), RejectApplicationRequest (rejection_reason required)
- [X] T032 [US2] Add admin routes + controllers for claim, start-processing, approve, reject
  (POST forms → flash redirect); wire store() with `authorize` (T3 completes enforcement)

**Checkpoint**: US2 is fully functional and independently testable.

---

## Phase 5: User Story 3 - Yêu cầu bổ sung & Citizen nộp tài liệu bổ sung (Priority: P1)

**Goal**: Staff requests supplement with a required note; Citizen sees the request + missing requirement
slots, uploads supplement documents for missing slots (not modifying earlier ones); Staff resumes
processing (soft, returning missing list).

**Independent Test**: Staff S requests supplement with reason on a `processing` app missing a required doc →
`supplement_required` + note in history; Citizen sees note + missing slots, uploads valid PDF →
`document_kind=supplement`; S resumes → `processing`; Citizen cannot modify earlier documents; upload at
`approved`/`rejected` → 403.

### Tests for User Story 3

- [X] T033 [P] [US3] Add request-supplement-success test (status + note in history) and
  missing-note 422 test in tests/Feature/Admin/ApplicationProcessingTest.php
- [X] T034 [P] [US3] Add request-supplement-from-received 422 test (received→supplement_required) in tests/Feature/Admin/ApplicationProcessingTest.php
- [X] T035 [P] [US3] Add resume-success test (supplement_required→processing, missing list returned) in tests/Feature/Admin/ApplicationProcessingTest.php
- [X] T036 [P] [US3] Add citizen-supplement-upload test (201, document_kind=supplement, requirement_code
  set, already-uploaded slot 422) in tests/Feature/Api/V1/ApplicationDocumentTest.php
- [X] T037 [P] [US3] Add citizen-upload-at-approved/rejected 403 test in tests/Feature/Api/V1/ApplicationDocumentTest.php
- [X] T038 [P] [US3] Add show-supplement-info test (supplement_note + missing_required_documents +
  cannot modify earlier docs) in tests/Feature/Api/V1/ApplicationSubmissionTest.php

### Implementation for User Story 3

- [X] T039 [P] [US3] Create app/Actions/Application/RequestSupplementAction.php (transition + note)
  and app/Actions/Application/ResumeProcessingAction.php (transition + return missing list)
- [X] T040 [P] [US3] Add admin routes + controllers for request-supplement, resume
- [X] T041 [US3] Extend app/Http/Resources/Api/V1/ApplicationResource.php: `processing_started_at`,
  `completed_at`, `result_note`, `rejection_reason`, `assigned_staff`, `supplement_note`, `timeline`
  (owner/internal only; null for others)

**Checkpoint**: US3 is fully functional and independently testable.

---

## Phase 6: User Story 4 - Citizen xem tiến độ xử lý và kết quả (Priority: P1)

**Goal**: Citizen views the full timeline, supplement note, and result (result_note / rejection_reason /
completed_at) and downloads the result document; other citizens/unauthenticated are denied.

**Independent Test**: Approve an app with result_note + result document → Citizen sees timeline
(received→processing→approved), result_note, completed_at and downloads the result file; a rejected app
shows rejection_reason; another citizen → 403.

### Tests for User Story 4

- [X] T042 [P] [US4] Add result-note+completed_at visibility test in tests/Feature/Api/V1/ApplicationSubmissionTest.php
- [X] T043 [P] [US4] Add rejection_reason visibility test in tests/Feature/Api/V1/ApplicationSubmissionTest.php
- [X] T044 [P] [US4] Add timeline-ordered test (from/to, actor name, created_at ASC) in tests/Feature/Api/V1/ApplicationSubmissionTest.php
- [X] T045 [P] [US4] Add result-document download test (owner 200; other citizen 403; guest 401) in tests/Feature/Api/V1/ApplicationDocumentTest.php
- [X] T046 [P] [US4] Add cross-application result-doc 404 test (scopeBindings) in tests/Feature/Api/V1/ApplicationDocumentTest.php

### Implementation for User Story 4

- [X] T047 [P] [US4] Create app/Actions/Application/StoreResultDocumentAction.php (document_kind=result,
  only while `processing`, staff-only) + app/Http/Requests/Admin/Applications/StoreResultDocumentRequest.php
  (reuse document validation rules from F04)
- [X] T048 [US4] Add admin route + controller for result-documents (multipart POST → flash redirect)

**Checkpoint**: US4 is fully functional and independently testable.

---

## Phase 7: Authorization & Policies (Ticket #99477)

**Goal**: Server-side authorization for every processing/assignment action via `ApplicationPolicy`,
scoped queries, and blocking of inactive/trashed actors and soft-deleted applications.

### Tests

- [X] T049 [P] Add assign/claim/transition 403 matrix (staff other department, unassigned staff,
  manager-not-assigned, citizen) in tests/Feature/Admin/ApplicationAuthorizationTest.php
- [X] T050 [P] Add inactive/trashed-staff blocked 403 test (FR-017) in tests/Feature/Admin/ApplicationAuthorizationTest.php
- [X] T051 [P] Add soft-deleted-application blocked 403 test (FR-017) in tests/Feature/Admin/ApplicationAuthorizationTest.php
- [X] T052 [P] Add super-admin-override tests (assign/claim/transition allowed) in tests/Feature/Admin/ApplicationAuthorizationTest.php
- [X] T053 [P] Add scope query tests (index: staff sees only own; manager sees dept; superadmin all) in tests/Feature/Admin/ApplicationAuthorizationTest.php

### Implementation

- [X] T054 [P] Add abilities to app/Policies/ApplicationPolicy.php: `assign`, `claim`, `startProcessing`,
  `requestSupplement`, `resume`, `approve`, `reject`, `uploadResultDocument` (assigned_staff_id + role +
  department scope + active/not-trashed guard; Super Admin override)
- [X] T055 [P] Add scopes to app/Models/Application.php: `scopeVisibleTo(actor)` (Staff: assigned;
  Manager: responsible dept; SuperAdmin: all), `scopeAssignableTo(actor)`, `scopeClaimableBy(actor)`
- [X] T056 Wire `authorize()` into every admin controller action; confirm `internal` middleware still
  filters roles; run ApplicationAuthorizationTest

**Checkpoint**: F05 authorization is complete and enforced server-side.

---

## Phase 8: Polish & Cross-Cutting Concerns (Ticket #99478)

**Purpose**: Edge cases, citizen SPA polish and quality gates affecting multiple user stories.

- [X] T057 Add result-doc-upload-when-rejected 409/403 test in tests/Feature/Admin/ApplicationProcessingTest.php
- [X] T058 Add reassign-during-processing test (old staff loses access, new staff gains, no reset of
  processing_started_at) in tests/Feature/Admin/ApplicationProcessingTest.php
- [X] T059 Add reassign-terminal 422 and claim-terminal 422 tests in tests/Feature/Admin/ApplicationProcessingTest.php
- [ ] T060 Update resources/js/citizen/pages/MyApplicationDetailPage.jsx: timeline block, supplement
  banner + missing-slot upload (reuse DocumentUploader), result/rejection block
- [ ] T061 Update tests/Feature/CitizenSpaTest.php route-render tests after UI change
- [ ] T062 Run `php artisan test --env=testing` (full suite), `composer run lint`, `npm run lint`,
  `npm run build` and fix issues

---

## Phase 9: Admin UI Staff Workspace — polish & requirements fixes (Ticket #99476)

**Purpose**: Hoàn thiện worklist/board và trang chi tiết hồ sơ cho Staff/Manager/Super Admin theo
FR-025 → FR-028: khối yêu cầu bổ sung nổi bật, hướng dẫn bước tiếp theo, preview tài liệu inline,
và fix lỗi chữ tràn/lệch card.

- [ ] T063 [P] Fix layout overflow: wrap/truncate văn bản dài trong
  resources/views/admin/applications/index.blade.php (tên dịch vụ, người nộp) và
  show.blade.php (form_data, note, mô tả) — chống tràn chữ khỏi card ở mọi breakpoint
- [ ] T064 [P] Add supplement-required banner in resources/views/admin/applications/show.blade.php:
  khi `status == supplement_required`, hiển thị khối warning ghi chú staff (`supplementNote()`) +
  danh sách tài liệu bắt buộc còn thiếu (FR-025)
- [ ] T065 [P] Add "Bước tiếp theo" guidance block in show.blade.php: map status → hành động hợp lệ
  theo quyền actor (@can), ẩn nút không áp dụng ở trạng thái hiện tại; hiển thị rõ việc cần làm
  (vd received → Nhận hồ sơ/Bắt đầu xử lý; processing → Yêu cầu bổ sung/Duyệt/Từ chối) (FR-026)
- [ ] T066 [P] Add document preview in show.blade.php: xem trước PDF/image inline (iframe/object hoặc
  URL blob) cho tài liệu submission/supplement/result; giữ nút Tải; giới hạn cho 3 role nội bộ
  (FR-027)
- [ ] T067 [P] Refactor index.blade.php thành worklist phân nhóm: "Hồ sơ của tôi" (assigned) + "Có
  thể nhận" (claimable) cho Staff; Assignment Board (pending/overdue) cho Manager/Super Admin (FR-028)
- [ ] T068 Test render Blade qua route trong tests/Feature/Admin/ApplicationWorkspaceViewTest.php:
  index (staff/manager/superadmin) + show (banner supplement, guidance, preview, layout) không lỗi
- [ ] T069 Run `php artisan test --env=testing --filter ApplicationWorkspaceViewTest` + `composer run
  lint` + `npm run lint` + `npm run build`

---

## Dependencies & Execution Order

- **Setup (Phase 1)** → **Foundational (Phase 2)** blocks all user stories.
- **US1 (Phase 3)** → **US2 (Phase 4)** → **US3 (Phase 5)**; **US4 (Phase 6)** depends on US2 (approve).
- **Authz (Phase 7)** overlaps US1–US4 implementation but gates final enforcement.
- **Polish (Phase 8)** depends on all user stories + authz.

### Within each user story

- Tests first (fail before implementation), then model/action, then controller/route.
- Parallel `[P]` tasks touch different files.

### Parallel Opportunities

- All `[P]` test tasks in a phase can run together.
- `ApplicationProcessingTest.php` (Admin) covers US1–US3 transitions; `ApplicationDocumentTest`/
  `ApplicationSubmissionTest` (API) cover citizen supplement + result view; run together at end of phase.

## Notes

- Use FormRequest, ApiResponse envelope (API only), Actions for logic, Policies for authorization
  (SunLint S005/S025/S028, LV002/LV005).
- Admin forms are Blade SSR POST with CSRF (contracts/admin-workflow.md); no JSON from admin UI.
- No new migrations (data-model.md).
- Do not commit `.env`, secrets, or unrelated changes (e.g. `.specify/memory/constitution.md`).