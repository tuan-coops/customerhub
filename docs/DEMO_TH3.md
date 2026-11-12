# Kịch bản Báo cáo & Trình diễn (Demo Script) – BTL TH3 EduCare CRM

**Hệ thống:** Website Quản lý thông tin khách hàng trung tâm ngoại ngữ (EduCare CRM)  
**Công nghệ:** Frontend HTML5 + CSS3 + Bootstrap 5.3 + JavaScript thuần (Fetch API / AJAX), Backend PHP thuần + PDO + MySQL. Font chữ: **Be Vietnam Pro** (Sans-serif).

---

## I. Tóm tắt đáp ứng yêu cầu môn học (TH3)

| Yêu cầu đề bài | Cách hệ thống đáp ứng |
|---|---|
| **1. Đầy đủ CRUD** | Thiết kế module trọng tâm **Quản lý thông tin khách hàng**: Thêm (Create), Xem (Read/Search), Sửa (Update), Xóa (Delete). |
| **2. Sử dụng AJAX thuần** | Mọi thao tác CRUD, tìm kiếm, lọc, phân trang đều dùng native **Fetch API**, gửi/nhận JSON/FormData mà **không tải lại trang**. |
| **3. Có 2 vai trò** | **Khách hàng**: Truy cập trang công khai `consultation.html`, đăng ký tư vấn, xem bản đồ & fanpage (không cần tài khoản).<br>**Admin**: Truy cập `customers.html`, tự động nhận quyền phiên quản trị để thực hiện CRUD (không cần form đăng nhập). |
| **4. Thông tin liên hệ đã nhúng** | Nhúng bản đồ **Google Maps iframe** và **Facebook Fanpage iframe** chính thức của trung tâm. |
| **5. Font chữ Sans-serif** | Sử dụng font **Be Vietnam Pro** chuẩn tiếng Việt, giao diện hiện đại, chuẩn Responsive trên cả PC và Mobile. |

---

## II. Kịch bản Demo 4 bước tự tin lấy điểm cao

### Bước 1: Khách hàng vào trang công khai (`consultation.html`)
- **URL:** `http://127.0.0.1:8000/consultation.html`
- **Hành động trình bày:**
  1. Giới thiệu giao diện trung tâm ngoại ngữ EduCare: các khóa học (IELTS, TOEIC, Tiếng Nhật, Hàn, Trung), quy trình tư vấn và câu hỏi thường gặp FAQ.
  2. Cuộn xuống xem **Bản đồ Google Maps** chỉ đường đến trung tâm và **Fanpage Facebook** được nhúng trực tiếp bằng iframe.
  3. Thực hiện chức năng **Đăng ký tư vấn (Thêm mới từ phía Khách hàng qua AJAX)**:
     - Điền Họ tên, Số điện thoại, Ngoại ngữ quan tâm, Mục tiêu học.
     - Bấm **"Gửi yêu cầu tư vấn miễn phí"**.
     - Form gửi bằng AJAX đến `api/customers/public-create.php`, trả về thông báo thành công kèm mã hồ sơ (ví dụ: `KH2026110001`) ngay lập tức mà không tải lại trang.

---

### Bước 2: Admin mở trang Quản lý khách hàng (`customers.html`)
- **Hành động:** Bấm nút **"Quản trị Khách hàng"** ở góc phải thanh navbar trên cùng.
- **Điểm nhấn:**
  - Không cần gõ tài khoản/mật khẩu rườm rà (Backend tự động cấp phiên Admin qua `includes/auth.php`).
  - Giao diện Admin chuyên nghiệp, bảng danh sách khách hàng dàn trang rộng rãi, hiện đại.
  - Khách hàng vừa đăng ký ở Bước 1 lập tức xuất hiện ngay đầu bảng danh sách.

---

### Bước 3: Trình diễn tính năng Xem & Tìm kiếm (Read & Search bằng AJAX)
- **Hành động:**
  1. **Tìm kiếm tức thời:** Gõ tên hoặc số điện thoại vào ô tìm kiếm -> Bảng tự động lọc kết quả sau 350ms (Debounce) thông qua API `api/customers/list.php`.
  2. **Bộ lọc trạng thái & nguồn:** Chọn lọc theo Trạng thái (Tiềm năng, Quan tâm cao, Đã đăng ký...) hoặc theo Nguồn (Website, Facebook, Hotline).
  3. **Phân trang AJAX:** Bấm các số trang bên dưới để chuyển trang mượt mà mà không giật hay load lại trang.

---

### Bước 4: Trình diễn tính năng Thêm - Sửa - Xóa (Create, Update, Delete)
- **Hành động:**
  1. **Thêm khách hàng (Create):**
     - Bấm nút **"+ Thêm khách hàng"** -> Mở Bootstrap Modal.
     - Nhập thông tin khách hàng mới -> Bấm **"Lưu khách hàng"**.
     - AJAX gửi POST đến `api/customers/create.php`, lưu vào MySQL và tự động vẽ lại bảng kèm Toast thông báo thành công.
  2. **Sửa thông tin khách hàng (Update):**
     - Bấm nút **"Sửa"** ở một dòng bất kỳ -> Modal hiển thị dữ liệu hiện tại của khách hàng đó.
     - Đổi trạng thái từ *"Tiềm năng"* sang *"Đã đăng ký"* hoặc đổi số điện thoại, ghi chú -> Bấm **"Cập nhật thông tin"**.
     - AJAX gửi POST đến `api/customers/update.php`, cập nhật MySQL ngay lập tức.
  3. **Xóa khách hàng (Delete):**
     - Bấm nút **"Xóa"** ở một khách hàng -> Modal xác nhận màu đỏ cảnh báo hiện ra để tránh xóa nhầm.
     - Bấm **"Xóa vĩnh viễn"** -> AJAX gửi POST đến `api/customers/delete.php`, bản ghi biến mất khỏi bảng và hiển thị Toast thành công.

---

## III. Các câu hỏi vấn đáp thường gặp và câu trả lời mẫu

### 1. AJAX trong dự án được triển khai như thế nào?
> **Trả lời:** Em sử dụng native **Fetch API** của JavaScript, được đóng gói trong hàm tiện ích `App.request()` tại `assets/js/app.js`. Khi người dùng thao tác (submit form, tìm kiếm, bấm nút sửa/xóa), JavaScript sẽ gửi HTTP request (GET hoặc POST) cùng FormData hoặc query string đến các file PHP trong thư mục `api/`. Backend trả về JSON `{ success, message, data }`, sau đó JavaScript cập nhật lại DOM (thay đổi HTML của bảng, modal, thông báo) mà không cần tải lại toàn bộ trang web.

### 2. Hai vai trò Khách hàng và Admin hoạt động ra sao khi không có đăng nhập?
> **Trả lời:**
> - **Khách hàng:** Vào trang `consultation.html` để xem thông tin, gửi yêu cầu tư vấn. Họ chỉ có quyền tạo yêu cầu tư vấn (`public-create.php`), không thể xem danh sách hoặc can thiệp dữ liệu của người khác.
> - **Admin:** Vào trang `customers.html`. Để thuận tiện cho việc chấm bài và demo cục bộ, backend tự động khởi tạo Session Admin thông qua hàm `ensureAutoAdminSession()` trong `includes/auth.php`. Nhờ đó, Admin có đầy đủ quyền Thêm, Xem, Sửa, Xóa trên toàn bộ hồ sơ khách hàng.

### 3. Cơ sở dữ liệu của hệ thống gồm những bảng nào?
> **Trả lời:** Module Quản lý khách hàng tập trung vào bảng cốt lõi là **`customers`** chứa toàn bộ thông tin: họ tên, số điện thoại, email, ngoại ngữ quan tâm, mục tiêu học tập, lịch học, hình thức học, nguồn khách hàng, trạng thái tư vấn và trạng thái hồ sơ. Ngoài ra có bảng **`users`** để đại diện cho tài khoản quản trị viên phụ trách.

### 4. Hệ thống chống lỗi bảo mật SQL Injection và XSS như thế nào?
> **Trả lời:**
> - **Chống SQL Injection:** Ở backend PHP, 100% các câu lệnh truy vấn đều sử dụng **PDO Prepared Statements** với tham số ràng buộc (`:full_name`, `:phone_number`...), dữ liệu người dùng không bao giờ được nối trực tiếp vào chuỗi SQL.
> - **Chống XSS:** Ở frontend JavaScript, toàn bộ dữ liệu lấy từ server trước khi chèn vào `innerHTML` đều được xử lý qua hàm `App.escape()` để chuyển đổi các ký tự đặc biệt (`<`, `>`, `&`, `"`, `'`) thành mã HTML entities an toàn.
