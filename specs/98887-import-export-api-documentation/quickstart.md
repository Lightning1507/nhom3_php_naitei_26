# Quickstart & Verification Guide: F08 - Import/Export & API Documentation

## 1. Automated Verification

Chạy bộ kiểm thử tự động cho các chức năng Import/Export CSV và API Documentation:

```bash
# Chạy bộ test tính năng Import/Export Admin
php artisan test --filter=AdminImportExportTest

# Chạy bộ test xác thực chuẩn hóa REST API v1
php artisan test --filter=ApiV1StandardizationTest
```

---

## 2. Manual Verification Walkthrough

### Scenario A: Import danh sách Citizen từ CSV có chứa hàng lỗi
1. Đăng nhập vào trang Admin (`/admin/login`) bằng tài khoản Admin (`admin@example.com`).
2. Truy cập vào trang Quản lý Công dân -> nhấn nút **Import CSV**.
3. Tải lên tệp `sample_citizens.csv` gồm 5 dòng (3 dòng đúng format, 1 dòng email sai format, 1 dòng trùng CCCD).
4. **Kỳ vọng**:
   * Giao diện thông báo: "Đã nhập thành công 3/5 tài khoản".
   * Hiển thị bảng danh sách 2 lỗi với thông tin chi tiết:
     * Dòng 4: Lỗi trùng số CCCD.
     * Dòng 5: Lỗi sai định dạng Email.
   * Kiểm tra trong CSDL thấy 3 tài khoản hợp lệ đã được tạo với vai trò `citizen`.

### Scenario B: Export danh sách Hồ sơ (Applications) ra CSV theo bộ lọc
1. Đăng nhập Admin, vào trang Quản lý Hồ sơ (`/admin/applications`).
2. Chọn lọc trạng thái "Đang xử lý" (`processing`) và tìm kiếm theo tên dịch vụ "Cấp lại CCCD".
3. Nhấn nút **Export CSV**.
4. Mở tệp CSV vừa tải về bằng Excel.
5. **Kỳ vọng**:
   * File tải về có tên `applications-export-YYYYMMDD.csv`.
   * Nội dung hiển thị tiếng Việt chuẩn có dấu, chỉ chứa các hồ sơ khớp đúng bộ lọc đã chọn.

### Scenario C: Xem API Documentation trực quan
1. Truy cập URL `/docs/api` trên trình duyệt.
2. **Kỳ vọng**:
   * Trang Swagger/Scramble hiển thị đầy đủ giao diện danh sách API `/api/v1/...`.
   * Kiểm thử nút "Try it out" gửi yêu cầu thành công khi cung cấp Bearer Token.
