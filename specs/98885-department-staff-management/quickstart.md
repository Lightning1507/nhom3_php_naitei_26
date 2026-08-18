# Quickstart: F03 - Department & Staff Management

## Prerequisites

- PHP 8.5 đang active trong shell.
- Composer và NPM dependencies đã được cài.
- PostgreSQL testing/development database có thể truy cập.
- `.env` trỏ tới database phát triển an toàn, không phải production hoặc shared database chưa được nhóm cho phép thay đổi.
- Đã đọc [data-model.md](./data-model.md) và [Admin interface contract](./contracts/admin-departments.md).

## Setup

```powershell
composer install
npm install
php artisan config:clear
php artisan migrate:status
```

Migration F03 có preflight cho collision của Department code canonical. Chỉ chạy migrate/seed sau khi xác nhận database đích an toàn và không có collision cần xử lý thủ công:

```powershell
php artisan migrate
php artisan db:seed
```

Seed hiện có cung cấp:

- Super Admin: `admin@example.test` / `password`
- Manager: `manager@example.test` / `password`
- Staff: `staff1@example.test`, `staff2@example.test` / `password`
- Ba Department và các Service Type liên kết để kiểm tra read-only association

## Run The App

Chạy ở hai terminal:

```powershell
php artisan serve
```

```powershell
npm run dev
```

Mở `/admin/login`, đăng nhập bằng tài khoản nội bộ rồi vào `/admin/departments`.

## Validation Scenarios

### 1. Super Admin tạo và cập nhật Department

1. Đăng nhập bằng Super Admin.
2. Tạo Department với name hợp lệ, code `xd`, address tùy chọn và chưa cần leader.
3. Mở list/detail; xác nhận code hiển thị canonical `XD`, status active và trạng thái chưa có leader.
4. Sửa name/address rồi reload list/detail.
5. Thử tạo hoặc đổi Department khác sang `xD`.

**Expected**: create/update nhất quán; duplicate khác hoa/thường bị từ chối tại field `code`; không có record thay đổi một phần.

### 2. Validation và stale form

1. Thử name/code chỉ có whitespace, code chứa khoảng trắng nội bộ hoặc ký tự ngoài format, address dài hơn 1.000 ký tự.
2. Mở cùng Department ở hai browser/tab.
3. Lưu thay đổi ở tab A, sau đó submit form cũ ở tab B.

**Expected**: lỗi nằm sát field và giữ valid input; tab B nhận conflict yêu cầu reload, không ghi đè dữ liệu mới và không có audit giả.

### 3. Chọn và thay đổi leader

1. Chọn một Manager active làm leader.
2. Xác nhận Manager đồng thời xuất hiện đúng một lần trong member list.
3. Chọn lại cùng Manager, rồi đổi sang Manager khác hoặc unset leader.
4. Thử chọn Staff, Citizen, Super Admin, inactive hoặc soft-deleted User bằng request crafted.

**Expected**: chỉ active Manager hợp lệ được nhận; membership không trùng; unset không tự remove leader cũ; forged candidate bị server từ chối.

### 4. Thêm và gỡ member

1. Với Super Admin, thêm một Staff và một non-leader Manager active.
2. Thử thêm lại cùng User.
3. Gỡ Staff và xác nhận User account vẫn tồn tại.
4. Thử gỡ current leader trước khi change/unset leader.

**Expected**: mỗi User xuất hiện tối đa một lần; duplicate có thông báo rõ; remove chỉ xóa pivot; current leader không thể bị remove.

### 5. Manager scope và role boundary

1. Đăng nhập bằng Manager seed.
2. Xác nhận list/stats chỉ gồm các Department Manager đó lãnh đạo.
3. Thêm/gỡ Staff ở Department mình lãnh đạo.
4. Thử create/edit/archive/change leader, thêm Manager member hoặc gọi route của Department ngoài scope.
5. Thử `/admin/departments` bằng Staff và Citizen.

**Expected**: Manager chỉ quản lý Staff trong scope; resource ngoài scope không bị lộ; Staff/Citizen không có quyền F03; không mutation trái phép nào được ghi.

### 6. Transfer Staff nguyên tử

1. Chọn Staff đang ở Department A và Department B active chưa có Staff đó.
2. Transfer A -> B bằng Super Admin.
3. Xác nhận Staff mất khỏi A, xuất hiện đúng một lần ở B và audit có source/target.
4. Thử target trùng source, target đã có Staff, target archived hoặc version cũ.
5. Với Manager, chỉ thử transfer khi cùng Manager lãnh đạo cả A và B.

**Expected**: success thay đổi cả hai phía; mọi failure giữ nguyên cả A và B; Application assignment/history không bị sửa.

### 7. Archive và historical visibility

1. Archive một Department có leader, member, Service Type và reference lịch sử.
2. Xác nhận Department biến mất khỏi list active/candidate target.
3. Lọc archived và mở detail bằng người vẫn có quyền.
4. Thử update, change leader, add/remove/transfer member trên archived Department.
5. Kiểm tra linked Services, membership và historical references vẫn tồn tại.

**Expected**: archive là soft delete, detail read-only; code vẫn được bảo lưu; mọi relationship/history hiện hữu còn nguyên.

### 8. Search, filter, pagination và scale

1. Search bằng toàn bộ/một phần name, code và address; thử ký tự `%`/`_` để xác nhận query không coi input là raw wildcard ngoài chủ ý.
2. Filter manager/status, chuyển trang và quay lại.
3. Chạy fixture benchmark với ít nhất 1.000 Department và 10.000 membership trên PostgreSQL.

**Expected**: chỉ kết quả trong actor scope; filter được giữ khi paginate; không N+1; list/search/detail đạt mục tiêu dưới 2 giây trong môi trường benchmark đã ghi nhận.

### 9. Audit

Thực hiện create, update, archive, change leader, add, remove và transfer rồi kiểm tra `activity_logs`.

**Expected**: mỗi mutation thành công có đúng action, actor, subject, timestamp và before/after hoặc source/target metadata; mutation thất bại không để lại event thành công.

### 10. UI, responsive và accessibility

1. Kiểm tra desktop gần frame tham chiếu 1089px và mobile khoảng 375px.
2. Dùng bàn phím mở/đóng dialog, chọn candidate, submit/cancel và quan sát focus.
3. Kiểm tra table overflow, cards 4/2/1 cột, filter wrap, badges/status text, empty/no-result/error states.

**Expected**: Admin compact visual language bám `design-context.md`; không làm đổi component Citizen; dialog hỗ trợ Escape/return focus; action/copy archive/remove không gây hiểu nhầm là xóa User/hard delete.

## Test Commands

Chạy suite F03 trước, sau đó toàn bộ regression suite:

```powershell
php artisan test --testsuite=Feature --filter=Department
php artisan test
```

Chạy quality gates bắt buộc:

```powershell
composer run lint
npm run lint
npm run build
```

F03 chỉ được coi là hoàn tất implementation khi tests, lint và build đều pass, đồng thời không có route/UI ngoài phạm vi account management, Service CRUD hoặc Application workflow.

## Implementation Validation Record (2026-08-18)

Validation được thực hiện trên PostgreSQL 17 cô lập tại local, dùng schema mới hoàn toàn và dữ liệu từ `DatabaseSeeder`. Không sử dụng hoặc thay đổi dữ liệu development/production.

- Scenarios 1–9: PASS qua các feature test Department về CRUD, validation/stale version, leader/member, role scope, transfer, archive, query và audit.
- Scenario 10 desktop: PASS tại viewport 1089px; action Super Admin hiển thị đúng, dialog nhận focus, Escape đóng dialog và focus trở về trigger.
- Scenario 10 mobile: PASS tại viewport 375px; header không tràn ngang, cards/filter wrap đúng, bảng rộng cuộn trong `.admin-table-wrap` thay vì làm tràn trang.
- Keyboard candidate flow: PASS; Space mở dialog thêm member, Arrow Down/Enter chọn candidate, Space hủy dialog và focus trở về trigger; không submit mutation trong browser QA.
- Manager scope: PASS; thấy đúng ba Department do Manager seed lãnh đạo và không có create/edit/archive controls.

Kết quả command:

```text
php artisan test --testsuite=Feature --filter=Department
47 passed (335 assertions), 9.16s

php artisan test --testsuite=DepartmentPerformance
1 passed (6 assertions)
Fixture: 1,000 departments / 10,000 memberships
List: 157.73ms, 8 queries
Detail: 47.82ms, 3 queries

php artisan test
111 passed (636 assertions), 13.37s

composer run lint
PASS

npm run lint
PASS with 0 errors; 5 pre-existing Citizen warnings outside F03

npm run build
PASS; 105 modules transformed
```

Scope audit: 14 route dưới `/admin/departments` chỉ bao gồm Department CRUD/soft archive, candidate lookup, leader, member và Staff transfer. Không có account management, password/role mutation, Service mutation hoặc Application workflow trong route, policy hay view F03.
