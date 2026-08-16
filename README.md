# Smartstudy.vn

Mã nguồn website chính thức của [Smartstudy.vn](https://smartstudy.vn), được quản lý nội bộ bởi Smartstudy.vn.

## Cấu hình

1. Sao chép `.env.example` thành `.env`.
2. Điền thông tin kết nối cơ sở dữ liệu và các khóa bí mật tại môi trường triển khai.
3. Cài đặt các phụ thuộc theo `composer.lock` khi cần.

## Lưu ý bảo mật

- Không commit `.env`, bản dump cơ sở dữ liệu, log hoặc các gói sao lưu.
- Dữ liệu vận hành trên hosting phải được sao lưu trước mỗi lần triển khai.
- Các định danh dữ liệu cũ được giữ lại trong mã chỉ nhằm bảo đảm tương thích với cơ sở dữ liệu hiện tại.

