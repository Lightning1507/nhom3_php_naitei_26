# Data Model: F04 - Application Submission & Document Management

## Tổng quan

Feature dùng các bảng `applications`, `application_status_histories`,
`application_code_sequences` và `application_documents` (đã có migration từ F00/F04).
File nhị phân của tài liệu nằm ở disk `local` (private), không nằm trong database.
Bảng `application_code_sequences` là migration mới của T1 để chống trùng mã khi nộp đồng thời.

## Application

Bản ghi hồ sơ dịch vụ công do citizen tạo.

**Backing table**: `applications`

### Fields

| Field | Type | Rule |
|---|---|---|
| `id` | bigint | Primary key |
| `application_code` | string | Bắt buộc; unique |
| `citizen_id` | bigint FK -> `users.id` | Bắt buộc; `restrictOnDelete`; index |
| `service_type_id` | bigint FK -> `service_types.id` | Bắt buộc; `restrictOnDelete`; index |
| `assigned_staff_id` | bigint FK -> `users.id` nullable | `nullOnDelete` |
| `status` | string | Bắt buộc; giá trị từ enum `ApplicationStatus`; mặc định `received`; index |
| `form_data` | json nullable | Dữ liệu biểu mẫu đã validate |
| `submitted_at` | timestamp nullable | Thời điểm nộp |
| `processing_started_at` | timestamp nullable | Thời điểm bắt đầu xử lý |
| `completed_at` | timestamp nullable | Thời điểm hoàn thành |
| `result_note` / `rejection_reason` | text nullable | Kết quả xử lý |
| `created_at` / `updated_at` | timestamp | Mốc thời gian |
| `deleted_at` | timestamp nullable | `null` = active; có giá trị = soft deleted |

### Quan hệ

- `Application.belongsTo(User, citizen_id)` — chủ hồ sơ.
- `Application.belongsTo(ServiceType)` — dịch vụ đăng ký.
- `Application.hasMany(ApplicationDocument, application_id)` — danh sách tài liệu.
- `Application.hasMany(ApplicationStatusHistory)` — lịch sử chuyển trạng thái.

### Quy tắc nghiệp vụ

- `application_code` duy nhất, định dạng `HS-YYYYMMDD-xxxxx`.
- Citizen chỉ truy cập hồ sơ của chính mình (Policy).
- Trạng thái chuyển qua `ApplicationStatusHistory` để phục vụ kiểm toán.

## ApplicationCodeSequence

Bảng đếm số thứ tự mã hồ sơ theo ngày, đảm bảo mã không trùng khi nộp đồng thời.

**Backing table**: `application_code_sequences`

### Fields

| Field | Type | Rule |
|---|---|---|
| `id` | bigint | Primary key |
| `sequence_date` | date | Bắt buộc; unique |
| `last_sequence` | bigint | Bắt buộc; số thứ tự cuối cùng của ngày |

### Quy tắc nghiệp vụ

- Mỗi ngày một dòng; khi tạo mã, `lockForUpdate` dòng theo ngày rồi tăng `last_sequence`.

## ApplicationStatusHistory

Bản ghi lịch sử chuyển trạng thái của hồ sơ.

**Backing table**: `application_status_histories`

### Fields

| Field | Type | Rule |
|---|---|---|
| `id` | bigint | Primary key |
| `application_id` | bigint FK -> `applications.id` | Bắt buộc; index |
| `from_status` | string nullable | Trạng thái trước; `null` = khởi tạo |
| `to_status` | string | Trạng thái sau |
| `changed_by` | bigint FK -> `users.id` | Người thực hiện |
| `note` | text nullable | Ghi chú |
| `created_at` | timestamp | Thời điểm |

## ApplicationDocument

Bản ghi metadata của một tài liệu đính kèm một hồ sơ.

**Backing table**: `application_documents`

### Fields

| Field | Type | Rule |
|---|---|---|
| `id` | bigint | Primary key |
| `application_id` | bigint FK -> `applications.id` | Bắt buộc; `restrictOnDelete`; index |
| `uploaded_by` | bigint FK -> `users.id` | Bắt buộc; `restrictOnDelete`; index |
| `document_kind` | string | Bắt buộc; giá trị từ enum `DocumentKind` (`submission`) |
| `original_name` | string | Tên file gốc do client gửi |
| `disk` | string | Disk lưu file; luôn `local` trong feature này |
| `path` | text | Đường dẫn tương đối trong disk (vd `applications/{id}/...`) |
| `mime_type` | string nullable | MIME thực tế của file |
| `file_size` | unsignedBigInteger nullable | Dung lượng file (bytes) |
| `created_at` / `updated_at` | timestamp | Mốc thời gian |
| `deleted_at` | timestamp nullable | `null` = active; có giá trị = soft deleted |

### Quan hệ

- `ApplicationDocument.belongsTo(Application)` qua `application_id`.
- `ApplicationDocument.belongsTo(User, uploaded_by)` — người upload.
- `Application.hasMany(ApplicationDocument, application_id)` — danh sách tài liệu của hồ sơ.

### Quy tắc nghiệp vụ

- `document_kind` = `submission` cho tài liệu đính kèm hồ sơ (các giá trị `supplement`/`result`
  dành cho feature sau).
- Xóa tài liệu là soft delete (`deleted_at` được gán); bản ghi được giữ để phục vụ kiểm toán.
- `mime_type` và `file_size` phải khớp file thực tế được lưu (ghi từ `UploadedFile` đã validate).
- Quyền truy cập không nằm trong data model — được xác định bằng Policy theo chủ sở hữu
  (`applications.citizen_id`) và vai trò người dùng.

## ServiceType (tham chiếu)

Dịch vụ công trong catalog mà citizen đăng ký. Chỉ chấp nhận khi `is_active` và không soft-delete.
`form_schema` xác định danh sách trường bắt buộc và kiểu dữ liệu để validate `form_data`.