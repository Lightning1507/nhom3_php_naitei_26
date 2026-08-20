# Research: F05 - Application Processing Workflow

## Decision: State machine tách riêng và chuyển trạng thái trong transaction có khóa hàng

**Rationale**: Quy tắc chuyển trạng thái là ràng buộc nghiệp vụ quan trọng nhất của F05 và được
dùng lại ở cả Admin lẫn kiểm thử. Tách tập chuyển hợp lệ vào một lớp riêng
`app/Support/Application/ApplicationTransitionMap.php` để chỗ khác không thể "tự bịa" transition.
Mỗi chuyển trạng thái được thực hiện trong `DB::transaction` với `lockForUpdate` trên dòng hồ sơ
(`applications`), sau đó mới đọc lại trạng thái hiện tại và so khớp tập chuyển hợp lệ — bảo đảm hai
yêu cầu đồng thời trên cùng hồ sơ chỉ một thành công (SC-005, edge case "chuyển đồng thời").

**Alternatives considered**: Đặt map chuyển trạng thái ngay trong enum `ApplicationStatus` bị loại
vì enum chỉ nên khai báo giá trị, map chuyển + message lỗi dễ phình to và khó unit-test độc lập.
`updateOrFail` với điều kiện `where('status', $from)` ở tầng query thay cho `lockForUpdate` bị loại
vì `lockForUpdate` kết hợp kiểm tra lại trong transaction minh bạch hơn và dễ đọc hơn trong Action.

## Decision: Phạm vi staff đủ điều kiện gán/nhận = Staff thuộc phòng ban phụ trách dịch vụ, không dùng pivot `service_staff`

**Rationale**: spec.md (US1, FR-007) quy định staff được chọn phải thuộc phòng ban phụ trách dịch vụ
của hồ sơ (`ServiceType.responsible_department_id` → `Department` → `users`). Bảng pivot
`service_staff` có trong schema nhưng chưa được F04 quản lý (không có UI/thao tác duy trì), nên nếu
lấy nó làm nguồn phạm vi thì mọi hồ sơ sẽ không có staff nào đủ điều kiện. Dùng
`responsible_department_id` tận dụng dữ liệu F03/F02 đã có và nhất quán với phạm vi Manager.

**Alternatives considered**: Pivot `service_staff` làm nguồn phạm vi bị loại vì chưa có dữ liệu và
chưa được quản lý (nêu ở Assumptions của spec). Staff theo phòng ban của chính staff (qua
`department_user`) bị loại vì một staff có thể thuộc nhiều phòng ban, làm mơ hồ phạm vi gán.

## Decision: Phạm vi Manager theo `Department.leader_id`; Super Admin override toàn bộ

**Rationale**: F03 đã xác lập "Manager chỉ quản lý phòng ban mình lãnh đạo" và cung cấp
`Department::scopeVisibleTo(actor)` (Super Admin → tất cả, Manager → `leader_id = actor`, còn lại →
rỗng). F05 tái sử dụng quy tắc đó: Manager nhìn/gán được hồ sơ của các dịch vụ thuộc phòng ban mình
lãnh đạo; Super Admin thấy tất cả. Điều này tạo một nguồn phân quyền duy nhất, không bám vai trò rời
rạc trong controller.

**Alternatives considered**: Tự viết lại logic scope trong controller F05 bị loại vì trùng lặp với
`Department::scopeVisibleTo` và dễ lệch giữa F03/F05. Cho Manager tự xử lý hồ sơ như staff bị loại vì
trái Assumption của spec ("Manager muốn xử lý phải tự gán cho chính mình").

## Decision: Staff được "claim" hồ sơ chưa gán thuộc phạm vi phòng ban phụ trách

**Rationale**: spec.md US2/FR-004 cho phép Staff đang hoạt động tự nhận (claim) hồ sơ chưa có
`assigned_staff_id` trong phòng ban phụ trách dịch vụ. Claim tạo một bản ghi
`application_assignments` với `assigned_by` bằng chính staff — đúng quy ước append-only và khiến
staff trở thành người xử lý duy nhất (quyền xử lý bám theo `assigned_staff_id`). Đây là điểm phân
phối việc phụ cho Staff ngoài luồng gán của Manager.

**Alternatives considered**: Bỏ claim, chỉ cho Manager gán bị loại vì trái US2 trong spec.md (người
dùng đã xác nhận spec là chuẩn). Claim đồng thời cùng một hồ sơ được xử lý bằng `lockForUpdate` —
staff thứ hai sẽ thấy hồ sơ đã có người gán và bị từ chối.

## Decision: Resume processing (`supplement_required → processing`) do Staff thực hiện, xác thực soft tài liệu thiếu

**Rationale**: spec.md US3/FR-014 quy định sau khi Citizen bổ sung tài liệu, Staff tiếp tục xử lý
bằng cách resume hồ sơ; transition này không yêu cầu đủ tài liệu bắt buộc (soft) để nhất quán với
F04 (upload không chặn nộp hồ sơ thiếu giấy tờ). Hệ thống trả kèm danh sách tài liệu còn thiếu để
Staff cân nhắc, nhưng không chặn chuyển trạng thái.

**Alternatives considered**: Tự động quay về `processing` ngay khi Citizen upload tài liệu bổ sung
bị loại vì người dùng đã chốt hướng "Staff resume" (spec.md là chuẩn) và tránh thay đổi trạng thái
ngoài ý muốn khi citizen còn đang upload nhiều tài liệu. Bắt buộc đủ tài liệu trước khi resume bị
loại vì trái FR-014 và trái hành vi soft của F04.

## Decision: Tài liệu kết quả (`document_kind=result`) chỉ gắn khi duyệt; reject chỉ có `rejection_reason`

**Rationale**: spec.md FR-015/FR-016 và edge case "tài liệu kết quả upload khi reject: không cho
phép" — kết quả dạng file là đặc quyền của luồng approve. Staff upload tài liệu kết quả qua endpoint
riêng khi hồ sơ ở `processing` (trước/trong lúc duyệt); `document_kind=result` được lưu trên
`application_documents`, tái sử dụng download policy F04 (chủ hồ sơ + nội bộ). Không tạo bảng mới.

**Alternatives considered**: Nhúng file kết quả vào payload của approve (multipart + JSON) bị loại vì
khó validate và tách biệt xử lý. Cho upload result khi `rejected` bị loại vì trái edge case trong
spec.

## Decision: Overdue trong dashboard = chưa hoàn thành quá `processing_time_days` kể từ `submitted_at`

**Rationale**: spec.md FR-024/Assumption định nghĩa overdue là hồ sơ `completed_at` null (chưa duyệt/
từ chối) có `submitted_at + processing_time_days < now()`. Công thức này dùng dữ liệu đã có
(`ServiceType.processing_time_days`, `applications.submitted_at`), không cần cột mới, và phục vụ đúng
mục đích điều phối của Assignment Board (US1 AC7).

**Alternatives considered**: Dùng deadline cố định trên bảng `applications` bị loại vì phải thêm
migration/cột trong khi có thể tính từ dữ liệu hiện có. Đếm overdue theo `updated_at` bị loại vì
không phản ánh thời gian chờ thực tế kể từ khi nộp.

## Decision: Không có migration mới — tận dụng toàn bộ cột/schema F00/F04

**Rationale**: Mọi cột cần cho workflow (`assigned_staff_id`, `processing_started_at`,
`completed_at`, `result_note`, `rejection_reason`, `application_assignments.*`,
`application_status_histories.*`, `application_documents.document_kind/requirement_code`) đã tồn tại.
F05 chỉ thêm logic và code (Actions, Policies, controllers, views, factories), không thay đổi schema.
Điều này giảm rủi ro migration và giữ mock project gọn.

**Alternatives considered**: Thêm cột `deadline_at` hay index mới cho dashboard bị loại vì volume dữ
liệu mock nhỏ; nếu cần tối ưu sau này sẽ là task riêng ngoài F05.

## Decision: Authorization tập trung vào `ApplicationPolicy`, kết hợp scope phòng ban tái sử dụng F03

**Rationale**: F05 thêm các ability `assign`, `claim`, `transition` (và variant theo từng trạng thái)
vào `ApplicationPolicy`; quyền "thực hiện chuyển trạng thái" được quyết định bởi `assigned_staff_id`
+ role (Super Admin override) — nằm đúng chuẩn SunLint S005/LV005 của dự án. Phạm vi xem danh sách
(worklist/board) dùng query scope phòng ban thay vì ability để lọc dữ liệu ngay tầng DB.

**Alternatives considered**: Kiểm tra vai trò trong controller (`if ($user->isStaff())`) bị loại vì
vi phạm Policy/Gate. Dùng middleware `internal` làm điều kiện đủ bị loại vì `internal` chỉ lọc vai
trò, không biểu diễn quan hệ gán theo từng hồ sơ.