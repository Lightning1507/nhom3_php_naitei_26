# Implementation Plan: F04 - Application Submission & Document Management

**Branch**: `task/98886-application-submission-and-document-management` | **Date**: 2026-08-15 | **Ticket**: #98886 | **Spec**: [spec.md](./spec.md)

## Summary

Xây luồng Citizen nộp hồ sơ dịch vụ công trực tuyến: chọn service, nhập `form_data`
theo `form_schema` động, upload tài liệu, sinh mã hồ sơ `HS-YYYYMMDD-xxxxx`, xem danh
sách/chi tiết hồ sơ, và bảo vệ quyền sở hữu bằng Policy. Feature gồm 5 task nhỏ
(T1–T5), mỗi task một ticket Redmine riêng nhưng tất cả cùng thuộc feature #98886;
toàn bộ artifact (spec, plan, research, data-model, contracts, tasks) được giữ trong
một thư mục feature duy nhất `specs/98886-.../`.

## Quyết định đã chốt

- **Mã hồ sơ**: `HS-YYYYMMDD-xxxxx`, số thứ tự **reset theo ngày**, lưu trong bảng mới
  `application_code_sequences` (migration mới) để chống trùng khi nộp đồng thời.
- **Branch prefix**: `task/<ticket-id>-<slug>` (nhất quán với branch đã merge PR #2/#3).
- **DB test**: dùng PostgreSQL local (Docker) `public_service_management_testing`,
  migrate bằng `--env=testing`; **không đụng** Supabase (`.env`).
- **Tài liệu upload**: disk `local` (private) dưới `applications/{application_id}/`;
  validate `mimes:pdf,jpg,jpeg,png` + `max:10240` (10 MB); metadata
  `original_name`, `mime_type`, `file_size`, `document_kind=submission` vào
  `application_documents`; download qua endpoint có authorization
  (chủ hồ sơ + Staff/Manager/Super Admin); xóa mềm khi hồ sơ còn `received`;
  route `scopeBindings()` chống truy cập chéo hồ sơ.
- **T3 cần endpoint tối thiểu** `GET /api/v1/services` (list active) vì public catalog
  F02 chưa xong; F02 sẽ thay thế sau.
- **Auth trong T1-T3**: test dùng `$this->actingAs($citizen)`; cưỡng chế thật bằng
  Policy/Middleware nằm ở T4.
- **Lỗi validation 422**: giữ format mặc định Laravel trong T1-T4; thống nhất envelope
  `{success, message, errors}` ở T5.

## Technical Context

**Language/Version**: PHP 8.3 / Laravel 13 (đang chạy PHP 8.5.4 + Composer 2.9.5)

**Primary Dependencies**: Laravel Sanctum, Eloquent ORM, Vite + React 19 (Citizen SPA)

**Storage**: PostgreSQL (dev = Supabase; test = local Docker `postgres:17`),
filesystem disk `local` (private) cho tài liệu

**Testing**: PHPUnit (Laravel Feature Tests) - `tests/Feature/Api/V1/`

**Project Type**: Web application (Laravel REST API `/api/v1` + React SPA + Blade SSR)

**Conventions**: FormRequest thay `$request->validate()`, ApiResponse envelope,
Actions/Services cho business logic, Policies cho authorization (SunLint S005/S025/S028,
LV004/LV011/LV002)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Laravel-First Backend & Simplicity**: PASS. Luồng Route -> Controller -> FormRequest
  -> Policy -> Action -> Eloquent; controller mỏng; không repository/DDD/microservice.
- **II. Feature-Driven Development**: PASS. Toàn bộ F04 đi qua Spec Kit với một spec/plan/
  tasks tập trung tại `specs/98886-.../`; mỗi task có ticket Redmine riêng và test tiêu chí.
- **III. Application-Centric Domain**: PASS. Status mặc định `received`, chuyển trạng thái
  qua `application_status_histories`; chỉ citizen sở hữu mới tác động lên hồ sơ của mình.
- **IV. Authorization & Data Protection**: PASS. Mọi thao tác bảo vệ kiểm tra server-side
  bằng Policy; citizen chỉ truy cập hồ sơ/tài liệu của mình; Staff/Manager/Super Admin được
  download tài liệu; tài liệu lưu private disk.
- **V. Database Integrity & Auditability**: PASS. `application_code_sequences` chống trùng mã;
  unique constraint; transaction cho tạo hồ sơ và sinh mã; soft delete tài liệu bảo toàn
  bản ghi kiểm toán; history chuyển trạng thái.
- **VI. Citizen React SPA & Admin Blade SSR**: PASS. API nằm dưới `/api/v1` cho Citizen SPA;
  không thêm UI Blade cho business flow Citizen.
- **VII. Quality & Definition of Done**: PASS. Test feature cho nộp hồ sơ và upload/download/
  xóa tài liệu kèm các ca từ chối và phân quyền; cuối mỗi task chạy PHPUnit + lint.

## Phân rã Task

| # | Ticket | Task | Est | Due | Phụ thuộc | Trạng thái |
|---|--------|------|-----|-----|-----------|-----------|
| T1 | 98999 | Application Submission API | 6h | 08/16 | F00 (migration sẵn) | ✅ merged (PR #5) |
| T2 | 99000 | Document Upload & Download | 6h | 08/16 | T1 | 🚧 in progress |
| T3 | 99001 | Citizen SPA (Service Detail + Form + My Applications) | 6h | 08/17 | T1, T2 | ⬜ |
| T4 | 99002 | Authorization & Ownership (Policies) | 3h | 08/17 | T1, T2 | ⬜ |
| T5 | 99003 | Test tổng hợp & edge cases | 3h | 08/17 | T1-T4 | ⬜ |

## Thứ tự thực hiện

**T1 → T2 → T3 → T4 → T5**. Mỗi task một nhánh riêng tạo từ `master` đã sync
(theo `docs/workflow.md` mục 6-7); tạo nhánh kế sau khi PR trước được merge.

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

### T1 - #98999 Application Submission API (6h)

**Branch**: `task/98999-application-submission-api`

**Nội dung**:
- Migration mới `application_code_sequences` (sequence_date + last_sequence, unique date).
- `app/Services/ApplicationCodeService.php` - sinh mã `HS-YYYYMMDD-xxxxx` trong transaction
  (`lockForUpdate` dòng sequence theo ngày).
- `app/Actions/Application/CreateApplicationAction.php` - transaction: sinh code -> tạo
  `applications` (status `received`, `submitted_at`) -> ghi `application_status_histories`
  (`from=null` -> `to=received`).
- `app/Http/Requests/Api/V1/StoreApplicationRequest.php` - validate `service_type_id`
  (tồn tại, active, không soft-delete) + `form_data` theo `form_schema`.
- `app/Http/Resources/Api/V1/ApplicationResource.php`.
- `app/Http/Controllers/Api/V1/ApplicationController.php` - `store` (201), `index`
  (paginate theo citizen), `show`.
- Routes trong `routes/api.php` (prefix `v1`).
- Factory `database/factories/ServiceTypeFactory.php` (chưa có).
- Test `tests/Feature/Api/V1/ApplicationSubmissionTest.php`: tạo OK; thiếu field bắt buộc
  -> 422; service inactive -> 422; nộp đồng thời không trùng mã; index chỉ trả hồ sơ của
  mình; history ghi đúng `received`.

**Commit**: `feat: #98999 add application submission API`

### T2 - #99000 Document Upload & Download (6h)

**Branch**: `task/99000-document-upload-download`

**Nội dung**:
- **Storage**: disk `local` (private), file đặt dưới `applications/{application_id}/`;
  không có route công khai, mọi download đi qua endpoint có authorization.
- **Validation**: `StoreApplicationDocumentRequest` — bắt buộc file,
  `mimes:pdf,jpg,jpeg,png` (MIME thực tế, không chỉ đuôi mở rộng), `max:10240` (10 MB).
- **Metadata**: `application_documents` lưu `original_name`, `mime_type`, `file_size`,
  `disk`, `path`, `document_kind=submission`, `uploaded_by`.
- **Authorization** (Policy, server-side):
  - Upload: chỉ citizen sở hữu hồ sơ (`ApplicationPolicy::uploadDocument`).
  - Download: chủ hồ sơ + Staff/Manager/Super Admin (`ApplicationDocumentPolicy::download`).
  - Xóa: chỉ chủ hồ sơ và hồ sơ `received` (`ApplicationDocumentPolicy::delete`).
- **Route binding**: `GET/DELETE /applications/{application}/documents/{document}` dùng
  `scopeBindings()` — tài liệu phải thuộc đúng hồ sơ trong URL (không → 404).
- **Xóa mềm**: `ApplicationDocument` dùng `SoftDeletes`; bản ghi được giữ để kiểm toán.
- **Envelope**: `ApiResponse` (`{success, message, data}` / lỗi) +
  `ApplicationDocumentResource`.
- Test `tests/Feature/Api/V1/ApplicationDocumentTest.php`: upload PDF/ảnh OK;
  `.exe`/quá dung lượng 422; owner download OK; staff/manager download OK;
  citizen khác 403; chưa login 401; đã xóa mềm 404; tài liệu thuộc hồ sơ khác 404;
  soft-delete khi `received` OK; xóa khi `processing` 403; file mất trên disk → 404.

**Commit**: `feat: #99000 add document upload and download`

### T3 - #99001 Citizen SPA (6h)

**Branch**: `task/99001-citizen-spa-application-form`

**Nội dung**:
- Endpoint tối thiểu `GET /api/v1/services` (list active) + `ServiceTypeResource`.
- Pages: `ServiceDetailPage.jsx`, `ApplyPage.jsx` (form động theo `form_schema` + upload),
  `MyApplicationsPage.jsx` (list + chi tiết + trạng thái).
- Routes trong `App.jsx` (giữ fallback citizen); dùng `api/client.js`.
- Cover bằng feature test qua route (chuẩn `CitizenSpaTest`).

**Commit**: `feat: #99001 build citizen application SPA`

### T4 - #99002 Authorization & Ownership (3h)

**Branch**: `task/99002-authorization-ownership`

**Nội dung**:
- `app/Policies/ApplicationPolicy` + `ApplicationDocumentPolicy` (S005, LV005).
- Gắn `authorize` vào controller; 401 khi chưa login, 403 khi không sở hữu.
- Test `tests/Feature/Api/V1/ApplicationAuthorizationTest.php` (truy cập chéo 2 citizen).

**Commit**: `feat: #99002 enforce application authorization policies`

### T5 - #99003 Test tổng hợp & edge cases (3h)

**Branch**: `task/99003-final-testing-edge-cases`

**Nội dung**:
- Edge cases: nộp đồng thời, upload lỗi/thiếu/quá dung lượng, service inactive/soft-delete,
  chống n+1 (LV002).
- Chạy full suite trên DB test local; rà SunLint.
- Thống nhất envelope lỗi validation 422 `{success, message, errors}`.

**Commit**: `test: #99003 finalize application submission tests`

---

## Chuẩn bị môi trường (chạy 1 lần trước T1)

```bash
composer install
docker run -d --name psm-pg -e POSTGRES_USER=postgres -e POSTGRES_PASSWORD='' \
  -e POSTGRES_DB=public_service_management_testing -p 5432:5432 postgres:17
php artisan migrate --env=testing
```

## Ghi chú

- Không commit `.agent/`, `AGENTS.md`, `docs/workflow.md` (file untracked ngoài phạm vi).
- Không commit `.env`, secrets.
- Task #99000 không phải một feature riêng: toàn bộ artifact được gộp vào thư mục feature
  `specs/98886-.../` (spec, research, data-model, contracts, tasks). Thư mục
  `specs/99000-document-upload-download/` đã được xóa khi redo speckit (2026-08-18).
- Sau khi mỗi PR merge: `git switch master && git pull --ff-only upstream master && git push origin master`
  rồi mới tạo nhánh task kế.
- Redmine: `In Progress -> PR -> Resolved -> (merge) -> Closed`, % Done + spent time hàng ngày.
