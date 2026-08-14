Public Service Management – 10-Day Development Plan

## 1. Cách tổ chức

*   **Team**: 4 thành viên
*   **Thời gian**: 10 ngày
*   **Mô hình**: Agile – 1 Sprint
*   **Development approach**: Feature-based với Spec-Kit
*   **Timeline**:
    *   **Day 1**: Requirement analysis + Database Design + Feature Planning
    *   **Day 2–8**: Implement features
    *   **Day 9**: Integration + Testing + Bug fixing
    *   **Day 10**: Final testing + Documentation + Demo
*   **Mỗi feature đi theo flow**: Specify → Plan → Tasks → Implement → Test → Merge
*   **Nguyên tắc**:
    *   Mỗi feature phải chạy được độc lập trước khi merge.
    *   Không để integration đến ngày cuối.
    *   PR nhỏ, review chéo giữa các thành viên.
    *   Các task nên ≤ 8 giờ/task.

## 2. Feature Backlog

Đây mới chỉ là các feature, sau khi dùng spec-kit để phân tích sẽ ra các task.
=> Có các task => Dùng lệnh `/implement` của speckit để code.

### F01 – Authentication, User Profile & Authorization
*   **Mục tiêu**: Xây dựng nền tảng user và phân quyền.
*   **Bao gồm**:
    *   Register/Login/Logout cho Citizen.
    *   Admin/Staff login.
    *   Citizen profile.
    *   Role: Citizen / Staff / Manager / Super Admin.
    *   Citizen chỉ truy cập dữ liệu của chính mình.
    *   Middleware + Policy/Gate.
    *   CCCD unique và không cho Citizen tự thay đổi.
*   **Estimate**: 1.5 ngày
*   **Deadline**: cuối Day 3.

### F02 – Public Service Catalog Management
*   **Mục tiêu**: Quản lý danh mục dịch vụ công mà Citizen có thể đăng ký.
*   **Bao gồm**:
    *   Service Category.
    *   Service Type.
    *   CRUD Service cho Admin.
    *   Service detail: mô tả, yêu cầu hồ sơ, phí, thời gian xử lý.
    *   Citizen xem/search/filter danh sách dịch vụ.
    *   Active/Inactive hoặc Soft Delete Service.
*   **Estimate**: 1.5 ngày
*   **Deadline**: cuối Day 3.

### F03 – Department & Staff Management
*   **Mục tiêu**: Quản lý cơ cấu đơn vị và cán bộ xử lý hồ sơ.
*   **Bao gồm**:
    *   CRUD Department.
    *   CRUD/Manage Staff.
    *   Gán Staff vào Department.
    *   Manager của Department.
    *   Phân quyền Staff/Manager.
*   **Estimate**: 1 ngày
*   **Deadline**: cuối Day 3.

### F04 – Application Submission & Document Management
*   **Mục tiêu**: Citizen có thể thực sự nộp hồ sơ dịch vụ công.
*   **Bao gồm**:
    *   Citizen tạo Application.
    *   Chọn Service Type.
    *   Tự sinh mã hồ sơ, ví dụ HS-20260811-00001.
    *   Upload PDF/Image.
    *   Private file storage.
    *   My Applications.
    *   Application Detail.
    *   Download document có authorization.
*   **Estimate**: 2 ngày
*   **Deadline**: cuối Day 4.

### F05 – Application Processing Workflow
*   **Mục tiêu**: Mô phỏng quy trình cán bộ xử lý hồ sơ thật.
*   **Workflow**: Received → Processing → Supplement Required → Processing → Approved / Rejected
*   **Bao gồm**:
    *   Manager assign/reassign Staff.
    *   Staff nhận hồ sơ.
    *   Start Processing.
    *   Request Supplement.
    *   Citizen bổ sung tài liệu.
    *   Approve.
    *   Reject + reason.
    *   Result document/note.
    *   Validate các status transition.
    *   Transaction khi cập nhật trạng thái.
*   **Estimate**: 2 ngày
*   **Deadline**: cuối Day 6.
*   Đây là feature có business logic phức tạp nhất.

### F06 – Application History, Notifications & Audit Log
*   **Mục tiêu**: Toàn bộ quá trình xử lý có khả năng theo dõi và truy vết.
*   **Bao gồm**:
    *   Application Status History.
    *   Citizen xem timeline xử lý.
    *   Database Notification.
    *   Email notification nếu còn thời gian.
    *   Activity Log:
        *   login
        *   create/update service
        *   assign application
        *   approve/reject...
    *   Admin xem/search Activity Log.
*   **Estimate**: 1.5 ngày
*   **Deadline**: cuối Day 7.

### F07 – Admin Management & Search
*   **Mục tiêu**: Hoàn thiện các chức năng quản trị.
*   **Bao gồm**:
    *   Admin Application List.
    *   Search/filter theo:
        *   mã hồ sơ
        *   Citizen
        *   Service
        *   Department
        *   Status
    *   Pagination.
    *   User management.
    *   Application management.
    *   Basic dashboard/statistics nếu đủ thời gian.
*   **Estimate**: 1.5 ngày
*   **Deadline**: cuối Day 7.

### F08 – Import/Export & API Documentation
*   **Mục tiêu**: Hoàn thiện các requirement hỗ trợ và chuẩn hóa API.
*   **Bao gồm**:
    *   Import Citizen/Staff bằng CSV.
    *   Validate từng row.
    *   Báo cáo row lỗi.
    *   Export:
        *   Citizens
        *   Applications
        *   Services
        *   Departments
        *   Staff
    *   REST API `/api/v1/....`
    *   OpenAPI/Swagger/Scramble documentation.
*   **Estimate**: 1 ngày
*   **Deadline**: cuối Day 8.
