# Implementation Plan: F08 - Import/Export & API Documentation

**Branch**: `task/98887-import-export-api-documentation` | **Date**: 2026-08-20 | **Spec**: [spec.md](file:///c:/Users/sf/Documents/GitHub/nhom3_php_naitei_26/specs/98887-import-export-api-documentation/spec.md)

**Input**: Feature specification from `/specs/98887-import-export-api-documentation/spec.md` & Requirements from `docs/` directory ([business-analysis.md](file:///c:/Users/sf/Documents/GitHub/nhom3_php_naitei_26/docs/business-analysis.md), [database-design.md](file:///c:/Users/sf/Documents/GitHub/nhom3_php_naitei_26/docs/database-design.md), [technology-stack.md](file:///c:/Users/sf/Documents/GitHub/nhom3_php_naitei_26/docs/technology-stack.md), [ui-guidelines.md](file:///c:/Users/sf/Documents/GitHub/nhom3_php_naitei_26/docs/ui-guidelines.md))

## Summary

Triển khai tính năng **Import/Export dữ liệu CSV số lượng lớn** cho Admin và **Chuẩn hóa REST API kết hợp API Documentation tự động**. Sử dụng `fgetcsv`/`StreamedResponse` của Laravel/PHP native để xử lý CSV nhẹ nhàng, an toàn và bộ nhớ tối ưu. Tích hợp `dedoc/scramble` để tự động tạo tài liệu OpenAPI cho toàn bộ các endpoint thuộc `/api/v1/`.

Mọi thiết kế đều tuân thủ 100% các tài liệu hướng dẫn trong thư mục `docs/`:
- Phân tách Admin Blade SSR (`/admin/...`) và Citizen React SPA (`/api/v1/...`) theo `docs/business-analysis.md` & `docs/technology-stack.md`.
- Sử dụng chuẩn JSON Envelope (`success`, `message`, `data`/`errors`) theo Mục 4 của `docs/technology-stack.md`.
- Sử dụng đúng cấu trúc bảng và trường trong CSDL (`users.citizen_id`, `users.role`, `departments.id`, v.v.) theo `docs/database-design.md`.

## Technical Context

**Language/Version**: PHP 8.5, Laravel 13

**Primary Dependencies**: Native PHP CSV Stream (`fgetcsv`, `fputcsv`), `dedoc/scramble` (OpenAPI docs generator), Laravel Sanctum (API Auth)

**Storage**: PostgreSQL (`users`, `departments`, `applications`, `service_types` tables)

**Testing**: PHPUnit / Laravel Feature Tests (`tests/Feature/Admin`, `tests/Feature/Api`)

**Target Platform**: Web (Admin Blade SSR + Alpine.js + Citizen React SPA)

**Project Type**: Laravel Web Application + REST API

**Performance Goals**: Export/Import 10,000 bản ghi mà không vượt quá giới hạn bộ nhớ (Memory Limit < 128MB), thời gian phản hồi API < 200ms

**Constraints**: Tuân thủ nghiêm ngặt Hiến pháp dự án & Tài liệu kiến trúc trong `docs/` (Phân quyền Admin/Citizen, UTF-8 BOM cho CSV tiếng Việt)

**Scale/Scope**: 5 tài nguyên chính cần Export (Citizens, Applications, Services, Departments, Staff), 2 tài nguyên Import (Citizens, Staff)

## Constitution & Docs Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- [x] **I. Laravel-First Backend & Simplicity**: Sử dụng hàm PHP CSV stream native và Laravel Validator, không kéo package xử lý file nặng không cần thiết.
- [x] **II. Feature-Driven Development**: Quy trình Specify -> Plan -> Tasks -> Implement -> Test được thực thi đầy đủ.
- [x] **III. Application-Centric Domain**: Giữ nguyên trạng thái và quy trình xử lý của `Application`.
- [x] **IV. Authorization & Data Protection**: Phân định rõ Admin Blade routes (`/admin/...`) cho phép Import/Export; Citizen API (`/api/v1/...`) bảo mật bằng Sanctum và Policy.
- [x] **V. Database Integrity & Auditability**: Sử dụng Database Transactions cho luồng Import dữ liệu.
- [x] **VI. Citizen React SPA & Admin Blade SSR**: Admin SSR sử dụng Blade + Alpine.js; API tuân thủ cấu trúc JSON Envelope đồng nhất (`success`, `message`, `data`/`errors`).
- [x] **VII. Quality & Definition of Done**: Viết bổ sung các bài Feature Tests kiểm thử luồng Import/Export và API docs.
- [x] **Docs Alignment**:
  - `business-analysis.md`: Import/Export giao cho Super Admin / Admin tại khu vực Admin Site.
  - `database-design.md`: Khớp các tên cột `citizen_id`, `role`, `department_id`, `phone`, `email`.
  - `technology-stack.md`: Khớp chuẩn envelope `{"success": true, "message": "...", "data": {...}}`.
  - `ui-guidelines.md`: Tích hợp các nút Import/Export trên Admin Blade tables và Swagger UI tại `/docs/api`.

## Project Structure

### Documentation (this feature)

```text
specs/98887-import-export-api-documentation/
├── spec.md              # Feature specification
├── plan.md              # Implementation Plan (this file)
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 API/Interface contracts
└── tasks.md             # Phase 2 output ($speckit-tasks command)
```

### Source Code Structure

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── UserImportController.php
│   │   │   └── DataExportController.php
│   │   └── Api/V1/
│   ├── Requests/
│   │   ├── Admin/
│   │   │   └── CsvImportRequest.php
│   │   └── Api/V1/
│   └── Resources/
│       └── Api/V1/
├── Services/
│   ├── CsvImportService.php
│   └── CsvExportService.php

resources/
├── views/
│   └── admin/
│       ├── users/
│       │   └── import.blade.php
│       └── exports/

routes/
├── api.php             # /api/v1/... REST API endpoints
└── web.php             # /admin/... SSR routes & /docs/api

tests/
├── Feature/
│   ├── Admin/
│   │   └── AdminImportExportTest.php
│   └── Api/
│       └── ApiV1StandardizationTest.php
```

**Structure Decision**: Cấu trúc ứng dụng Laravel tiêu chuẩn với Controllers nằm đúng phân khu `Admin` (Blade SSR) và `Api/V1` (REST API). Xử lý Import/Export logic được đóng gói gọn trong `CsvImportService` và `CsvExportService`.

## Complexity Tracking

> Không có vi phạm Hiến pháp dự án hay tài liệu kiến trúc. Mọi thiết kế đều tuân thủ các quy tắc Laravel-First đơn giản và hiệu quả.
