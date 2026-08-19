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

### User Story 6 - Admin cấu hình tài liệu yêu cầu theo từng service (Priority: P1)

Admin khi tạo/sửa một dịch vụ công khai danh sách các **requirement tài liệu minh chứng**: nhãn hiển thị (`label`), bắt buộc hay không (`required`), và loại file chấp nhận (`type`). Hệ thống tự sinh mã máy đọc (`code`) duy nhất cho từng requirement. Khi citizen nộp hồ sơ, form sẽ render đúng từng slot tương ứng.

**Why this priority**: Đây là nguồn dữ liệu quyết định tính "động theo service" của form nộp tài liệu; nếu admin không khai được loại/bắt buộc/type thì citizen và staff không thể xác định tài liệu cần nộp.

**Independent Test**: Admin tạo service với 2 requirement (1 bắt buộc type `pdf`, 1 tùy chọn type `image`), xác nhận service trả về `document_requirements` shape chuẩn `{code, label, required, type}` qua API; thử `type` không hợp lệ và xác nhận bị từ chối.

**Acceptance Scenarios**:

1. **Given** admin tạo/sửa service với danh sách `document_requirements`, **When** mỗi requirement có `name`/`label`, `is_required`/`required` và `type` hợp lệ, **Then** hệ thống lưu shape chuẩn `{code, label, required, type}` với `code` tự sinh duy nhất trong service.
2. **Given** admin nhập `type` không thuộc `{pdf, image, mixed}`, **When** gửi lên, **Then** hệ thống từ chối (422) và không lưu.
3. **Given** hai requirement có `name` giống nhau (sinh `code` trùng), **When** lưu service, **Then** `code` được đảm bảo unique (thêm hậu tố) để không đụng nhau khi gắn tài liệu.
4. **Given** service cũ đã có `document_requirements` shape cũ (`{name, is_required}`), **When** hệ thống chạy backfill, **Then** dữ liệu được chuẩn hoá sang shape mới với `type` mặc định `mixed` và `code` sinh lại, không mất requirement nào.

---

### User Story 7 - Citizen nộp tài liệu theo từng requirement (Priority: P1)

Citizen ở bước nộp hồ sơ thấy **một slot upload cho mỗi requirement** của service; mỗi file được gắn `requirement_code` tương ứng. Loại file chấp nhận của slot do `type` của requirement quyết định (pdf/image/mixed), không còn là một vùng upload tự do.

**Why this priority**: Ràng buộc file với requirement giúp staff (khi xem chi tiết) biết mỗi file chứng minh cho yêu cầu nào; là tiền đề cho luồng xử lý và yêu cầu bổ sung sau này.

**Independent Test**: Mở Apply của service có requirement `pdf` bắt buộc, upload file ảnh vào slot đó và xác nhận bị từ chối; upload PDF vào slot `pdf` và xác nhận thành công kèm `requirement_code` được lưu.

**Acceptance Scenarios**:

1. **Given** service có danh sách `document_requirements`, **When** citizen vào bước tài liệu, **Then** thấy từng slot với label, dấu `*` nếu bắt buộc và hint loại file (PDF / Ảnh / PDF hoặc Ảnh).
2. **Given** citizen upload file vào một slot, **When** file nộp lên API, **Then** yêu cầu phải kèm `requirement_code` thuộc service (nếu thiếu/sai → 422) và tài liệu được lưu với đúng `requirement_code`.
3. **Given** citizen upload file không khớp `type` của slot (`pdf` slot nhưng file ảnh), **When** gửi lên, **Then** hệ thống từ chối (422) với thông báo rõ ràng.
4. **Given** service **không** có requirement nào, **When** citizen upload, **Then** vẫn cho upload tự do không cần `requirement_code` (giữ luồng hiện tại).

---

### User Story 8 - Cảnh báo thiếu tài liệu bắt buộc (soft) và lock theo trạng thái (Priority: P1)

Citizen **vẫn được nộp** hồ sơ dù thiếu tài liệu bắt buộc; hệ thống ghi nhận danh sách thiếu (`missing_required_documents`) và hiển thị **cảnh báo đỏ** ở giao diện. Tài liệu bị **khoá theo trạng thái nghiệp vụ**: không upload khi hồ sơ đang xử lý/đã xong; không xóa khi đã được staff nhận. Staff xem chi tiết hồ sơ sẽ thấy tài liệu thiếu và yêu cầu nộp bổ sung (luồng này nằm ở feature xử lý hồ sơ sau).

**Why this priority**: Đảm bảo tính toàn vẹn hồ sơ khi staff bắt đầu xử lý (không bị mất/sửa tài liệu), đồng thời không chặn cứng công dân khỏi việc nộp khi chưa đủ giấy tờ — staff xử lý sau bằng cơ chế yêu cầu bổ sung.

**Independent Test**: Nộp hồ sơ thiếu 1 tài liệu bắt buộc và xác nhận vẫn tạo hồ sơ (201) kèm `missing_required_documents`; chuyển hồ sơ sang `processing` rồi thử upload/xóa và xác nhận bị từ chối (403).

**Acceptance Scenarios**:

1. **Given** hồ sơ đang ở `received` và thiếu một requirement bắt buộc, **When** citizen nộp, **Then** hồ sơ vẫn được tạo (201) và response kèm `missing_required_documents` liệt kê đúng `code`/`label`.
2. **Given** hồ sơ đang thiếu tài liệu bắt buộc, **When** xem chi tiết, **Then** giao diện hiện cảnh báo đỏ "Thiếu tài liệu bắt buộc: <label>" và trạng thái vẫn cho phép bổ sung khi `received`.
3. **Given** hồ sơ ở `processing`/`approved`/`rejected`, **When** citizen cố upload tài liệu, **Then** hệ thống từ chối (403) và không thay đổi dữ liệu.
4. **Given** hồ sơ ở `supplement_required`, **When** citizen upload, **Then** chỉ cho upload loại bổ sung (`document_kind=supplement`), không được sửa/xóa tài liệu nộp trước đó.
5. **Given** hồ sơ `received` nhưng đã được gán `assigned_staff_id`, **When** citizen cố xóa tài liệu, **Then** hệ thống từ chối (403) để staff không bị mất tài liệu khi đang xem.

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
- **FR-016**: System MUST lưu `document_requirements` của service theo shape chuẩn `{code, label, required, type}` với `type ∈ {pdf, image, mixed}` và `code` duy nhất trong service.
- **FR-017**: System MUST bắt buộc `requirement_code` thuộc service khi upload tài liệu vào hồ sơ của service có ≥ 1 requirement; service không có requirement thì upload không cần `requirement_code`.
- **FR-018**: System MUST dùng `type` của requirement để xác định loại file chấp nhận của slot đó (`pdf` → chỉ PDF; `image` → chỉ JPEG/JPG/PNG; `mixed` → cả hai), kết hợp giới hạn dung lượng 10 MB.
- **FR-019**: System MUST cho phép nộp hồ sơ dù thiếu tài liệu bắt buộc (soft), đồng thời trả `missing_required_documents` trong response và hiển thị cảnh báo đỏ ở giao diện citizen.
- **FR-020**: System MUST chặn upload tài liệu khi hồ sơ ở trạng thái `processing`, `approved` hoặc `rejected` (403); chỉ cho upload khi `received` hoặc `supplement_required`.
- **FR-021**: System MUST chặn xóa tài liệu khi hồ sơ đã được gán `assigned_staff_id`, kể cả khi trạng thái còn `received` (403), để bảo toàn tài liệu đang được staff xem xét.

### Key Entities *(include if feature involves data)*

- **Application**: Hồ sơ dịch vụ công do citizen tạo; gồm mã hồ sơ duy nhất `HS-YYYYMMDD-xxxxx`, citizen sở hữu, dịch vụ đăng ký, trạng thái xử lý, dữ liệu biểu mẫu và các mốc thời gian. Là đơn vị sở hữu các tài liệu; quyền truy cập xác định theo chủ sở hữu và trạng thái xử lý.
- **ApplicationStatusHistory**: Bản ghi lịch sử chuyển trạng thái của hồ sơ, gồm trạng thái trước/sau, người thực hiện và thời điểm.
- **ApplicationDocument**: Bản ghi metadata của tài liệu đính kèm một hồ sơ. Gồm liên kết hồ sơ, người upload, tên gốc, loại nội dung, dung lượng, đường dẫn lưu trữ, loại tài liệu (`submission`) và hỗ trợ xóa mềm. Thuộc về một Application. Từ Increment 2 có thêm `requirement_code` (nullable) liên kết tài liệu với một requirement của service; `document_kind` có thể là `submission` hoặc `supplement` tùy trạng thái hồ sơ.
- **ServiceType**: Dịch vụ công trong catalog mà citizen đăng ký; xác định `form_schema` để kiểm tra `form_data` và `document_requirements` (shape chuẩn `{code, label, required, type}`) để render slot upload và xác định tài liệu thiếu; chỉ chấp nhận khi dịch vụ đang hoạt động.
- **ApplicationCodeSequence**: Bảng đếm số thứ tự mã hồ sơ theo ngày, đảm bảo mã không trùng khi nộp đồng thời.
- **ServiceSchema (helper)**: Bộ chuẩn hoá dùng chung (backend + resource) để dung nạp shape cũ `{name, is_required}`/`{name, label, required}` về shape chuẩn của `form_schema` và `document_requirements`, tránh duplicate logic giữa admin và citizen.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% hồ sơ hợp lệ được tạo thành công với mã duy nhất định dạng `HS-YYYYMMDD-xxxxx` và trạng thái `received`, kể cả khi nộp đồng thời.
- **SC-002**: 100% hồ sơ thiếu trường bắt buộc hoặc đăng ký dịch vụ không hoạt động bị từ chối và không tạo bản ghi.
- **SC-003**: 100% citizen chỉ xem được danh sách/chi tiết hồ sơ của chính mình; hồ sơ của người khác luôn bị từ chối.
- **SC-004**: 100% tài liệu PDF/ảnh hợp lệ trong giới hạn dung lượng được upload thành công và metadata (`original_name`, `mime_type`, `file_size`) lưu chính xác.
- **SC-005**: 100% file không đúng định dạng hoặc vượt dung lượng bị từ chối với thông báo rõ ràng và không tạo bản ghi tài liệu.
- **SC-006**: 100% tài liệu đã upload nằm ở vùng lưu trữ riêng tư, không thể mở trực tiếp bằng URL công khai.
- **SC-007**: 100% yêu cầu tải xuống từ citizen không sở hữu hồ sơ hoặc người chưa đăng nhập bị từ chối với mã 403/401; chủ hồ sơ và Staff, Manager, Super Admin luôn tải được tài liệu.
- **SC-008**: 100% tài liệu của hồ sơ ở trạng thái `received` có thể được xóa mềm bởi chủ hồ sơ và không còn tải xuống được; tài liệu của hồ sơ đã chuyển sang xử lý hoặc đã gán staff không thể bị xóa.
- **SC-009**: Bộ kiểm thử tự động cho luồng nộp hồ sơ và upload/download/xóa tài liệu đạt 100% thông qua (kèm các ca từ chối và phân quyền).
- **SC-010**: 100% service có `document_requirements` shape chuẩn `{code, label, required, type}`; admin khai `type` không hợp lệ luôn bị từ chối (422).
- **SC-011**: 100% tài liệu upload vào hồ sơ có service có requirement đều được lưu kèm `requirement_code` hợp lệ thuộc service; file không khớp `type` của slot luôn bị từ chối.
- **SC-012**: 100% hồ sơ nộp thiếu tài liệu bắt buộc vẫn được tạo (soft) và response/giao diện hiển thị đúng `missing_required_documents`; 100% upload khi `processing`/`approved`/`rejected` và xóa khi đã gán staff bị từ chối (403).

## Assumptions

- Giới hạn dung lượng tối đa mỗi tài liệu mặc định là 10 MB.
- Định dạng ảnh được chấp nhận là JPEG/JPG và PNG; định dạng tài liệu là PDF. Các loại khác bị từ chối.
- Quy tắc kiểm định loại file dựa trên loại nội dung thực tế của file, không chỉ đuôi mở rộng.
- "Chưa được nộp xong" được hiểu là hồ sơ đang ở trạng thái `received` (mới tiếp nhận, chưa chuyển sang `processing`); khi hồ sơ đã chuyển sang `processing` trở lên hoặc đã gán `assigned_staff_id` thì không còn được xóa tài liệu.
- Staff, Manager và Super Admin được phép tải xuống tài liệu của hồ sơ trong phạm vi feature này; không giới hạn theo quyền xử lý cụ thể từng hồ sơ (việc gán xử lý hồ sơ nằm ngoài phạm vi).
- Upload được thực hiện qua API của khu vực Citizen; không có giới hạn số lượng tài liệu trên mỗi hồ sơ trong phạm vi feature này.
- Phụ thuộc: catalog dịch vụ (ServiceType) và cơ chế xác thực citizen (Sanctum) đã có sẵn từ F01/F02.
- `type` của requirement quyết định loại file chấp nhận (`pdf`/`image`/`mixed`); giới hạn dung lượng vẫn là 10 MB cho mọi loại (Increment 2).
- Việc yêu cầu nộp bổ sung (chuyển `supplement_required`) và xử lý hồ sơ của staff nằm ngoài phạm vi; Increment 2 chỉ chuẩn bị hạ tầng cho phép upload loại `supplement` khi trạng thái đó được thiết lập.
