# Quickstart: F05 - Application Processing Workflow

## Prerequisites

- PHP 8.3+ đang active trong shell (đang chạy PHP 8.5).
- Composer và NPM dependencies đã được cài.
- PostgreSQL testing database có thể truy cập (Docker `psm-pg`).
- `.env` trỏ tới database phát triển an toàn; `.env.testing` đã cấu hình DB test
  `public_service_management_testing`.
- Đã đọc [spec.md](./spec.md), [plan.md](./plan.md) và [data-model.md](./data-model.md).
- F04 đã merge (upload/download/delete tài liệu theo requirement, lock theo status).

## Setup

```bash
docker ps --filter name=psm-pg          # đảm bảo Postgres test đang chạy
php artisan config:clear
php artisan migrate --env=testing       # migrate DB test (F05 không có migration mới)
```

## Chạy test của feature

```bash
php artisan test --env=testing --filter ApplicationProcessingTest      # T1 transitions & API
php artisan test --env=testing --filter ApplicationWorkspaceViewTest   # T2 Blade views
php artisan test --env=testing --filter ApplicationAuthorizationTest   # T3 policies
php artisan test --env=testing          # full suite
```

## Kiểm tra chất lượng

```bash
composer run lint     # Pint --test
npm run lint
npm run build
```

## Smoke test thủ công (tùy chọn)

1. Migrate DB phát triển và seed: department + leader (Manager) + staff thuộc department + service
   (`responsible_department_id` = department, `processing_time_days` > 0) + citizen đã nộp hồ sơ.
2. Login Staff → `GET /admin/applications` → thấy nhóm hồ sơ chưa gán thuộc phòng ban mình (claim).
3. `POST /admin/applications/{id}/claim` → hồ sơ vào danh sách "của tôi".
4. `POST /admin/applications/{id}/start-processing` → status `processing`, `processing_started_at` set.
5. `POST /admin/applications/{id}/request-supplement` (kèm `note`) → status `supplement_required`.
6. Login Citizen → `GET /api/v1/applications/{id}` → thấy `supplement_note` + `missing_required_documents`;
   upload tài liệu bổ sung (đã upload → 422).
7. Login Staff → `POST /admin/applications/{id}/resume` → status `processing`; thấy danh sách thiếu.
8. `POST /admin/applications/{id}/result-documents` (khi `processing`) → doc `document_kind=result`;
   `POST /admin/applications/{id}/approve` (kèm `result_note`) → status `approved`, `completed_at` set.
9. Login Citizen → detail: thấy timeline + kết quả; tải result document qua
   `GET /api/v1/applications/{id}/documents/{doc}`.
10. Tạo hồ sơ `processing`, thử `POST /admin/applications/{id}/approve` bằng staff khác phòng ban → 403;
    thử chuyển `rejected → processing` → 409.

## Ghi chú

- Không commit `.env`, secrets, `.agent/`, `AGENTS.md`, `docs/workflow.md`.
- Mọi transition ghi history append-only trong transaction (row-lock); assignment append-only.