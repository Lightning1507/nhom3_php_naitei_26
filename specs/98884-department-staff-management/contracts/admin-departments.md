# Admin Interface Contract: F03 Department & Staff Management

## Boundary and Conventions

- Tất cả interface nằm dưới `/admin`, khai báo trong `routes/web.php`, dùng Laravel web session và middleware `auth`, `internal`.
- Page chính được server-render bằng Blade. Alpine chỉ điều khiển dialog, combobox, pending/feedback cục bộ.
- Mọi mutation dùng CSRF token và method spoofing chuẩn Laravel khi cần.
- Form success theo Post/Redirect/Get, có flash message tiếng Việt.
- Validation giữ old input và gắn error vào field; business conflict có copy hướng dẫn hành động tiếp theo.
- Route binding Department phải hỗ trợ soft-deleted record cho authorized historical view; archived record không được mutate.
- Collection/stats/candidate query luôn scope theo actor trước khi filter/count.
- Resource ngoài scope của Manager/Staff được trả như không tồn tại để tránh resource enumeration.
- F03 không cung cấp route dưới `/api/v1`, không có route quản lý User/role/password/status, Service CRUD hoặc Application workflow.

## Authorization Matrix

| Capability | Super Admin | Manager | Staff | Citizen |
|---|---|---|---|---|
| Xem Department list/stats | Toàn bộ trong filter hợp lệ | Chỉ Department mình lãnh đạo | Không | Không |
| Xem active/archived detail | Toàn bộ | Chỉ Department mình lãnh đạo | Không | Không |
| Create/update/archive Department | Có | Không | Không | Không |
| Change/unset leader | Có | Không | Không | Không |
| Add/remove member | Active Staff hoặc Manager | Active Staff, chỉ Department mình lãnh đạo | Không | Không |
| Transfer Staff | Giữa hai Department active | Chỉ khi lãnh đạo cả source và target | Không | Không |
| Xem linked Services | Read-only trong Department có quyền | Read-only trong Department có quyền | Không | Không |

Guest được redirect tới `/admin/login`. Citizen hoặc inactive internal user bị middleware từ chối. Staff đã đăng nhập vẫn phải bị `DepartmentPolicy` từ chối.

## Page Routes

### GET `/admin/departments`

Hiển thị Department list, scoped summary cards, filter và pagination.

**Authorization**: `DepartmentPolicy::viewAny`; chỉ Super Admin và Manager.

**Query parameters**:

| Name | Rule | Meaning |
|---|---|---|
| `search` | nullable string, max 100 | Case-insensitive partial match trên name/code/address; wildcard phải được escape |
| `manager_id` | nullable integer | Filter leader; Super Admin dùng toàn scope, Manager không thể mở rộng khỏi scope của mình |
| `status` | `active`, `archived`, `all`; default `active` | Archived/all dùng explicit `withTrashed` trong actor scope |
| `page` | positive integer | 15 record/trang |

**View data**:

- Bốn scoped cards: tổng Department trong phạm vi, active, thiếu leader hợp lệ, tổng Staff membership trong active scope.
- Row: code, name/address, leader + account status, member count, service count, Department status và actions theo policy.
- Query eager-load leader, member/service counts; stable sort và pagination giữ toàn bộ filter.
- Empty state phân biệt chưa có dữ liệu với không có kết quả filter.

### GET `/admin/departments/create`

Hiển thị dedicated create form.

**Authorization**: Super Admin only.

### POST `/admin/departments`

Tạo Department, optional leader và leader membership trong một transaction.

**Authorization**: Super Admin only.

**Form fields**:

| Field | Rule |
|---|---|
| `name` | required, normalized string, max 255 |
| `code` | required, trim + uppercase, 2–50, canonical regex, unique kể cả archived |
| `address` | nullable, trim, empty -> null, max 1.000 |
| `leader_id` | nullable, active non-deleted Manager |

**Success**: `302` tới detail mới; flash xác nhận tạo thành công.

**Failure**: redirect back với field errors. Concurrent duplicate code phải map vào `code`, không trả raw database error.

### GET `/admin/departments/{department}`

Hiển thị active hoặc archived detail trong actor scope.

**Authorization**: Super Admin hoặc Manager có `department.leader_id = actor.id`; out-of-scope là 404-masked.

**View data**:

- Identity, status, `lock_version`, created/updated metadata.
- Leader, kể cả inactive/soft-deleted, với warning nếu không còn eligible.
- Member list gồm member còn pivot kể cả inactive/soft-deleted; phân trang/search nếu cần.
- Linked Service Types ở chế độ read-only.
- Actions từ policy; archived detail tuyệt đối read-only.

### GET `/admin/departments/{department}/edit`

Hiển thị dedicated edit form cho active Department.

**Authorization**: Super Admin only.

### PATCH `/admin/departments/{department}`

Cập nhật identity của active Department.

**Authorization**: Super Admin only.

**Form fields**: `name`, `code`, `address` như create và `version` integer bắt buộc. Leader không được đổi qua route này.

**Success**: `302` tới detail; version tăng 1.

**Stale version**: `409` Blade response nêu dữ liệu đã thay đổi, có link reload detail/edit; không ghi update/audit.

### DELETE `/admin/departments/{department}`

Lưu trữ bằng soft delete; đây không phải hard delete.

**Authorization**: Super Admin only.

**Form fields**: `version` integer bắt buộc và confirmation value theo UI.

**Success**: `302` tới list active; flash giải thích record/lịch sử vẫn được giữ.

**Rules**: giữ leader, membership, Service association và Application history; code không được tái sử dụng.

## Leadership Route

### PATCH `/admin/departments/{department}/leader`

Set, change hoặc unset leader.

**Authorization**: Super Admin only; Department phải active.

**Form fields**:

- `leader_id`: nullable integer; nếu có phải là active, non-deleted Manager.
- `version`: integer bắt buộc.

**Behavior**:

- Leader mới được auto-add làm member nếu chưa có.
- Unset leader không tự remove leader cũ khỏi membership.
- Cùng một leader là idempotent; không tạo pivot trùng.
- Mutation, version increment và audit cùng transaction.

**Success**: `302` về detail với flash; stale version trả `409`.

## Membership Routes

### POST `/admin/departments/{department}/members`

Thêm một member vào active Department.

**Authorization**: Super Admin hoặc Manager lãnh đạo Department.

**Form fields**:

- `user_id`: integer bắt buộc.
- `version`: integer bắt buộc.

**Eligibility**:

- Super Admin: active non-deleted Staff hoặc Manager.
- Manager actor: active non-deleted Staff only.
- User chưa là member của Department.

**Success**: `302` về detail; flash xác nhận, version tăng 1.

**Failure**: duplicate/race map vào `user_id`; candidate forged/ineligible dùng thông báo domain chung, không lộ thông tin account ngoài phạm vi.

### DELETE `/admin/departments/{department}/members/{member}`

Gỡ một membership, không xóa/deactivate User.

**Authorization**: Super Admin hoặc Manager lãnh đạo Department; Manager chỉ remove Staff.

**Form fields**: `version` integer bắt buộc.

**Rules**:

- `{member}` phải có pivot với Department; nếu không, trả 404-masked.
- Current leader không thể bị remove cho đến khi leader được change/unset.
- Không sửa Application assignment/history.

**Success**: `302` về detail; flash xác nhận, version tăng 1.

### POST `/admin/departments/{department}/members/{member}/transfer`

Chuyển Staff từ source `{department}` sang target như một operation nguyên tử.

**Authorization**: Super Admin; hoặc Manager lãnh đạo cả source và target.

**Form fields**:

| Field | Rule |
|---|---|
| `target_department_id` | required, khác source, active, visible/authorized target |
| `source_version` | required integer |
| `target_version` | required integer |

**Rules**:

- `{member}` phải là Staff active, non-deleted và đang là source member.
- Target chưa có member đó.
- Lock hai Department theo thứ tự ID; attach target, detach source, tăng hai version, ghi audit trong một transaction.
- Target invalid/duplicate/stale/audit failure giữ nguyên source và target.

**Success**: `302` tới target detail với flash xác nhận transfer.

## Candidate Lookup Routes

Các endpoint này chỉ progressive-enhance Blade combobox, không phải public/Citizen API. Request cần `Accept: application/json`; kết quả tối đa 20 item và không bao giờ là danh sách user tổng quát.

### GET `/admin/departments/manager-candidates?search={term}`

**Authorization**: Super Admin only.

**Filter**: term 2–100 ký tự; active non-deleted Manager; match name/email. Leader hiện tại, kể cả khi inactive, được page render riêng để giữ context nhưng không được trả như một candidate hợp lệ.

### GET `/admin/departments/{department}/member-candidates?search={term}`

**Authorization**: actor phải có quyền add member vào active Department.

**Filter**: term 2–100; exclude current members. Super Admin nhận active Staff/Manager; Manager nhận active Staff only.

### GET `/admin/departments/{department}/members/{member}/transfer-targets?search={term}`

**Authorization**: actor phải có quyền transfer source member.

**Filter**: active Department khác source, target chưa có member; Manager chỉ nhận Department mình cũng lãnh đạo.

### Candidate response

```json
{
  "data": [
    {
      "id": 12,
      "name": "Nguyen Van A",
      "email": "staff@example.test",
      "role": "staff"
    }
  ],
  "meta": {
    "has_more": false
  }
}
```

Transfer target item thay `email`/`role` bằng `code`/`version`. Không trả profile, credential, citizen identifier hoặc account management fields.

## Error and Conflict Contract

| Condition | Outcome |
|---|---|
| Guest page request | Redirect `/admin/login` |
| Citizen/inactive internal user | `403` từ internal boundary |
| Staff hoặc Manager ngoài scope | `404` masked cho resource/candidate; collection bị `403` nếu không có capability |
| Department/member không tồn tại | `404` |
| Archived mutation | Domain error: Department đã lưu trữ và chỉ còn read-only |
| Invalid field/role/status/duplicate | Redirect back với field error; JSON candidate dùng `422` khi query malformed |
| Stale `version` | `409`, không mutation/audit, hướng dẫn reload |
| Transfer target unavailable | Generic domain error, không xác nhận target ID ngoài scope có tồn tại |
| Unexpected transaction failure | Rollback; error page/flash chung; không hiển thị SQL/stack trace |

## UI Presentation Contract

- Nội dung người dùng bằng tiếng Việt; chuỗi tiếng Anh trong Figma chỉ là visual reference.
- Admin shell, compact controls, primary `#0052CC`, Inter, cards, table, badges và pagination bám [design-context.md](../design-context.md).
- Create/Edit dùng dedicated page; add/change/transfer/remove/archive dùng native `<dialog>` với Alpine, Cancel, action rõ ràng và destructive styling khi phù hợp.
- Không dùng status toggle trực tiếp trên row; archive luôn có confirmation.
- Action visibility phản ánh policy nhưng không thay thế server authorization.
- Status/role có label text, không chỉ màu; field có label thật và error association; dialog hỗ trợ Escape/return focus.
- Summary cards 4/2/1 cột; filter wrap; table horizontal-scroll trên viewport nhỏ; touch target khoảng 40–44px.
- Bắt buộc có states: data, empty, no-result, archived, invalid candidate, duplicate, stale conflict và success feedback.
