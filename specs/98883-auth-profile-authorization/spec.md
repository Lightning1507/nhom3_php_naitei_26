# Feature Specification: F01 - Authentication, User Profile & Authorization

**Feature Branch**: `feature/98883-authentication-user-profile-authorization`

**Created**: 2026-08-11

**Status**: Draft

**Input**: User description: "Xây dựng nền tảng người dùng và phân quyền, gồm đăng ký/đăng nhập/đăng xuất cho Citizen, đăng nhập nội bộ, hồ sơ Citizen, bốn vai trò, giới hạn truy cập theo chủ sở hữu và CCCD duy nhất, không cho Citizen tự thay đổi."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Citizen đăng ký và xác thực (Priority: P1)

Là một Citizen, tôi muốn tạo tài khoản bằng thông tin định danh hợp lệ, đăng nhập và đăng xuất để sử dụng an toàn khu vực dịch vụ công dành cho người dân.

**Why this priority**: Citizen phải có danh tính đã xác thực trước khi sử dụng các chức năng nộp và theo dõi hồ sơ trong những feature tiếp theo.

**Independent Test**: Có thể kiểm thử độc lập bằng cách đăng ký một Citizen mới, đăng nhập bằng tài khoản vừa tạo, truy cập khu vực được bảo vệ rồi đăng xuất và xác nhận quyền truy cập đã bị thu hồi.

**Acceptance Scenarios**:

1. **Given** email và CCCD chưa tồn tại cùng toàn bộ thông tin bắt buộc hợp lệ, **When** Citizen đăng ký, **Then** hệ thống tạo tài khoản mang vai trò Citizen và cho phép tài khoản đăng nhập.
2. **Given** email hoặc CCCD đã thuộc một tài khoản, **When** Citizen gửi đăng ký, **Then** hệ thống từ chối tạo tài khoản trùng và hiển thị lỗi phù hợp.
3. **Given** một Citizen đang hoạt động nhập đúng thông tin xác thực, **When** đăng nhập, **Then** Citizen được truy cập khu vực Citizen và nhận diện đúng tài khoản của mình.
4. **Given** một Citizen đã đăng nhập, **When** đăng xuất, **Then** phiên xác thực hiện tại mất hiệu lực và tài nguyên được bảo vệ yêu cầu đăng nhập lại.
5. **Given** thông tin xác thực sai hoặc tài khoản không được phép hoạt động, **When** đăng nhập, **Then** hệ thống từ chối mà không tiết lộ trường thông tin nào không chính xác hoặc trạng thái nhạy cảm của tài khoản.

---

### User Story 2 - Người dùng nội bộ đăng nhập đúng khu vực (Priority: P1)

Là Staff, Manager hoặc Super Admin, tôi muốn đăng nhập vào khu vực Internal/Admin để thực hiện các chức năng đúng với vai trò được giao và không bị trộn lẫn với khu vực Citizen.

**Why this priority**: Xử lý dịch vụ công phụ thuộc vào việc nhận diện đáng tin cậy người dùng nội bộ và vai trò của họ.

**Independent Test**: Có thể kiểm thử với một tài khoản của từng vai trò nội bộ, xác nhận cả ba đăng nhập được vào khu vực Internal/Admin, Citizen bị từ chối tại khu vực này và người dùng nội bộ không được sử dụng luồng đăng nhập Citizen.

**Acceptance Scenarios**:

1. **Given** tài khoản Staff, Manager hoặc Super Admin đang hoạt động, **When** người dùng nhập đúng thông tin xác thực tại khu vực Internal/Admin, **Then** hệ thống đăng nhập và nhận diện đúng vai trò hiện tại.
2. **Given** một Citizen, **When** Citizen cố truy cập khu vực Internal/Admin, **Then** hệ thống từ chối truy cập kể cả khi Citizen đã đăng nhập hợp lệ.
3. **Given** một người dùng nội bộ, **When** người dùng cố sử dụng luồng đăng nhập dành cho Citizen, **Then** hệ thống từ chối và hướng người dùng đến đúng khu vực.
4. **Given** người dùng nội bộ đã đăng xuất, **When** sử dụng lại phiên cũ, **Then** hệ thống từ chối mọi tài nguyên được bảo vệ.

---

### User Story 3 - Citizen quản lý hồ sơ cá nhân (Priority: P2)

Là một Citizen đã đăng nhập, tôi muốn xem và cập nhật thông tin cá nhân được phép để dữ liệu liên hệ luôn chính xác, đồng thời CCCD đã đăng ký được bảo vệ khỏi thay đổi tùy ý.

**Why this priority**: Thông tin cá nhân chính xác hỗ trợ liên hệ và xử lý hồ sơ, còn CCCD ổn định bảo vệ tính nhất quán của danh tính.

**Independent Test**: Có thể kiểm thử bằng cách xem hồ sơ của một Citizen, cập nhật từng trường được phép, thử thay đổi CCCD và xác nhận hệ thống chỉ lưu các thay đổi hợp lệ không liên quan đến CCCD.

**Acceptance Scenarios**:

1. **Given** Citizen đã đăng nhập, **When** xem hồ sơ, **Then** hệ thống chỉ trả về hồ sơ gắn với tài khoản đó.
2. **Given** Citizen cung cấp dữ liệu hợp lệ cho các trường được phép, **When** cập nhật hồ sơ, **Then** hệ thống lưu và hiển thị dữ liệu mới.
3. **Given** Citizen cố thay đổi CCCD trực tiếp hoặc gửi CCCD kèm yêu cầu cập nhật hồ sơ, **When** hệ thống xử lý yêu cầu, **Then** CCCD hiện có không thay đổi và yêu cầu bị từ chối rõ ràng.
4. **Given** Citizen gửi dữ liệu hồ sơ không hợp lệ, **When** cập nhật, **Then** hệ thống không lưu thay đổi không hợp lệ và chỉ rõ trường cần sửa.

---

### User Story 4 - Thực thi ranh giới phân quyền và sở hữu (Priority: P1)

Là chủ sở hữu dữ liệu hoặc người dùng nội bộ, tôi muốn hệ thống kiểm tra vai trò, quyền hạn và quan hệ sở hữu trên mọi thao tác được bảo vệ để dữ liệu công dân không bị xem hoặc sửa trái phép.

**Why this priority**: Rò rỉ dữ liệu giữa Citizen hoặc cấp quyền nội bộ sai là rủi ro bảo mật nghiêm trọng và sẽ ảnh hưởng mọi feature nghiệp vụ về sau.

**Independent Test**: Có thể kiểm thử bằng hai Citizen và tài khoản thuộc từng vai trò, sau đó thực hiện cùng một thao tác trên dữ liệu của mình, dữ liệu của người khác và khu vực sai vai trò để xác nhận tất cả trường hợp trái phép đều bị từ chối ở phía hệ thống.

**Acceptance Scenarios**:

1. **Given** hai Citizen khác nhau, **When** Citizen thứ nhất yêu cầu xem hoặc thay đổi tài nguyên thuộc Citizen thứ hai, **Then** hệ thống từ chối mà không trả về dữ liệu của Citizen thứ hai.
2. **Given** một Citizen sở hữu tài nguyên và hành động đó được phép, **When** Citizen thao tác trên tài nguyên của mình, **Then** hệ thống cho phép theo quy tắc của tài nguyên đó.
3. **Given** người dùng chưa đăng nhập, **When** yêu cầu tài nguyên được bảo vệ, **Then** hệ thống từ chối truy cập.
4. **Given** người dùng đã đăng nhập nhưng không có vai trò hoặc quyền cần thiết, **When** thực hiện thao tác được bảo vệ, **Then** hệ thống từ chối bất kể giao diện có hiển thị thao tác hay không.

### Edge Cases

- Hai yêu cầu đăng ký đồng thời sử dụng cùng email hoặc CCCD: tối đa một tài khoản được tạo thành công.
- Email có khác biệt về chữ hoa/chữ thường hoặc khoảng trắng: hệ thống chuẩn hóa trước khi kiểm tra trùng.
- CCCD có khoảng trắng, ký tự không phải số hoặc không đủ 12 chữ số: đăng ký bị từ chối.
- Citizen gửi thêm trường vai trò trong yêu cầu đăng ký hoặc cập nhật hồ sơ: hệ thống bỏ qua hoặc từ chối và không nâng quyền.
- Vai trò của một tài khoản thay đổi trong khi đang có phiên đăng nhập: lần kiểm tra quyền tiếp theo phải dùng quyền hiện hành, không tiếp tục quyền cũ.
- Một tài khoản gửi nhiều yêu cầu đăng nhập thất bại liên tiếp: hệ thống hạn chế thử lại mà không làm lộ việc tài khoản có tồn tại hay không.
- Citizen cố truy cập hồ sơ người khác bằng cách thay đổi mã định danh trên yêu cầu: hệ thống từ chối và không trả về dữ liệu nhạy cảm.
- Phiên đã hết hạn, bị thu hồi hoặc đã đăng xuất: mọi yêu cầu được bảo vệ đều bị từ chối.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Hệ thống MUST cho phép một cá nhân tự đăng ký tài khoản Citizen bằng các thông tin bắt buộc hợp lệ.
- **FR-002**: Mỗi tài khoản MUST có đúng một vai trò hiện hành trong tập Citizen, Staff, Manager hoặc Super Admin.
- **FR-003**: Tài khoản tự đăng ký MUST luôn được gán vai trò Citizen và MUST NOT chấp nhận vai trò do người đăng ký tự cung cấp.
- **FR-004**: Email đăng nhập MUST là duy nhất sau khi được chuẩn hóa.
- **FR-005**: CCCD MUST là duy nhất trên toàn bộ hồ sơ Citizen và MUST được kiểm tra tính duy nhất ngay cả khi có đăng ký đồng thời.
- **FR-006**: CCCD đăng ký MUST gồm đúng 12 chữ số.
- **FR-007**: Hệ thống MUST bảo vệ bí mật xác thực để không thể đọc lại dưới dạng nguyên bản.
- **FR-008**: Hệ thống MUST cho phép Citizen đang hoạt động đăng nhập qua khu vực Citizen bằng thông tin xác thực hợp lệ.
- **FR-009**: Hệ thống MUST cho phép Staff, Manager và Super Admin đang hoạt động đăng nhập qua khu vực Internal/Admin bằng thông tin xác thực hợp lệ.
- **FR-010**: Hệ thống MUST ngăn Citizen đăng nhập hoặc truy cập khu vực Internal/Admin.
- **FR-011**: Hệ thống MUST ngăn Staff, Manager và Super Admin sử dụng luồng đăng nhập dành cho Citizen.
- **FR-012**: Thông báo đăng nhập thất bại MUST không tiết lộ tài khoản, email hay CCCD có tồn tại hay không.
- **FR-013**: Hệ thống MUST hạn chế các lần thử đăng nhập thất bại lặp lại và cho phép thử lại sau một khoảng thời gian phù hợp.
- **FR-014**: Hệ thống MUST cho phép người dùng đã đăng nhập đăng xuất và MUST thu hồi phiên xác thực đang dùng.
- **FR-015**: Hệ thống MUST từ chối tài nguyên được bảo vệ khi phiên không tồn tại, hết hạn, đã bị thu hồi hoặc tài khoản không còn hoạt động.
- **FR-016**: Citizen MUST có thể xem hồ sơ cá nhân của chính mình.
- **FR-017**: Hồ sơ Citizen MUST chứa tối thiểu họ tên, ngày sinh, địa chỉ liên hệ, số điện thoại, email và CCCD.
- **FR-018**: Citizen MUST có thể cập nhật họ tên, ngày sinh, địa chỉ liên hệ và số điện thoại khi dữ liệu mới hợp lệ.
- **FR-019**: Citizen MUST NOT tự thay đổi CCCD sau khi tài khoản được tạo; mọi yêu cầu trực tiếp hoặc gián tiếp thay đổi CCCD MUST bị từ chối.
- **FR-020**: Việc thay đổi email đăng nhập, sửa CCCD bởi người có thẩm quyền và quy trình khôi phục mật khẩu MUST nằm ngoài phạm vi F01 cho đến khi được đặc tả trong feature riêng.
- **FR-021**: Mọi thao tác được bảo vệ MUST được kiểm tra quyền ở phía hệ thống dựa trên trạng thái xác thực, vai trò hiện hành và quan hệ sở hữu khi có áp dụng.
- **FR-022**: Citizen MUST chỉ xem hoặc thay đổi dữ liệu thuộc chính mình, trừ khi một quy tắc nghiệp vụ được đặc tả rõ ràng cho phép khác đi.
- **FR-023**: Quyền truy cập MUST bị từ chối mặc định nếu không có quy tắc rõ ràng cho phép thao tác.
- **FR-024**: Việc ẩn hoặc hiện chức năng trên giao diện MUST NOT được xem là biện pháp phân quyền đầy đủ.
- **FR-025**: Hệ thống MUST áp dụng quyền hiện hành của tài khoản trong mỗi lần kiểm tra thao tác được bảo vệ.
- **FR-026**: Hệ thống MUST ghi nhận tối thiểu thời điểm, tài khoản và kết quả của các sự kiện đăng nhập, đăng xuất và từ chối truy cập quan trọng để phục vụ kiểm tra bảo mật.
- **FR-027**: Dữ liệu xác thực và hồ sơ không hợp lệ MUST không được lưu một phần.
- **FR-028**: Phản hồi cho khu vực Citizen MUST có cấu trúc nhất quán cho kết quả thành công, lỗi xác thực, lỗi kiểm tra dữ liệu và lỗi phân quyền.

### Key Entities *(include if feature involves data)*

- **User Account**: Danh tính có thể xác thực; gồm email đăng nhập duy nhất, bí mật xác thực được bảo vệ, vai trò hiện hành, trạng thái hoạt động và các mốc thời gian liên quan.
- **Citizen Profile**: Thông tin cá nhân gắn một-một với tài khoản Citizen; gồm họ tên, ngày sinh, địa chỉ liên hệ, số điện thoại và CCCD duy nhất, bất biến đối với Citizen.
- **Role**: Một trong bốn mức Citizen, Staff, Manager hoặc Super Admin, dùng để xác định khu vực và nhóm thao tác người dùng có thể truy cập.
- **Authentication Session**: Bằng chứng đăng nhập tạm thời gắn với một tài khoản, có vòng đời hoạt động, hết hạn hoặc bị thu hồi.
- **Security Event**: Bản ghi truy vết một sự kiện xác thực hoặc phân quyền quan trọng, gồm tác nhân, loại sự kiện, thời điểm và kết quả.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Citizen có dữ liệu hợp lệ hoàn tất đăng ký và đăng nhập lần đầu trong vòng 3 phút.
- **SC-002**: 100% trường hợp đăng ký trùng email hoặc CCCD, kể cả hai yêu cầu đồng thời, chỉ tạo tối đa một tài khoản.
- **SC-003**: 100% kịch bản kiểm thử truy cập chéo giữa hai Citizen đều bị từ chối mà không làm lộ dữ liệu của người còn lại.
- **SC-004**: 100% kịch bản kiểm thử sai khu vực hoặc thiếu quyền đối với bốn vai trò đều bị từ chối ở phía hệ thống.
- **SC-005**: 100% yêu cầu cập nhật hồ sơ của Citizen có chứa thay đổi CCCD không làm thay đổi CCCD đã lưu.
- **SC-006**: Ít nhất 95% người dùng thử nghiệm hoàn tất đăng nhập đúng khu vực ngay trong lần thử đầu tiên khi được cung cấp thông tin xác thực hợp lệ.
- **SC-007**: Người dùng nhận được kết quả của thao tác đăng nhập, đăng xuất, xem hồ sơ hoặc cập nhật hồ sơ trong không quá 2 giây ở điều kiện vận hành thông thường.
- **SC-008**: 100% sự kiện đăng nhập, đăng xuất và từ chối truy cập thuộc danh sách bắt buộc đều có bản ghi truy vết với đủ tác nhân, thời điểm và kết quả.

## Assumptions

- Citizen sử dụng email và mật khẩu để đăng nhập; email được chuẩn hóa không phân biệt chữ hoa/chữ thường.
- CCCD là bắt buộc khi đăng ký Citizen và có đúng 12 chữ số theo định dạng định danh công dân được dự án sử dụng.
- Các tài khoản Staff, Manager và Super Admin được cấp sẵn bởi quy trình quản trị; giao diện tạo, sửa, khóa hoặc gán vai trò cho tài khoản nội bộ không thuộc F01.
- Sau đăng ký thành công, Citizen có thể đăng nhập ngay; xác minh email, xác minh số điện thoại và khôi phục mật khẩu sẽ được đặc tả ở feature khác nếu cần.
- Citizen có thể sửa họ tên, ngày sinh, địa chỉ liên hệ và số điện thoại; thay đổi email đăng nhập và quy trình chỉnh sửa CCCD bởi người có thẩm quyền không thuộc F01.
- F01 thiết lập nguyên tắc phân quyền theo vai trò và chủ sở hữu. Quyền chi tiết trên Application, Document, Service và các đối tượng nghiệp vụ khác sẽ do feature sở hữu đối tượng đó đặc tả.
- Hệ thống có một nguồn thời gian tin cậy để xác định hết hạn phiên và ghi nhận sự kiện bảo mật.
