# Feature Specification: F05 - Application Processing Workflow

**Feature Branch**: `feature/98887-application-processing-workflow`

**Created**: 2026-08-20

**Status**: Draft

**Input**: User description: "F05 – Application Processing Workflow. Mô phỏng quy trình cán bộ xử lý hồ sơ thật theo workflow Received → Processing → Supplement Required → Processing → Approved / Rejected: Manager phân công/re-phân công Staff, Staff tiếp nhận và xử lý hồ sơ, yêu cầu bổ sung, Citizen nộp tài liệu bổ sung, duyệt/từ chối kèm lý do, kết quả xử lý (ghi chú + tài liệu kết quả). Mọi chuyển trạng thái phải được validate và thực hiện trong transaction, ghi lịch sử append-only. Admin dùng Blade SSR (Staff Workspace + Manager Assignment Board), Citizen xem tiến độ/bổ sung/kết quả qua React SPA."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Manager phân công hồ sơ cho Staff trên Assignment Board (Priority: P1)

Manager nhìn thấy các hồ sơ mới tiếp nhận (`received`) và đang xử lý (`processing`/`supplement_required`) trong phạm vi phòng ban mình lãnh đạo, gán cho một Staff thuộc phòng ban phụ trách dịch vụ, gán lại (reassign) khi cần, đồng thời xem nhanh số lượng hồ sơ đang chờ và quá hạn để điều phối công việc.

**Why this priority**: Phân công là bước khởi đầu của mọi xử lý hồ sơ; không có staff được gán thì Staff không có quyền xử lý (quyền xử lý bám theo `assigned_staff_id`). Đây cũng là nơi xác lập rào cản authorization theo phạm vi phòng ban cho toàn bộ feature.

**Independent Test**: Tạo một hồ sơ `received` của service thuộc phòng ban do Manager A lãnh đạo; đăng nhập Manager A gán cho Staff S thuộc phòng ban đó, xác nhận `assigned_staff_id` cập nhật, có bản ghi `application_assignments` và trạng thái vẫn `received`; gán lại cho Staff S2 và xác nhận assignment cũ bị đóng (`ended_at`); đăng nhập một Staff khác và xác nhận không gán được (403).

**Acceptance Scenarios**:

1. **Given** một hồ sơ đang ở `received` thuộc phạm vi quản lý của Manager, **When** Manager gán hồ sơ cho một Staff đang hoạt động thuộc phòng ban phụ trách dịch vụ, **Then** `assigned_staff_id` được cập nhật, một bản ghi `application_assignments` được tạo (staff, assigned_by, assigned_at, note) và trạng thái hồ sơ không đổi.
2. **Given** hồ sơ đã được gán cho Staff S, **When** Manager gán lại cho Staff S2, **Then** assignment của S được đóng (`ended_at` được gán), một assignment mới cho S2 được tạo và `assigned_staff_id` trỏ về S2.
3. **Given** một hồ sơ ở trạng thái cuối (`approved`/`rejected`), **When** Manager cố gán hoặc gán lại, **Then** hệ thống từ chối (422) và không thay đổi dữ liệu.
4. **Given** một Staff (không phải Manager/Super Admin), **When** cố gán hồ sơ, **Then** hệ thống từ chối (403).
5. **Given** một Manager lãnh đạo phòng ban khác (không phải phòng ban phụ trách dịch vụ), **When** truy cập danh sách hồ sơ để gán, **Then** không thấy hồ sơ của phòng ban khác; Super Admin thấy và gán được tất cả.
6. **Given** Manager gán một Staff không thuộc phòng ban phụ trách dịch vụ, **When** gửi yêu cầu, **Then** hệ thống từ chối (422) và không lưu assignment.
7. **Given** Manager mở Assignment Board, **When** xem tổng quan, **Then** thấy số lượng hồ sơ đang chờ xử lý (pending) và số lượng quá hạn (overdue) theo `processing_time_days` của dịch vụ.

---

### User Story 2 - Staff tiếp nhận và xử lý hồ sơ (Priority: P1)

Staff xử lý hồ sơ thuộc phạm vi của mình: nhận hồ sơ chưa có ai phụ trách (claim), bắt đầu xử lý, và kết thúc bằng duyệt hồ sơ (kèm ghi chú kết quả và tài liệu kết quả tùy chọn) hoặc từ chối hồ sơ (kèm lý do bắt buộc). Mọi bước chuyển trạng thái đều được hệ thống xác nhận hợp lệ, ghi lịch sử và chống chuyển đồng thời.

**Why this priority**: Đây là lõi nghiệp vụ của feature — mô phỏng chính xác hành vi của cán bộ xử lý hồ sơ, đồng thời là nơi đặt các ràng buộc chuyển trạng thái và transaction quan trọng nhất của hệ thống.

**Independent Test**: Gán hồ sơ cho Staff S, đăng nhập S, claim một hồ sơ khác chưa gán, start processing và xác nhận `processing_started_at` + history; approve hồ sơ kèm `result_note` và xác nhận `completed_at` + trạng thái `approved`; tạo hồ sơ khác rồi reject thiếu `rejection_reason` và xác nhận bị từ chối (422); thử chuyển `received → approved` và xác nhận bị từ chối (422).

**Acceptance Scenarios**:

1. **Given** một hồ sơ chưa có `assigned_staff_id` thuộc phòng ban phụ trách, **When** Staff thuộc phòng ban đó claim hồ sơ, **Then** staff trở thành người xử lý (`assigned_staff_id` = staff, `assigned_by` = chính staff) và trạng thái không đổi.
2. **Given** hồ sơ `received` đã được gán cho Staff S, **When** Staff S bắt đầu xử lý, **Then** trạng thái chuyển `received → processing`, `processing_started_at` được gán (nếu chưa có) và lịch sử được ghi.
3. **Given** hồ sơ `processing` thuộc Staff S, **When** Staff S duyệt hồ sơ, **Then** trạng thái chuyển `processing → approved`, `completed_at` và `result_note` (tùy chọn) được lưu, lịch sử được ghi; tài liệu kết quả (`document_kind=result`) được đính kèm nếu có.
4. **Given** hồ sơ `processing` thuộc Staff S, **When** Staff S từ chối hồ sơ, **Then** trạng thái chuyển `processing → rejected`, `rejection_reason` được lưu (bắt buộc, thiếu → 422), `completed_at` được gán và lịch sử được ghi.
5. **Given** một Staff S2 không được gán hồ sơ, **When** S2 cố bắt đầu xử lý/duyệt/từ chối hồ sơ đó, **Then** hệ thống từ chối (403) và không thay đổi dữ liệu.
6. **Given** một chuyển trạng thái không hợp lệ (ví dụ `received → approved`, `approved → processing`, `supplement_required → rejected`), **When** thực hiện, **Then** hệ thống từ chối (422) và không thay đổi dữ liệu.
7. **Given** hai Staff cùng gửi yêu cầu chuyển trạng thái trên cùng một hồ sơ tại cùng thời điểm, **When** cả hai được xử lý, **Then** chỉ một yêu cầu thành công, yêu cầu còn lại bị từ chối và lịch sử không bị mất hoặc trùng.

---

### User Story 3 - Yêu cầu bổ sung & Citizen nộp tài liệu bổ sung (Priority: P1)

Staff phát hiện hồ sơ thiếu giấy tờ và yêu cầu Citizen bổ sung kèm lý do. Citizen thấy rõ hồ sơ đang cần bổ sung những tài liệu nào (theo từng requirement), upload các tài liệu bổ sung cho đúng các slot còn thiếu mà không thể sửa tài liệu đã nộp. Staff sau đó tiếp tục xử lý.

**Why this priority**: Vòng lặp bổ sung là tình huống thực tế phổ biến nhất trong xử lý hồ sơ công và hoàn thiện workflow theo đúng mô tả nghiệp vụ. F04 đã chuẩn bị hạ tầng (`document_kind=supplement`, policy upload khi `supplement_required`) nhưng chủ động để phần trải nghiệm này lại cho F05.

**Independent Test**: Tạo hồ sơ thiếu một tài liệu bắt buộc, gán cho Staff S; S bắt đầu xử lý rồi yêu cầu bổ sung kèm lý do; đăng nhập Citizen chủ hồ sơ, xác nhận thấy yêu cầu + slot requirement còn thiếu, upload PDF hợp lệ và xác nhận `document_kind=supplement`; đăng nhập S, tiếp tục xử lý và xác nhận trạng thái `processing`; xác nhận Citizen không sửa được tài liệu nộp trước.

**Acceptance Scenarios**:

1. **Given** hồ sơ `processing` thuộc Staff S còn thiếu tài liệu bắt buộc, **When** S yêu cầu bổ sung, **Then** trạng thái chuyển `processing → supplement_required`, ghi chú/lý do bắt buộc được lưu vào lịch sử và Citizen thấy yêu cầu kèm danh sách tài liệu còn thiếu.
2. **Given** hồ sơ ở `supplement_required`, **When** Citizen chủ hồ sơ mở chi tiết, **Then** thấy cảnh báo yêu cầu bổ sung, phần "Tải thêm" hiển thị đúng các slot requirement còn thiếu (kèm label và loại file chấp nhận).
3. **Given** Citizen upload tài liệu vào slot hợp lệ khi `supplement_required`, **When** gửi lên, **Then** tài liệu được lưu với `document_kind=supplement` và `requirement_code` đúng; không thể sửa/xóa tài liệu đã nộp trước đó.
4. **Given** hồ sơ ở `approved` hoặc `rejected`, **When** Citizen cố upload tài liệu bổ sung, **Then** hệ thống từ chối (403) và không thay đổi dữ liệu.
5. **Given** hồ sơ ở `supplement_required` đã có tài liệu bổ sung, **When** Staff S tiếp tục xử lý, **Then** trạng thái chuyển `supplement_required → processing`; nếu vẫn còn thiếu tài liệu bắt buộc, hệ thống vẫn cho phép chuyển nhưng trả kèm danh sách còn thiếu (soft).
6. **Given** Citizen B không sở hữu hồ sơ, **When** B cố upload tài liệu bổ sung, **Then** hệ thống từ chối (403).

---

### User Story 4 - Citizen xem tiến độ xử lý và kết quả (Priority: P1)

Citizen theo dõi toàn bộ vòng đời hồ sơ của mình qua timeline trạng thái (ai xử lý, khi nào, ghi chú gì) và nhận kết quả cuối cùng: ghi chú kết quả khi được duyệt hoặc lý do từ chối khi bị từ chối, kèm thời điểm hoàn thành và tài liệu kết quả (nếu có).

**Why this priority**: Nhận kết quả là giá trị cuối cùng citizen nhận được sau toàn bộ quy trình; timeline giúp citizen hiểu hồ sơ đang ở đâu. Đây đồng thời là điểm kiểm tra authorization: thông tin xử lý là dữ liệu cá nhân, chỉ chủ hồ sơ và người nội bộ có quyền xem.

**Independent Test**: Duyệt một hồ sơ kèm `result_note` + tài liệu kết quả, đăng nhập Citizen chủ hồ sơ, xác nhận thấy timeline đầy đủ (received → processing → approved), `result_note`, `completed_at` và tải được tài liệu kết quả; tạo hồ sơ bị từ chối, xác nhận Citizen thấy `rejection_reason`; đăng nhập Citizen khác và xác nhận bị từ chối (403).

**Acceptance Scenarios**:

1. **Given** một hồ sơ đã qua nhiều trạng thái, **When** Citizen chủ hồ sơ xem chi tiết, **Then** thấy timeline gồm từng trạng thái (trước/sau), người thực hiện, thời điểm và ghi chú theo đúng thứ tự thời gian.
2. **Given** hồ sơ được duyệt (`approved`), **When** Citizen xem chi tiết, **Then** thấy ghi chú kết quả (`result_note`) và thời điểm hoàn thành (`completed_at`).
3. **Given** hồ sơ bị từ chối (`rejected`), **When** Citizen xem chi tiết, **Then** thấy lý do từ chối (`rejection_reason`) và thời điểm hoàn thành.
4. **Given** hồ sơ `approved` có tài liệu kết quả (`document_kind=result`), **When** Citizen chủ hồ sơ yêu cầu tải, **Then** tải được file kết quả; một Citizen khác hoặc người chưa đăng nhập bị từ chối (403/401).
5. **Given** Citizen B cố xem chi tiết hồ sơ của Citizen A, **When** truy cập bằng ID hồ sơ, **Then** hệ thống từ chối (403) và không lộ thông tin xử lý.

---

### Edge Cases

- Chuyển trạng thái đồng thời bởi nhiều staff trên cùng một hồ sơ: chỉ một chuyển thành công (row lock), chuyển còn lại bị từ chối, không mất lịch sử.
- Reassign khi hồ sơ đang xử lý: staff cũ mất quyền xử lý tiếp, staff mới được quyền; assignment cũ được đóng.
- Claim một hồ sơ đã có `assigned_staff_id`: bị từ chối.
- Reject thiếu `rejection_reason`: 422; Request Supplement thiếu note/lý do: 422.
- Yêu cầu bổ sung chuyển `received → supplement_required` (bỏ qua processing): bị từ chối (422).
- Chuyển tới đúng trạng thái hiện tại (no-op, ví dụ `processing → processing`): bị từ chối.
- Citizen upload bổ sung khi `approved`/`rejected` hoặc với `requirement_code` không thuộc service: 403/422.
- Staff đang xử lý bị vô hiệu hóa (`is_active=false`) hoặc bị xóa mềm: mọi thao tác xử lý bị chặn (403) cho tới khi được gán lại.
- Hồ sơ bị xóa mềm: chặn mọi thao tác xử lý/phân công.
- Manager gán staff không thuộc phòng ban phụ trách hoặc staff không hoạt động: 422.
- Gán/reassign hồ sơ ở trạng thái cuối (`approved`/`rejected`): 422.
- Tài liệu kết quả upload khi reject: không cho phép (result document chỉ gắn khi approve); reject chỉ có `rejection_reason`.
- Tải tài liệu kết quả của hồ sơ khác bằng cách đổi `{application}` trong URL: 404 do ràng buộc phạm vi.

## Requirements *(mandatory)*

### Functional Requirements

#### Phân công & phạm vi xử lý

- **FR-001**: System MUST cho phép Manager hoặc Super Admin gán hồ sơ chưa ở trạng thái cuối cho một Staff đang hoạt động thuộc phòng ban phụ trách dịch vụ của hồ sơ.
- **FR-002**: System MUST ghi mỗi lần phân công vào `application_assignments` gồm `staff_id`, `assigned_by`, `assigned_at`, `note` (tùy chọn) và đồng thời cập nhật `applications.assigned_staff_id` trong cùng một transaction.
- **FR-003**: System MUST hỗ trợ gán lại (reassign): đóng assignment hiện tại (`ended_at` được gán), tạo assignment mới và cập nhật `assigned_staff_id`.
- **FR-004**: System MUST cho phép một Staff đang hoạt động "nhận hồ sơ" (claim) một hồ sơ chưa có `assigned_staff_id` thuộc phòng ban phụ trách dịch vụ; `assigned_by` bằng chính staff đó.
- **FR-005**: System MUST chặn gán/claim/reassign khi hồ sơ ở trạng thái cuối (`approved`, `rejected`).
- **FR-006**: System MUST giới hạn phạm vi của Manager theo các phòng ban mà manager lãnh đạo (`Department.leader_id`); Super Admin không bị giới hạn.
- **FR-007**: System MUST chỉ chọn được Staff thuộc phòng ban phụ trách dịch vụ của hồ sơ làm ứng viên gán; staff khác bị từ chối (422).

#### Chuyển trạng thái & transaction

- **FR-008**: System MUST chỉ cho phép các chuyển trạng thái: `received → processing`, `processing → supplement_required`, `supplement_required → processing`, `processing → approved`, `processing → rejected`; mọi chuyển khác (kể cả no-op và chuyển từ trạng thái cuối) bị từ chối (422).
- **FR-009**: System MUST thực hiện mỗi chuyển trạng thái trong một transaction có khóa hàng trên bản ghi hồ sơ, để hai yêu cầu đồng thời chỉ một thành công.
- **FR-010**: System MUST ghi mỗi chuyển trạng thái vào `application_status_histories` dạng append-only (`from_status`, `to_status`, `changed_by`, `note`, `created_at`) và cập nhật `applications.status` trong cùng transaction.
- **FR-011**: System MUST chỉ cho phép staff được gán (`assigned_staff_id`) thực hiện các thao tác xử lý (start processing, request supplement, resume, approve, reject); Super Admin được phép như quyền override; mọi actor khác bị từ chối (403).
- **FR-012**: Start processing (`received → processing`) MUST gán `processing_started_at` nếu chưa có.
- **FR-013**: Request supplement (`processing → supplement_required`) MUST bắt buộc có ghi chú/lý do.
- **FR-014**: Resume processing (`supplement_required → processing`) MUST được phép kể cả khi tài liệu bắt buộc vẫn còn thiếu (soft), đồng thời trả về danh sách tài liệu còn thiếu.
- **FR-015**: Approve (`processing → approved`) MUST lưu `result_note` (tùy chọn) và gán `completed_at`; có thể kèm tài liệu kết quả (`document_kind=result`).
- **FR-016**: Reject (`processing → rejected`) MUST bắt buộc `rejection_reason` (thiếu → 422) và gán `completed_at`; không cho đính kèm tài liệu kết quả.
- **FR-017**: System MUST chặn mọi thao tác xử lý/phân công khi hồ sơ bị xóa mềm hoặc người thực hiện không còn hoạt động.

#### Citizen bổ sung & xem kết quả

- **FR-018**: System MUST hiển thị cho Citizen chủ hồ sơ yêu cầu bổ sung gồm ghi chú của staff và danh sách tài liệu bắt buộc còn thiếu (theo `requirement_code`/`label`) khi hồ sơ ở `supplement_required`.
- **FR-019**: System MUST cho phép Citizen chủ hồ sơ upload tài liệu bổ sung (`document_kind=supplement`) cho các slot requirement còn thiếu khi hồ sơ ở `supplement_required`; không được sửa/xóa tài liệu đã nộp.
- **FR-020**: System MUST chặn upload tài liệu (submission/supplement) khi hồ sơ ở `approved` hoặc `rejected` (403).
- **FR-021**: System MUST hiển thị cho Citizen chủ hồ sơ timeline đầy đủ các trạng thái (trước/sau, người thực hiện, thời điểm, ghi chú) theo thứ tự thời gian.
- **FR-022**: System MUST hiển thị cho Citizen chủ hồ sơ kết quả: `result_note` khi `approved`, `rejection_reason` khi `rejected`, kèm `completed_at`.
- **FR-023**: System MUST cho phép Citizen chủ hồ sơ tải tài liệu kết quả (`document_kind=result`) của hồ sơ mình; người khác bị từ chối (403), người chưa đăng nhập bị yêu cầu đăng nhập (401).

#### Dashboard quản lý

- **FR-024**: System MUST cung cấp cho Manager/Super Admin tổng quan số hồ sơ đang chờ xử lý (pending: `received`/`processing`/`supplement_required`) và số hồ sơ quá hạn (overdue: chưa hoàn thành quá `processing_time_days` của dịch vụ kể từ `submitted_at`) trong phạm vi quản lý.

#### Admin UI — hiển thị, hướng dẫn & preview tài liệu

- **FR-025**: System MUST hiển thị rõ ràng trên trang chi tiết hồ sơ (Admin) ghi chú yêu cầu bổ sung của staff và danh sách tài liệu bắt buộc còn thiếu (theo `requirement_code`/`label`) khi hồ sơ ở `supplement_required` — dưới dạng khối cảnh báo nổi bật, không chỉ ẩn trong timeline.
- **FR-026**: System MUST hiển thị trên trang chi tiết hồ sơ (Admin) hướng dẫn "bước tiếp theo" theo trạng thái hiện tại và quyền của actor: chỉ bày nút hành động hợp lệ kế tiếp (vd `received` → Nhận/Bắt đầu xử lý; `processing` → Yêu cầu bổ sung/Duyệt/Từ chối), các nút không áp dụng ở trạng thái hiện tại bị ẩn (hoặc vô hiệu kèm lý do) — không hiển thị bừa bãi mọi nút.
- **FR-027**: System MUST cho phép Staff/Manager/Super Admin xem trước (preview) tài liệu đính kèm (PDF/image) ngay trong trang chi tiết hồ sơ mà không cần tải xuống; nút tải xuống vẫn giữ để lưu file.
- **FR-028**: System MUST render worklist (`index`) và chi tiết (`show`) không bị tràn/lệch chữ khỏi card ở mọi kích thước màn hình (văn bản dài được wrap/truncate đúng chỗ); hiển thị chính xác trạng thái, badge quá hạn, người xử lý và phân nhóm "Hồ sơ của tôi" (assigned) vs "Có thể nhận" (claimable) cho Staff.

### Key Entities *(include if feature involves data)*

- **Application**: Hồ sơ dịch vụ công; là trung tâm của workflow. Trạng thái (`received`/`processing`/`supplement_required`/`approved`/`rejected`) được chuyển theo tập chuyển hợp lệ duy nhất; `assigned_staff_id` xác định staff duy nhất có quyền xử lý; các cột `processing_started_at`, `completed_at`, `result_note`, `rejection_reason` được cập nhật theo từng bước xử lý.
- **ApplicationAssignment**: Bản ghi lịch sử phân công theo hướng append-only (staff, phòng ban, người gán, thời điểm gán, thời điểm kết thúc, ghi chú). Xác định phạm vi quyền xử lý của staff và phục vụ kiểm toán các giai đoạn phân công.
- **ApplicationStatusHistory**: Dòng thời gian chuyển trạng thái append-only (from/to, người thực hiện, ghi chú, thời điểm); là nguồn duy nhất cho timeline Citizen xem và kiểm toán.
- **ApplicationDocument**: Tài liệu của hồ sơ; trong F05 thêm vai trò tài liệu kết quả (`document_kind=result`) do staff đính kèm khi duyệt và tài liệu bổ sung (`document_kind=supplement`) do citizen nộp khi `supplement_required`; vẫn chịu các lock theo trạng thái nghiệp vụ.
- **User**: Thực thể thực hiện; vai trò quyết định quyền — Staff xử lý theo `assigned_staff_id`, Manager lãnh đạo phòng ban, Super Admin override.
- **ServiceType**: Xác định phòng ban phụ trách (`responsible_department_id`) để tính phạm vi gán và `processing_time_days` để tính hồ sơ quá hạn.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% phân công/claim/reassign hợp lệ cập nhật đúng `assigned_staff_id` và ghi đủ `application_assignments`; 100% phân công không hợp lệ (staff sai phạm vi, hồ sơ ở trạng thái cuối, actor sai vai trò) bị từ chối và không làm thay đổi dữ liệu.
- **SC-002**: 100% chuyển trạng thái hợp lệ được thực hiện và ghi lịch sử chính xác; 100% chuyển trạng thái không hợp lệ hoặc no-op bị từ chối (422) và không làm thay đổi dữ liệu.
- **SC-003**: 100% thao tác xử lý chỉ thành công khi do chính staff được gán hoặc Super Admin thực hiện; staff khác, Manager chưa được gán và Citizen luôn bị từ chối (403).
- **SC-004**: 100% request supplement có lý do và 100% reject có `rejection_reason`; 100% approve ghi `completed_at` (và `result_note` nếu có).
- **SC-005**: 100% hồ sơ được duyệt/từ chối có `completed_at` và không còn thao tác xử lý/phân công nào áp dụng được; 100% yêu cầu đồng thời trên cùng hồ sơ chỉ một thành công, lịch sử không mất/trùng.
- **SC-006**: 100% Citizen chủ hồ sơ thấy đúng timeline, yêu cầu bổ sung (ghi chú + tài liệu thiếu) và kết quả (`result_note`/`rejection_reason`/`completed_at`); 100% người khác bị từ chối (403).
- **SC-007**: 100% tài liệu bổ sung (`document_kind=supplement`) của citizen nộp đúng slot requirement còn thiếu khi `supplement_required`; 100% upload khi `approved`/`rejected` bị từ chối (403).
- **SC-008**: 100% tài liệu kết quả (`document_kind=result`) được chủ hồ sơ và staff có quyền tải; citizen khác/người chưa đăng nhập bị từ chối (403/401).
- **SC-009**: Manager/Super Admin thấy chính xác số hồ sơ pending và overdue trong phạm vi quản lý; dữ liệu khớp với trạng thái thực tế.
- **SC-010**: Bộ kiểm thử tự động cho toàn bộ workflow (assign → process → supplement → resume → approve/reject) cùng luồng citizen bổ sung/xem kết quả và các ca từ chối/phân quyền đạt 100% thông qua.

## Assumptions

- Thư mục spec dùng mã feature ticket tiếp nối dãy F01–F04 (98883–98886) → **98887**; các nhánh khi triển khai đặt theo mã từng task (ví dụ `task/99475-admin-processing-api`), đúng quy ước `docs/workflow.md` và `REDMINE.md`.
- Phạm vi staff đủ điều kiện gán/nhận hồ sơ = Staff đang hoạt động (`is_active=true`, chưa bị xóa mềm) thuộc phòng ban phụ trách dịch vụ của hồ sơ (`Department` qua `responsible_department_id`). Pivot `service_staff` tồn tại trong DB nhưng chưa được F04 quản lý nên không dùng làm nguồn phạm vi trong F05.
- Phạm vi Manager = các phòng ban do manager lãnh đạo (`Department.leader_id`); Super Admin không bị giới hạn. Manager muốn trực tiếp xử lý phải gán hồ sơ cho chính mình (hoặc dùng quyền Super Admin nếu có).
- Approve: `result_note` và tài liệu kết quả là tùy chọn; không bắt buộc hồ sơ duyệt phải có kết quả dạng file.
- Reject: bắt buộc `rejection_reason`; không đính kèm tài liệu kết quả khi từ chối.
- Resume processing sau khi bổ sung: cho phép kể cả khi tài liệu bắt buộc còn thiếu (soft validation, nhất quán với F04); hệ thống trả kèm danh sách còn thiếu để staff cân nhắc.
- `approved` và `rejected` là trạng thái cuối (terminal); không cho phép bất kỳ chuyển tiếp nào từ hai trạng thái này.
- Overdue trong dashboard Manager = hồ sơ chưa hoàn thành (`completed_at` null) quá `processing_time_days` của dịch vụ kể từ `submitted_at`.
- Khi `supplement_required`, citizen chỉ được upload tài liệu `document_kind=supplement` cho slot còn thiếu, tái sử dụng hạ tầng đã chuẩn bị ở F04 Increment 2 (policy, `requirement_code`, mime/size theo type).
- Thông báo (notification/email) cho citizen khi trạng thái thay đổi **ngoài phạm vi** F05 (thuộc F06). F05 chỉ hiển thị trạng thái trong giao diện.
- Mọi thao tác xử lý/phân công chạy qua luồng Admin (Blade SSR, form POST có CSRF) hoặc API Citizen (Sanctum); không có endpoint công khai không xác thực.
- Phụ thuộc: hồ sơ và tài liệu (F04), cấu trúc phòng ban (F03), xác thực nội bộ (F01) đã sẵn sàng.