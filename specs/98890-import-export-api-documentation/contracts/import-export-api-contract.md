# API & Interface Contracts: F08 - Import/Export & API Documentation

## 1. Import CSV Endpoints (Admin Only - Blade SSR / Web API)

### POST `/admin/users/import/citizens`
* **Auth**: Session (`auth`, `internal` - Admin/Super Admin)
* **Headers**: `Content-Type: multipart/form-data`
* **Request Payload**:
  * `csv_file`: File (Required, `.csv`, max 10MB)
* **Response (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Đã nhập thành công 8/10 tài khoản công dân.",
    "data": {
      "total_rows": 10,
      "success_count": 8,
      "failure_count": 2,
      "errors": [
        {
          "line_number": 4,
          "field": "citizen_id",
          "message": "Mã công dân 001098123456 đã tồn tại trên hệ thống."
        }
      ]
    }
  }
  ```

---

### POST `/admin/users/import/staff`
* **Auth**: Session (`auth`, `internal` - Admin/Super Admin)
* **Headers**: `Content-Type: multipart/form-data`
* **Request Payload**:
  * `csv_file`: File (Required, `.csv`, max 10MB)
* **Response (200 OK)**: Phản hồi chuẩn `success`, `message`, `data` tương tự endpoint Citizen Import.

---

## 2. Export CSV Endpoints (Admin Only)

### GET `/admin/export/{resource}`
* **Auth**: Session (`auth`, `internal` - Admin/Super Admin)
* **Path Parameters**:
  * `resource`: Enum (`citizens`, `applications`, `services`, `departments`, `staff`)
* **Query Parameters**:
  * `search`: String (Tùy chọn)
  * `status`: String (Tùy chọn)
  * `department_id`: Integer (Tùy chọn)
  * `date_from`, `date_to`: Date Y-m-d (Tùy chọn)
* **Response (200 OK)**: `StreamedResponse` (Content-Type: `text/csv; charset=UTF-8`, Content-Disposition: `attachment; filename="{resource}-export-{YmdHis}.csv"`)

---

## 3. OpenAPI Documentation Endpoint

### GET `/docs/api`
* **Auth**: Guest / Authorized Developers
* **Response (200 OK)**: Trang Swagger UI / Scramble OpenAPI Viewer rendering toàn bộ danh sách routes thuộc `/api/v1/`.
