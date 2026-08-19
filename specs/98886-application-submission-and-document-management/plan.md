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

---

# Increment 2 — 2026-08-19: Dynamic per-service document requirements, per-requirement upload & business locking

**Branch**: `task/99001-citizen-spa-application-form` (làm trong task #99001) | **Spec**: [spec.md](./spec.md)

## Context

Hiện tại `document_requirements` của service chỉ là danh sách mô tả hiển thị; upload tài liệu là
vùng tự do **không ràng buộc file với requirement nào**, `application_documents` không có cột
`requirement_code`, và admin chỉ khai `{name, is_required}` (thiếu `type`, thiếu `code/label`).
Ngoài ra lock tài liệu mới chỉ ở `delete` (chặn khi status ≠ `received`); upload chưa bị chặn theo
status → citizen có thể upload tài liệu kể cả khi hồ sơ đang được xử lý.

Increment này làm **tài liệu bám theo từng requirement của service** (admin khai requirement:
tên, bắt buộc hay không, type pdf/image/mixed), citizen nộp **theo từng slot requirement**,
thêm **lock nghiệp vụ** upload/delete theo trạng thái, và **soft validation** (thiếu tài liệu bắt
buộc → cảnh báo đỏ nhưng vẫn cho nộp; staff xem chi tiết sẽ yêu cầu bổ sung ở feature sau).

## Quyết định đã chốt (xác nhận với user)

- **Soft validation**: thiếu tài liệu bắt buộc → **log đỏ báo thiếu**, **KHÔNG chặn nộp**. Hệ thống
  trả `missing_required_documents` trong response; staff sau này xem chi tiết sẽ thấy thiếu gì và
  yêu cầu nộp bổ sung (chuyển `supplement_required`).
- **`type` của requirement**: enum `pdf | image | mixed`:
  - `pdf` → chỉ chấp nhận `application/pdf`.
  - `image` → chỉ chấp nhận `image/jpeg`, `image/png`.
  - `mixed` → cả pdf + ảnh (tương đương quy tắc hiện tại).
- **Phạm vi**: làm trọn trong task #99001 — backend + migration + admin editor + citizen SPA + lock.
- **NGOÀI phạm vi**: UI Admin xử lý hồ sơ (index/detail hồ sơ, nút "Yêu cầu bổ sung", approve/
  reject, assign staff) → feature xử lý hồ sơ sau. Increment này chỉ **chuẩn bị hạ tầng**: policy cho
  phép upload bổ sung khi `supplement_required`, `document_kind=supplement` đã có trong enum.

## Thay đổi data model

1. **Migration mới** `add_requirement_code_to_application_documents_table`:
   - `requirement_code` string nullable, có index.
   - Dữ liệu cũ: `NULL` (legacy, vẫn xem/tải được; không gán requirement).
2. **Chuẩn hoá `document_requirements`** (JSON trên `service_types`):
   - Shape chuẩn: `{ "code": string, "label": string, "required": bool, "type": "pdf|image|mixed" }`.
   - Backfill dữ liệu (command/script chạy 1 lần, có idempotent): với mỗi service, normalize từng
     requirement:
     - `label` = `label` nếu có, ngược lại `name` (shape admin cũ).
     - `required` = `required` nếu có, ngược lại `is_required`.
     - `code` = `code` nếu có, ngược lại `Str::slug(label)`; bảo đảm unique trong service
       (nếu trùng → thêm hậu tố `-2`, `-3`).
     - `type` = `type` nếu có, ngược lại `mixed`.
3. **Helper dùng chung** `app/Support/ServiceSchema.php` (hoặc `app/Services/`):
   - `normalizeDocumentRequirements(array|json): array<{code,label,required,type}>`
   - `normalizeFormSchema(array|json): array<{name,label,type,required}>` (dung nạp `{name,type,
     is_required}` lẫn `{name,label,required}`; bỏ qua trường type `file`).
   - Dùng ở cả Action backend lẫn expose ra resource (không duplicate logic).

## Thay đổi API contract (`/api/v1`)

- `POST /api/v1/applications/{application}/documents`:
  - Thêm field `requirement_code` (string, optional).
  - **Bắt buộc** khi service của hồ sơ có ≥ 1 requirement; phải nằm trong tập code của service
    (không → 422). Khi service không có requirement → cho phép upload tự do (không cần code).
  - `document_kind` được đặt theo status hiện tại: `received` → `submission`;
    `supplement_required` → `supplement` (Action tự quyết, client không gửi).
  - Response item tài liệu thêm `requirement_code` + `requirement_label`.
- `POST /api/v1/applications` (store): không chặn thiếu tài liệu; response `data` kèm
  `missing_required_documents: [{code, label}]` (tính từ documents hiện có, mặc định rỗng lúc tạo).
- `GET /api/v1/applications/{id}` (show): `documents[]` mỗi item có `requirement_code`/
  `requirement_label`; response kèm `missing_required_documents`.
- `DELETE .../documents/{document}`: giữ nguyên, thêm điều kiện `assigned_staff_id IS NULL`.

## Lock nghiệp vụ (Policy — server-side, S005)

- `ApplicationPolicy::uploadDocument` (app/Policies/ApplicationPolicy.php):
  - Owner-only (giữ).
  - Thêm: `status ∈ {received, supplement_required}`. Các status khác → **403** (không upload khi
    đang xử lý/đã xong).
  - Khi `supplement_required`: chỉ cho upload (kind=supplement), không được thay đổi tài liệu cũ.
- `ApplicationDocumentPolicy::delete` (app/Policies/ApplicationDocumentPolicy.php):
  - Owner + `status === received` (giữ).
  - Thêm hardening: `assigned_staff_id === null` (khi staff đã nhận hồ sơ → khoá xóa dù status
    chưa kịp đổi) → **403**.
- `ApplicationDocumentPolicy::download`: giữ nguyên (owner + Staff/Manager/Super Admin).

## Phân rã công việc (trong task #99001)

### Phase A — Backend data & contract

1. Migration `requirement_code` trên `application_documents` (+ index).
2. Command backfill chuẩn hoá `document_requirements` các service hiện có (idempotent).
3. `app/Support/ServiceSchema.php` (normalize doc req + form schema).
4. `app/Http/Requests/Api/V1/StoreApplicationDocumentRequest.php`: rule `requirement_code`
   (`nullable|string`; khi service có requirement → `required`; `in:` tập code của service).
5. `app/Actions/Application/StoreApplicationDocumentAction.php`: nhận `requirement_code`, đặt
   `document_kind` theo status; validate requirement thuộc service (server-side, không tin client).
6. `app/Http/Resources/Api/V1/ApplicationDocumentResource.php`: + `requirement_code`,
   `requirement_label` (lazy resolve từ service của application).
7. `app/Http/Resources/Api/V1/ApplicationResource.php`: + `missing_required_documents` (tính từ
   service.document_requirements vs documents hiện có, chỉ khi status `received`/`supplement_required`).
8. `app/Http/Requests/Api/V1/StoreApplicationRequest.php`: không đổi luật chặn; `CreateApplicationAction`
   trả thêm `missing_required_documents` vào response (soft).

### Phase B — Lock policies

9. `ApplicationPolicy::uploadDocument`: status lock + (supplement_required → chỉ supplement).
10. `ApplicationDocumentPolicy::delete`: + `assigned_staff_id === null`.

### Phase C — Admin service-type editor (Blade + Alpine)

11. `StoreServiceTypeRequest`/`UpdateServiceTypeRequest`: `document_requirements.*` thêm
    `type => in:pdf,image,mixed`; giữ `name` (làm label), `is_required`.
12. `CreateServiceType`/`UpdateServiceType` Actions: chạy `ServiceSchema::normalize...` trước khi
    lưu → sinh `code` từ `name`, đổ `type` mặc định `mixed`, lưu shape chuẩn.
13. `resources/views/admin/service-types/create.blade.php` + `edit.blade.php`: mỗi dòng requirement
    thêm select `type` (PDF/Ảnh/Cả hai) + preview `code` tự sinh; bỏ option `file` khỏi select
    type của `form_schema` (file → document requirement).

### Phase D — Citizen SPA per-requirement upload

14. `resources/js/citizen/utils/schema.js`: thêm `normalizeDocumentRequirements` (shape chuẩn,
    code/label/required/type) + `requirementAccept(requirement)` (danh sách mime theo type).
15. `resources/js/citizen/components/DocumentUploader.jsx`: chuyển thành **slot theo requirement** —
    nhận `requirement`, file gắn `requirement_code`; dropzone per-slot; hiển thị
    label + `*` nếu required + hint type (PDF / Ảnh / PDF hoặc Ảnh); cảnh báo đỏ khi slot required
    chưa có file; giữ validate mime/size theo `type` của slot.
16. `resources/js/citizen/pages/ApplyPage.jsx`:
    - Step 2: render danh sách slot requirement (thay cho 1 dropzone tự do); state `files`
      thành `[{requirement_code, file}]`; nếu service không có requirement → vẫn hiện 1 dropzone
      tự do như cũ.
    - Step 3 (review): khi thiếu slot bắt buộc → **dòng đỏ** "Thiếu N tài liệu bắt buộc: <label>"
      nhưng **vẫn cho phép nộp** (soft). Submit upload từng file kèm `requirement_code`.
17. `resources/js/citizen/pages/MyApplicationDetailPage.jsx`:
    - Documents hiển thị theo nhóm requirement (label) + code.
    - Banner đỏ "Thiếu tài liệu bắt buộc: ..." khi status `received` và còn thiếu.
    - Phần "Tải thêm": chỉ render các slot requirement còn thiếu (status `received`); ẩn hoàn toàn
      khi status ≠ `received` (phần supplement hiển thị khi feature admin sau làm `supplement_required`).

### Phase E — Tests & quality gates

18. Feature tests (xem danh sách dưới).
19. `npm run build`, `npm run lint`, `composer run lint` (Pint), full suite `php artisan test --env=testing`.

## Danh sách test

`tests/Feature/Api/V1/ApplicationDocumentTest.php` / `ApplicationSubmissionTest.php`:

- Upload có `requirement_code` hợp lệ → 201, doc lưu đúng `requirement_code` + `requirement_label`.
- Upload thiếu `requirement_code` khi service có requirement → 422.
- Upload `requirement_code` không thuộc service → 422.
- Service không có requirement → upload không cần `requirement_code` (giữ luồng cũ).
- Upload khi status `processing`/`approved`/`rejected` → 403.
- Upload khi `supplement_required` → 201, `document_kind=supplement`.
- Delete khi `received` + `assigned_staff_id=null` → 200; khi đã gán staff → 403.
- `show` trả `missing_required_documents` đúng (thiếu → liệt kê code/label; đủ → []).
- `store` (nộp thiếu tài liệu bắt buộc) → **201** (soft), response kèm `missing_required_documents`.

`tests/Feature/Admin/ServiceType...`:

- Store/Update service với `document_requirements` có `type` → lưu shape chuẩn, `code` tự sinh,
  unique trong service.
- `type` không hợp lệ → 422.

`tests/Feature/CitizenSpaTest.php`:

- Apply + My Applications render (route test) sau khi đổi UI slot.

## Files touched

- Migration mới `database/migrations/2026_08_19_*_add_requirement_code_to_application_documents_table.php`
- `app/Support/ServiceSchema.php` (mới)
- `app/Http/Requests/Api/V1/StoreApplicationDocumentRequest.php`
- `app/Actions/Application/StoreApplicationDocumentAction.php`
- `app/Http/Resources/Api/V1/ApplicationDocumentResource.php`
- `app/Http/Resources/Api/V1/ApplicationResource.php`
- `app/Policies/ApplicationPolicy.php`, `app/Policies/ApplicationDocumentPolicy.php`
- `app/Http/Requests/Admin/ServiceTypes/StoreServiceTypeRequest.php`, `UpdateServiceTypeRequest.php`
- `app/Actions/ServiceType/CreateServiceType.php`, `UpdateServiceType.php`
- `resources/views/admin/service-types/create.blade.php`, `edit.blade.php`
- `resources/js/citizen/utils/schema.js`, `components/DocumentUploader.jsx`,
  `pages/ApplyPage.jsx`, `pages/MyApplicationDetailPage.jsx`
- Tests: `ApplicationDocumentTest`, `ApplicationSubmissionTest`, `CitizenSpaTest`, Admin service-type test

## Ghi chú

- Data migration backfill chạy trên **cả dev DB (Supabase) và test DB** (idempotent, chạy lại an toàn).
- `document_kind` mặc định vẫn `submission`; `supplement`/`result` dành cho feature xử lý hồ sơ sau.
- Không tạo UI Admin xử lý hồ sơ trong increment này (đã chốt).
- Giữ nguyên mọi envelope `{success, message, data}` và resource hiện có để không vỡ contract cũ.
