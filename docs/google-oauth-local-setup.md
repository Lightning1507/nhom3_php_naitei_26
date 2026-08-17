# Google OAuth Local Setup

Tài liệu này mô tả các bước cấu hình Google OAuth khi chạy dự án ở local.
Mỗi thành viên cần tự cấu hình trên máy của mình vì PHP, browser cookie và
Google Cloud OAuth client là thiết lập theo môi trường.

## 1. Google Cloud Console

Trong Google Cloud Console, tạo hoặc dùng OAuth Client loại **Web application**.

Thêm Authorized redirect URI đúng với local URL đang dùng:

```text
http://127.0.0.1:8000/api/v1/auth/google/callback
```

Nếu dùng `localhost` thay vì `127.0.0.1`, mọi nơi phải dùng `localhost` thống
nhất. Không trộn `localhost` với `127.0.0.1` vì OAuth state dùng session cookie
và có thể bị mất session.

## 2. Local `.env`

Copy Google Client ID và Client Secret vào `.env` local:

```env
APP_URL=http://127.0.0.1:8000

GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/api/v1/auth/google/callback

SESSION_DOMAIN=
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax
```

Sau khi đổi `.env`, clear cache và restart server:

```powershell
php artisan optimize:clear
php artisan serve --host=127.0.0.1 --port=8000
```

## 3. PHP CA certificate trên Windows

Nếu đăng nhập Google lỗi và log có dạng:

```text
cURL error 60: SSL certificate OpenSSL verify result: unable to get local issuer certificate
```

PHP local đang thiếu CA certificate bundle để gọi HTTPS tới Google.

Tải file CA bundle:

```text
https://curl.se/ca/cacert.pem
```

Lưu vào ví dụ:

```text
D:\php-8.5\extras\ssl\cacert.pem
```

Mở file `php.ini` mà PHP đang dùng. Có thể kiểm tra bằng:

```powershell
php --ini
```

Thêm hoặc sửa:

```ini
curl.cainfo="D:\php-8.5\extras\ssl\cacert.pem"
openssl.cafile="D:\php-8.5\extras\ssl\cacert.pem"
```

Kiểm tra lại:

```powershell
php -i | findstr /C:"curl.cainfo" /C:"openssl.cafile"
```

Kết quả phải trỏ tới file `cacert.pem` vừa cấu hình. Sau đó restart
`php artisan serve`.

## 4. Lỗi thường gặp

### `InvalidStateException`

Nguyên nhân thường là OAuth callback không đọc được session state đã tạo ở bước
redirect. Kiểm tra:

- `APP_URL` và `GOOGLE_REDIRECT_URI` dùng cùng một host.
- Browser mở đúng host trong `.env`.
- `SESSION_DOMAIN=` để trống ở local.
- Đã chạy `php artisan optimize:clear` và restart server.

### `redirect_uri_mismatch`

Authorized redirect URI trong Google Cloud Console chưa khớp chính xác với
`GOOGLE_REDIRECT_URI` trong `.env`.

### `invalid_client`

Google Client ID hoặc Client Secret trong `.env` sai, thiếu hoặc lấy nhầm OAuth
client.

### `ERR_BLOCKED_BY_CLIENT`

Nếu console browser có request như `play.google.com/log ... ERR_BLOCKED_BY_CLIENT`,
đây thường là request telemetry của Google bị ad blocker chặn. Nó không phải lỗi
OAuth callback của dự án.
