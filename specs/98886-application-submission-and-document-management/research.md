# Research: F04 - Application Submission & Document Management

## Decision: Mã hồ sơ `HS-YYYYMMDD-xxxxx` sinh qua bảng sequence riêng trong transaction

**Rationale**: Mã hồ sơ phải duy nhất kể cả khi nộp đồng thời và reset theo ngày. Dùng bảng
`application_code_sequences` (sequence_date + last_sequence, unique date) với `lockForUpdate`
dòng sequence của ngày để chống race condition; toàn bộ việc sinh mã + tạo hồ sơ + ghi history
nằm trong một transaction.

**Alternatives considered**: `MAX(application_code) + 1` bị loại vì không an toàn khi nộp đồng
thời và khó parse lại mã. Random string bị loại vì không mang tính tuần tự theo ngày như yêu cầu.

## Decision: Lưu tài liệu vào disk `local` (private) và download qua endpoint có authorization

**Rationale**: Constitution yêu cầu tài liệu Citizen upload phải ở private storage. Dùng disk `local`
(đặt trong `storage/app/private`) nên không có URL công khai nào trỏ tới file; mọi truy cập đều đi
qua `GET /api/v1/applications/{application}/documents/{document}` có kiểm tra Policy. Việc này thỏa
AC "lưu đúng disk private, không truy cập trực tiếp qua URL public".

**Alternatives considered**: Disk `public` với symlink bị loại vì tạo URL công khai có thể truy
cập ngoài authorization. Cloud storage (S3) bị loại vì môi trường mock local không cần và làm phức
tạp cài đặt/test. Trả stream nội dung qua base64 trong JSON bị loại vì kém hiệu quả và không đúng
kiểu HTTP file download.

## Decision: Validate loại file bằng MIME thực tế qua rule `mimes` của Laravel

**Rationale**: Rule `mimes:pdf,jpg,jpeg,png` của Laravel kiểm tra loại MIME thực tế (qua finfo),
không tin đuôi mở rộng, nên file đổi tên `.exe` thành `.pdf` vẫn bị từ chối — khớp edge case
"đuôi hợp lệ nhưng nội dung không phải PDF/ảnh". Kết hợp `max:10240` (10 MB) để từ chối file quá
dung lượng ngay tại tầng validation, không cần đọc toàn bộ file vào bộ nhớ.

**Alternatives considered**: Tự kiểm tra `finfo_file` trong Action bị loại vì lặp lại việc Laravel
đã làm trong rule. Rule `mimetypes` kết hợp kiểm tra đuôi bị loại vì dư thừa khi `mimes` đã kiểm
tra nội dung.

## Decision: Authorization đặt trong Policy, chủ hồ sơ + Staff/Manager/Super Admin được download

**Rationale**: Constitution yêu cầu mọi thao tác bảo vệ phải kiểm tra server-side qua
Policy/Gate (S005). Quyền download theo vai trò nội bộ được cấp ngay trong feature này theo quyết
định của team (mô tả ticket: "chỉ chủ hồ sơ và sau này staff/manager"). Upload/xóa vẫn giới hạn ở
chủ hồ sơ.

**Alternatives considered**: Dùng middleware `internal`/`citizen` làm điều kiện bị loại vì không
biểu diễn được quan hệ sở hữu theo từng tài nguyên. Kiểm tra `if` trong controller bị loại vì vi
phạm tách trách nhiệm và khó test riêng.

## Decision: Ràng buộc tài liệu thuộc đúng hồ sơ bằng route model binding có scope

**Rationale**: Route `.../documents/{document}` dùng `scopeBindings()` nên tài liệu được resolve
trong phạm vi `{application}` của URL. Tài liệu của hồ sơ khác → binding fail → 404, không lộ
thông tin chéo hồ sơ (edge case cuối trong spec).

**Alternatives considered**: Tự kiểm tra `$document->application_id === $application->id` trong
controller bị loại vì `scopeBindings()` đã làm việc này tại tầng routing, giảm code trùng lặp.

## Decision: Xóa mềm bằng trait `SoftDeletes` và giữ nguyên file trên disk

**Rationale**: Soft delete bảo toàn bản ghi metadata phục vụ kiểm toán (FR-013) và vẫn cần path
để truy vết; model bị ẩn khỏi mọi query thường nên download/liệt kê không thấy tài liệu đã xóa.
Không xóa file nhị phân ngay để tránh mất dữ liệu khi xóa nhầm; xử lý dọn file vật lý thuộc task
dọn dữ liệu sau này (ngoài phạm vi).

**Alternatives considered**: Xóa cứng bị loại vì mất dấu vết kiểm toán. Cờ `is_deleted` thủ công
bị loại vì `SoftDeletes` đã chuẩn hóa trong codebase.

## Decision: Xử lý file mất trên disk trả về 404 rõ ràng

**Rationale**: `Storage::download()` ném `FileNotFoundException` → 500 nếu file không còn. Kiểm
tra `Storage::exists()` trước khi download và trả về lỗi 404 với thông báo rõ ràng, khớp edge
case "file nhị phân bị mất: trả về lỗi rõ ràng thay vì lỗi hệ thống chung".

**Alternatives considered**: Bắt exception và trả 500 đã được ghi log bị loại vì user nhận lỗi
chung khó hiểu. Dựng lại file placeholder bị loại vì làm sai lệch dữ liệu.