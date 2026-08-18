# Implementation Plan: F03 - Department & Staff Management

**Branch**: `98885-department-staff-management` | **Date**: 2026-08-17 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `specs/98885-department-staff-management/spec.md`

## Summary

Triển khai khu vực quản lý cơ cấu tổ chức nội bộ bằng Laravel Blade SSR: Super Admin quản lý vòng đời Department, lãnh đạo và thành viên; Manager chỉ xem các Department mình lãnh đạo và quản lý Staff trong đúng phạm vi. Giải pháp tái sử dụng schema `departments`, `department_user`, `users`, `service_types` và `activity_logs`, bổ sung một migration tăng cường canonical code, optimistic concurrency và index; mọi thay đổi cơ cấu quan trọng đi qua Policy, Form Request, focused Action, transaction và audit log. Dịch vụ chỉ được hiển thị read-only; F03 không tạo tài khoản, đổi role hay xử lý Application.

## Technical Context

**Language/Version**: PHP 8.5, Laravel 13; JavaScript với Alpine.js 3.16; Blade, HTML và Tailwind CSS 4

**Primary Dependencies**: Laravel session authentication, Eloquent ORM, Blade SSR, Alpine.js, Tailwind CSS, Vite; không thêm package mới

**Storage**: PostgreSQL (Supabase cho môi trường shared development/demo), sử dụng các bảng hiện có `departments`, `department_user`, `users`, `service_types`, `application_assignments`, `activity_logs` và một migration tăng cường cho F03

**Testing**: PHPUnit 12.5 và Laravel Feature Tests dưới `tests/Feature/Admin/Departments`; kiểm tra hiệu năng SC-004 trên PostgreSQL bằng fixture riêng, không dùng assertion wall-clock không ổn định trong suite thường

**Target Platform**: Ứng dụng web Laravel; khu vực nội bộ server-rendered dưới `/admin/...`, desktop-first và hỗ trợ viewport mobile

**Project Type**: Một ứng dụng Laravel hybrid; F03 chỉ mở rộng Admin Blade SSR và backend dùng chung

**Performance Goals**: Danh sách, tìm kiếm và chi tiết Department phản hồi trong không quá 2 giây với ít nhất 1.000 Department và 10.000 membership; không có N+1 query; mọi danh sách và candidate lookup đều phân trang hoặc giới hạn kết quả

**Constraints**: Route -> Controller -> Form Request -> Policy -> focused Action -> Eloquent; deny-by-default; không repository/ACL package/Admin SPA; code Department duy nhất không phân biệt hoa thường; transfer/leader/archive/audit phải nguyên tử; soft delete phải bảo toàn service, assignment và membership hiện có; UI F03 không được biến thành user management, service CRUD hoặc application workflow

**Scale/Scope**: Department list/search/filter/pagination, create/edit/detail/archive, leader selection, member add/remove/transfer, candidate lookup có giới hạn, services read-only, bốn role nhưng chỉ Super Admin và scoped Manager có quyền F03

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Laravel-First Backend & Simplicity**: PASS. Thiết kế dùng controller mỏng, Form Request, `DepartmentPolicy`, focused Actions và Eloquent; không thêm repository, DDD, microservice hoặc package phân quyền.
- **II. Feature-Driven Development**: PASS. Phạm vi bám `spec.md`, `design-context.md` và các artifact trong thư mục F03; tasks/implementation sẽ thực hiện ở pha sau.
- **III. Application-Centric Domain**: PASS. F03 không đổi trạng thái hay workflow Application; `application_assignments` chỉ được bảo toàn và dùng tham chiếu lịch sử.
- **IV. Authorization & Data Protection**: PASS. Session `auth` + `internal` là biên đầu tiên; policy và scoped query thực thi quyền Super Admin/Manager phía server, Staff/Citizen bị từ chối và tài nguyên ngoài phạm vi được che như không tồn tại.
- **V. Database Integrity & Auditability**: PASS. Unique/check constraint, index, row lock, `lock_version`, transaction, soft delete và `activity_logs` bảo vệ tính nhất quán, concurrency và khả năng truy vết.
- **VI. Citizen React SPA & Admin Blade SSR**: PASS. Toàn bộ page F03 là Blade dưới `/admin`; Alpine chỉ progressive-enhance dialog/combobox/pending state; không thêm Citizen API hay Admin React.
- **VII. Quality & Definition of Done**: PASS. Thiết kế yêu cầu tests cho authorization, integrity, transaction rollback, audit, rendering; cuối implementation phải chạy PHPUnit, lint và build hiện có.

## Project Structure

### Documentation (this feature)

```text
specs/98885-department-staff-management/
|-- plan.md
|-- research.md
|-- data-model.md
|-- quickstart.md
|-- design-context.md
|-- contracts/
|   `-- admin-departments.md
`-- tasks.md                  # Phase 2 output; không tạo trong speckit-plan
```

### Source Code (repository root)

```text
app/
|-- Actions/Department/
|   |-- CreateDepartment.php
|   |-- UpdateDepartment.php
|   |-- ArchiveDepartment.php
|   |-- ChangeDepartmentLeader.php
|   |-- AddDepartmentMember.php
|   |-- RemoveDepartmentMember.php
|   `-- TransferDepartmentMember.php
|-- Http/Controllers/Admin/Departments/
|-- Http/Requests/Admin/Departments/
|-- Models/
|   |-- Department.php
|   |-- User.php
|   `-- ActivityLog.php
|-- Policies/DepartmentPolicy.php
`-- Providers/AppServiceProvider.php

database/
|-- factories/DepartmentFactory.php
|-- migrations/               # migration tăng cường integrity/index F03
`-- seeders/DatabaseSeeder.php

resources/
|-- css/app.css                # admin-specific compact variants; không đổi Citizen sizing
|-- js/admin/app.js            # Alpine progressive interactions
`-- views/admin/
    |-- components/
    |-- departments/
    |   |-- index.blade.php
    |   |-- create.blade.php
    |   |-- edit.blade.php
    |   |-- show.blade.php
    |   `-- partials/
    `-- layouts/app.blade.php

routes/web.php

tests/Feature/Admin/Departments/
|-- DepartmentAuthorizationTest.php
|-- DepartmentManagementTest.php
|-- DepartmentMembershipTest.php
|-- DepartmentTransferTest.php
|-- DepartmentArchiveTest.php
|-- DepartmentQueryTest.php
`-- DepartmentAuditTest.php
```

**Structure Decision**: Giữ một Laravel application hiện hữu. Page và form Admin ở Blade; controller nhận HTTP concern, policy quyết định quyền/phạm vi, focused Action sở hữu transaction và invariant, Eloquent làm persistence. Candidate search là endpoint nội bộ có session boundary phục vụ progressive combobox, không phải Citizen API và không biến Admin thành SPA.

## Phase 0: Research

Các quyết định kỹ thuật và alternative đã được chốt tại [research.md](./research.md). Không còn vấn đề kỹ thuật chưa được giải quyết.

## Phase 1: Design

- Entity, validation, relationship, state transition và concurrency: [data-model.md](./data-model.md)
- Contract cho Admin Blade routes, forms, candidate lookup và authorization: [contracts/admin-departments.md](./contracts/admin-departments.md)
- Hướng dẫn chạy và kiểm chứng end-to-end: [quickstart.md](./quickstart.md)
- Ngữ cảnh UI/Figma đầu vào: [design-context.md](./design-context.md)

## Post-Design Constitution Check

- **Ranh giới kiến trúc**: PASS. Contract chỉ dùng Admin Blade SSR và các response candidate nhỏ để progressive-enhance combobox; Citizen React/API không thay đổi.
- **Authorization**: PASS. Mọi collection/stats/candidate query scope trước filter; mọi resource mutation authorize bằng policy và revalidate invariant trong transaction.
- **Data integrity**: PASS. Canonical code + database uniqueness xử lý race; unique pivot ngăn member trùng; leader auto-membership, remove-leader guard và transfer all-or-nothing được mô hình hóa rõ.
- **History và audit**: PASS. Archive không detach quan hệ; soft-deleted leader/member vẫn có đường tra cứu có quyền; bảy mutation quan trọng ghi audit cùng transaction.
- **Application lifecycle**: PASS. Không có route/action nào assign Application hoặc đổi status; service/application data chỉ read-only hoặc được giữ nguyên.
- **Quality gate**: PASS. Quickstart bao phủ role boundary, concurrent/stale write, transaction rollback, archive preservation, responsive/accessibility và các lệnh test/lint/build.

## Complexity Tracking

Không có vi phạm constitution cần biện minh. `lock_version` và các focused Action là độ phức tạp trực tiếp phục vụ yêu cầu concurrent update, invariant nhiều bảng và audit nguyên tử; chúng không tạo thêm architectural layer tổng quát.
