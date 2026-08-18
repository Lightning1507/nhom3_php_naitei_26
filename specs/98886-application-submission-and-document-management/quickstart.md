# Quickstart: F04 - Application Submission & Document Management

## Prerequisites

- PHP 8.3+ đang active trong shell (đang chạy PHP 8.5).
- Composer và NPM dependencies đã được cài.
- PostgreSQL testing database có thể truy cập (Docker `psm-pg`).
- `.env` trỏ tới database phát triển an toàn; `.env.testing` đã cấu hình DB test
  `public_service_management_testing`.
- Đã đọc [spec.md](./spec.md), [plan.md](./plan.md) và [data-model.md](./data-model.md).

## Setup

```bash
docker ps --filter name=psm-pg          # đảm bảo Postgres test đang chạy
php artisan config:clear
php artisan migrate --env=testing       # migrate DB test
```

## Chạy test của feature

```bash
php artisan test --env=testing --filter ApplicationSubmissionTest
php artisan test --env=testing --filter ApplicationDocumentTest
php artisan test --env=testing          # full suite
```

## Kiểm tra chất lượng

```bash
composer run lint     # Pint --test
npm run lint
```

## Smoke test thủ công (tùy chọn)

1. Migrate DB phát triển và seed một citizen có dịch vụ hoạt động.
2. Login citizen lấy cookie/CSRF (khu vực `/api/v1/auth/login`).
3. `POST /api/v1/applications` với `service_type_id` + `form_data` hợp lệ → 201,
   nhận mã `HS-YYYYMMDD-xxxxx`.
4. `GET /api/v1/applications` → danh sách hồ sơ của chính mình.
5. `POST /api/v1/applications/{id}/documents` với file PDF hợp lệ → 201.
6. Thử file `.exe` hoặc file > 10 MB → 422.
7. `GET /api/v1/applications/{id}/documents/{doc}` → nhận file về với `original_name`.
8. `DELETE /api/v1/applications/{id}/documents/{doc}` khi hồ sơ `received` → 200; tải lại → 404.
9. Thử bằng citizen khác / staff (upload, download, delete) → 403.

## Ghi chú

- File lưu ở disk `local` (private), không có URL công khai; chỉ tải qua endpoint có authorization.
- Không commit `.env`, secrets.