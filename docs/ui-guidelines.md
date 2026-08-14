# Public Service Management - UI Guidelines & Screen Mapping

Tài liệu này ánh xạ các Feature trong Development Plan với thiết kế trên Figma, xác định các màn hình hiện có và các màn hình cần bổ sung, đồng thời cung cấp hướng dẫn triển khai UI theo đúng Tech Stack yêu cầu.

## 1. Tech Stack Overview

> **Lưu ý:** Chi tiết về toàn bộ công nghệ và kiến trúc hệ thống (React SPA, Laravel REST API, Blade SSR, Alpine.js) đã được quy định đầy đủ tại tài liệu [Technology Stack](technology-stack.md).

Tài liệu UI Guidelines này sẽ không đi sâu vào việc giải thích kiến trúc nữa, mà chỉ tập trung vào **Ánh xạ màn hình Figma (Feature to Screen Mapping)** và **hướng dẫn triển khai các thành phần giao diện (UI Components)**.

---

## 2. Feature to Screen Mapping

Dựa trên thiết kế Figma hiện tại, dưới đây là phân tích chi tiết từng Feature:

### F01 – Authentication, User Profile & Authorization
*   **Gồm các màn hình:** Đăng nhập, Đăng ký, Quên mật khẩu, Thông tin tài khoản.
*   **Đã có trên Figma:** (Chưa thiết kế)
*   **Cần bổ sung:**
    *   `Citizen - Login / Register`
    *   `Citizen - Profile` (Quản lý thông tin cá nhân)
    *   `Admin - Login` (Dành cho cán bộ)

### F02 – Public Service Catalog Management
*   **Gồm các màn hình:** Trang chủ, Danh sách dịch vụ công, Chi tiết dịch vụ công, Quản lý danh mục (Admin).
*   **Đã có trên Figma:**
    *   `Citizen - Home` (Trang chủ)
    *   `Citizen - Services` (Danh sách dịch vụ)
*   **Cần bổ sung:**
    *   `Citizen - Service Detail` (Chi tiết thủ tục, phí, thời gian)
    *   `Admin - Service Management` (CRUD dịch vụ công)

### F03 – Department & Staff Management
*   **Gồm các màn hình:** Quản lý phòng ban, Quản lý nhân viên (phân quyền).
*   **Đã có trên Figma:**
    *   `Super Admin` (Thực chất màn hình này đang hiển thị giao diện **User Management**: bao gồm danh sách Staff/Manager, thống kê số lượng User, các bộ lọc và nút Add User).
*   **Cần bổ sung:**
    *   `Admin - Department Management` (Có thể dùng chung phong cách với User Management)
    *   Modal/Form thêm mới/chỉnh sửa Staff và Department.

### F04 – Application Submission & Document Management
*   **Gồm các màn hình:** Các bước nộp hồ sơ, Tải lên tài liệu.
*   **Đã có trên Figma:**
    *   `Citizen - Apply Step 1`
    *   `Citizen - Apply Step 2`
    *   `Citizen - Apply Step 3`
*   **Cần bổ sung:** Đã tương đối đầy đủ các bước nộp. Cần bổ sung màn hình xem chi tiết Application sau khi nộp (My Applications).

### F05 – Application Processing Workflow
*   **Gồm các màn hình:** Tiếp nhận, Xử lý hồ sơ, Yêu cầu bổ sung, Duyệt/Từ chối hồ sơ.
*   **Đã có trên Figma:**
    *   `Staff` (Màn hình Workspace của chuyên viên: gồm cả Inbox danh sách hồ sơ bên trái và Form chi tiết/PDF Viewer bên phải)
    *   `Manager` (Màn hình Assignment Board dạng Kanban để kéo thả gán hồ sơ cho nhân viên, kèm theo Dashboard thống kê hồ sơ pending/overdue)
*   **Cần bổ sung:**
    *   Các Modal thao tác phụ nếu chưa có (ví dụ: Modal nhập lý do khi Reject/Request Supplement).

### F06 – Application History, Notifications & Audit Log
*   **Gồm các màn hình:** Theo dõi tiến độ hồ sơ, Lịch sử xử lý, Activity log cho Admin.
*   **Đã có trên Figma:**
    *   `Citizen - Track Application` (Theo dõi tình trạng hồ sơ)
*   **Cần bổ sung:**
    *   `Admin - Audit Log / Activity History` (Danh sách tra cứu log hệ thống)

### F07 – Admin Management & Search
*   **Gồm các màn hình:** Tìm kiếm nâng cao, Quản lý người dùng, Thống kê.
*   **Đã có trên Figma:**
    *   `Manager` (Đã chứa Dashboard/Statistics cơ bản)
    *   `Super Admin` (Đã chứa khung User Management)
*   **Cần bổ sung:**
    *   `Admin - Application Search & Filter` (Màn hình tìm kiếm hồ sơ nâng cao toàn hệ thống)

### F08 – Import/Export & API Documentation
*   **Gồm các màn hình:** Giao diện Import/Export, Swagger UI.
*   **Đã có trên Figma:** (Không áp dụng UI từ Figma)
*   **Cần bổ sung:**
    *   Các nút/modal Import/Export file CSV trên trang danh sách Staff/Citizen/Application.
    *   Tích hợp sẵn `/api/docs` bằng Swagger.

---

## 3. Hướng dẫn chi tiết triển khai (Implementation Guide)

### 3.1. Citizen Site (ReactJS + Tailwind CSS)
*   **Tổ chức Component:** Chia nhỏ các UI component dùng chung như `Button`, `Input`, `Card`, `Modal`, `StepIndicator`.
*   **State Management:** Sử dụng React State hoặc Context cho chức năng nộp hồ sơ nhiều bước (`Citizen - Apply Step 1-3`).
*   **API Integration:** Sử dụng `axios` hoặc `fetch`, tạo các custom hook (VD: `useAuth`, `useServices`, `useApplications`) để kết nối với Laravel REST API.
*   **Routing:** Sử dụng React Router (`react-router-dom`) cho các trang: `/`, `/services`, `/apply`, `/track`.
*   **Xử lý Form:** Nên sử dụng thư viện như `react-hook-form` để tối ưu validate và submit.

### 3.2. Admin Site (Laravel Blade + Alpine.js)
*   **Layout Structure:** Sử dụng Blade components để tạo layout chung (`layouts.admin`, `components.sidebar`, `components.header`).
*   **Tương tác UI với Alpine.js:** 
    *   Sử dụng `x-data` để quản lý trạng thái local cho các UI component động.
    *   Xử lý validate cơ bản, đóng/mở modal, menu xổ xuống (dropdown).
*   **Ví dụ Alpine.js + Tailwind (Modal Duyệt Hồ Sơ):**
    ```html
    <div x-data="{ showModal: false }">
        <button @click="showModal = true" class="bg-blue-600 text-white px-4 py-2 rounded">
            Duyệt hồ sơ
        </button>

        <!-- Modal Overlay -->
        <div x-show="showModal" style="display: none;" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div @click.away="showModal = false" class="bg-white p-6 rounded shadow-lg max-w-md w-full">
                <h3 class="text-lg font-bold mb-4">Xác nhận duyệt hồ sơ?</h3>
                <form action="{{ route('admin.applications.approve', $app->id) }}" method="POST">
                    @csrf
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showModal = false" class="px-4 py-2 text-gray-600 border rounded">Hủy</button>
                        <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">Đồng ý</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    ```

### 3.3. Khả năng tái sử dụng giữa Citizen và Admin (Shared UI)
Do sự khác biệt về bản chất công nghệ (ReactJS dùng JSX vs Laravel dùng Blade PHP), hai site **không thể dùng chung Component logic/code trực tiếp**. Tuy nhiên, hoàn toàn có thể đồng bộ về mặt Giao diện (UI) thông qua Tailwind:
*   **Dùng chung cấu hình Tailwind (`tailwind.config.js`)**: Cả 2 hệ thống có thể chung một bộ mã màu (colors), font chữ (typography), và khoảng cách (spacing) được trích xuất từ Figma.
*   **Dùng chung các lớp CSS tự định nghĩa (CSS Components)**: Thay vì viết chuỗi class dài dòng, có thể định nghĩa các component UI dùng chung trong file CSS gốc bằng `@apply` (Ví dụ: `.btn-primary { @apply bg-blue-600 text-white px-4 py-2 rounded; }`). Cả React và Blade đều chỉ cần gọi `class="btn-primary"`.
*   **Dùng chung Assets**: Các icon (SVG), hình ảnh logo, web fonts dùng chung một thư mục public/assets.

## 4. Hành động tiếp theo (Next Steps)
1. **Phân chia Task:** Cập nhật lại `tasks.md` bổ sung các ticket về UI chưa có trong thiết kế (Login, Admin CRUD).
2. **Citizen Team:** Khởi tạo project React, cài đặt Tailwind, định nghĩa Routes, code giao diện các trang đã có trên Figma (Home, Services, Apply 1-3, Track).
3. **Admin Team:** Khởi tạo project Laravel, setup Layout Blade kết hợp Alpine.js, code giao diện Manager, Staff, Super Admin dựa theo bản nháp trên Figma.
