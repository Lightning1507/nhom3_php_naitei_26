# Phase 1 Data Model: F08 - Import/Export & API Documentation

## Entities & Data Structures (Cross-referenced with docs/database-design.md & docs/technology-stack.md)

### 1. CSV Citizen Import Schema
Định dạng các cột bắt buộc trong tệp CSV nạp tài khoản Công dân (khớp với bảng `users` trong [database-design.md](file:///c:/Users/sf/Documents/GitHub/nhom3_php_naitei_26/docs/database-design.md)):

| Tên Cột CSV | Cột CSDL (`users`) | Loại Dữ Liệu | Bắt Buộc | Quy Tắc Kiểm Tra (Validation Rules) | Ví Dụ Dữ Liệu |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `name` | `name` | String | Có | `required\|string\|max:255` | Nguyễn Văn A |
| `email` | `email` | String | Có | `required\|email\|unique:users,email` | nguyenvana@gmail.com |
| `citizen_id` | `citizen_id` | String | Có | `required\|digits:12\|unique:users,citizen_id` | 001098123456 |
| `phone` | `phone` | String | Không | `nullable\|regex:/^[0-9]{10,11}$/` | 0987654321 |
| `address` | `address` | String | Không | `nullable\|string\|max:500` | 123 Đường Lê Lợi, Hà Nội |
| `date_of_birth` | `date_of_birth` | Date | Không | `nullable\|date_format:Y-m-d` | 1990-05-15 |

---

### 2. CSV Staff Import Schema
Định dạng các cột bắt buộc trong tệp CSV nạp tài khoản Cán bộ (khớp với bảng `users` & `department_user` trong [database-design.md](file:///c:/Users/sf/Documents/GitHub/nhom3_php_naitei_26/docs/database-design.md)):

| Tên Cột CSV | Cột CSDL | Loại Dữ Liệu | Bắt Buộc | Quy Tắc Kiểm Tra (Validation Rules) | Ví Dụ Dữ Liệu |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `name` | `users.name` | String | Có | `required\|string\|max:255` | Trần Thị B |
| `email` | `users.email` | String | Có | `required\|email\|unique:users,email` | tranthib@gov.vn |
| `department_id` | `department_user.department_id` | Integer | Có | `required\|integer\|exists:departments,id` | 2 |
| `role` | `users.role` | String | Có | `required\|in:staff,manager` | staff |
| `phone` | `users.phone` | String | Không | `nullable\|regex:/^[0-9]{10,11}$/` | 0912345678 |

---

### 3. ImportJobReport Value Object
Cấu trúc dữ liệu phản hồi sau khi xử lý xong một lượt Import CSV:

```json
{
  "success": true,
  "message": "Đã nhập thành công 8/10 tài khoản.",
  "data": {
    "total_rows": 10,
    "success_count": 8,
    "failure_count": 2,
    "errors": [
      {
        "line_number": 4,
        "field": "citizen_id",
        "message": "Mã công dân 001098123456 đã tồn tại trên hệ thống.",
        "raw_data": {
          "name": "Lê Văn C",
          "email": "levanc@gmail.com",
          "citizen_id": "001098123456"
        }
      },
      {
        "line_number": 7,
        "field": "email",
        "message": "Định dạng email không hợp lệ.",
        "raw_data": {
          "name": "Phạm Văn D",
          "email": "invalid-email-format",
          "citizen_id": "001098999888"
        }
      }
    ]
  }
}
```

---

### 4. Standardized API Response Envelopes (Chuẩn hóa theo Mục 4 - docs/technology-stack.md)

#### Chuẩn Success Response Envelope (HTTP 200 / 201)
```json
{
  "success": true,
  "message": "Thao tác thành công",
  "data": {
    "id": 1,
    "application_code": "HS-20260820-00001",
    "status": "received",
    "submitted_at": "2026-08-20T15:00:00Z"
  }
}
```

#### Chuẩn Paginated Response Envelope (HTTP 200)
```json
{
  "success": true,
  "message": "Lấy danh sách thành công",
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 75
  },
  "links": {
    "first": ".../api/v1/applications?page=1",
    "last": ".../api/v1/applications?page=5",
    "prev": null,
    "next": ".../api/v1/applications?page=2"
  }
}
```

#### Chuẩn Error Response Envelope (HTTP 422 / 401 / 403 / 500)
```json
{
  "success": false,
  "message": "Dữ liệu không hợp lệ.",
  "errors": {
    "email": [
      "Trường email là bắt buộc."
    ]
  }
}
```
