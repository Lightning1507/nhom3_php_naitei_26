# Feature Specification: F08 - Import/Export & API Documentation

**Feature Branch**: `task/98887-import-export-api-documentation`

**Created**: 2026-08-20

**Status**: Draft

**Input**: User description: "F08 – Import/Export & API Documentation. Import Citizen/Staff bằng CSV kèm validate từng row và báo cáo lỗi; Export Citizens, Applications, Services, Departments, Staff ra CSV/Excel theo bộ lọc; Chuẩn hóa REST API /api/v1/ và tích hợp tự động tạo API Documentation (OpenAPI/Swagger/Scramble)."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Admin Import danh sách Citizen và Staff từ CSV (Priority: P1)

Admin hoặc Quản trị viên hệ thống cần nhập hàng loạt tài khoản Công dân (Citizen) hoặc Cán bộ (Staff) từ tệp dữ liệu CSV vào hệ thống để tiết kiệm thời gian khởi tạo thủ công từng tài khoản. Hệ thống phải kiểm tra tính hợp lệ của từng dòng dữ liệu và trả về báo cáo kết quả chi tiết.

**Why this priority**: Khả năng nạp dữ liệu người dùng số lượng lớn là yêu cầu thiết yếu đối với quản trị viên khi đưa hệ thống vào vận hành thực tế hoặc đồng bộ từ dữ liệu có sẵn.

**Independent Test**: Chuẩn bị 1 tệp CSV gồm 5 dòng dữ liệu (3 dòng hợp lệ, 2 dòng lỗi như sai định dạng email hoặc trùng số CCCD). Admin tải file lên, hệ thống xử lý import 3 tài khoản thành công và trả về bảng báo cáo lỗi chi tiết cho 2 dòng vi phạm kèm dòng số bao nhiêu và lý do lỗi.

**Acceptance Scenarios**:

1. **Given** Admin chọn nhập tệp CSV danh sách Citizen/Staff hợp lệ, **When** gửi tệp lên hệ thống, **Then** tất cả các bản ghi được tạo tài khoản thành công và hệ thống báo số lượng bản ghi đã import.
2. **Given** tệp CSV chứa một số dòng bị lỗi (như thiếu trường bắt buộc, email sai định dạng, CCCD trùng lặp, Phòng ban không tồn tại), **When** gửi tệp lên hệ thống, **Then** hệ thống import các dòng hợp lệ và trả về báo cáo lỗi chi tiết cho các dòng vi phạm (gồm số dòng trong CSV, giá trị bị lỗi, và nguyên nhân lỗi).
3. **Given** người dùng tải lên tệp không đúng định dạng CSV (ví dụ `.pdf`, `.docx` hoặc `.exe`), **When** tải lên, **Then** hệ thống từ chối (422) và thông báo loại tệp không hợp lệ.
4. **Given** tệp CSV có kích thước vượt quá giới hạn cho phép hoặc cấu trúc cột/tiêu đề không đúng mẫu quy định, **When** gửi lên, **Then** hệ thống từ chối và hướng dẫn cấu trúc tiêu đề chuẩn.
5. **Given** người dùng không có vai trò Admin cố gắng truy cập chức năng Import, **When** gửi yêu cầu, **Then** hệ thống từ chối truy cập (403).

---

### User Story 2 - Admin Export dữ liệu hệ thống ra tệp CSV (Priority: P1)

Admin cần xuất danh sách dữ liệu thực tế từ hệ thống (Citizens, Applications, Services, Departments, Staff) ra tệp định dạng CSV để lưu trữ, báo cáo hoặc phân tích ngoại tuyến. File xuất ra phải tôn trọng các điều kiện tìm kiếm/lọc hiện tại.

**Why this priority**: Xuất dữ liệu phục vụ báo cáo và lưu trữ là tính năng nghiệp vụ quan trọng bắt buộc có ở các hệ thống quản lý công.

**Independent Test**: Áp dụng bộ lọc trạng thái "Processing" và Phòng ban "Tài nguyên Môi trường" trên danh sách hồ sơ, sau đó nhấn "Export CSV". Mở tệp CSV nhận được và xác nhận chỉ chứa đúng các hồ sơ khớp với bộ lọc đã chọn.

**Acceptance Scenarios**:

1. **Given** Admin đang xem danh sách (Citizens, Applications, Services, Departments, hoặc Staff) có/không có bộ lọc tìm kiếm, **When** chọn yêu cầu "Export CSV", **Then** hệ thống tải về tệp CSV chứa đúng tập dữ liệu khớp với bộ lọc.
2. **Given** tệp CSV được xuất ra, **When** mở bằng các công cụ đọc văn bản/bảng tính, **Then** định dạng ký tự tiếng Việt (UTF-8) không bị lỗi font, các tiêu đề cột rõ ràng và dấu phân cách chuẩn.
3. **Given** tập dữ liệu xuất ra có số lượng lớn bản ghi (ví dụ hàng nghìn bản ghi), **When** thực hiện export, **Then** hệ thống xử lý stream/chunked mượt mà, không bị treo bộ nhớ hoặc timeout.
4. **Given** Citizen hoặc người dùng không đúng quyền cố gắng gọi endpoint Export dữ liệu quản trị, **When** gửi yêu cầu, **Then** hệ thống từ chối (403).

---

### User Story 3 - Chuẩn hóa REST API /api/v1/ cho tài nguyên hệ thống (Priority: P1)

Tích hợp và chuẩn hóa các API endpoint `/api/v1/` theo chuẩn RESTful cho các đối tượng (Services, Applications, Citizens, Departments, Staff), đảm bảo cấu trúc dữ liệu trả về (JSON Envelope), phân trang (Pagination), mã lỗi HTTP chuẩn và xác thực qua Token (Sanctum).

**Why this priority**: API chuẩn hóa giúp các ứng dụng client (Web UI, Mobile App, hệ thống bên ngoài) giao tiếp ổn định, an toàn và dễ mở rộng.

**Independent Test**: Gọi API `GET /api/v1/services` với token hợp lệ, nhận về JSON payload gồm key `data` (danh sách dịch vụ) và key `meta` (thông tin phân trang). Gọi API không kèm token xác thực đối với các endpoint bảo mật, nhận về HTTP response 401 Unauthorized.

**Acceptance Scenarios**:

1. **Given** một client gửi request tới API thuộc `/api/v1/`, **When** request thành công, **Then** response trả về mã HTTP 200/201 với cấu trúc JSON envelope nhất quán (`data`, `meta`, `links`).
2. **Given** client gửi dữ liệu không hợp lệ tới API, **When** validation thất bại, **Then** hệ thống trả về HTTP 422 với mảng `errors` chi tiết theo từng trường.
3. **Given** client gọi endpoint yêu cầu xác thực mà không cung cấp token hoặc token hết hạn, **When** gửi request, **Then** hệ thống trả về HTTP 401 Unauthorized.
4. **Given** client truy cập tài nguyên không thuộc quyền sở hữu/cho phép, **When** gửi request, **Then** hệ thống trả về HTTP 403 Forbidden.

---

### User Story 4 - Tự động tạo và hiển thị API Documentation (Priority: P2)

Nhà phát triển và bên tích hợp cần xem tài liệu giao diện lập trình API trực quan, tự động cập nhật theo code (dựa trên OpenAPI / Swagger / Scramble) tại giao diện điều hướng công khai hoặc nội bộ.

**Why this priority**: Tài liệu API giúp giảm thời gian trao đổi, hỗ trợ tích hợp hệ thống dễ dàng và đảm bảo tính minh bạch của API.

**Independent Test**: Truy cập địa chỉ `/docs/api` trên trình duyệt, giao diện Swagger/Scramble hiển thị đầy đủ danh sách endpoint `/api/v1/`, bao gồm mô tả tham số request, các mã response trả về (200, 401, 422, ...) và nút thử nghiệm gửi request.

**Acceptance Scenarios**:

1. **Given** nhà phát triển truy cập URL tài liệu API (ví dụ `/docs/api`), **When** trang tải xong, **Then** hiển thị giao diện tương tác minh họa tất cả các endpoint API khả dụng thuộc `/api/v1/`.
2. **Given** một API endpoint mới hoặc thay đổi tham số truyền vào, **When** tài liệu API được tự động sinh, **Then** thông tin mới được cập nhật chính xác trên trang tài liệu mà không cần viết tay thủ công.
3. **Given** giao diện tài liệu API, **When** người dùng sử dụng tính năng "Try it out" kèm Token hợp lệ, **Then** request thực sự được gửi tới backend và hiển thị kết quả response mẫu thực tế.

---

### Edge Cases

- **Tệp CSV Import chứa các ký tự đặc biệt hoặc dấu ngoặc kép**: Hệ thống xử lý đúng quy tắc escape ký tự của chuẩn CSV, không bị lệch cột.
- **Tệp CSV Import chứa BOM UTF-8**: Hệ thống tự động loại bỏ BOM header trước khi parse dữ liệu để tránh lỗi tên cột đầu tiên.
- **Dữ liệu Export chứa nội dung cực lớn hoặc khoảng trắng xuống dòng**: Định dạng CSV được bao quanh bởi dấu ngoặc kép để không phá vỡ cấu hình hàng/cột.
- **Client truy cập API Doc trong môi trường production**: Cấu hình phân quyền xem API Docs (ví dụ chỉ cho phép Admin hoặc môi trường dev/staging nếu cần bảo mật).
- **Import bản ghi có email/CCCD trùng ngay trong cùng 1 file CSV**: Hệ thống phát hiện trùng lặp nội bộ trong file và báo lỗi ở dòng xuất hiện sau.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST cung cấp chức năng Import tài khoản Citizen và Staff từ tệp CSV cho Admin.
- **FR-002**: System MUST validate từng dòng trong tệp CSV (kiểm tra định dạng email, CCCD 12 chữ số duy nhất, phòng ban tồn tại, vai trò hợp lệ, các trường bắt buộc).
- **FR-003**: System MUST trả về báo cáo kết quả Import gồm: tổng số dòng, số dòng thành công, số dòng thất bại kèm bảng liệt kê chi tiết (số dòng trong CSV, tên trường lỗi, nguyên nhân lỗi).
- **FR-004**: System MUST lưu các bản ghi hợp lệ và trả về báo cáo chi tiết cho các bản ghi vi phạm (hoặc hỗ trợ tùy chọn Rollback toàn bộ transaction nếu tệp có dòng lỗi dựa trên cấu hình).
- **FR-005**: System MUST cho phép Admin Export danh sách dữ liệu (Citizens, Applications, Services, Departments, Staff) ra tệp CSV.
- **FR-006**: System MUST áp dụng đúng bộ lọc/tìm kiếm hiện tại của danh sách khi thực hiện Export.
- **FR-007**: System MUST xuất tệp CSV mã hóa UTF-8 (có hỗ trợ BOM để hiển thị tiếng Việt chuẩn trên MS Excel).
- **FR-008**: System MUST chuẩn hóa các API endpoint theo chuẩn RESTful dưới prefix `/api/v1/`.
- **FR-009**: System MUST sử dụng cấu trúc JSON Envelope đồng nhất cho tất cả REST API response (`success` boolean, `message` string, `data` chứa nội dung chính, `meta` chứa thông tin phân trang/bổ sung, `errors` chứa thông tin lỗi).
- **FR-010**: System MUST bảo vệ các REST API endpoint bằng Sanctum Token authentication và phân quyền Role/Policy tương ứng với từng vai trò (Citizen, Staff, Manager, Admin).
- **FR-011**: System MUST tích hợp công cụ tự động tạo API Documentation (OpenAPI/Swagger/Scramble) tại đường dẫn quy định (ví dụ `/docs/api`).
- **FR-012**: System MUST hiển thị đầy đủ thông tin endpoints, request body schemas, query parameters, response statuses (200, 201, 400, 401, 403, 404, 422, 500) và cơ chế xác thực Bearer Token trên trang API Documentation.

### Key Entities *(include if feature involves data)*

- **ImportJobReport**: Đối tượng đại diện cho kết quả của một lượt Import CSV (gồm tổng số dòng, thành công, thất bại, thời gian thực hiện, và danh sách các lỗi theo dòng `line_number`, `field`, `message`).
- **ExportRequest**: Yêu cầu xuất dữ liệu ra file CSV chứa loại tài nguyên (`resource_type`), các tham số bộ lọc (`filters`), định dạng tệp (`csv`), và danh sách cột được chọn.
- **ApiEnvelope**: Cấu hình định dạng response chuẩn cho hệ thống API (Data Wrapper, Pagination Meta, Error Array).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% tệp CSV chứa danh sách Citizen/Staff hợp lệ được import thành công và tạo đúng tài khoản người dùng tương ứng.
- **SC-002**: 100% dòng dữ liệu không hợp lệ trong tệp CSV được phát hiện và hiển thị chính xác trong báo cáo lỗi (đúng số dòng và nguyên nhân lỗi).
- **SC-003**: 100% dữ liệu xuất ra CSV khớp chính xác với bộ lọc tìm kiếm và không bị lỗi hiển thị ký tự tiếng Việt (UTF-8).
- **SC-004**: 100% các endpoint thuộc `/api/v1/` tuân thủ đúng chuẩn RESTful JSON envelope và trả về mã HTTP status code chính xác.
- **SC-005**: 100% API bảo mật từ chối request không có token (401) hoặc không đủ thẩm quyền (403).
- **SC-006**: Trang API Documentation hiển thị đầy đủ 100% các route thuộc `/api/v1/` và cho phép thử nghiệm thành công với Bearer Token.

## Assumptions

- Tệp CSV tải lên sử dụng dấu phẩy `,` làm dấu phân cách cột mặc định và định dạng UTF-8.
- Dung lượng tệp CSV Import tối đa là 10 MB (tương đương khoảng 20.000 bản ghi).
- Công cụ tự động sinh tài liệu API (Scramble/Swagger) sẽ trích xuất thông tin trực tiếp từ FormRequests, Controllers, và API Resources trong Laravel.
- Quyền Import/Export dữ liệu quản trị chỉ dành riêng cho vai trò Super Admin / Admin.
