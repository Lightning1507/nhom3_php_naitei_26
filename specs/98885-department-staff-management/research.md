# Research: F03 - Department & Staff Management

## Decision: Giữ F03 trong Admin Blade SSR và kiến trúc Laravel hiện hữu

**Rationale**: Constitution quy định người dùng nội bộ sử dụng Blade SSR dưới `/admin`, còn codebase đã có session authentication, middleware `internal`, Alpine entry và Tailwind/Vite. F03 chỉ cần mở rộng route, controller, Form Request, policy, focused Action, Eloquent và Blade; không có nhu cầu thêm frontend framework hoặc architectural layer.

**Alternatives considered**: Admin React/Vue SPA bị loại vì vi phạm rendering boundary. Repository, permission package và service layer tổng quát bị loại vì bốn role cố định cùng các rule F03 chưa tạo nhu cầu đủ cụ thể.

## Decision: Tái sử dụng schema tổ chức hiện có và chỉ thêm một migration tăng cường

**Rationale**: `departments` đã có nullable `leader_id`, timestamps và soft delete; `department_user` đã biểu diễn many-to-many với unique `(department_id, user_id)`; `service_types`, `application_assignments` và `activity_logs` đã giữ các liên kết cần bảo toàn. Migration F03 chỉ cần canonical hóa code hiện hữu sau bước preflight collision, thêm check constraint cho canonical format, `lock_version`, index `departments.leader_id` và index `department_user.user_id`.

**Alternatives considered**: Viết lại schema, thêm bảng leadership hoặc pivot có cờ `is_leader` bị loại vì phá foundation và không tự enforce role/status. Bảng membership history/soft-delete pivot bị loại vì pivot là current state; lịch sử thay đổi đã được ghi trong `activity_logs` và lịch sử Application nằm ở bảng riêng.

## Decision: Lưu Department code ở dạng canonical uppercase

**Rationale**: Form Request trim rồi uppercase code; code dài 2–50 ký tự và khớp `^[A-Z0-9]+(?:[-_][A-Z0-9]+)*$`. Database check bảo đảm writer nào cũng lưu canonical; unique constraint hiện hữu lúc đó trở thành chốt cuối cho uniqueness không phân biệt hoa/thường, kể cả hai request đồng thời. Code của Department đã archive vẫn được bảo lưu và không tái sử dụng.

**Alternatives considered**: Chỉ dùng validation `unique` bị loại vì có race. PostgreSQL `citext` bị loại vì thêm extension không cần thiết. Functional unique index `lower(code)` là lựa chọn hợp lệ nhưng dư thừa khi canonical form được enforce. Tự biến whitespace nội bộ thành dấu nối bị loại vì thay đổi input khó đoán; input đó phải bị từ chối.

## Decision: Dùng Policy kết hợp scoped query và che tài nguyên ngoài phạm vi bằng 404

**Rationale**: Middleware `internal` hiện cho cả Staff, Manager và Super Admin đi qua nên không đủ biểu diễn quyền F03. `DepartmentPolicy` deny-by-default: Super Admin quản trị toàn bộ; Manager chỉ xem Department mình lãnh đạo, quản lý Staff ở Department active và chỉ transfer khi lãnh đạo cả nguồn lẫn đích; Staff/Citizen không có quyền F03. Collection, stats và candidate lookup scope theo actor trước khi search/filter/count. Resource ngoài phạm vi trả kết quả như không tồn tại để không lộ cấu trúc tổ chức.

**Alternatives considered**: UI-only visibility bị loại vì không phải authorization control. Một role middleware cho mọi rule bị loại vì không biểu diễn được quyền theo từng Department. Query toàn bộ rồi lọc trong PHP bị loại vì rò rỉ dữ liệu và không đạt scale.

## Decision: Department archive là soft delete và vẫn tra cứu được trong đúng scope

**Rationale**: `deleted_at` đã là lifecycle marker phù hợp; không cần thêm `is_active`. Archive không detach leader/member, không sửa Service, không xóa assignment/history và không giải phóng code. Detail có quyền dùng `withTrashed`; relationship hiển thị lịch sử phải có biến thể bao gồm soft-deleted leader/member. Mọi mutation re-read Department với lock và từ chối nếu đã archive.

**Alternatives considered**: Hard delete bị loại vì phá lịch sử và có thể bị FK chặn. Detach toàn bộ membership khi archive bị loại vì làm mất current snapshot. Thêm cột trạng thái song song với `deleted_at` bị loại vì tạo hai nguồn sự thật.

## Decision: Bảo vệ leader và membership bằng transaction, row lock và database uniqueness

**Rationale**: Leader vẫn là nullable `departments.leader_id`; candidate phải là Manager active, chưa soft-delete. Change leader khóa Department và candidate, bảo đảm leader mới đồng thời được `syncWithoutDetaching` vào membership, update leader và ghi audit trong một transaction. Add member revalidate role/status và dùng unique pivot làm final guard. Remove khóa Department rồi từ chối nếu target là leader hiện tại.

**Alternatives considered**: Composite foreign key từ leader sang pivot hoặc database trigger bị loại vì tạo vòng đời phức tạp mà vẫn không kiểm tra được role/status. Application-only duplicate check bị loại vì hai request có thể vượt qua cùng lúc.

## Decision: Transfer là một use case nguyên tử riêng

**Rationale**: Action transfer khóa source và target theo thứ tự ID ổn định, khóa/revalidate membership nguồn, kiểm tra target active/khác source/chưa chứa user và user vẫn là Staff active. Nó insert target trước, detach source sau, tăng version hai Department và ghi một audit event trong cùng transaction; bất kỳ lỗi nào rollback tất cả. Manager phải lãnh đạo cả source và target; Super Admin không bị giới hạn scope này.

**Alternatives considered**: Gọi riêng endpoint add rồi remove bị loại vì có trạng thái dở dang. `sync()` trên cả collection bị loại vì có thể vô tình xóa membership không liên quan. Cho transfer vào target đã có member bị loại vì làm mờ lỗi duplicate; request phải thất bại và giữ source.

## Decision: Dùng `lock_version` cho stale form, không dùng timestamp làm version

**Rationale**: Row lock bảo vệ invariant nhưng không cho người dùng biết form đang chỉnh đã cũ. `departments.lock_version` bắt đầu từ 0; mỗi structural mutation gửi version hiện tại, compare trong transaction và tăng version khi thành công. Transfer kiểm tra/tăng version của cả source và target. Conflict trả thông báo reload và thử lại, đáp ứng business state concurrent update trong design context.

**Alternatives considered**: `updated_at` bị loại vì precision timestamp hiện tại có thể không phân biệt hai write gần nhau. PostgreSQL `xmin` bị loại vì ràng buộc quá sâu vào implementation của database. Không phát hiện stale form bị loại vì có thể ghi đè thay đổi hợp lệ của quản trị viên khác.

## Decision: Ghi audit event cùng transaction thay đổi cơ cấu

**Rationale**: Dùng `activity_logs` hiện có cho `department.created`, `department.updated`, `department.archived`, `department.leader_changed`, `department.member_added`, `department.member_removed`, `department.member_transferred`. Event chứa actor, timestamp, subject và metadata snapshot before/after/source/target đủ để đọc ngay cả khi model sau này soft/hard-delete. Audit failure phải rollback mutation để FR-023 luôn đúng.

**Alternatives considered**: Chỉ ghi Laravel log file bị loại vì khó truy vấn cho F06. Ghi audit sau commit kiểu best-effort bị loại vì có thể tạo thay đổi không truy vết được. Thêm một bảng audit riêng cho F03 bị loại vì trùng foundation.

## Decision: Query server-side có scope, eager load, count và pagination

**Rationale**: Department list dùng search case-insensitive theo name/code/address, filter manager/status, thứ tự ổn định, eager-load leader, `withCount` member/service và `paginate(15)->withQueryString()`. Candidate combobox gọi endpoint Admin nội bộ, yêu cầu ít nhất 2 ký tự và trả tối đa 20 kết quả hợp lệ. Cách này phù hợp Blade SSR, không preload toàn bộ user và đủ cho mục tiêu 1.000 Department/10.000 membership.

**Alternatives considered**: `Model::all()` và client-side filtering bị loại vì scale/rò dữ liệu. Cursor pagination bị loại vì màn tham chiếu cần số trang. Preload mọi Staff/Manager vào `<select>` bị loại vì không có bound rõ ràng.

## Decision: Chuyển visual language Figma sang component Admin compact riêng

**Rationale**: Node Figma `4:2284` là User Management nên chỉ dùng admin shell, primary color, Inter, card, filter, table, badge và pagination. F03 xây Department List, dedicated Create/Edit, Detail và native dialogs; CSS/component compact riêng tránh thay đổi `.btn-*`/`.input-field` cỡ lớn đang phục vụ Citizen. Table scroll ngang trên mobile, cards 4/2/1 cột, trạng thái luôn có text và dialog có keyboard/focus behavior.

**Alternatives considered**: Sao chép Add User, role/status toggle, joined/last-login bị loại vì thuộc F01/F07. Ghi đè global Citizen component bị loại vì regression. Modal tự chế không quản lý focus bị loại; native `<dialog>` + Alpine là lựa chọn nhẹ hơn.

## Decision: Dùng PHPUnit Feature Tests và manual responsive/accessibility QA

**Rationale**: Repo đã chuẩn hóa Laravel Feature Tests cho Admin. Suite F03 phải phủ route/view authorization, scoped query, CRUD/normalization, duplicate race mapping, candidate eligibility, leader invariant, transfer rollback, archive preservation, audit và action visibility. SC-004 dùng fixture/benchmark PostgreSQL riêng; browser QA tại khoảng 1089px và 375px kiểm tra dialog, keyboard, overflow và copy tiếng Việt.

**Alternatives considered**: Chỉ test nút ẩn/hiện bị loại vì server authorization mới là nguồn sự thật. Thêm Dusk/Playwright bị loại ở F03 vì repo chưa có dependency và rủi ro không tương xứng với scope một sprint ngắn.
