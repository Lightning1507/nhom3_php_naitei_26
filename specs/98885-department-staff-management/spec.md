# Feature Specification: F03 - Department & Staff Management

**Feature Branch**: `[98885-department-staff-management]`

**Created**: 2026-08-17

**Status**: Draft

**Input**: User description: "F03 quản lý cơ cấu tổ chức nội bộ: Department, Manager, Staff và quan hệ thành viên; không bao gồm xác thực, quản lý dịch vụ hay xử lý hồ sơ."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Quản lý thông tin phòng ban (Priority: P1)

Super Admin cần tạo và duy trì danh mục phòng ban để hệ thống biết các đơn vị nội bộ nào chịu trách nhiệm cung cấp dịch vụ công. Mỗi phòng ban có tên, mã nhận diện duy nhất, địa chỉ tùy chọn và có thể chưa có người lãnh đạo tại thời điểm tạo.

**Why this priority**: Phòng ban là nền tảng để tổ chức Manager và Staff. Nếu chưa có phòng ban hợp lệ thì không thể thiết lập cơ cấu nhân sự cho các feature xử lý hồ sơ sau này.

**Independent Test**: Đăng nhập bằng Super Admin, tạo một phòng ban mới, sửa thông tin, mở trang chi tiết và xác nhận thông tin được lưu chính xác mà không cần có hồ sơ dịch vụ công.

**Acceptance Scenarios**:

1. **Given** Super Admin đang ở danh sách phòng ban, **When** tạo phòng ban với tên và mã hợp lệ chưa tồn tại, **Then** phòng ban mới xuất hiện trong danh sách và có thể mở trang chi tiết.
2. **Given** một phòng ban đang hoạt động, **When** Super Admin thay đổi tên hoặc địa chỉ bằng dữ liệu hợp lệ, **Then** thông tin mới được hiển thị trong danh sách và trang chi tiết.
3. **Given** đã tồn tại phòng ban có mã `XD`, **When** Super Admin tạo hoặc sửa phòng ban khác với mã trùng `XD` hoặc chỉ khác chữ hoa/chữ thường, **Then** hệ thống từ chối và chỉ rõ mã phòng ban đã được sử dụng.
4. **Given** người dùng không phải Super Admin, **When** họ cố tạo, sửa hoặc lưu trữ phòng ban, **Then** hệ thống từ chối và không thay đổi dữ liệu.

---

### User Story 2 - Thiết lập Manager và thành viên phòng ban (Priority: P1)

Super Admin cần chọn một Manager đang hoạt động làm người lãnh đạo phòng ban và thêm các Staff hoặc Manager đang hoạt động vào phòng ban. Manager cần quản lý danh sách Staff của chính phòng ban mình lãnh đạo mà không thể tác động đến phòng ban khác.

**Why this priority**: Giá trị cốt lõi của F03 là trả lời được ai làm việc ở đâu và ai chịu trách nhiệm quản lý. Cấu trúc này là dữ liệu đầu vào bắt buộc cho việc phân công hồ sơ trong F05 và giới hạn dữ liệu trong F07.

**Independent Test**: Tạo một phòng ban, chọn Manager A làm lãnh đạo, thêm Staff B và Staff C, xác nhận trang chi tiết hiển thị đúng; sau đó xóa Staff C và xác nhận Manager A cùng Staff B vẫn giữ nguyên.

**Acceptance Scenarios**:

1. **Given** một phòng ban đang hoạt động và một tài khoản Manager đang hoạt động, **When** Super Admin chọn Manager đó làm lãnh đạo, **Then** Manager được hiển thị là lãnh đạo và đồng thời là thành viên của phòng ban.
2. **Given** một Staff đang hoạt động chưa thuộc phòng ban, **When** Super Admin hoặc Manager lãnh đạo phòng ban thêm Staff đó, **Then** Staff xuất hiện đúng một lần trong danh sách thành viên.
3. **Given** một Staff đã thuộc phòng ban, **When** người quản lý cố thêm lại Staff đó, **Then** hệ thống từ chối bản ghi trùng và giữ nguyên một quan hệ thành viên.
4. **Given** một Citizen hoặc tài khoản nội bộ không còn hoạt động, **When** người quản lý cố thêm tài khoản đó vào phòng ban hoặc chọn làm lãnh đạo, **Then** hệ thống từ chối và giải thích lý do.
5. **Given** một Manager chỉ lãnh đạo Phòng ban A, **When** Manager đó cố thay đổi thành viên của Phòng ban B, **Then** hệ thống từ chối và không thay đổi dữ liệu Phòng ban B.
6. **Given** một người đang là lãnh đạo phòng ban, **When** người quản lý cố xóa người đó khỏi danh sách thành viên mà chưa thay đổi hoặc bỏ chỉ định lãnh đạo, **Then** hệ thống từ chối để tránh cơ cấu không nhất quán.

---

### User Story 3 - Tra cứu cơ cấu tổ chức (Priority: P2)

Super Admin cần xem và tìm kiếm toàn bộ phòng ban; Manager cần xem các phòng ban mình lãnh đạo. Trang chi tiết cần thể hiện rõ lãnh đạo, thành viên và các dịch vụ đang liên kết để người dùng nội bộ hiểu cơ cấu hiện tại trước khi thao tác.

**Why this priority**: Tra cứu là điều kiện để quản trị đúng đối tượng và hỗ trợ các feature sau, nhưng chỉ có giá trị đầy đủ sau khi dữ liệu phòng ban và thành viên đã được thiết lập.

**Independent Test**: Tạo nhiều phòng ban, tìm bằng tên hoặc mã, mở một kết quả và xác nhận lãnh đạo, thành viên cùng dịch vụ liên kết được hiển thị đúng theo phạm vi quyền của người dùng.

**Acceptance Scenarios**:

1. **Given** có nhiều phòng ban đang hoạt động, **When** Super Admin mở danh sách, **Then** mỗi dòng hiển thị tối thiểu mã, tên, lãnh đạo, số thành viên và hành động phù hợp.
2. **Given** danh sách có nhiều phòng ban, **When** người dùng tìm theo một phần tên hoặc mã, **Then** chỉ các phòng ban phù hợp được hiển thị.
3. **Given** Manager A chỉ lãnh đạo Phòng ban A, **When** Manager A mở khu vực quản lý phòng ban, **Then** Manager A chỉ thấy và mở được dữ liệu thuộc phạm vi Phòng ban A.
4. **Given** phòng ban có các dịch vụ liên kết, **When** người dùng có quyền mở trang chi tiết, **Then** các dịch vụ được hiển thị chỉ để tham khảo và F03 không cung cấp thao tác sửa dịch vụ.

---

### User Story 4 - Chuyển và gỡ thành viên an toàn (Priority: P2)

Super Admin cần chuyển Staff từ phòng ban nguồn sang phòng ban đích hoặc gỡ Staff khỏi một phòng ban, đồng thời bảo đảm dữ liệu lịch sử của các nghiệp vụ khác không bị mất. Manager chỉ được gỡ hoặc điều chuyển Staff ra khỏi phòng ban mình lãnh đạo khi có quyền với cả thao tác nguồn và đích.

**Why this priority**: Nhân sự có thể thay đổi phòng ban trong quá trình vận hành. Việc điều chuyển phải giữ cơ cấu hiện tại chính xác mà không phá vỡ lịch sử xử lý hồ sơ.

**Independent Test**: Chuyển Staff B từ Phòng ban A sang Phòng ban B và xác nhận B không còn trong danh sách hiện tại của A, xuất hiện đúng một lần trong B, còn dữ liệu nghiệp vụ lịch sử vẫn được giữ nguyên.

**Acceptance Scenarios**:

1. **Given** Staff đang thuộc Phòng ban A và Phòng ban B đang hoạt động, **When** Super Admin chuyển Staff sang Phòng ban B, **Then** quan hệ hiện tại với A được gỡ và quan hệ với B được tạo như một thao tác hoàn chỉnh.
2. **Given** Staff có thể hợp lệ thuộc nhiều phòng ban, **When** người quản lý chỉ thêm Staff vào Phòng ban B thay vì chọn thao tác chuyển, **Then** Staff tiếp tục thuộc Phòng ban A và đồng thời thuộc Phòng ban B.
3. **Given** việc thêm Staff vào phòng ban đích không hợp lệ, **When** thực hiện chuyển, **Then** Staff vẫn thuộc phòng ban nguồn và không có thay đổi dở dang.

---

### User Story 5 - Lưu trữ phòng ban không còn hoạt động (Priority: P3)

Super Admin cần đưa một phòng ban không còn hoạt động ra khỏi danh sách sử dụng thường xuyên mà vẫn giữ được các liên kết và lịch sử nghiệp vụ đã phát sinh.

**Why this priority**: Đây là nhu cầu vòng đời dữ liệu cần thiết nhưng không chặn việc thiết lập và sử dụng cơ cấu tổ chức ban đầu.

**Independent Test**: Lưu trữ một phòng ban đã có thành viên và dịch vụ liên kết, xác nhận phòng ban không còn được chọn cho quan hệ mới nhưng dữ liệu chi tiết và lịch sử liên quan vẫn có thể được người có quyền tra cứu.

**Acceptance Scenarios**:

1. **Given** một phòng ban đang hoạt động, **When** Super Admin xác nhận lưu trữ, **Then** phòng ban không còn xuất hiện trong danh sách hoạt động hoặc lựa chọn cho quan hệ mới.
2. **Given** phòng ban đã có dịch vụ hoặc lịch sử nghiệp vụ liên quan, **When** phòng ban được lưu trữ, **Then** các liên kết và lịch sử đã có vẫn được bảo toàn.
3. **Given** một phòng ban đã được lưu trữ, **When** Manager hoặc Staff cố thay đổi thành viên của phòng ban đó, **Then** hệ thống từ chối thay đổi.

### Edge Cases

- Hai người đồng thời tạo phòng ban với cùng một mã: chỉ một phòng ban được tạo và người còn lại nhận thông báo mã đã tồn tại.
- Hai người đồng thời thêm cùng một Staff vào cùng một phòng ban: danh sách cuối cùng chỉ có một quan hệ thành viên.
- Lãnh đạo được chọn sau đó bị vô hiệu hóa bởi F01: phòng ban vẫn giữ tham chiếu để tra cứu nhưng được cảnh báo cần chọn lãnh đạo hợp lệ trước các thao tác quản trị tiếp theo.
- Staff bị vô hiệu hóa hoặc lưu trữ bởi feature quản lý tài khoản: quan hệ hiện có được giữ để phục vụ tra cứu, nhưng Staff không còn được chọn cho quan hệ mới.
- Phòng ban chưa có lãnh đạo vẫn có thể tồn tại, nhưng trang danh sách và chi tiết phải hiển thị rõ trạng thái chưa có lãnh đạo.
- Tên hoặc mã chỉ gồm khoảng trắng, mã chứa ký tự không được chấp nhận, hoặc địa chỉ vượt giới hạn cho phép phải bị từ chối với thông báo theo từng trường.
- Người dùng mở một phòng ban không tồn tại, đã bị lưu trữ ngoài phạm vi xem, hoặc không thuộc phạm vi quyền của họ phải nhận kết quả từ chối phù hợp và không thấy dữ liệu nhạy cảm.
- Việc gỡ thành viên không được xóa tài khoản người dùng hay dữ liệu xử lý hồ sơ trước đây của người đó.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Hệ thống MUST cung cấp khu vực quản lý phòng ban chỉ cho người dùng nội bộ đã xác thực và MUST từ chối Citizen truy cập khu vực này.
- **FR-002**: Super Admin MUST có thể xem toàn bộ phòng ban, tạo phòng ban, cập nhật thông tin, chọn lãnh đạo, quản lý thành viên và lưu trữ phòng ban.
- **FR-003**: Manager MUST chỉ có thể xem phòng ban mình lãnh đạo và quản lý Staff trong các phòng ban đó; Manager MUST NOT tạo, lưu trữ, đổi mã phòng ban, thay đổi lãnh đạo hoặc quản lý phòng ban ngoài phạm vi.
- **FR-004**: Staff MUST NOT được phép tạo, sửa, lưu trữ hoặc thay đổi cơ cấu thành viên phòng ban.
- **FR-005**: Mỗi phòng ban MUST có tên và mã nhận diện; địa chỉ và lãnh đạo MAY để trống.
- **FR-006**: Mã phòng ban MUST là duy nhất không phân biệt chữ hoa/chữ thường, MUST được loại bỏ khoảng trắng thừa và MUST chỉ chấp nhận định dạng mã nhận diện hợp lệ.
- **FR-007**: Hệ thống MUST hiển thị lỗi cụ thể theo trường khi dữ liệu phòng ban không hợp lệ và MUST không lưu thay đổi một phần.
- **FR-008**: Danh sách phòng ban MUST hiển thị mã, tên, lãnh đạo hiện tại, số thành viên và trạng thái hoạt động.
- **FR-009**: Người dùng có quyền MUST có thể tìm phòng ban theo toàn bộ hoặc một phần tên và mã.
- **FR-010**: Trang chi tiết phòng ban MUST hiển thị thông tin phòng ban, lãnh đạo, danh sách thành viên và danh sách dịch vụ liên kết nếu có.
- **FR-011**: Danh sách dịch vụ liên kết trong F03 MUST chỉ có tính tra cứu; tạo, sửa, kích hoạt hoặc lưu trữ dịch vụ nằm ngoài phạm vi F03.
- **FR-012**: Chỉ tài khoản đang hoạt động có role Staff hoặc Manager mới được thêm làm thành viên phòng ban.
- **FR-013**: Chỉ tài khoản Manager đang hoạt động mới được chọn làm lãnh đạo phòng ban.
- **FR-014**: Khi một Manager được chọn làm lãnh đạo, hệ thống MUST bảo đảm Manager đó đồng thời là thành viên của phòng ban.
- **FR-015**: Một tài khoản MAY là thành viên của nhiều phòng ban, nhưng cùng một tài khoản MUST NOT xuất hiện nhiều hơn một lần trong cùng một phòng ban.
- **FR-016**: Hệ thống MUST từ chối thêm Citizen, Super Admin, tài khoản không hoạt động hoặc tài khoản đã bị lưu trữ vào phòng ban.
- **FR-017**: Super Admin và Manager có quyền với phòng ban MUST có thể thêm hoặc gỡ Staff khỏi phòng ban đang hoạt động.
- **FR-018**: Hệ thống MUST ngăn việc gỡ lãnh đạo hiện tại khỏi danh sách thành viên cho đến khi lãnh đạo được thay đổi hoặc bỏ chỉ định.
- **FR-019**: Super Admin MUST có thể chuyển Staff từ một phòng ban nguồn sang phòng ban đích đang hoạt động; thao tác MUST hoàn thành toàn bộ hoặc không thay đổi quan hệ ở cả hai phòng ban.
- **FR-020**: Việc gỡ hoặc chuyển thành viên MUST NOT xóa tài khoản, lịch sử phân công hồ sơ hoặc dữ liệu nghiệp vụ trước đây của người đó.
- **FR-021**: Thao tác xóa phòng ban thông thường MUST được thực hiện dưới dạng lưu trữ/vô hiệu hóa; hệ thống MUST NOT xóa vĩnh viễn phòng ban qua giao diện F03.
- **FR-022**: Phòng ban đã lưu trữ MUST không được chọn làm phòng ban đích, nhận thành viên mới hoặc thay đổi cơ cấu hiện tại, nhưng dữ liệu đã có MUST vẫn được bảo toàn để tra cứu theo quyền.
- **FR-023**: Các thay đổi quan trọng gồm tạo, cập nhật, lưu trữ phòng ban, thay đổi lãnh đạo, thêm, gỡ và chuyển thành viên MUST xác định được người thực hiện và thời điểm thực hiện để hỗ trợ kiểm toán.
- **FR-024**: F03 MUST sử dụng các tài khoản và role do F01 quản lý; F03 MUST NOT tạo luồng đăng nhập, tạo tài khoản, đổi mật khẩu, kích hoạt tài khoản hoặc thay đổi role.
- **FR-025**: F03 MUST NOT phân công Application, thay đổi trạng thái Application, quản lý Service, cung cấp tìm kiếm Application hoặc triển khai các thao tác workflow thuộc F02, F05 hay F07.

### Scope Boundaries

**In scope**:

- Danh sách, tìm kiếm, tạo, xem, cập nhật và lưu trữ phòng ban.
- Chọn hoặc thay đổi Manager lãnh đạo phòng ban.
- Thêm, gỡ và chuyển Staff giữa các phòng ban.
- Hiển thị cơ cấu thành viên và dịch vụ liên kết ở chế độ chỉ đọc.
- Phân quyền theo Super Admin và Manager đối với cơ cấu tổ chức.

**Out of scope**:

- Đăng ký, đăng nhập, quản lý mật khẩu, kích hoạt tài khoản và thay đổi role.
- Tạo tài khoản Citizen, Staff, Manager hoặc Super Admin.
- CRUD danh mục và loại dịch vụ công.
- Phân công Staff xử lý Application hoặc thực hiện approve, reject, supplement.
- Danh sách vận hành, tìm kiếm, lọc Application, user management tổng quát và dashboard.
- Giao diện Citizen; F03 chỉ phục vụ khu vực quản trị nội bộ.

### Key Entities

- **Department**: Một đơn vị tổ chức nội bộ, được nhận diện bởi tên và mã duy nhất; có địa chỉ tùy chọn, trạng thái hoạt động và một Manager lãnh đạo tùy chọn.
- **Internal User**: Tài khoản dùng chung từ hệ thống người dùng, có role Staff hoặc Manager và trạng thái hoạt động; F03 chỉ tổ chức tài khoản vào phòng ban, không quản lý thông tin xác thực.
- **Department Membership**: Quan hệ giữa một phòng ban và một Staff hoặc Manager. Một người có thể thuộc nhiều phòng ban nhưng chỉ có một quan hệ với mỗi phòng ban.
- **Department Leadership**: Quan hệ chỉ định một Manager đang hoạt động chịu trách nhiệm lãnh đạo phòng ban; người lãnh đạo đồng thời phải là thành viên phòng ban.
- **Service Association**: Quan hệ đọc-only cho biết các dịch vụ do phòng ban phụ trách; nội dung dịch vụ được quản lý bởi F02.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Trong kịch bản demo, Super Admin có thể tạo một phòng ban, chọn một Manager và thêm hai Staff trong không quá 5 phút mà không cần hỗ trợ kỹ thuật.
- **SC-002**: 100% thử nghiệm với Citizen, Staff hoặc Manager ngoài phạm vi đều bị từ chối trước khi dữ liệu cơ cấu tổ chức bị thay đổi.
- **SC-003**: 100% trường hợp mã phòng ban trùng, thành viên trùng hoặc role không hợp lệ đều bị từ chối với thông báo chỉ rõ nguyên nhân.
- **SC-004**: Người dùng có quyền nhận được danh sách hoặc kết quả tìm kiếm phòng ban trong không quá 2 giây với ít nhất 1.000 phòng ban và 10.000 quan hệ thành viên trong dữ liệu kiểm thử.
- **SC-005**: Sau mọi thao tác thêm, gỡ hoặc chuyển thành viên, trang chi tiết phản ánh đúng cơ cấu hiện tại trong lần tải tiếp theo và không có thành viên trùng.
- **SC-006**: 100% phòng ban được lưu trữ không còn được dùng cho quan hệ mới, trong khi toàn bộ liên kết và lịch sử nghiệp vụ đã tồn tại vẫn tra cứu được bởi người có quyền.
- **SC-007**: Luồng demo hoàn chỉnh “tạo phòng ban → chọn Manager → thêm hai Staff → xem chi tiết → gỡ một Staff” hoàn thành thành công và cho kết quả nhất quán ở danh sách lẫn trang chi tiết.

## Assumptions

- F01 cung cấp xác thực cho khu vực Admin, bốn role chuẩn và trạng thái hoạt động của tài khoản trước khi F03 được đưa vào sử dụng.
- Super Admin là role duy nhất có quyền quản trị toàn bộ vòng đời phòng ban; Manager chỉ quản lý Staff trong phòng ban mình lãnh đạo. Đây là mặc định ít quyền nhất để tránh mở rộng quyền ngoài trách nhiệm.
- Một phòng ban có thể tạm thời chưa có lãnh đạo vì trường lãnh đạo trong thiết kế dữ liệu hiện tại là tùy chọn.
- Một Staff hoặc Manager có thể thuộc nhiều phòng ban theo thiết kế quan hệ hiện tại; “chuyển” là thao tác riêng với “thêm vào phòng ban khác”.
- Tài khoản không hoạt động vẫn có thể được hiển thị trong cơ cấu lịch sử nhưng không thể được thêm mới hoặc chọn làm lãnh đạo.
- F02 có thể chạy song song với F03; trang chi tiết chỉ hiển thị dịch vụ liên kết nếu dữ liệu đó đã tồn tại và không chặn việc hoàn thành các luồng cốt lõi của F03.
- F06 cung cấp trải nghiệm tra cứu audit tổng thể; F03 vẫn phải làm cho các thay đổi cơ cấu quan trọng có đủ thông tin người thực hiện và thời điểm để F06 sử dụng.
- Lưu trữ phòng ban là thao tác có xác nhận. Khôi phục phòng ban và xóa vĩnh viễn là ngoài phạm vi phiên bản đầu của F03.
