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

## Cập nhật LMS

Sau khi upload một bản cập nhật có LMS, chạy script cập nhật nội bộ (`install.php` hoặc tên file updater đã được đổi trên hosting) một lần. Script này chạy migration idempotent tại `database/migrations/lms_schema.php`, chỉ tạo bảng và cột LMS còn thiếu.

Nếu không thể chạy updater, import cả `database/migrations/add_course_tables.sql` và `database/migrations/20260813_lms_bridge.sql` bằng công cụ quản trị cơ sở dữ liệu. Sau đó gán các quyền `view_course`, `edit_course` và `manage_students_course` cho các vai trò quản trị phù hợp.
