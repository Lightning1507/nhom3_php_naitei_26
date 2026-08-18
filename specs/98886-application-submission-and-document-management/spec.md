# Feature Specification: F04 - Application Submission & Document Management

**Feature Branch**: `task/98886-application-submission-and-document-management`

**Created**: 2026-08-18

**Status**: Draft

**Input**: User description: "F04 – Nộp hồ sơ dịch vụ công trực tuyến. Citizen chọn service từ catalog, nhập `form_data` theo `form_schema` động của service, đính kèm tài liệu (PDF/ảnh, giới hạn dung lượng, lưu private), hệ thống sinh mã hồ sơ `HS-YYYYMMDD-xxxxx`, xem danh sách/chi tiết hồ sơ và bảo vệ quyền sở hữu bằng Policy. Tài liệu đính kèm: upload/download có authorization (chỉ chủ hồ sơ và Staff/Manager/Super Admin), xóa mềm khi hồ sơ chưa được xử lý."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Citizen nộp hồ sơ dịch vụ công (Priority: P1)

Citizen sau khi tìm thấy dịch vụ phù hợp trong catalog cần tạo hồ sơ: chọn dịch vụ, nhập thông tin theo biểu mẫu yêu cầu, và hệ thống xác nhận tiếp nhận bằng một mã hồ sơ duy nhất để citizen theo dõi quá trình xử lý.

**Why this priority**: Nộp hồ sơ là hành động cốt lõi nhất của hệ thống; tất cả các feature xử lý hồ sơ sau này đều bắt đầu từ một hồ sơ được tạo hợp lệ và có mã nhận diện duy nhất.

**Independent Test**: Đăng nhập bằng Citizen, chọn một dịch vụ đang hoạt động, nhập đầy đủ trường bắt buộc theo biểu mẫu của dịch vụ, gửi hồ sơ và xác nhận nhận được mã hồ sơ định dạng `HS-YYYYMMDD-xxxxx` và trạng thái mới tiếp nhận.

**Acceptance Scenarios**:

1. **Given** citizen đã đăng nhập và có một dịch vụ đang hoạt động, **When** gửi hồ sơ với `service_type_id` hợp lệ và `form_data` đầy đủ trường bắt buộc, **Then** hệ thống tạo hồ sơ với mã duy nhất định dạng `HS-YYYYMMDD-xxxxx`, trạng thái `received` và ghi lại lịch sử chuyển trạng thái ban đầu.
2. **Given** citizen gửi hồ sơ thiếu một trường bắt buộc trong `form_schema` của dịch vụ, **When** gửi lên, **Then** hệ thống từ chối (422) và không tạo hồ sơ.
3. **Given** citizen gửi hồ sơ cho một dịch vụ không hoạt động hoặc đã bị xóa mềm, **When** gửi lên, **Then** hệ thống từ chối (422) và không tạo hồ sơ.
4. **Given** nhiều citizen nộp hồ sơ trong cùng một ngày, **When** các hồ sơ được tạo đồng thời, **Then** mỗi hồ sơ nhận một mã khác nhau, không có mã trùng lặp.
5. **Given** một tài khoản nội bộ (Staff/Manager/Super Admin), **When** cố nộp hồ sơ qua khu vực Citizen, **Then** hệ thống từ chối (403).

---

### User Story 2 - Citizen xem danh sách và chi tiết hồ sơ của mình (Priority: P1)

Citizen cần xem lại các hồ sơ đã nộp của chính mình để biết dịch vụ nào đã đăng ký, trạng thái xử lý hiện tại và các thông tin liên quan, phục vụ theo dõi trong toàn bộ vòng đời xử lý hồ sơ.

**Why this priority**: Theo dõi trạng thái là giá trị chính mà citizen nhận được sau khi nộp; đồng thời đây là điểm kiểm tra phân quyền sở hữu quan trọng vì dữ liệu hồ sơ là thông tin cá nhân của công dân.

**Independent Test**: Tạo hai hồ sơ cho hai citizen khác nhau, đăng nhập bằng citizen thứ nhất, xác nhận chỉ thấy hồ sơ của mình; truy cập chi tiết hồ sơ của citizen khác và xác nhận bị từ chối (403).

**Acceptance Scenarios**:

1. **Given** một citizen đã nộp một hoặc nhiều hồ sơ, **When** mở danh sách hồ sơ, **Then** chỉ thấy các hồ sơ của chính mình, sắp xếp theo thời gian nộp mới nhất.
2. **Given** citizen mở một hồ sơ của mình, **When** xem chi tiết, **Then** thấy mã hồ sơ, dịch vụ đăng ký, trạng thái hiện tại và thời gian nộp.
3. **Given** citizen cố xem chi tiết hồ sơ của citizen khác, **When** truy cập bằng ID hồ sơ, **Then** hệ thống từ chối (403) và không lộ bất kỳ thông tin nào của hồ sơ.

---

### User Story 3 - Citizen upload tài liệu đính kèm hồ sơ (Priority: P1)

Citizen đã có hồ sơ cần đính kèm các giấy tờ bắt buộc trước khi hồ sơ được xử lý. Người dùng chọn file PDF hoặc ảnh từ máy, hệ thống kiểm tra loại file và dung lượng, lưu tài liệu vào nơi riêng tư và ghi lại thông tin mô tả tài liệu để dùng sau này.

**Why this priority**: Tài liệu đính kèm là phần bắt buộc của hồ sơ dịch vụ công. Nếu không upload được thì citizen không thể hoàn thiện hồ sơ, nên đây là chức năng cốt lõi của F04.

**Independent Test**: Tạo một hồ sơ bằng API nộp hồ sơ, upload một file PDF hợp lệ, xác nhận tài liệu xuất hiện với đúng `mime_type`, `file_size`, `original_name`; thử upload file `.exe` và file vượt dung lượng và xác nhận bị từ chối.

**Acceptance Scenarios**:

1. **Given** citizen đã đăng nhập và có một hồ sơ của mình, **When** upload một file PDF hợp lệ trong giới hạn dung lượng, **Then** tài liệu được chấp nhận, lưu vào nơi riêng tư và trả về thông tin gồm `original_name`, `mime_type`, `file_size`.
2. **Given** citizen upload một file không phải PDF/ảnh (ví dụ `.exe`, `.zip`), **When** gửi lên, **Then** hệ thống từ chối với thông báo rõ ràng về định dạng không hợp lệ và không tạo bản ghi tài liệu.
3. **Given** citizen upload một file vượt quá giới hạn dung lượng cho phép, **When** gửi lên, **Then** hệ thống từ chối với thông báo rõ ràng và không tạo bản ghi tài liệu.
4. **Given** citizen upload vào hồ sơ của người khác, **When** gửi yêu cầu, **Then** hệ thống từ chối (403) và không thay đổi dữ liệu.

---

### User Story 4 - Tải xuống tài liệu của hồ sơ (Priority: P1)

Citizen cần xem lại hoặc tải xuống một tài liệu đã đính kèm trong hồ sơ của mình để kiểm tra thông tin trước/trong quá trình xử lý. Staff, Manager và Super Admin cũng được tải tài liệu của hồ sơ phục vụ xử lý công việc.

**Why this priority**: Download là thao tác bổ trợ cần thiết sau upload; phân quyền tải xuống là điểm kiểm tra authorization quan trọng vì tài liệu chứa dữ liệu cá nhân của công dân.

**Independent Test**: Upload một tài liệu vào hồ sơ, tải xuống bằng chính citizen sở hữu và xác nhận nhận đúng file; tải bằng Staff/Manager/Super Admin và xác nhận thành công; thử tải bằng một citizen khác và xác nhận bị từ chối (403).

**Acceptance Scenarios**:

1. **Given** citizen sở hữu hồ sơ có chứa một tài liệu, **When** yêu cầu tải tài liệu đó, **Then** nhận được file đúng nội dung với tên gốc (`original_name`) và loại nội dung chính xác.
2. **Given** một Staff, Manager hoặc Super Admin yêu cầu tải tài liệu của một hồ sơ, **When** gửi yêu cầu, **Then** hệ thống cho tải xuống thành công.
3. **Given** một citizen khác (không sở hữu hồ sơ) yêu cầu tải tài liệu, **When** gửi yêu cầu, **Then** hệ thống từ chối (403).
4. **Given** người dùng chưa đăng nhập yêu cầu tải tài liệu, **When** gửi yêu cầu, **Then** hệ thống yêu cầu đăng nhập và không trả file.

---

### User Story 5 - Citizen xóa tài liệu khi hồ sơ chưa được nộp xong (Priority: P2)

Citizen upload nhầm hoặc muốn thay tài liệu trước khi nộp; cần xóa tài liệu đó để không nộp kèm thông tin sai. Việc xóa chỉ được phép khi hồ sơ chưa được xử lý.

**Why this priority**: Đây là thao tác sửa chữa phục vụ trải nghiệm nhưng phải bảo vệ tính toàn vẹn hồ sơ sau khi đã nộp, nên có độ ưu tiên thấp hơn hai luồng chính.

**Independent Test**: Upload một tài liệu, xóa nó khi hồ sơ chưa xử lý, xác nhận tài liệu không còn tải xuống được; thử xóa khi hồ sơ đã chuyển sang xử lý và xác nhận bị từ chối.

**Acceptance Scenarios**:

1. **Given** hồ sơ chưa được xử lý (trạng thái `received`) và có tài liệu đã upload, **When** citizen sở hữu yêu cầu xóa tài liệu, **Then** tài liệu bị xóa mềm, không còn xuất hiện trong danh sách và không còn tải xuống được.
2. **Given** hồ sơ đã chuyển sang trạng thái xử lý hoặc cao hơn, **When** citizen cố xóa tài liệu, **Then** hệ thống từ chối (403) và tài liệu vẫn còn nguyên.
3. **Given** citizen cố xóa tài liệu của hồ sơ người khác, **When** gửi yêu cầu, **Then** hệ thống từ chối (403).

---

### Edge Cases

- File có đuôi mở rộng hợp lệ nhưng nội dung thực tế không phải PDF/ảnh: hệ thống từ chối dựa trên loại nội dung thực tế.
- File rỗng (0 byte): bị từ chối như vi phạm dung lượng/định dạng.
- File đúng bằng giới hạn dung lượng tối đa: được chấp nhận (boundary hợp lệ).
- Upload trùng tên file với tài liệu đã có: không bị coi là trùng lặp, mỗi lần upload tạo bản ghi riêng.
- Tải xuống một tài liệu đã bị xóa mềm: hệ thống trả về 404, không trả file.
- Tài liệu tồn tại trên hệ thống nhưng file nhị phân bị mất/thất lạc trên disk: trả về lỗi rõ ràng (404) thay vì lỗi hệ thống chung.
- Yêu cầu tài liệu của hồ sơ khác (thay đổi `{application}` trong URL): hệ thống trả về 404 do ràng buộc phạm vi, không lộ dữ liệu chéo.
- Nộp hồ sơ đồng thời cho cùng một dịch vụ trong cùng ngày: mã hồ sơ luôn duy nhất, không có mã trùng.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST cho phép citizen đã đăng nhập nộp hồ sơ cho một dịch vụ công đang hoạt động, với `form_data` được kiểm tra theo `form_schema` của dịch vụ.
- **FR-002**: System MUST tạo cho mỗi hồ sơ một mã duy nhất định dạng `HS-YYYYMMDD-xxxxx`, số thứ tự reset theo ngày, không trùng kể cả khi nộp đồng thời.
- **FR-003**: System MUST ghi lịch sử chuyển trạng thái ban đầu (trạng thái `received`) kèm người thực hiện và thời điểm.
- **FR-004**: System MUST chỉ cho citizen xem danh sách và chi tiết các hồ sơ của chính mình; hồ sơ của người khác bị từ chối (403).
- **FR-005**: System MUST cho phép citizen đã đăng nhập upload tài liệu vào hồ sơ của chính mình.
- **FR-006**: System MUST chỉ chấp nhận tài liệu thuộc định dạng PDF hoặc ảnh phổ biến (JPEG/JPG, PNG).
- **FR-007**: System MUST từ chối tài liệu vượt quá giới hạn dung lượng tối đa (xem Assumptions) kèm thông báo rõ ràng.
- **FR-008**: System MUST lưu tài liệu đã upload vào vùng lưu trữ riêng tư, không thể truy cập trực tiếp qua URL công khai.
- **FR-009**: System MUST lưu metadata của mỗi tài liệu gồm `original_name`, `mime_type`, `file_size`, hồ sơ liên kết, người upload và loại tài liệu `submission`.
- **FR-010**: System MUST cho phép tải xuống tài liệu bởi citizen sở hữu hồ sơ hoặc bởi Staff, Manager, Super Admin; mọi người khác (citizen khác, người chưa đăng nhập) bị từ chối với mã 403.
- **FR-011**: System MUST chỉ cho phép xóa (soft delete) tài liệu khi hồ sơ đang ở trạng thái chưa xử lý (`received`) và chỉ bởi citizen sở hữu.
- **FR-012**: System MUST ngăn việc tải xuống tài liệu đã bị xóa mềm.
- **FR-013**: System MUST ghi lại dấu vết xóa mềm sao cho có thể phục vụ kiểm toán (bản ghi được giữ, chỉ ẩn khỏi truy cập thường).
- **FR-014**: System MUST trả về lỗi 404/403 rõ ràng khi yêu cầu tài liệu không tồn tại, đã xóa, không thuộc hồ sơ trong URL hoặc không thuộc quyền.
- **FR-015**: System MUST trả về lỗi rõ ràng thay vì lỗi hệ thống chung khi file nhị phân của tài liệu đã bị mất trên disk.

### Key Entities *(include if feature involves data)*

- **Application**: Hồ sơ dịch vụ công do citizen tạo; gồm mã hồ sơ duy nhất `HS-YYYYMMDD-xxxxx`, citizen sở hữu, dịch vụ đăng ký, trạng thái xử lý, dữ liệu biểu mẫu và các mốc thời gian. Là đơn vị sở hữu các tài liệu; quyền truy cập xác định theo chủ sở hữu và trạng thái xử lý.
- **ApplicationStatusHistory**: Bản ghi lịch sử chuyển trạng thái của hồ sơ, gồm trạng thái trước/sau, người thực hiện và thời điểm.
- **ApplicationDocument**: Bản ghi metadata của tài liệu đính kèm một hồ sơ. Gồm liên kết hồ sơ, người upload, tên gốc, loại nội dung, dung lượng, đường dẫn lưu trữ, loại tài liệu (`submission`) và hỗ trợ xóa mềm. Thuộc về một Application.
- **ServiceType**: Dịch vụ công trong catalog mà citizen đăng ký; xác định `form_schema` để kiểm tra `form_data` và chỉ chấp nhận khi dịch vụ đang hoạt động.
- **ApplicationCodeSequence**: Bảng đếm số thứ tự mã hồ sơ theo ngày, đảm bảo mã không trùng khi nộp đồng thời.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% hồ sơ hợp lệ được tạo thành công với mã duy nhất định dạng `HS-YYYYMMDD-xxxxx` và trạng thái `received`, kể cả khi nộp đồng thời.
- **SC-002**: 100% hồ sơ thiếu trường bắt buộc hoặc đăng ký dịch vụ không hoạt động bị từ chối và không tạo bản ghi.
- **SC-003**: 100% citizen chỉ xem được danh sách/chi tiết hồ sơ của chính mình; hồ sơ của người khác luôn bị từ chối.
- **SC-004**: 100% tài liệu PDF/ảnh hợp lệ trong giới hạn dung lượng được upload thành công và metadata (`original_name`, `mime_type`, `file_size`) lưu chính xác.
- **SC-005**: 100% file không đúng định dạng hoặc vượt dung lượng bị từ chối với thông báo rõ ràng và không tạo bản ghi tài liệu.
- **SC-006**: 100% tài liệu đã upload nằm ở vùng lưu trữ riêng tư, không thể mở trực tiếp bằng URL công khai.
- **SC-007**: 100% yêu cầu tải xuống từ citizen không sở hữu hồ sơ hoặc người chưa đăng nhập bị từ chối với mã 403/401; chủ hồ sơ và Staff, Manager, Super Admin luôn tải được tài liệu.
- **SC-008**: 100% tài liệu của hồ sơ ở trạng thái `received` có thể được xóa mềm bởi chủ hồ sơ và không còn tải xuống được; tài liệu của hồ sơ đã chuyển sang xử lý không thể bị xóa.
- **SC-009**: Bộ kiểm thử tự động cho luồng nộp hồ sơ và upload/download/xóa tài liệu đạt 100% thông qua (kèm các ca từ chối và phân quyền).

## Assumptions

- Giới hạn dung lượng tối đa mỗi tài liệu mặc định là 10 MB.
- Định dạng ảnh được chấp nhận là JPEG/JPG và PNG; định dạng tài liệu là PDF. Các loại khác bị từ chối.
- Quy tắc kiểm định loại file dựa trên loại nội dung thực tế của file, không chỉ đuôi mở rộng.
- "Chưa được nộp xong" được hiểu là hồ sơ đang ở trạng thái `received` (mới tiếp nhận, chưa chuyển sang `processing`); khi hồ sơ đã chuyển sang `processing` trở lên thì không còn được xóa tài liệu.
- Staff, Manager và Super Admin được phép tải xuống tài liệu của hồ sơ trong phạm vi feature này; không giới hạn theo quyền xử lý cụ thể từng hồ sơ (việc gán xử lý hồ sơ nằm ngoài phạm vi).
- Upload được thực hiện qua API của khu vực Citizen; không có giới hạn số lượng tài liệu trên mỗi hồ sơ trong phạm vi feature này.
- Phụ thuộc: catalog dịch vụ (ServiceType) và cơ chế xác thực citizen (Sanctum) đã có sẵn từ F01/F02.
