# API Contract: Application Submission (Citizen, `/api/v1`)

Khu vực Citizen dùng Sanctum cookie/session auth (CSRF + `withCredentials`).
Mọi endpoint dưới đây nằm trong group `auth:sanctum`.

Envelope chung: `{ success, message, data }` (thành công) /
`{ success, message, errors }` (lỗi).

## Nộp hồ sơ

`POST /api/v1/applications`

Content-Type: `application/json`.

| Field | Bắt buộc | Mô tả |
|---|---|---|
| `service_type_id` | Có | ID dịch vụ công đang hoạt động (không soft-deleted) |
| `form_data` | Có (theo `form_schema` của dịch vụ) | Object chứa các trường theo `form_schema`; trường `required` phải có mặt |

Authorization: chỉ Citizen (Staff/Manager/Super Admin → 403).

**201 Created** — hồ sơ được tạo:

```json
{
  "success": true,
  "message": "Application submitted successfully",
  "data": {
    "id": 1,
    "application_code": "HS-20260818-00001",
    "status": "received",
    "service_type": { "id": 5, "name": "..." },
    "form_data": { "full_name": "Nguyen Van A" },
    "submitted_at": "2026-08-18T00:00:00+00:00"
  }
}
```

**422 Unprocessable Entity** — `service_type_id` không tồn tại / không hoạt động /
thiếu trường bắt buộc trong `form_data`:

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": { "form_data.full_name": ["The form data full name field is required."] }
}
```

**403 Forbidden** — tài khoản không phải Citizen.

## Danh sách hồ sơ của tôi

`GET /api/v1/applications`

Authorization: Citizen đã đăng nhập. Chỉ trả hồ sơ của chính người gọi, mới nhất trước.

Query params: `page`, `per_page` (mặc định 15, tối đa 100).

**200 OK**:

```json
{
  "success": true,
  "message": "Applications retrieved successfully",
  "data": { "data": [ { "id": 1, "application_code": "HS-20260818-00001", "status": "received" } ], "links": {}, "meta": {} }
}
```

## Chi tiết hồ sơ

`GET /api/v1/applications/{application}`

Authorization: chỉ chủ hồ sơ (khác → 403).

**200 OK**: như response `store`, kèm `service_type`.

**403 Forbidden** — hồ sơ của người khác.
**404 Not Found** — hồ sơ không tồn tại.