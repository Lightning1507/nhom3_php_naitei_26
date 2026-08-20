# Contract: Admin Workflow (Blade SSR, `/admin`)

Admin dùng Laravel web session (`auth` + `internal` middleware). Mọi route dưới đây nằm trong
group `auth` + `internal`, prefix `admin`, dưới tên `admin.*`.

Mọi action là form POST thông thường (Blade + Alpine, không dùng JSON). Sau khi thành công redirect
về `admin.applications.show` với `->with('success', ...)` (lỗi: redirect back với `->withErrors` /
`->with('error', ...)`). Lỗi 403 → abort(403) trang lỗi.

## Danh sách & chi tiết

### `GET /admin/applications` — worklist / assignment board

Hiển thị theo vai trò người dùng (FR-024):

- **Staff**: danh sách hồ sơ **được gán cho chính mình** (`assigned_staff_id = me`), gồm cả hồ sơ
  chưa nhận (chưa `processing`) và đang xử lý; cộng nhóm "có thể claim" (hồ sơ `received`,
  `assigned_staff_id` null, thuộc phòng ban phụ trách dịch vụ, chưa quá hạn… không bắt buộc —
  spec cho phép claim hồ sơ chưa gán).
- **Manager / Super Admin**: Assignment Board — mọi hồ sơ thuộc phạm vi (Manager: dịch vụ của các
  phòng ban `leader_id = me`; Super Admin: tất cả), kèm 2 số liệu tổng: số hồ sơ **đang chờ xử lý**
  (`status IN received, processing, supplement_required`) và số hồ sơ **quá hạn**
  (`completed_at IS NULL AND submitted_at + processing_time_days < now()`).

Param lọc (query string, giữ qua redirect):
`status` (enum ApplicationStatus), `assigned_staff_id`, `overdue` (1/0), `q` (application_code).

### `GET /admin/applications/{application}` — chi tiết

Nội bộ có quyền xem (FR-005). Hiển thị: code, service, citizen, form_data, trạng thái, timeline
(status_histories), danh sách tài liệu (submission/supplement/result), staff đang xử lý, nút hành
động phù hợp trạng thái + quyền.

## Hành động workflow (mỗi hành động một route POST + FormRequest + Action)

### Gán / đổi staff

`POST /admin/applications/{application}/assign` — Manager/Super Admin gán hoặc **đổi** staff.

| Field | Bắt buộc | Mô tả |
|---|---|---|
| `staff_id` | Có | Active Staff thuộc phòng ban phụ trách dịch vụ (khác → 422). Có thể là chính staff đang giữ (no-op hợp lệ) |
| `note` | Không | ghi vào bản ghi assignment (lý do đổi) |

- Đổi staff: đóng assignment đang mở (`ended_at = now`), tạo assignment mới, cập nhật
  `assigned_staff_id` — cùng transaction. `lockForUpdate` trên hồ sơ.
- Không đổi trạng thái hồ sơ (kể cả khi `processing` — đổi người xử lý không reset `processing_started_at`).
- Hồ sơ đã hoàn tất (`approved`/`rejected`) → 409 (không gán lại).

### Claim

`POST /admin/applications/{application}/claim` — Staff tự nhận hồ sơ chưa gán.

- Chỉ khi `assigned_staff_id` null và status `received` (khác → 409). Staff phải thuộc phòng ban phụ
  trách dịch vụ (khác → 403).
- Tạo assignment (`assigned_by = me`, `department_id` = phòng ban phụ trách), set
  `assigned_staff_id = me`. Row-lock đảm bảo hai staff claim cùng lúc chỉ một người thắng.
- `staff_id` không cần gửi (lấy từ actor).

### Bắt đầu xử lý

`POST /admin/applications/{application}/start-processing` — Staff đang giữ hồ sơ.

- `received → processing`. Set `processing_started_at` nếu null. Ghi history.
- Hồ sơ chưa được gán cho actor → 403.

### Yêu cầu bổ sung tài liệu

`POST /admin/applications/{application}/request-supplement` — Staff đang giữ hồ sơ.

| Field | Bắt buộc | Mô tả |
|---|---|---|
| `note` | Có | nội dung yêu cầu bổ sung (ghi vào history + không ghi lên application) |

- `processing → supplement_required`. Citizen sẽ thấy note trong detail (contract citizen).

### Tiếp tục xử lý sau bổ sung

`POST /admin/applications/{application}/resume` — Staff đang giữ hồ sơ.

- `supplement_required → processing`. Không chặn thiếu tài liệu bắt buộc (soft validation — spec
  FR-014); hiển thị danh sách tài liệu còn thiếu trong chi tiết để staff cân nhắc.

### Duyệt

`POST /admin/applications/{application}/approve` — Staff đang giữ hồ sơ.

| Field | Bắt buộc | Mô tả |
|---|---|---|
| `result_note` | Không | ghi lên `applications.result_note` + history note |

- `processing → approved`; set `completed_at = now`. Kèm (tuỳ chọn, khác endpoint) upload tài liệu
  kết quả `document_kind = result`.

### Từ chối

`POST /admin/applications/{application}/reject` — Staff đang giữ hồ sơ.

| Field | Bắt buộc | Mô tả |
|---|---|---|
| `rejection_reason` | Có | ghi lên `applications.rejection_reason` + history note |

- `processing → rejected`; set `completed_at = now`. Không cho gắn tài liệu kết quả.

### Upload tài liệu kết quả

`POST /admin/applications/{application}/result-documents` — Staff đang giữ hồ sơ.

Multipart/form-data: `document` (file, như contract citizen upload) + `requirement_code` (tuỳ chọn,
không thuộc service thì 422). Chỉ khi status `processing` (khác → 409/403). Lưu
`document_kind = result`. Citizen tải qua endpoint download sẵn có (contract citizen).

## Transition map tóm tắt (bắt buộc, xem research)

```
received             → processing            (start-processing, claim + start có thể tách)
processing           → supplement_required   (request-supplement)
supplement_required  → processing            (resume)
processing           → approved              (approve)
processing           → rejected              (reject)
```

Mọi chuyển khác → 409 với message lỗi rõ ràng. `completed_at` chỉ set ở trạng thái cuối.
Mỗi chuyển trạng thái ghi đúng một dòng `application_status_histories` trong cùng transaction
(row-lock).