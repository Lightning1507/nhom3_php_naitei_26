# Implementation Plan: F05 - Application Processing Workflow

**Branch**: `feature/98887-application-processing-workflow` (artifacts committed on
`task/99475-admin-processing-api` for now) | **Date**: 2026-08-20 | **Ticket**: #98887 |
**Spec**: [spec.md](./spec.md)

## Summary

Xây luồng cán bộ xử lý hồ sơ dịch vụ công: Manager gán/nhận hồ sơ, Staff nhận việc (claim) và
chuyển trạng thái qua vòng đời `received → processing → supplement_required → processing →
approved/rejected`, Citizen bổ sung tài liệu và theo dõi kết quả. Không thêm migration (schema F00/F04
đã đủ); toàn bộ logic nằm ở Actions + Policy + Admin Blade SSR + mở rộng `ApplicationResource` cho SPA.

Feature gồm 4 ticket con: #99475 (Admin Processing API), #99476 (Admin UI Staff Workspace),
#99477 (Authorization & Policies), #99478 (Test tổng hợp & edge cases). Mỗi ticket một nhánh
`task/<ticket>-<slug>` từ `master` đã sync upstream; artifact dùng chung một thư mục
`specs/98887-application-processing-workflow/`.

## Quyết định đã chốt (xác nhận với user)

- **spec.md là chuẩn**: gồm Staff claim (FR-004) và Staff resume sau bổ sung
  (`supplement_required → processing`, soft validation, KHÔNG cho citizen tự quay về processing).
- **State machine** chỉ cho phép: `received→processing`, `processing→supplement_required`,
  `supplement_required→processing`, `processing→approved`, `processing→rejected`; mọi chuyển khác → 409.
- **Phạm vi gán/claim**: staff đủ điều kiện = Active Staff thuộc phòng ban phụ trách dịch vụ
  (`ServiceType.responsible_department_id`), **không** dùng pivot `service_staff` (F04 không quản lý).
- **Phạm vi Manager**: `Department.leader_id` (tái dùng `Department::scopeVisibleTo`); Super Admin override.
- **Mỗi chuyển trạng thái** nằm trong `DB::transaction` + `lockForUpdate` (chống chuyển đồng thời),
  ghi đúng một dòng `application_status_histories` (`changed_by` = actor, note).
- **Đổi staff khi đang `processing`**: đóng assignment hiện tại (`ended_at`), tạo assignment mới,
  update `assigned_staff_id`, không reset `processing_started_at`. Đổi staff không đổi status.
- **Tài liệu kết quả** (`document_kind=result`): staff upload qua endpoint riêng khi `processing`;
  `rejected` → không cho upload result (edge case).
- **Overdue dashboard** = `completed_at IS NULL AND submitted_at + processing_time_days < now()`.
- **Các mốc thời gian**: `processing_started_at` set lần đầu khi `received→processing`;
  `completed_at` chỉ set ở `approved`/`rejected`.
- **4 ticket con**, thứ tự: #99475 → #99476 → #99477 → #99478 (mỗi ticket ≤ 8h theo `docs/workflow.md`).

## Technical Context

**Language/Version**: PHP 8.3 / Laravel 13 (đang chạy PHP 8.5.x + Composer 2.9.x)

**Primary Dependencies**: Laravel Sanctum, Eloquent ORM, Vite + React 19 (Citizen SPA), Alpine (Admin Blade)

**Storage**: PostgreSQL (dev = Supabase; test = local Docker `postgres:17`), disk `local` (private)
cho tài liệu (đã có từ F04)

**Testing**: PHPUnit (Laravel Feature Tests) — `tests/Feature/Admin/`, `tests/Feature/Api/V1/`

**Project Type**: Web application (Laravel REST API `/api/v1` + React SPA + Blade SSR)

**Conventions**: FormRequest thay `$request->validate()`, `ApiResponse` envelope (chỉ cho API citizen),
Actions/Services cho business logic, Policies cho authorization (SunLint S005/S025/S028, LV004/LV011/LV002)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Laravel-First Backend & Simplicity**: PASS. Controller mỏng; logic chuyển trạng thái trong
  Actions + một TransitionMap dùng chung; không DDD/repository.
- **II. Feature-Driven Development**: PASS. Toàn bộ F05 đi qua Spec Kit, artifact tập trung tại
  `specs/98887-.../`; mỗi ticket con có ticket Redmine riêng và test tiêu chí.
- **III. Application-Centric Domain**: PASS. Trạng thái hồ sơ là nguồn sự thật; mọi thay đổi ghi
  history append-only; assignment append-only.
- **IV. Authorization & Data Protection**: PASS. Gán/claim/chuyển trạng thái kiểm tra server-side
  bằng Policy + scope phòng ban; staff khác phòng ban không nhìn/xử lý hồ sơ; citizen chỉ xem hồ sơ
  mình và tài liệu hợp lệ.
- **V. Database Integrity & Auditability**: PASS. Transaction + row-lock cho mọi chuyển trạng thái/
  gán; history và assignment append-only bảo toàn kiểm toán; không thêm cột mới.
- **VI. Citizen React SPA & Admin Blade SSR**: PASS. Admin xử lý hồ sơ bằng Blade SSR + Alpine dưới
  `/admin`; Citizen bổ sung tài liệu/xem kết quả qua API `/api/v1` + SPA.
- **VII. Quality & Definition of Done**: PASS. Test feature cho từng transition, từng lỗi từ chối
  và từng phân quyền; chạy full suite + lint cuối mỗi ticket.

## Phân rã Task

| # | Ticket | Task | Est | Due | Phụ thuộc | Trạng thái |
|---|--------|------|-----|-----|-----------|-----------|
| T1 | 99475 | Admin Processing API — transitions & transaction | 6h | 08/20 | F00/F04 (schema sẵn) | 🚧 in progress |
| T2 | 99476 | Admin UI — Staff Workspace (Blade) | 6h | 08/20 | T1 | ⬜ |
| T3 | 99477 | Authorization & Policies (Processing) | 4h | 08/20 | T1 | ⬜ |
| T4 | 99478 | Test tổng hợp, edge cases & hoàn thiện (F05) | 2h | 08/20 | T1–T3 | ⬜ |

## Thứ tự thực hiện

**T1 → T2 → T3 → T4**. Mỗi task một nhánh riêng tạo từ `master` đã sync; tạo nhánh kế sau khi PR
trước merge (docs/workflow.md mục 6-7).

```bash
git switch master
git pull --ff-only upstream master
git push origin master
git switch -c task/<ticket-id>-<slug>
# code + test + commit
git push -u origin task/<ticket-id>-<slug>
# PR: origin/task/<ticket-id> -> upstream/master, title "#<ticket-id> <Subject>"
```

---

### T1 - #99475 Admin Processing API — Status transitions & Transaction (6h)

**Branch**: `task/99475-admin-processing-api`

**Nội dung** (backend trọn vẹn, UI tối thiểu ở T2):
- `app/Support/Application/ApplicationTransitionMap.php` — map transition hợp lệ + `assertTransition`.
- `app/Actions/Application/` các Action (mỗi Action = 1 transaction + `lockForUpdate` + ghi history):
  `AssignApplicationAction`, `ClaimApplicationAction`, `StartProcessingAction`,
  `RequestSupplementAction`, `ResumeProcessingAction`, `ApproveApplicationAction`,
  `RejectApplicationAction`, `StoreResultDocumentAction`.
- Model helper: `Application::activeAssignment()`, `Application::isOverdue()`, scope worklist/board.
- Admin controllers + routes (`auth`+`internal`, prefix `admin`, name `admin.applications.*`):
  index, show, assign, claim, start-processing, request-supplement, resume, approve, reject,
  result-documents (form POST, redirect + flash).
- FormRequests: `AssignApplicationRequest`, `RequestSupplementRequest`, `ApproveApplicationRequest`,
  `RejectApplicationRequest`.
- Mở rộng `ApplicationResource`: `processing_started_at`, `completed_at`, `result_note`,
  `rejection_reason`, `assigned_staff`, `supplement_note`, `timeline`.
- Factories: `ApplicationFactory`, `ApplicationAssignmentFactory`, `ApplicationStatusHistoryFactory`.
- Test `tests/Feature/Admin/ApplicationProcessingTest.php` (transitions, hành vi mỗi action, lỗi 409).

**Commit**: `feat: #99475 add admin application processing API`

### T2 - #99476 Admin UI — Staff Workspace (6h)

**Branch**: `task/99476-admin-ui-staff-workspace`

**Nội dung**:
- Blade `resources/views/admin/applications/index.blade.php` — worklist (Staff: hồ sơ của mình +
  nhóm claim được) và Assignment Board (Manager/Super Admin: + 2 số liệu pending/overdue) + bộ lọc.
- `resources/views/admin/applications/show.blade.php` — chi tiết: form_data, timeline, tài liệu
  (submission/supplement/result), nút hành động theo status + quyền (dialog Alpine, dùng
  `x-admin` components như `departments/show.blade.php`), upload result doc, khối thiếu tài liệu.
- Alpine dialog cho assign (candidate combobox staff phòng ban phụ trách), request-supplement,
  approve, reject.
- Test render qua route (Blade) `tests/Feature/Admin/ApplicationWorkspaceViewTest.php`.

**Commit**: `feat: #99476 build admin staff workspace UI`

### T3 - #99477 Authorization & Policies (Processing) (4h)

**Branch**: `task/99477-authorization-policies-processing`

**Nội dung**:
- `app/Policies/ApplicationPolicy.php`: thêm `assign`, `claim`, `startProcessing`, `requestSupplement`,
  `resume`, `approve`, `reject`, `uploadResultDocument` — bám `assigned_staff_id` + role (Super Admin
  override) + phạm vi phòng ban.
- Gắn `authorize()` vào từng controller action; `internal` middleware vẫn là lớp lọc vai trò.
- Scope truy vấn: `Application::scopeVisibleTo(actor)` (Staff: assigned; Manager: dept phụ trách;
  Super Admin: all), `scopeAssignableTo(actor)`, `scopeClaimableBy(actor)`.
- Test `tests/Feature/Admin/ApplicationAuthorizationTest.php` (chéo role/phòng ban, inactive/trashed).

**Commit**: `feat: #99477 enforce processing authorization policies`

### T4 - #99478 Test tổng hợp, edge cases & hoàn thiện (F05) (2h)

**Branch**: `task/99478-final-testing-edge-cases`

**Nội dung**:
- Edge cases: chuyển trạng thái đồng thời (row-lock), no-op transition, chuyển từ trạng thái cuối,
  claim đã có người gán, đổi staff giữa chừng, thiếu note/reason, result doc khi reject, trùng lịch
  sử, soft-delete service/department, n+1 (LV002).
- Citizen SPA polish: `MyApplicationDetailPage.jsx` hiển thị timeline, banner bổ sung, slot
  `DocumentUploader` cho `missing_required_documents`, khối kết quả/ từ chối.
- Chạy full suite `php artisan test --env=testing` + `composer run lint` + `npm run lint` + `npm run build`.

**Commit**: `test: #99478 finalize application processing tests`

---

## Chuẩn bị môi trường (chạy 1 lần trước T1)

```bash
composer install
docker run -d --name psm-pg -e POSTGRES_USER=postgres -e POSTGRES_PASSWORD='' \
  -e POSTGRES_DB=public_service_management_testing -p 5432:5432 postgres:17
php artisan migrate --env=testing
```

## Ghi chú

- Không commit `.agent/`, `AGENTS.md`, `docs/workflow.md`, `.env`, secrets.
- Artifact F05 gộp một thư mục `specs/98887-application-processing-workflow/` (spec, research,
  data-model, contracts, plan, tasks, quickstart) — giống F04.
- Redmine: `In Progress -> PR -> Resolved -> (merge) -> Closed`, % Done + spent time hàng ngày.
- Sau khi mỗi PR merge: `git switch master && git pull --ff-only upstream master && git push origin master`
  rồi mới tạo nhánh task kế.