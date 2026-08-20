# Phase 0 Research: F08 - Import/Export & API Documentation

## Technical Decisions & Research Findings

### 1. CSV Import Handling & Row-by-Row Validation
- **Decision**: Sử dụng `fgetcsv` kết hợp với `fopen` native trong PHP/Laravel và `Validator::make` của Laravel cho từng dòng thay vì thêm package nặng như `maatwebsite/excel`.
- **Rationale**:
  - Tôn trọng Nguyên tắc I trong Hiến pháp dự án (*Laravel-First & Simplicity*): Không cài đặt các package quá phức tạp khi các hàm xử lý stream của PHP native và Laravel Validator hoàn toàn đáp ứng xuất sắc.
  - Xử lý được file CSV dung lượng lớn (Memory Efficient) bằng cách đọc từng stream line thay vì load toàn bộ file vào bộ nhớ.
  - Cho phép kiểm tra chi tiết từng dòng dữ liệu và ghi lại chính xác dòng lỗi (`line_number`), cột vi phạm (`field`) và thông báo lỗi (`message`).
- **Alternatives Considered**:
  - `maatwebsite/excel`: Package phổ biến nhưng nặng, kéo theo nhiều phụ thuộc không cần thiết đối với thao tác CSV cơ bản.
  - `league/csv`: Gọn nhẹ hơn nhưng native PHP `fgetcsv` + `SplFileObject` của Laravel vẫn tối ưu hơn về mặt đơn giản.

### 2. CSV Export Streaming & Memory Efficiency
- **Decision**: Sử dụng `Symfony\Component\HttpFoundation\StreamedResponse` kết hợp `fputcsv` ghi ra `php://output`. Thêm UTF-8 BOM (`\xEF\xBB\xBF`) ở đầu file.
- **Rationale**:
  - Cho phép xuất danh sách hàng ngàn bản ghi mà không vượt quá bộ nhớ RAM cho phép (Chunked processing với `cursor()` hoặc `chunk(1000)` trong Eloquent).
  - Chuỗi UTF-8 BOM đảm bảo khi Admin mở file CSV bằng Microsoft Excel trên Windows sẽ tự động nhận diện đúng tiếng Việt có dấu mà không bị vỡ font.
  - Tôn trọng bộ lọc tìm kiếm hiện tại bằng cách tái sử dụng Query Builder scope từ các Controller/Service hiện có.
- **Alternatives Considered**:
  - Export dạng Excel `.xlsx`: Đòi hỏi thư viện nén zip và XML phức tạp, CSV đáp ứng tốt yêu cầu nhẹ và tương thích cao.

### 3. REST API Envelope & Sanctum Authorization
- **Decision**: Tận dụng `Illuminate\Http\Resources\Json\JsonResource` và `ResourceCollection` sẵn có của Laravel với custom envelope structure:
  ```json
  {
    "data": { ... },
    "meta": { "timestamp": "...", "version": "v1" }
  }
  ```
  Dùng Laravel Sanctum middleware (`auth:sanctum`) cho authentication và Policy/Gate cho authorization.
- **Rationale**:
  - Đúng chuẩn RESTful đã thiết lập tại Nguyên tắc VI (*Citizen React SPA & Admin Blade SSR*).
  - Tận dụng cơ chế FormRequest validation của Laravel (trả về 422 tự động khi thất bại).

### 4. API Documentation Generation
- **Decision**: Tích hợp `dedoc/scramble` (hoặc Scramble OpenAPI Generator dành cho Laravel REST APIs).
- **Rationale**:
  - Scramble tự động đọc các route thuộc `/api/v1/`, trích xuất kiểu dữ liệu từ FormRequest, Eloquent Models và API Resources mà không bắt buộc viết hàng ngàn dòng PHPDoc thủ công.
  - Cung cấp giao diện Swagger/OpenAPI UI trực quan tại route `/docs/api` cho Admin và Developers.
- **Alternatives Considered**:
  - `darkaonline/l5-swagger`: Cần viết quá nhiều file annotation PHPDoc thủ công, dễ dẫn tới vỡ sync giữa code và tài liệu.
