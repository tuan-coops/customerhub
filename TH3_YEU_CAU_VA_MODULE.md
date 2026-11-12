# Yêu cầu dự án TH3 – EduCare CRM

## 1. Lĩnh vực

Website quản lý khách hàng có nhu cầu học ngoại ngữ tại trung tâm ngoại ngữ.

## 2. Công nghệ

- Frontend: HTML5, CSS3, Bootstrap 5.3, JavaScript thuần.
- Trao đổi dữ liệu: AJAX bằng Fetch API, phản hồi JSON, không tải lại toàn trang.
- Backend: PHP thuần, PDO, PHP Session.
- Cơ sở dữ liệu: MySQL.
- Font chữ duy nhất: **Be Vietnam Pro**, nhóm **sans-serif**.

## 3. Hai vai trò

Hệ thống có hai vai trò nghiệp vụ: Khách hàng và Admin. Khách hàng không có tài khoản; trong môi trường demo local, backend tự khởi tạo phiên Admin nên không có thao tác đăng nhập.

### 3.1. Khách hàng – không cần đăng nhập

- Xem giới thiệu trung tâm và các lợi ích tư vấn.
- Điền biểu mẫu đăng ký tư vấn.
- Cung cấp họ tên, số điện thoại, email, ngoại ngữ quan tâm, mục tiêu, lịch mong muốn và hình thức học.
- Gửi yêu cầu bằng AJAX và nhận thông báo kết quả ngay trên trang.
- Không được xem danh sách hoặc dữ liệu của khách hàng khác.

### 3.2. Quản trị viên – truy cập trực tiếp trong bản demo

- Xem Dashboard và số liệu tổng quan.
- Xem, tìm kiếm, lọc và phân trang danh sách khách hàng.
- Thêm hồ sơ khách hàng.
- Xem chi tiết hồ sơ khách hàng.
- Sửa thông tin và trạng thái tư vấn.
- Xóa khách hàng sau bước xác nhận.
- Ghi nhận lịch sử tư vấn.
- Tạo và hoàn thành lịch chăm sóc.
- Xem Dashboard có KPI, xu hướng khách hàng và phân bố trạng thái tư vấn.
- Xem báo cáo có bộ lọc thời gian, xu hướng khách hàng, phễu trạng thái, nguồn tiếp cận và tỷ lệ chuyển đổi.

## 4. Module được chọn để thiết kế chi tiết

### Module Quản lý thông tin khách hàng

#### Dữ liệu đầu vào

- Họ và tên.
- Số điện thoại.
- Email.
- Nghề nghiệp.
- Ngoại ngữ quan tâm.
- Mục tiêu học tập.
- Trình độ hiện tại.
- Lịch học mong muốn.
- Hình thức học.
- Nguồn khách hàng.
- Trạng thái tư vấn.
- Trạng thái hồ sơ.
- Ghi chú.

#### Chức năng CRUD

1. **Create – Thêm:** Khách hàng tự gửi yêu cầu hoặc Admin thêm hồ sơ.
2. **Read – Xem:** Admin xem danh sách, tìm kiếm, lọc, phân trang và xem chi tiết.
3. **Update – Sửa:** Admin cập nhật thông tin và trạng thái bằng Bootstrap Modal + AJAX.
4. **Delete – Xóa:** Admin xác nhận trước khi xóa; backend xóa hồ sơ và dữ liệu liên quan trong quan hệ cascade.

#### API AJAX

| Nghiệp vụ | Endpoint | Quyền |
|---|---|---|
| Khách hàng gửi yêu cầu | `POST api/customers/public-create.php` | Công khai |
| Danh sách khách hàng | `GET api/customers/list.php` | Admin |
| Xem chi tiết | `GET api/customers/detail.php?id=...` | Admin |
| Admin thêm | `POST api/customers/create.php` | Admin |
| Admin sửa | `POST api/customers/update.php` | Admin |
| Admin xóa | `POST api/customers/delete.php` | Admin |

#### Luồng chính

**Luồng khách hàng:**

`Khách hàng mở consultation.html → điền form → JavaScript kiểm tra dữ liệu → Fetch gửi POST → public-create.php xác thực CSRF và dữ liệu → PDO thêm hồ sơ lead → JSON phản hồi → trang hiện thông báo và mã yêu cầu.`

**Luồng Admin:**

`Admin mở customers.html → backend tự khởi tạo Session Admin → Fetch tải danh sách → chọn thêm/sửa/xóa → Bootstrap Modal xác nhận → PHP API kiểm tra quyền và CSRF → PDO cập nhật MySQL → JSON phản hồi → bảng tải lại bằng AJAX.`

#### Thiết kế giao diện module

- Thanh bộ lọc gồm từ khóa, trạng thái tư vấn và trạng thái hồ sơ.
- Bảng dữ liệu gồm khách hàng, nhu cầu, trạng thái, người phụ trách, ngày cập nhật và thao tác.
- Nhóm thao tác trên mỗi dòng: Xem, Sửa, Xóa.
- Form thêm/sửa dùng chung một Bootstrap Modal; tiêu đề, nút lưu và endpoint thay đổi theo chế độ.
- Xóa sử dụng Modal xác nhận riêng và cảnh báo dữ liệu liên quan sẽ bị xóa cascade.
- Sau mỗi thao tác thành công, danh sách được tải lại bằng Fetch API, không tải lại toàn trang.

`Khách hàng điền form → AJAX gửi API → PHP kiểm tra dữ liệu → PDO lưu MySQL → JSON phản hồi → giao diện hiện thông báo.`

`Admin mở trực tiếp danh sách → tìm/chọn khách hàng → thêm/sửa/xóa bằng AJAX → bảng cập nhật không tải lại trang.`

#### Quy tắc kiểm tra

- Họ tên, số điện thoại, ngoại ngữ quan tâm là bắt buộc.
- Số điện thoại phải đúng định dạng và không trùng.
- Email phải đúng định dạng nếu được nhập.
- Các API quản trị kiểm tra Session và vai trò Admin ở backend.
- Các yêu cầu thay đổi dữ liệu phải có CSRF token.
- SQL sử dụng PDO Prepared Statement.
- Dữ liệu đưa ra giao diện phải được escape để chống XSS.
