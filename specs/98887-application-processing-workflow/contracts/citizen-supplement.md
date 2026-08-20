# Contract: Citizen Supplement & Result View (React SPA, `/api/v1`)

Citizen dùng Sanctum cookie/session auth (CSRF + `withCredentials`, axios client
`resources/js/citizen/api/client.js`). Envelope: `{ success, message, data }` / `{ success, message, errors }`.

F05 **không thêm endpoint mới** cho citizen — tái sử dụng endpoint tài liệu của F04 và **mở rộng**
`ApplicationResource` thêm trường timeline/result.

## 1. Upload tài liệu bổ sung (tái sử dụng F04)

`POST /api/v1/applications/{application}/documents` — multipart:

| Field | Bắt buộc | Mô tả |
|---|---|---|
| `document` | Có | PDF/JPEG/JPG/PNG ≤ 10 MB |
| `requirement_code` | Có khi service có ≥ 1 requirement | phải thuộc service (khác → 422) |

Authorization: chủ hồ sơ (403 nếu khác) + status `received` hoặc `supplement_required` (403 khác).
Khi `supplement_required` → `document_kind = supplement`. **201 Created** trả document (như contract
F04). `requirement_code` phải là slot *bắt buộc* đang thiếu khi bổ sung (đã upload → 422). Chi tiết
lỗi/403/404 giữ nguyên như contract `application-documents.md` của F04.

## 2. Download tài liệu (tái sử dụng F04)

`GET /api/v1/applications/{application}/documents/{document}` — chủ hồ sơ hoặc nội bộ. Dùng cho cả
tài liệu kết quả (`document_kind = result`) staff gắn khi duyệt.

## 3. Chi tiết hồ sơ — mở rộng `ApplicationResource`

`GET /api/v1/applications/{application}` (route có sẵn, `auth:sanctum`, owner-only) — **thêm**:

```json
{
  "id": 1,
  "application_code": "APP-2026-0001",
  "service_type": { "id": 1, "name": "…", "code": "…", "document_requirements": [] },
  "status": "supplement_required",
  "form_data": {},
  "submitted_at": "2026-08-18T00:00:00+00:00",
  "created_at": "2026-08-18T00:00:00+00:00",
  "missing_required_documents": [{ "code": "citizen-id-copy", "label": "…" }],
  "documents": [],
  "processing_started_at": "2026-08-19T00:00:00+00:00",
  "completed_at": null,
  "result_note": null,
  "rejection_reason": null,
  "assigned_staff": { "id": 5, "name": "…" },
  "supplement_note": "Cần bổ sung sổ hộ khẩu bản sao.",
  "timeline": [
    { "from_status": null, "to_status": "received",     "changed_by_name": "…", "note": null,         "created_at": "2026-08-18T00:00:00+00:00" },
    { "from_status": "received", "to_status": "processing", "changed_by_name": "…", "note": null, "created_at": "2026-08-19T00:00:00+00:00" },
    { "from_status": "processing", "to_status": "supplement_required", "changed_by_name": "…", "note": "Cần bổ sung…", "created_at": "2026-08-19T00:10:00+00:00" }
  ]
}
```

**Trường mới (F05):**

| Field | Nguồn | Mô tả |
|---|---|---|
| `processing_started_at` | `applications` | ISO hoặc null |
| `completed_at` | `applications` | ISO hoặc null |
| `result_note` | `applications` | chỉ hiển thị khi `approved` |
| `rejection_reason` | `applications` | chỉ hiển thị khi `rejected` |
| `assigned_staff` | `assignedStaff` (chỉ `id`, `name`) | null nếu chưa gán |
| `supplement_note` | `status_histories` mới nhất có `to_status = supplement_required` | null nếu không có |
| `timeline` | `status_histories` ASC (`created_at`, `id`) | mọi chuyển trạng thái |

**Quyền hiển thị**: `result_note`/`rejection_reason`/`timeline`/`assigned_staff` chỉ trả cho **chủ
hồ sơ** hoặc **nội bộ**; trường này trả `null` cho người ngoài (không rò rỉ). Thêm field này không
đổi các field cũ — SPA hiện tại (`MyApplicationDetailPage.jsx`, `StatusBadge.jsx`, `api/applications.js`)
không hỏng.

## 4. UI Citizen (SPA, trong #99478)

- `MyApplicationDetailPage.jsx`: hiển thị timeline, trạng thái, note bổ sung (banner đỏ khi
  `supplement_required`), `missing_required_documents` làm slot upload (reuse `DocumentUploader.jsx`),
  khối kết quả (result_note + tải result document) khi `approved`, lý do từ chối khi `rejected`.
- Không cần thay đổi `api/applications.js` về hình thức gọi (vẫn `GET`/`POST` cùng endpoint).