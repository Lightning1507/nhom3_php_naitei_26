# F03 Design Context — Department & Staff Management

## 1. Mục đích tài liệu

Tài liệu này chuyển ngôn ngữ thiết kế của màn Figma `Super Admin` thành định hướng UI đầy đủ cho F03 — Department & Staff Management.

Figma hiện chỉ cung cấp một màn `User Management`. Màn đó được dùng làm nguồn tham chiếu cho admin shell, màu sắc, typography, spacing, card, filter, table và pagination; không được dùng để mở rộng F03 sang quản lý tài khoản người dùng.

Tài liệu này là đầu vào thiết kế cho bước `$speckit-plan`. Nghiệp vụ chính thức vẫn được xác định bởi `spec.md`, BA và constitution.

## 2. Nguồn và thứ tự ưu tiên

Thứ tự giải quyết khi các nguồn khác nhau:

1. Constitution, BA và `spec.md`: quyết định phạm vi, quyền và business rule.
2. Figma: quyết định visual language và cách tổ chức màn hình.
3. `docs/ui-guidelines.md`: ánh xạ màn hình và quy ước UI dùng chung.
4. Code/CSS hiện tại: nền tảng kỹ thuật cần tái sử dụng có chọn lọc.

Nguồn Figma đã khảo sát:

- File: `Public Service Management`
- File key: `3pEraleB83a163IpQYfwey`
- Node: `4:2284`
- Node name: `Super Admin`
- Kích thước frame: `1089 × 1191 px`
- Link: <https://www.figma.com/design/3pEraleB83a163IpQYfwey/Public-Service-Management?node-id=4-2284>

Các nguồn nội bộ liên quan:

- `specs/98884-department-staff-management/spec.md`
- `docs/business-analysis.md`
- `docs/ui-guidelines.md`
- `resources/css/app.css`

## 3. Phân định Figma và phạm vi F03

### 3.1. Có thể tái sử dụng từ Figma

- Super Admin navigation shell.
- Page heading và primary action.
- Summary statistic cards.
- Search/filter toolbar.
- Data table, badges, row actions và pagination.
- Card surface, border, shadow, radius và spacing.
- Visual language cho form controls, dropdown và dialogs.

### 3.2. Không thuộc F03

Các phần sau xuất hiện trong màn Figma nhưng thuộc F01 hoặc F07, không được triển khai như nghiệp vụ F03:

- `Add User` và tạo tài khoản.
- Danh sách toàn bộ Citizen/Staff/Manager như user management.
- Sửa email, password, role hoặc account status.
- Bật/tắt tài khoản bằng status toggle.
- Thông tin `Joined` và `Last Login`.
- Dashboard, application search hoặc application workflow.

### 3.3. Chuyển đổi đúng sang F03

| Mẫu Figma | Cách sử dụng trong F03 |
|---|---|
| User Management heading | Department Management heading |
| Add User | Add Department |
| User summary cards | Department summary cards |
| Search user | Search theo department name/code/address |
| Role/Department/Status filters | Manager/Status filters |
| User table | Department table |
| User detail/actions | Department detail/edit/archive |
| Account toggle | Department status badge và archive action có xác nhận |

## 4. Design tokens đọc từ Figma

### 4.1. Màu sắc

| Token đề xuất | Giá trị | Cách dùng |
|---|---:|---|
| `admin-primary` | `#0052CC` | Navbar, primary button, link/action, Staff badge text |
| `surface` | `#FFFFFF` | Card, table, modal, input |
| `page-background` | `#F1F3F6` | Nền main admin page |
| `surface-subtle` | `#F9FAFB` / `#FAFAFA` | Header/table row/secondary surface khi cần |
| `control-background` | `#F3F4F6` | Control hoặc trạng thái trung tính |
| `border` | `#E5E7EB` | Card, table và divider |
| `border-strong` | `#D1D5DB` | Input/control border |
| `text-primary` | `#0A0A0A` | Heading và nội dung chính |
| `text-secondary` | `#374151` | Label/control text |
| `text-muted` | `#6B7280` | Description và metadata |
| `text-disabled` | `#9CA3AF` | Table heading, disabled/placeholder |
| `success` | `#059669` | Active/success state |
| `success-surface` | `#F0FDF4` | Active/success badge background |
| `manager` | `#7C3AED` | Manager badge |
| `manager-surface` | `#FDF4FF` | Manager badge background |
| `citizen` | `#3B5BDB` | Citizen badge khi xuất hiện ngoài F03 |
| `citizen-surface` | `#F0F4FF` | Citizen badge background |
| `staff-surface` | `#E8F0FE` | Staff badge background |

Inactive navigation text dùng màu trắng với opacity khoảng `55%`. Navigation item đang active dùng nền trắng opacity khoảng `18–22%`.

Màu nguy hiểm cho archive/remove không được thể hiện rõ trong node này; bước plan nên tái sử dụng destructive token sẵn có của dự án thay vì tự suy ra từ màu primary.

### 4.2. Typography

Font giao diện chính là `Inter`.

| Thành phần | Font / size / line-height | Weight |
|---|---|---:|
| Page title | Inter `22 / 33 px` | 700 |
| Stat value | Inter `22 / 22 px` | 700 |
| Top navigation | Inter `14 / 20 px` | 600 |
| Brand label | Inter `14 / 20 px` | 700 |
| Page subtitle | Inter `14 / 21 px` | 400 |
| Primary button | Inter `14 / 21 px` | 600 |
| User/member name | Inter `14 / 21 px` | 600 |
| Stat label | Inter `13 / 19.5 px` | 400 |
| Filter/select | Inter `13 / 19.5 px` | 500 |
| Table heading | Inter `12 / 18 px`, letter-spacing `0.84 px` | 700 |
| Badge | Inter `12 / 18 px` | 600 |
| Pagination | Inter `14 / 20 px` | 600 |
| Email/technical metadata | Cousine `13 / 19.5 px` | 400 |

Figma dùng `Cousine` cho email trong table, trong khi CSS hiện tại khai báo `Consolas` và chỉ import `Inter`. F03 không bắt buộc thêm font chỉ cho một trường metadata; plan cần chọn một trong hai hướng nhất quán:

- import `Cousine` nếu toàn dự án muốn bám Figma tuyệt đối; hoặc
- dùng monospace stack hiện tại nếu tránh thêm dependency font là ưu tiên.

### 4.3. Spacing, radius và shadow

- Main content: `32 px` hai bên, `24 px` trên/dưới.
- Khoảng cách giữa header, stats và data card: `24 px`.
- Stat card padding: `16 px` dọc, `20 px` ngang.
- Filter toolbar padding: `16 px` dọc, `20 px` ngang; gap `16 px`.
- Footer/pagination padding: `16 px` dọc, `20 px` ngang.
- Primary button padding: `10 px` dọc, `20 px` ngang; icon gap `8 px`.
- Border radius thường dùng: `10 px`, `14 px`, `16 px`; badge/pill dùng full radius.
- Primary button radius: `10 px`.
- Data card radius: `16 px`.
- Shadow card gồm hai lớp:
  - `0 1px 3px rgba(0, 0, 0, 0.10)`
  - `0 1px 2px -1px rgba(0, 0, 0, 0.10)`

### 4.4. Kích thước cấu trúc màn tham chiếu

- Admin navbar: `1089 × 64 px`, padding ngang `32 px`.
- Main content: `1089 × 1127 px`.
- Page header row: `1025 × 56 px`.
- Primary action: khoảng `127 × 41 px` trong bản `Add User`.
- Stats row: `1025 × 78 px`, bốn card khoảng `244 × 78 px`.
- Data card: `1025 × 896 px`.
- Filter toolbar: `1023 × 76 px`.
- Table area: `1023 × 753 px`.
- Footer: `1023 × 65 px`.

Các kích thước này là mốc desktop, không phải fixed width bắt buộc trong code.

## 5. Component inventory cho F03

Các component dưới đây có thể là Blade components/partials. Không cần tạo framework UI mới.

### 5.1. Admin shell

- `AdminNav`: giữ đúng chiều cao, màu primary, active navigation và account area của Figma.
- `AdminPageHeader`: title, description và primary action theo quyền.
- Navigation F03 nên đặt tại mục quản lý tổ chức phù hợp với IA của dự án; không gắn vào `User Management` nếu route và menu đã tách riêng.

### 5.2. Summary cards

Department List dùng bốn card:

1. Total Departments.
2. Active Departments.
3. Departments Missing Manager.
4. Assigned Staff Members.

Các số liệu này giúp F03 nhưng không biến màn thành dashboard F07.

### 5.3. Filter toolbar

- Search input: department name, code hoặc address.
- Manager filter.
- Status filter: Active/Archived nếu archive records được phép xem trong danh sách.
- Reset filters.
- Filter state phải được giữ khi phân trang.
- Không cần role filter vì danh sách chính là Department, không phải User.

### 5.4. Data table

Department table đề xuất:

| Column | Nội dung |
|---|---|
| Code | Mã Department, dễ scan, có thể dùng monospace |
| Department | Tên và address rút gọn nếu có |
| Manager | Avatar/name hoặc `Not assigned` |
| Staff | Số thành viên Staff hiện tại |
| Services | Số Service Type liên quan, read-only; chỉ thêm khi quan hệ F02 đã sẵn sàng |
| Status | Active/Archived badge |
| Actions | View, Edit, Archive theo quyền |

Không dùng status toggle trực tiếp trên row. Archive là hành động nghiệp vụ có tác động và phải qua confirmation.

### 5.5. Form controls

- Text input cho name, code, address.
- Searchable select cho manager và staff candidate vì danh sách user có thể lớn.
- Helper/error text đặt dưới field.
- Disabled/read-only state rõ ràng với Manager không đủ quyền.
- Server validation là nguồn sự thật; Alpine chỉ hỗ trợ interaction và feedback tức thời.

### 5.6. Badges và identity

- Manager badge: purple language từ Figma.
- Staff badge: blue language từ Figma.
- Active badge: green language từ Figma.
- Archived/Inactive badge: neutral gray.
- Không chỉ dùng màu để truyền đạt trạng thái; luôn có label text.

### 5.7. Dialogs

Dùng modal/confirm dialog cùng visual language của data card cho các thao tác ngắn:

- Add Staff.
- Change Manager.
- Transfer Staff.
- Remove Staff confirmation.
- Archive Department confirmation.

Dialog phải có title nói rõ hành động, mô tả tác động, Cancel và action button. Remove/archive dùng destructive styling.

## 6. Screen inventory đầy đủ của F03

### 6.1. Department List

Route khuyến nghị: `/admin/departments`.

Mục tiêu: giúp Super Admin nhìn, tìm và mở Department; Manager chỉ thấy Department mình được phép quản lý.

Cấu trúc:

1. Admin navbar.
2. Header:
   - Title: `Department Management`.
   - Description: `Manage departments, managers, and staff assignments`.
   - `Add Department` chỉ hiển thị cho Super Admin.
3. Bốn summary cards.
4. Data card gồm search/filter, table và pagination.

Row actions:

- Super Admin: View, Edit, Archive.
- Manager: View Department mình lãnh đạo; không thấy create/edit/archive.

States bắt buộc:

- Initial loading hoặc server response pending.
- Có dữ liệu.
- Không có Department nào.
- Không có kết quả search/filter.
- Lỗi tải dữ liệu.
- Archived record được phân biệt rõ nếu được đưa vào kết quả.

### 6.2. Department Create/Edit

Route khuyến nghị:

- `/admin/departments/create`
- `/admin/departments/{department}/edit`

Ưu tiên dedicated form page thay vì modal vì form cần validation, manager selection và có thể phát triển thêm metadata. Có thể dùng chung một Blade form partial cho create/edit.

Fields:

- Department name — required.
- Department code — required, unique.
- Address — optional theo spec hiện tại.
- Manager — nullable nếu BA cho phép Department tạm thời chưa có leader; candidate phải là active Manager hợp lệ.

Actions:

- Cancel quay về list hoặc detail hợp lý.
- Create/Save Changes.

Validation behavior:

- Lỗi hiển thị sát field và giữ lại input hợp lệ.
- Code duplicate phải được báo rõ.
- Không hiển thị Citizen hoặc Staff như manager candidate.
- Edit phải vẫn hiển thị leader hiện tại nếu tài khoản vừa inactive, kèm warning và yêu cầu chọn lại trước khi lưu nếu rule bắt buộc.

### 6.3. Department Detail

Route khuyến nghị: `/admin/departments/{department}`.

Cấu trúc:

1. Header có Department name, code, status và actions theo quyền.
2. Department information card: name, code, address, created/updated metadata nếu hữu ích.
3. Manager card: identity, role/status và Change Manager action cho Super Admin.
4. Staff members card:
   - Search trong danh sách hiện tại khi cần.
   - Add Staff action.
   - Table: Staff, Email, Status, Joined Department/metadata có sẵn, Actions.
   - Remove và Transfer actions theo quyền.
5. Services card: danh sách Service Type liên quan ở chế độ read-only; CRUD thuộc F02.

Manager dùng cùng màn detail nhưng:

- chỉ truy cập Department mình lãnh đạo;
- có thể add/remove Staff theo spec;
- không sửa identity Department, archive Department hoặc đổi leader;
- transfer chỉ được phép khi Manager có quyền với cả Department nguồn và Department đích; nếu chỉ lãnh đạo một phía thì action không được hiển thị và backend phải từ chối.

### 6.4. Add Staff dialog

- Search/select chỉ trả về active User có role Staff hoặc Manager hợp lệ theo rule membership.
- Với Manager, candidate list chỉ gồm active Staff; Manager không được tự tổ chức các Manager khác.
- Loại bỏ user đã là member của Department khỏi candidate list.
- Hiển thị name, email, role và Department hiện tại để tránh gán nhầm.
- Confirm action ghi membership một lần; duplicate phải bị chặn cả UI và database.

### 6.5. Change Manager dialog

- Chỉ Super Admin thấy action.
- Candidate chỉ gồm active Manager.
- Hiển thị manager hiện tại và manager mới.
- Khi xác nhận, leader mới phải đồng thời là member của Department theo business rule.
- UI cần thông báo rõ nếu thao tác tự động thêm membership.

### 6.6. Transfer Staff dialog

- Hiển thị staff, Department nguồn và Department đích.
- Không cho chọn Department hiện tại làm đích.
- Operation phải được trình bày như một hành động nguyên tử: remove nguồn và add đích cùng thành công hoặc cùng thất bại.
- Sau thành công, quay lại detail phù hợp và hiển thị success feedback.

### 6.7. Remove Staff confirmation

- Nêu rõ tên Staff và Department.
- Chỉ xóa membership, không xóa hoặc deactivate User account.
- Nếu Staff là leader hiện tại, UI phải chặn và hướng người dùng đổi leader trước.

### 6.8. Archive Department confirmation

- Chỉ Super Admin thực hiện.
- Nêu rõ archive không xóa lịch sử nghiệp vụ.
- Nếu Department có quan hệ nghiệp vụ, vẫn giữ record và relationship theo rule soft delete/archive.
- Sau archive, các assignment mới vào Department phải bị chặn bởi backend.

## 7. Permission-driven presentation

| Khả năng | Super Admin | Manager | Staff/Citizen |
|---|:---:|:---:|:---:|
| Xem toàn bộ Department | Có | Không | Không |
| Xem Department mình lãnh đạo | Có | Có | Không |
| Tạo Department | Có | Không | Không |
| Sửa name/code/address | Có | Không | Không |
| Archive Department | Có | Không | Không |
| Đổi Manager | Có | Không | Không |
| Add/remove Staff | Có | Có, trong Department mình lãnh đạo | Không |
| Transfer Staff liên phòng | Có | Chỉ khi lãnh đạo cả nguồn và đích | Không |
| Sửa account/role/password | Không thuộc F03 | Không thuộc F03 | Không thuộc F03 |

UI ẩn hoặc disable action để giảm nhầm lẫn, nhưng authorization bắt buộc được enforce ở server bằng policy/gate/middleware phù hợp.

## 8. Responsive behavior

Figma là desktop-first. F03 cần giữ được chức năng trên viewport nhỏ hơn:

- Main horizontal padding: `32 px` desktop, `24 px` tablet, `16 px` mobile.
- Summary cards: 4 cột desktop, 2 cột tablet, 1 cột mobile.
- Filter controls được wrap theo hàng; search chiếm phần rộng nhất.
- Table dùng horizontal scroll ở viewport hẹp; không ép chữ thành cột quá nhỏ.
- Department name/code và action quan trọng phải vẫn dễ truy cập.
- Có thể chuyển row actions vào overflow menu trên mobile.
- Dialog có max width và co về viewport với margin `16 px`.
- Click/touch target nên đạt ít nhất khoảng `40–44 px`.
- Navbar tuân responsive shell chung của dự án; không tạo một navbar riêng chỉ cho F03.

## 9. Accessibility và localization

- UI implementation dùng nội dung tiếng Việt theo ngôn ngữ dự án; chuỗi tiếng Anh trong Figma chỉ là visual reference.
- Mỗi field có label thật, không dùng placeholder thay label.
- Focus state phải nhìn thấy rõ trên input, button, dropdown và row action.
- Modal khóa focus hợp lý, hỗ trợ Escape và trả focus về trigger khi đóng.
- Icon-only button cần accessible name/tooltip.
- Status luôn có text, không chỉ dùng màu.
- Lỗi validation cần liên kết với field và có summary nếu form dài.
- Confirmation copy phải phân biệt `xóa khỏi phòng ban` với `xóa tài khoản`.

## 10. Đối chiếu với CSS/code hiện tại

`resources/css/app.css` đang có component kích thước lớn thiên về Citizen UI:

- `.btn-primary`: khoảng `18 px`, padding `36 × 20 px`, radius `16 px`.
- `.input-field`: khoảng `18 px`, padding `24 × 16 px`, radius `16 px`.

Figma Admin dùng mật độ cao hơn:

- Button `14 px`, padding `20 × 10 px`, radius `10 px`.
- Filter/control khoảng `13 px`, radius gần `14 px`.

Không nên đổi global class hiện tại vì có thể làm hỏng màn Citizen. Bước plan nên tạo Admin-specific variants hoặc các Blade components có size variant rõ ràng, ví dụ `admin`, `compact`, thay vì ghi đè toàn cục.

Nền tảng thực thi được giữ theo codebase hiện tại:

- Laravel Blade SSR cho page rendering.
- Tailwind cho layout/style.
- Alpine.js cho dropdown, modal và interaction cục bộ.
- Server-side authorization, validation, filter và pagination.
- Không thêm React/Vue chỉ để triển khai F03.

## 11. Business states cần thể hiện trong UI

- Department code đã tồn tại.
- Department không có Manager.
- Manager candidate không hợp lệ hoặc inactive.
- Staff candidate không hợp lệ/inactive.
- User đã là member của Department.
- Staff đang là leader nên không thể remove.
- Transfer trùng Department nguồn.
- Department đã archived nên không thể nhận membership mới.
- Concurrent update: dữ liệu vừa thay đổi bởi admin khác.
- Operation thành công: create, update, add, remove, transfer, change leader, archive.

Thông báo lỗi phải mô tả hành động người dùng có thể làm tiếp, không chỉ hiển thị lỗi kỹ thuật.

## 12. Design verification checklist cho bước implement

- [ ] F03 không triển khai Add User, role edit, password hoặc account activation.
- [ ] Admin shell và visual density bám node Figma `4:2284`.
- [ ] Department List có search, filters, pagination và empty/no-result/error states.
- [ ] Department Detail thể hiện Manager, Staff và Services read-only.
- [ ] Tất cả action được ẩn/hiện đúng theo Super Admin và Manager.
- [ ] Archive/remove có confirmation và copy không gây hiểu nhầm với hard delete.
- [ ] Role/status badges có label, không phụ thuộc riêng vào màu.
- [ ] Form validation giữ input và chỉ ra lỗi sát field.
- [ ] Table sử dụng horizontal overflow hợp lý trên màn nhỏ.
- [ ] Admin-specific control sizes không làm thay đổi Citizen components.
- [ ] Không có `Application::all()` hoặc user/department list không phân trang khi dữ liệu có thể lớn.
- [ ] UI được kiểm tra lại ở desktop reference width và ít nhất một mobile width.

## 13. Kết luận dùng cho plan

Figma cung cấp một design system admin đủ rõ nhưng không cung cấp đầy đủ screen flow F03. Plan phải xây thêm Department List, Create/Edit, Detail và các membership/leader/archive dialogs, đồng thời giữ nguyên ranh giới nghiệp vụ: F03 quản lý cơ cấu `Department ↔ Manager/Staff`, không quản lý tài khoản và không xử lý hồ sơ Application.
