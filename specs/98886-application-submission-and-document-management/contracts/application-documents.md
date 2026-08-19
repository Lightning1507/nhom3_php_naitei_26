# API Contract: Application Documents (Citizen, `/api/v1`)

Khu vực Citizen dùng Sanctum cookie/session auth (CSRF + `withCredentials`).
Mọi endpoint dưới đây nằm trong group `auth:sanctum`.

Envelope chung: `{ success, message, data }` (thành công) /
`{ success, message, errors }` (lỗi).

## Upload tài liệu

`POST /api/v1/applications/{application}/documents`

Multipart/form-data:

| Field | Bắt buộc | Mô tả |
|---|---|---|
| `document` | Có | File PDF/JPEG/JPG/PNG, tối đa 10 MB |
| `requirement_code` | Có khi service có ≥ 1 requirement | Mã requirement của service mà file chứng minh; phải thuộc service (khác → 422) |

Authorization: chỉ citizen sở hữu hồ sơ (khác → 403) và hồ sơ ở trạng thái `received` hoặc
`supplement_required` (các trạng thái khác → 403). Khi `supplement_required`, tài liệu được ghi
loại `supplement`; khi `received`, loại `submission`.

**201 Created** — đã upload và lưu metadata:

```json
{
  "success": true,
  "message": "Document uploaded successfully",
  "data": {
    "id": 1,
    "application_id": 10,
    "document_kind": "submission",
    "requirement_code": "citizen-id-copy",
    "requirement_label": "Căn cước công dân (bản sao)",
    "original_name": "cmnd.pdf",
    "mime_type": "application/pdf",
    "file_size": 102400,
    "created_at": "2026-08-18T00:00:00+00:00"
  }
}
```

**422 Unprocessable Entity** — sai định dạng (kể cả không khớp `type` của slot), quá dung lượng,
thiếu file, thiếu hoặc sai `requirement_code`:

```json
{
  "success": false,
  "message": "The document field must be a file of type: pdf, jpg, jpeg, png. (and 1 more error)",
  "errors": { "document": ["..."] }
}
```

**403 Forbidden** — không phải chủ hồ sơ, hoặc hồ sơ không ở trạng thái `received`/`supplement_required`.

## Download tài liệu

`GET /api/v1/applications/{application}/documents/{document}`

Authorization: chủ hồ sơ hoặc Staff/Manager/Super Admin (khác → 403).
`document` phải thuộc `application` trong URL (không → 404, do `scopeBindings`).

**200 OK** — stream file về với header:

- `Content-Type`: giá trị `mime_type` của tài liệu.
- `Content-Disposition`: attachment với `original_name`.

**401 Unauthorized** — chưa đăng nhập.
**403 Forbidden** — không có quyền tải (citizen không sở hữu hồ sơ).
**404 Not Found** — tài liệu không tồn tại / đã xóa mềm / không thuộc hồ sơ trong URL /
file nhị phân bị mất trên disk.

## Xóa tài liệu (soft delete)

`DELETE /api/v1/applications/{application}/documents/{document}`

Authorization: chỉ chủ hồ sơ, hồ sơ ở trạng thái `received` **và chưa được gán staff**
(`assigned_staff_id` null).

**200 OK**:

```json
{ "success": true, "message": "Document deleted successfully" }
```

**403 Forbidden** — không phải chủ hồ sơ, hoặc hồ sơ đã chuyển sang `processing` trở lên, hoặc
đã được gán staff.
**404 Not Found** — tài liệu không tồn tại / không thuộc hồ sơ trong URL.