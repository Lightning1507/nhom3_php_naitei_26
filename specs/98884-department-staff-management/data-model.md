# Data Model: F03 - Department & Staff Management

## Tổng quan

F03 dùng schema nền hiện có và phân biệt rõ:

- **Current organizational state**: `departments.leader_id` và `department_user`.
- **Historical business state**: `application_assignments` cùng các bảng Application history hiện có.
- **Change audit**: `activity_logs` ghi ai thay đổi cơ cấu, khi nào và before/after metadata.

F03 không tạo bảng user/staff riêng, không thay đổi role và không tạo lịch sử Application mới.

## Department

Đơn vị tổ chức nội bộ.

**Backing table**: `departments`

### Fields

| Field | Type | Rule |
|---|---|---|
| `id` | bigint | Primary key |
| `name` | varchar | Bắt buộc, 1–255 ký tự sau normalize |
| `code` | varchar | Bắt buộc, canonical uppercase, unique, không tái sử dụng sau archive |
| `address` | text nullable | Tùy chọn, tối đa 1.000 ký tự |
| `leader_id` | bigint nullable FK -> `users.id` | Chỉ một Manager active, non-deleted; nullable |
| `lock_version` | unsigned integer | Mặc định 0; optimistic concurrency token của toàn bộ cơ cấu Department |
| `created_at` | timestamp | Thời điểm tạo |
| `updated_at` | timestamp | Thời điểm cập nhật gần nhất |
| `deleted_at` | timestamp nullable | `null` = active; có giá trị = archived |

### Canonicalization và validation

- `name`: trim và collapse chuỗi whitespace liên tiếp thành một khoảng trắng; không chấp nhận rỗng; tối đa 255 ký tự.
- `code`: trim, uppercase; dài 2–50 ký tự; khớp `^[A-Z0-9]+(?:[-_][A-Z0-9]+)*$`.
- `address`: trim; chuỗi rỗng thành `null`; tối đa 1.000 ký tự.
- `leader_id`: nullable; nếu có phải trỏ tới User role `manager`, `is_active = true`, `deleted_at = null`.
- `lock_version`: client phải gửi version đã render cho mọi structural mutation; mismatch tạo conflict và không thay đổi dữ liệu.
- `code` của record archived vẫn tham gia unique constraint.

### Constraints và indexes

- Primary key `id`.
- Existing FK `leader_id -> users.id`, `nullOnDelete` cho hard delete đặc biệt.
- Existing unique `departments.code`.
- F03 migration preflight collision theo `upper(trim(code))`, canonicalize dữ liệu hợp lệ rồi thêm check constraint cho uppercase/trim, độ dài 2–50 và format.
- F03 migration thêm index `departments.leader_id` cho Manager scope.
- Soft delete là lifecycle source of truth; không thêm `is_active`.

### Relationships

- `leader`: belongs to User; read model phải có khả năng `withTrashed()` để hiển thị leader lịch sử.
- `members`: belongs to many User qua `department_user`; read model chi tiết phải gồm inactive/soft-deleted member đang còn pivot.
- `serviceTypes`: has many ServiceType qua `responsible_department_id`; read-only trong F03.
- `applicationAssignments`: has many ApplicationAssignment; read-only/historical trong F03.

### Derived values

- `status`: `active` nếu `deleted_at = null`, ngược lại `archived`.
- `member_count`: số membership hiện có, gồm Staff/Manager và leader.
- `service_count`: số Service Type liên kết, không tạo quyền sửa Service.
- `missing_leader`: `leader_id = null` hoặc leader hiện tại không còn active/non-deleted; trạng thái này phải được cảnh báo nhưng không tự xóa tham chiếu.

## Internal User

Tài khoản nội bộ do F01 quản lý; F03 chỉ đọc eligibility và gắn quan hệ.

**Backing table**: `users`

### Fields used by F03

- `id`, `name`, `email`: identity hiển thị/chọn.
- `role`: `staff`, `manager`, `citizen`, `super_admin`.
- `is_active`: điều kiện nhận membership/leadership mới.
- `deleted_at`: soft-deleted user không được chọn cho quan hệ mới.

### Eligibility rules

- Super Admin có thể thêm User active, non-deleted role Staff hoặc Manager làm member.
- Manager chỉ có thể thêm User active, non-deleted role Staff vào Department mình lãnh đạo.
- Chỉ Manager active, non-deleted mới được đặt làm leader, và chỉ Super Admin thực hiện.
- Citizen và Super Admin không bao giờ là Department member qua F03.
- User inactive/soft-deleted đang có quan hệ vẫn được hiển thị cho tra cứu với status warning nhưng bị loại khỏi candidate lookup.
- F03 không sửa `role`, `is_active`, credential hoặc profile User.

## Department Membership

Quan hệ current-state giữa Department và một Staff/Manager.

**Backing table**: `department_user`

### Fields

| Field | Type | Rule |
|---|---|---|
| `department_id` | bigint FK | Department sở hữu membership |
| `user_id` | bigint FK | Staff/Manager member |
| `created_at` | timestamp | Thời điểm quan hệ current được tạo |
| `updated_at` | timestamp | Metadata pivot hiện có |

### Constraints và indexes

- Existing unique `(department_id, user_id)` là final guard chống duplicate/race.
- Existing FK cascade chỉ áp dụng nếu hard delete đặc biệt; archive thông thường không kích hoạt cascade.
- F03 migration thêm index `department_user.user_id` cho lookup ngược.
- Không có unique riêng trên `user_id`: một user có thể thuộc nhiều Department.

### Invariants

- Mỗi cặp Department/User chỉ có một membership.
- Department archived không nhận, gỡ hoặc transfer membership.
- Leader hiện tại phải có membership cùng Department.
- Không được remove leader hiện tại; phải change/unset leader trước.
- Gỡ membership không xóa User, Service relation, Application assignment hay history.
- Pivot là current state, không phải complete history; add/remove/transfer history nằm trong `activity_logs`.

## Department Leadership

Quan hệ lãnh đạo được mô hình bằng `departments.leader_id`, không có bảng riêng.

### Rules

- Department có 0 hoặc 1 leader.
- Một Manager có thể lãnh đạo nhiều Department; schema/spec không đặt giới hạn một-phòng-ban.
- Set/change leader phải atomically bảo đảm leader mới là member.
- Unset leader đặt `leader_id = null`; leader cũ vẫn là member cho đến khi có thao tác remove riêng.
- Nếu leader bị F01 deactivate/soft-delete, tham chiếu và membership được giữ để tra cứu; UI cảnh báo cần chọn leader hợp lệ.

## Service Association

Liên kết read-only giữa Department và Service Type.

**Backing relation**: `service_types.responsible_department_id -> departments.id`

### Rules

- F03 chỉ hiển thị name, code và active/archived status của Service Type nếu dữ liệu tồn tại.
- Archive Department không update/delete Service Type.
- F03 không cung cấp route/form create, update, activate hoặc archive Service.

## Application Assignment Reference

Liên kết lịch sử giữa Application và Department.

**Backing relation**: `application_assignments.department_id -> departments.id`

### Rules

- Remove/transfer member không sửa hoặc xóa assignment hiện hữu.
- Archive Department không sửa assignment history.
- F03 không tạo assignment, đổi assigned staff hoặc Application status.

## Organizational Audit Event

**Backing table**: `activity_logs`

| Action | Subject | Required metadata |
|---|---|---|
| `department.created` | Department | Snapshot name/code/address/leader/version |
| `department.updated` | Department | `before`, `after`, version |
| `department.archived` | Department | Snapshot, version |
| `department.leader_changed` | Department | old/new leader snapshot, auto-membership flag, version |
| `department.member_added` | Department | member snapshot, role, version |
| `department.member_removed` | Department | member snapshot, role, version |
| `department.member_transferred` | Department/source | source/target Department snapshot, member snapshot, source/target version |

Mọi event có `actor_id`, `created_at`, IP/user-agent khi request cung cấp và được insert trong cùng transaction với mutation.

## State Transitions

### Department lifecycle

```text
[new]
  -> active (create)

active
  -> active (update identity / change or unset leader / add / remove / transfer member)
  -> archived (Super Admin archive)

archived
  -> read-only historical view
```

Restore và hard delete ngoài phạm vi F03.

### Leadership

```text
unassigned
  -> assigned(active Manager + ensure membership)

assigned
  -> assigned(other active Manager + ensure membership)
  -> unassigned
  -> warning state (leader later becomes inactive/deleted by F01)
```

### Membership

```text
absent in Department A
  -> present in Department A (add)

present in Department A
  -> absent in Department A (remove, unless current leader)
  -> present in Department B and absent in A (atomic transfer)
  -> present in A and B (separate add to B, not transfer)
```

## Atomic Mutation Rules

### Create Department

1. Normalize and validate fields/candidate.
2. Insert Department with `lock_version = 0`.
3. Nếu có leader, attach membership trong cùng transaction.
4. Insert `department.created` audit.

### Update Department

1. Lock active Department và compare `lock_version`.
2. Recheck canonical unique code, kể cả archived records.
3. Update identity, increment version và insert audit.

### Change/Unset Leader

1. Lock active Department, compare version.
2. Nếu set leader: lock/re-read User và revalidate Manager eligibility.
3. Insert membership nếu chưa có; update leader.
4. Increment version và insert audit.

### Add/Remove Member

1. Lock active Department, compare version và authorize actor scope.
2. Lock/re-read target User/membership và revalidate business rule.
3. Add hoặc remove; duplicate/remove-leader bị từ chối.
4. Increment version và insert audit.

### Transfer Staff

1. Lock source/target Department theo thứ tự ID, compare cả hai version.
2. Lock source membership và revalidate Staff/target/actor eligibility.
3. Insert target membership; unique violation làm transaction fail.
4. Delete source membership.
5. Increment cả hai version và insert một transfer audit.

### Archive Department

1. Lock active Department và compare version.
2. Soft delete Department; giữ leader, pivot, services và historical references.
3. Insert audit trong cùng transaction.

## Concurrency and Failure Semantics

- Duplicate code và duplicate membership: friendly validation pre-check + database constraint là final guard; constraint exception được map về field/domain error tiếng Việt.
- Stale version: không mutation nào được ghi; trả conflict hướng người dùng reload dữ liệu.
- Archive đua với add/remove/change: action lock/re-read rồi từ chối nếu Department đã archived.
- Transfer lỗi ở bất kỳ bước nào: source và target đều giữ nguyên, không có audit event dở dang.
- Candidate lookup chỉ hỗ trợ UX; action luôn revalidate User/Department trong transaction.
