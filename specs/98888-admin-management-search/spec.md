# Feature Specification: F07 - Admin Management & Search

**Feature Branch**: `[98888-admin-management-search]`

**Created**: 2026-08-21

**Status**: Draft

**Input**: User description: "F07 cung cấp workspace quản trị cho Staff, Manager và Super Admin để tra cứu hồ sơ theo đúng phạm vi quyền, tìm kiếm/lọc/phân trang, xem chi tiết hồ sơ, quản lý trạng thái tài khoản người dùng và theo dõi thống kê vận hành cơ bản; không viết lại nghiệp vụ xử lý hồ sơ của F05."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Tra cứu danh sách hồ sơ theo phạm vi được phép (Priority: P1)

Staff, Manager và Super Admin cần một danh sách hồ sơ vận hành tập trung để nhanh chóng tìm thấy các hồ sơ thuộc trách nhiệm của mình. Danh sách phải tự giới hạn dữ liệu theo vai trò, hỗ trợ tìm kiếm, kết hợp nhiều bộ lọc, sắp xếp và phân trang mà không làm lộ hồ sơ ngoài phạm vi được phép.

**Why this priority**: Đây là giá trị cốt lõi của F07. Khi số lượng hồ sơ tăng, người dùng nội bộ không thể vận hành hiệu quả nếu phải mở từng hồ sơ hoặc nhìn thấy dữ liệu không liên quan đến trách nhiệm của mình.

**Independent Test**: Chuẩn bị hồ sơ thuộc nhiều công dân, dịch vụ, phòng ban, nhân viên và trạng thái; đăng nhập lần lượt bằng Staff, Manager và Super Admin; xác nhận mỗi vai trò chỉ nhận được dữ liệu đúng phạm vi, sau đó kết hợp từ khóa, trạng thái, dịch vụ, phòng ban, nhân viên và khoảng ngày để kiểm tra kết quả cùng phân trang.

**Acceptance Scenarios**:

1. **Given** một Staff đang hoạt động có các hồ sơ được phân công và các hồ sơ khác không được phân công cho mình, **When** Staff mở danh sách hồ sơ, **Then** chỉ các hồ sơ đang được phân công cho Staff đó được hiển thị.
2. **Given** một Manager đang lãnh đạo một hoặc nhiều phòng ban, **When** Manager mở danh sách hồ sơ, **Then** chỉ các hồ sơ thuộc dịch vụ do những phòng ban đó phụ trách được hiển thị.
3. **Given** một Super Admin đang hoạt động, **When** Super Admin mở danh sách hồ sơ, **Then** toàn bộ hồ sơ không bị lưu trữ trong hệ thống được hiển thị theo phân trang.
4. **Given** người dùng nhập mã hồ sơ, tên công dân, mã định danh công dân hoặc tên dịch vụ, **When** thực hiện tìm kiếm, **Then** danh sách chỉ chứa các hồ sơ trong phạm vi quyền có thông tin phù hợp với từ khóa.
5. **Given** người dùng chọn đồng thời trạng thái, dịch vụ, phòng ban, nhân viên phụ trách và khoảng ngày nộp, **When** áp dụng bộ lọc, **Then** chỉ các hồ sơ thỏa mãn tất cả điều kiện đã chọn được hiển thị.
6. **Given** danh sách có nhiều hơn một trang, **When** người dùng chuyển trang, **Then** từ khóa, bộ lọc và thứ tự hiện tại được giữ nguyên.
7. **Given** không có hồ sơ nào phù hợp, **When** kết quả được hiển thị, **Then** người dùng nhận được trạng thái rỗng rõ ràng và có thể xóa bộ lọc để quay lại danh sách trong phạm vi của mình.

---

### User Story 2 - Xem đầy đủ chi tiết hồ sơ được phép (Priority: P2)

Người dùng nội bộ cần mở một hồ sơ từ kết quả tra cứu để xem tổng hợp công dân, dịch vụ, phòng ban phụ trách, nhân viên đang xử lý, dữ liệu đã khai, tài liệu, lịch sử phân công, lịch sử trạng thái, các mốc thời gian và kết quả xử lý. Quyền mở trực tiếp hồ sơ phải giống hệt phạm vi áp dụng cho danh sách.

**Why this priority**: Danh sách chỉ giúp xác định hồ sơ; cán bộ cần trang chi tiết đáng tin cậy để hiểu bối cảnh trước khi thực hiện nghiệp vụ F05 hoặc hỗ trợ công dân.

**Independent Test**: Từ mỗi vai trò nội bộ, mở một hồ sơ trong phạm vi và một hồ sơ ngoài phạm vi bằng cả liên kết từ danh sách và địa chỉ trực tiếp; xác nhận hồ sơ hợp lệ hiển thị đầy đủ còn hồ sơ ngoài phạm vi không bị tiết lộ.

**Acceptance Scenarios**:

1. **Given** một hồ sơ nằm trong phạm vi của người dùng, **When** người dùng mở chi tiết, **Then** hệ thống hiển thị mã hồ sơ, trạng thái, công dân, dịch vụ, phòng ban, nhân viên phụ trách, dữ liệu đã nộp và các mốc thời gian hiện có.
2. **Given** hồ sơ có tài liệu, lịch sử phân công và lịch sử trạng thái, **When** trang chi tiết được mở, **Then** các dữ liệu lịch sử được hiển thị theo trình tự thời gian rõ ràng và tài liệu chỉ có thể được tải khi người dùng có quyền.
3. **Given** hồ sơ đã được duyệt hoặc từ chối, **When** người dùng có quyền mở chi tiết, **Then** kết quả hoặc lý do từ chối tương ứng được hiển thị mà không làm thay đổi trạng thái hồ sơ.
4. **Given** một Staff hoặc Manager cố mở trực tiếp hồ sơ ngoài phạm vi của mình, **When** yêu cầu được gửi, **Then** hệ thống từ chối mà không xác nhận hồ sơ đó có tồn tại.
5. **Given** F05 cho phép người dùng thực hiện một hành động xử lý trên hồ sơ, **When** trang chi tiết được hiển thị, **Then** giao diện có thể cung cấp điểm truy cập tới hành động đó; quy tắc, validation và thay đổi trạng thái vẫn hoàn toàn do F05 chịu trách nhiệm.

---

### User Story 3 - Quản lý người dùng từ khu vực quản trị (Priority: P3)

Super Admin cần tra cứu danh sách tài khoản, xem chi tiết và kích hoạt hoặc vô hiệu hóa tài khoản để kiểm soát quyền sử dụng hệ thống. Chức năng này chỉ quản lý trạng thái vận hành và khả năng quan sát tài khoản; nó không thay thế đăng ký, đăng nhập, mật khẩu, phân quyền nền tảng hoặc quản lý thành viên phòng ban của các feature khác.

**Why this priority**: Hệ thống vận hành cần một cách an toàn để xử lý tài khoản nghỉ việc, bị khóa hoặc cần mở lại mà không xóa lịch sử hồ sơ và hoạt động liên quan.

**Independent Test**: Đăng nhập bằng Super Admin, tìm một Citizen và một Staff theo các tiêu chí khác nhau, xem chi tiết, vô hiệu hóa rồi kích hoạt lại; xác nhận tài khoản bị vô hiệu hóa không còn truy cập được và dữ liệu lịch sử vẫn còn nguyên. Lặp lại bằng Staff hoặc Manager để xác nhận bị từ chối.

**Acceptance Scenarios**:

1. **Given** Super Admin mở danh sách người dùng, **When** tìm theo tên, email hoặc mã định danh công dân và lọc theo vai trò hoặc trạng thái tài khoản, **Then** kết quả phù hợp được hiển thị theo phân trang.
2. **Given** Super Admin mở chi tiết một tài khoản, **When** trang được hiển thị, **Then** thông tin hồ sơ, vai trò, trạng thái, quan hệ phòng ban và thông tin tổng hợp liên quan được trình bày mà không hiển thị thông tin xác thực nhạy cảm.
3. **Given** một tài khoản đủ điều kiện bị vô hiệu hóa, **When** Super Admin xác nhận thao tác, **Then** tài khoản mất quyền truy cập vào khu vực được bảo vệ nhưng các hồ sơ, lịch sử và quan hệ đã có vẫn được bảo toàn.
4. **Given** một tài khoản đang bị vô hiệu hóa, **When** Super Admin kích hoạt lại, **Then** tài khoản có thể đăng nhập và truy cập trở lại theo đúng vai trò hiện có.
5. **Given** Staff, Manager hoặc Citizen cố truy cập chức năng quản lý người dùng, **When** yêu cầu được gửi, **Then** hệ thống từ chối truy cập và không tiết lộ danh sách tài khoản.
6. **Given** Super Admin cố vô hiệu hóa chính mình, Super Admin hoạt động cuối cùng, Manager đang lãnh đạo phòng ban hoạt động hoặc Staff/Manager đang xử lý hồ sơ chưa hoàn thành, **When** gửi yêu cầu, **Then** hệ thống từ chối và chỉ rõ điều kiện nghiệp vụ cần giải quyết trước.

---

### User Story 4 - Theo dõi tổng quan vận hành (Priority: P4)

Người dùng nội bộ cần nhìn nhanh tình trạng hồ sơ trong đúng phạm vi của mình để nhận biết khối lượng đang chờ, đang xử lý, cần bổ sung, đã hoàn thành và quá hạn trước khi đi vào danh sách chi tiết.

**Why this priority**: Thống kê cơ bản giúp ưu tiên công việc nhưng chỉ có giá trị sau khi phạm vi dữ liệu, tra cứu và chi tiết hồ sơ đã chính xác.

**Independent Test**: Chuẩn bị hồ sơ ở tất cả trạng thái và nhiều phòng ban, sau đó đăng nhập bằng từng vai trò để đối chiếu từng chỉ số với danh sách hồ sơ mà vai trò đó được phép xem.

**Acceptance Scenarios**:

1. **Given** Staff mở tổng quan, **When** các chỉ số được hiển thị, **Then** chúng chỉ được tính từ các hồ sơ đang được phân công cho Staff đó.
2. **Given** Manager mở tổng quan, **When** các chỉ số được hiển thị, **Then** chúng chỉ được tính từ hồ sơ thuộc các phòng ban Manager lãnh đạo.
3. **Given** Super Admin mở tổng quan, **When** các chỉ số được hiển thị, **Then** chúng phản ánh toàn bộ hồ sơ không bị lưu trữ trong hệ thống.
4. **Given** người dùng chọn một nhóm trạng thái từ tổng quan, **When** chuyển tới danh sách, **Then** danh sách được mở với điều kiện tương ứng và vẫn giữ đúng phạm vi quyền.

### Edge Cases

- Từ khóa có khoảng trắng đầu/cuối, khác biệt chữ hoa/chữ thường, dấu tiếng Việt hoặc ký tự đặc biệt phải được xử lý an toàn và không làm mở rộng phạm vi dữ liệu.
- Khoảng ngày có ngày bắt đầu sau ngày kết thúc, ngày không hợp lệ hoặc vượt giới hạn cho phép phải bị từ chối với thông báo rõ ràng.
- Tham số trạng thái, dịch vụ, phòng ban, nhân viên hoặc trang không hợp lệ phải được xử lý có kiểm soát, không gây lỗi hệ thống và không làm lộ dữ liệu ngoài phạm vi.
- Manager không lãnh đạo phòng ban nào và Staff không có hồ sơ được phân công phải thấy trạng thái rỗng cùng các chỉ số bằng không.
- Manager lãnh đạo nhiều phòng ban phải thấy hợp nhất hồ sơ của tất cả phòng ban đó mà không bị trùng lặp.
- Hồ sơ có công dân, dịch vụ, phòng ban hoặc nhân viên đã bị vô hiệu hóa/lưu trữ vẫn phải hiển thị được thông tin lịch sử phù hợp thay vì làm hỏng trang chi tiết.
- Nếu hồ sơ được phân công lại trong lúc Staff đang xem, yêu cầu tiếp theo phải áp dụng quyền mới; Staff cũ không được tiếp tục truy cập chỉ vì đã mở trang trước đó.
- Khi kết quả trên trang hiện tại không còn tồn tại sau thay đổi dữ liệu hoặc bộ lọc, hệ thống phải đưa người dùng về một trang kết quả hợp lệ hoặc hiển thị trạng thái rỗng rõ ràng.
- Tài khoản bị vô hiệu hóa đồng thời ở một phiên khác phải mất quyền truy cập ở yêu cầu được bảo vệ tiếp theo.
- Việc kích hoạt/vô hiệu hóa không được xóa, chuyển quyền hoặc tự động thay đổi bất kỳ hồ sơ, lịch sử, vai trò hay quan hệ phòng ban nào.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST chỉ cho phép Staff, Manager và Super Admin đang hoạt động truy cập workspace quản trị; Citizen, khách và tài khoản nội bộ bị vô hiệu hóa MUST bị từ chối.
- **FR-002**: System MUST áp dụng cùng một quy tắc phạm vi dữ liệu cho danh sách hồ sơ, kết quả tìm kiếm, bộ lọc, phân trang, trang chi tiết, số liệu tổng quan và dữ liệu dùng để tạo lựa chọn bộ lọc.
- **FR-003**: Staff MUST chỉ xem được hồ sơ có nhân viên phụ trách hiện tại là chính Staff đó.
- **FR-004**: Manager MUST chỉ xem được hồ sơ thuộc dịch vụ do một trong các phòng ban mà Manager đang lãnh đạo phụ trách.
- **FR-005**: Super Admin MUST xem được toàn bộ hồ sơ không bị lưu trữ.
- **FR-006**: Danh sách hồ sơ MUST hiển thị tối thiểu mã hồ sơ, công dân, dịch vụ, phòng ban phụ trách, nhân viên phụ trách, trạng thái, ngày nộp và hành động xem chi tiết.
- **FR-007**: Người dùng MUST có thể tìm hồ sơ theo một từ khóa áp dụng cho mã hồ sơ, tên công dân, mã định danh công dân và tên dịch vụ trong phạm vi được phép.
- **FR-008**: Người dùng MUST có thể lọc hồ sơ theo trạng thái, dịch vụ, phòng ban, nhân viên phụ trách, ngày nộp từ ngày và ngày nộp đến ngày.
- **FR-009**: Khi nhiều điều kiện được cung cấp, System MUST kết hợp chúng theo phép giao; tìm kiếm văn bản MUST bỏ qua khoảng trắng thừa và không phân biệt chữ hoa/chữ thường; khoảng ngày MUST bao gồm cả hai ngày biên.
- **FR-010**: System MUST kiểm tra tính hợp lệ của mọi điều kiện tra cứu và hiển thị thông báo có thể xử lý được cho người dùng khi điều kiện không hợp lệ.
- **FR-011**: Danh sách hồ sơ MUST mặc định sắp xếp theo ngày nộp mới nhất, hiển thị 20 hồ sơ mỗi trang và giữ nguyên toàn bộ điều kiện tra cứu khi chuyển trang.
- **FR-012**: Các lựa chọn dịch vụ, phòng ban và nhân viên trong bộ lọc MUST được giới hạn theo phạm vi dữ liệu mà người dùng có thể quan sát; giá trị ngoài phạm vi MUST không làm lộ tên hoặc số lượng dữ liệu bị bảo vệ.
- **FR-013**: System MUST cung cấp trạng thái rỗng riêng cho trường hợp không có dữ liệu trong phạm vi và trường hợp không có kết quả khớp bộ lọc, đồng thời cung cấp cách xóa điều kiện tra cứu.
- **FR-014**: Quyền xem trực tiếp chi tiết một hồ sơ MUST giống quyền làm hồ sơ đó xuất hiện trong danh sách; yêu cầu ngoài phạm vi MUST bị từ chối mà không xác nhận tài nguyên tồn tại.
- **FR-015**: Trang chi tiết hồ sơ MUST hiển thị các thông tin hiện có gồm mã, trạng thái, công dân, dịch vụ, phòng ban, nhân viên phụ trách, dữ liệu đã khai, tài liệu, lịch sử phân công, lịch sử trạng thái, ngày nộp, ngày bắt đầu xử lý, ngày hoàn thành, kết quả và lý do từ chối.
- **FR-016**: Tài liệu hồ sơ MUST chỉ được liệt kê và tải xuống khi người dùng có quyền xem hồ sơ và quyền truy cập tài liệu tương ứng; tài liệu MUST không được công khai qua trang quản trị.
- **FR-017**: Lịch sử phân công và lịch sử trạng thái MUST được trình bày theo thứ tự thời gian xác định, nhận diện được người thực hiện, thời điểm, thay đổi và ghi chú khi dữ liệu tồn tại.
- **FR-018**: F07 MAY hiển thị các hành động xử lý do F05 cung cấp khi người dùng được phép, nhưng MUST NOT định nghĩa lại quy tắc phân công, nhận xử lý, yêu cầu bổ sung, tiếp tục, duyệt, từ chối, tải kết quả hoặc chuyển trạng thái.
- **FR-019**: System MUST cung cấp cho Super Admin danh sách người dùng có tên, email, mã định danh công dân khi có, vai trò, trạng thái tài khoản và thông tin tổ chức tóm tắt.
- **FR-020**: Super Admin MUST có thể tìm người dùng theo tên, email hoặc mã định danh công dân; lọc theo vai trò và trạng thái; sắp xếp theo bản ghi mới nhất; xem 20 người dùng mỗi trang; và giữ nguyên điều kiện khi chuyển trang.
- **FR-021**: Super Admin MUST có thể xem chi tiết tài khoản mà không hiển thị mật khẩu, mã phiên, token hoặc thông tin xác thực bí mật.
- **FR-022**: Super Admin MUST có thể kích hoạt hoặc vô hiệu hóa một tài khoản đủ điều kiện; thay đổi MUST có hiệu lực với lần truy cập được bảo vệ tiếp theo và MUST bảo toàn dữ liệu lịch sử.
- **FR-023**: System MUST ngăn việc vô hiệu hóa chính tài khoản đang thao tác, Super Admin hoạt động cuối cùng, Manager đang lãnh đạo phòng ban hoạt động, hoặc Staff/Manager đang được phân công hồ sơ chưa hoàn thành.
- **FR-024**: F07 MUST NOT cho phép tạo tài khoản, đổi mật khẩu, thay đổi vai trò, chỉnh sửa định danh Citizen, thay đổi thành viên/leader phòng ban hoặc xóa vật lý người dùng.
- **FR-025**: Staff, Manager và Citizen MUST không truy cập được danh sách người dùng, chi tiết quản trị người dùng hoặc thao tác kích hoạt/vô hiệu hóa.
- **FR-026**: Tổng quan MUST hiển thị ít nhất số hồ sơ tổng cộng, mới tiếp nhận, đang xử lý, chờ bổ sung, đã hoàn thành và quá hạn trong phạm vi của người dùng.
- **FR-027**: Mọi số liệu tổng quan và liên kết đi từ số liệu sang danh sách MUST sử dụng cùng định nghĩa trạng thái, quá hạn và phạm vi quyền với danh sách hồ sơ.
- **FR-028**: Các thao tác kích hoạt/vô hiệu hóa tài khoản MUST ghi nhận người thực hiện, tài khoản bị tác động, trạng thái trước/sau và thời điểm để có thể kiểm tra lại.
- **FR-029**: Tìm kiếm, lọc, phân trang và xem chi tiết MUST không thay đổi Application, User, Service, Department hoặc bất kỳ dữ liệu lịch sử nào.
- **FR-030**: System MUST xử lý quan hệ đã bị vô hiệu hóa hoặc lưu trữ theo cách vẫn cho phép đọc lịch sử trong phạm vi quyền, đồng thời nhận diện rõ bản ghi không còn hoạt động.

### Key Entities *(include if feature involves data)*

- **Application**: Hồ sơ dịch vụ công được tra cứu; có mã công khai, công dân nộp, dịch vụ, trạng thái, nhân viên phụ trách hiện tại, dữ liệu đã khai và các mốc xử lý.
- **User**: Tài khoản của Citizen hoặc người dùng nội bộ; có thông tin nhận diện, vai trò, trạng thái hoạt động và các quan hệ nghiệp vụ cần được bảo toàn khi tài khoản bị vô hiệu hóa.
- **Department**: Đơn vị chịu trách nhiệm cho dịch vụ và là cơ sở xác định phạm vi hồ sơ của Manager.
- **Service Type**: Loại dịch vụ mà hồ sơ đăng ký; liên kết hồ sơ với phòng ban phụ trách và cung cấp tiêu chí lọc.
- **Application Assignment**: Lịch sử Staff được giao xử lý hồ sơ; phân biệt người phụ trách hiện tại với các lần phân công trước.
- **Application Status History**: Dòng thời gian các trạng thái hồ sơ, người thay đổi, thời điểm và ghi chú.
- **Application Document**: Thông tin tài liệu gắn với hồ sơ; chỉ được quan sát hoặc tải xuống trong phạm vi quyền.
- **Activity Log**: Bằng chứng kiểm toán cho thay đổi trạng thái tài khoản do Super Admin thực hiện.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% hồ sơ xuất hiện trong danh sách, trang chi tiết và số liệu tổng quan tuân thủ đúng phạm vi Staff, Manager và Super Admin; không có hồ sơ ngoài phạm vi bị tiết lộ qua địa chỉ trực tiếp hoặc giá trị bộ lọc.
- **SC-002**: Với tập dữ liệu ít nhất 10.000 hồ sơ, 95% thao tác mở danh sách, tìm kiếm, lọc hoặc chuyển trang cho người dùng thấy kết quả trong không quá 2 giây ở điều kiện vận hành bình thường.
- **SC-003**: Người dùng nội bộ có thể tìm một hồ sơ đã biết bằng mã hoặc thuộc tính nghiệp vụ, áp dụng bộ lọc và mở chi tiết trong không quá 60 giây và không quá 5 tương tác chính.
- **SC-004**: 100% tổ hợp bộ lọc được kiểm thử trả về đúng phép giao của các điều kiện, giữ nguyên điều kiện qua phân trang và không tạo bản ghi trùng trong kết quả.
- **SC-005**: 100% trang chi tiết được phép hiển thị đầy đủ các nhóm dữ liệu hiện có và 100% yêu cầu chi tiết ngoài phạm vi bị từ chối mà không tiết lộ sự tồn tại của hồ sơ.
- **SC-006**: Với tập dữ liệu ít nhất 10.000 người dùng, 95% thao tác tìm kiếm, lọc hoặc chuyển trang trong quản lý người dùng cho Super Admin thấy kết quả trong không quá 2 giây.
- **SC-007**: 100% thao tác kích hoạt/vô hiệu hóa hợp lệ có hiệu lực ở yêu cầu được bảo vệ tiếp theo, được ghi dấu kiểm toán và không làm mất hồ sơ hoặc lịch sử liên quan; 100% thao tác vi phạm điều kiện bảo vệ bị từ chối.
- **SC-008**: 100% chỉ số tổng quan khớp với tập hồ sơ trong phạm vi của người dùng và dẫn tới danh sách có điều kiện tương ứng.
- **SC-009**: Trong kiểm thử chấp nhận với các tác vụ chính, ít nhất 90% người dùng nội bộ hoàn thành tra cứu hồ sơ và hiểu trạng thái kết quả ngay lần đầu mà không cần hướng dẫn ngoài giao diện.

## Assumptions

- F01 đã cung cấp tài khoản, vai trò, trạng thái hoạt động, đăng nhập nội bộ và các ranh giới xác thực; F07 chỉ cung cấp giao diện quản trị trạng thái tài khoản và tái sử dụng các quy tắc nền tảng đó.
- F02 đã cung cấp Category, Service Type và quan hệ phòng ban phụ trách dịch vụ; F03 đã cung cấp Department, leader và membership; F04 đã cung cấp Application và tài liệu; F05 đã cung cấp phân công và workflow; F06 chịu trách nhiệm nội dung lịch sử, thông báo và audit tổng quát.
- Phạm vi Manager được xác định từ các phòng ban mà Manager là leader, không phải mọi phòng ban mà Manager chỉ là thành viên.
- Danh sách chính của Staff chỉ gồm hồ sơ đang được phân công cho Staff; hàng đợi nhận hồ sơ chưa phân công, nếu có, tiếp tục thuộc trải nghiệm và quy tắc của F05.
- "Ngày từ/đến" áp dụng cho ngày nộp hồ sơ, sử dụng ngày địa phương của hệ thống và bao gồm toàn bộ hai ngày biên.
- Mỗi danh sách dùng kích thước trang cố định 20 bản ghi; thay đổi kích thước trang không thuộc phạm vi phiên bản F07 này.
- "Đã hoàn thành" trong tổng quan là tổng của hồ sơ đã duyệt và đã từ chối; "quá hạn" là hồ sơ chưa hoàn thành vượt thời gian xử lý của dịch vụ kể từ ngày nộp.
- Vô hiệu hóa tài khoản là thay đổi có thể đảo ngược; không xóa tài khoản hay dữ liệu liên quan. Các phụ thuộc đang hoạt động phải được giải quyết bằng F03 hoặc F05 trước khi vô hiệu hóa.
- F07 không tạo domain entity mới; nó tổ chức việc quan sát và quản trị các entity đã được các feature nền tảng sở hữu.
- Export dữ liệu và tài liệu API thuộc F08; notification và màn hình Activity Log đầy đủ thuộc F06; biểu đồ nâng cao, báo cáo xu hướng và cấu hình dashboard cá nhân nằm ngoài phạm vi F07.
