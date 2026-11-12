# EduCare CRM – Hệ thống Quản lý Khách hàng Trung tâm Ngoại ngữ

Hệ thống quản lý thông tin khách hàng dành cho trung tâm ngoại ngữ EduCare, phục vụ bài tập lớn môn Lập trình Web (TH3).

---

## 1. Kiến trúc & Công nghệ

### Frontend thuần (Static & Client-side)
- **HTML5 & CSS3**: Giao diện chuẩn Semantic, tùy biến hiện đại.
- **Bootstrap 5.3 & Bootstrap Icons**: Khung bố cục responsive trên cả máy tính và di động.
- **JavaScript thuần (Vanilla JS)**: Sử dụng native **Fetch API** để thực hiện 100% các thao tác AJAX (không dùng jQuery, không tải lại trang).
- **Font chữ**: **Be Vietnam Pro** (nhóm Sans-serif).

### Backend thuần (RESTful JSON API)
- **PHP 8+ thuần**: Chỉ đóng vai trò API xử lý logic và trả về JSON qua thư mục `api/`.
- **PDO (PHP Data Objects)**: Thực hiện Prepared Statements chống SQL Injection.
- **Cơ sở dữ liệu**: MySQL (hỗ trợ cả XAMPP MySQL và Oracle MySQL Server cổng 3306).
- **Bảo mật**: Kiểm tra CSRF Token theo phiên, mã hóa ký tự đầu ra (`App.escape`) chống XSS.

**Luồng dữ liệu:** `HTML/Bootstrap + JavaScript (Fetch API) → PHP JSON API (PDO) → MySQL`.

---

## 2. Hai vai trò nghiệp vụ (Không cần đăng nhập)

1. **Khách hàng (`consultation.html`)**:
   - Truy cập công khai, không cần đăng ký tài khoản.
   - Xem thông tin các chương trình ngoại ngữ (IELTS, TOEIC, Tiếng Nhật, Hàn, Trung).
   - Xem bản đồ **Google Maps iframe** chỉ đường và **Facebook Fanpage iframe** được nhúng trực tiếp.
   - Trò chuyện với **Chatbot tư vấn tự động** (Rule-based) hỗ trợ giải đáp 24/7 về khóa học, học phí, lịch học bằng AJAX.
   - Gửi biểu mẫu **Đăng ký tư vấn** bằng AJAX (`api/customers/public-create.php`), hệ thống tự sinh mã hồ sơ và phản hồi ngay trên trang.

2. **Quản trị viên / Admin (`customers.html`)**:
   - Môi trường demo cục bộ: Backend tự động cấp phiên Admin qua `includes/auth.php` (không cần form đăng nhập).
   - **Xem danh sách (Read)**: Bảng dữ liệu khách hàng với avatar chữ cái đầu và nhãn trạng thái sinh động.
   - **Tìm kiếm tức thời (Search)**: Tìm theo Tên, SĐT, Email hoặc Mã hồ sơ với cơ chế Debounce 350ms.
   - **Bộ lọc đa tiêu chí**: Lọc theo Trạng thái tư vấn, Nguồn tiếp cận, Trạng thái hồ sơ và Sắp xếp.
   - **Phân trang (Pagination)**: Chuyển trang mượt mà bằng AJAX.
   - **Thêm mới (Create)**: Modal thêm khách hàng mới với validation đầy đủ.
   - **Chỉnh sửa (Update)**: Modal sửa thông tin khách hàng, cập nhật ngay lập tức vào MySQL.
   - **Xóa (Delete)**: Modal cảnh báo xác nhận xóa an toàn.

---

## 3. Cấu trúc thư mục

```text
customerhub/
│
├── index.html                   # Chuyển hướng tới consultation.html
├── consultation.html            # [Trang 1] Khách hàng: Form đăng ký, Khóa học, Bản đồ, Fanpage
├── customers.html               # [Trang 2] Quản trị: Bảng CRUD khách hàng bằng AJAX
├── start.bat                    # Script khởi động tự động 1-click
├── README.md                    # Tài liệu giới thiệu và hướng dẫn
├── docs/DEMO_TH3.md             # Kịch bản trình diễn và câu hỏi vấn đáp
│
├── assets/
│   ├── css/
│   │   ├── style.css            # CSS giao diện Admin và dùng chung
│   │   ├── public.css           # CSS giao diện trang Khách hàng
│   │   └── chatbot.css          # CSS giao diện widget Chatbot
│   └── js/
│       ├── app.js               # Tiện ích App.request (Fetch API), toast, escape, debounce
│       ├── layout.js            # Khung giao diện Admin và khởi tạo phiên tự động
│       ├── customers.js         # Logic CRUD bảng khách hàng bằng AJAX
│       ├── public-consultation.js # Logic gửi form tư vấn bằng AJAX
│       └── chatbot.js           # Widget chatbot tư vấn tự động
│
├── api/
│   ├── auth/
│   │   ├── session.php          # Cấp phiên Admin và CSRF token
│   │   └── csrf.php             # Cấp CSRF token cho form công khai
│   ├── chatbot/
│   │   └── message.php          # Xử lý câu hỏi chatbot bằng thuật toán so khớp từ khóa
│   └── customers/
│       ├── list.php             # Lấy danh sách, tìm kiếm, lọc, phân trang
│       ├── create.php           # Thêm khách hàng mới
│       ├── detail.php           # Lấy dữ liệu 1 khách hàng để điền vào form sửa
│       ├── update.php           # Cập nhật thông tin khách hàng
│       ├── delete.php           # Xóa khách hàng
│       └── public-create.php    # Khách hàng gửi yêu cầu tư vấn
│
├── config/database.php          # Kết nối CSDL PDO MySQL
├── includes/
│   ├── auth.php                 # Quản lý phiên Admin tự động
│   └── functions.php            # Hàm tiện ích backend (JSON, CSRF, làm sạch dữ liệu)
└── database/
    ├── educare_crm.sql          # File tạo database và bảng dữ liệu ban đầu
    └── seed_demo_data.php       # Script tạo dữ liệu mẫu phong phú
```

---

## 4. Hướng dẫn khởi động trên máy bất kỳ

### Cách 1: Khởi động 1-click bằng `start.bat` (Khuyên dùng trên Windows)
1. Bật dịch vụ MySQL (qua **XAMPP Control Panel** hoặc Windows Services).
2. Nhấp đúp chuột vào file **`start.bat`**.
   - Script tự động tìm PHP (trong XAMPP ổ C, D hoặc PATH).
   - Tự động kiểm tra cổng MySQL 3306.
   - Tự động import database `educare_crm` nếu chưa tồn tại.
   - Tự động nạp dữ liệu mẫu nếu chưa có.
   - Khởi động PHP Server tại `http://127.0.0.1:8000` và tự mở trình duyệt.

> **Lưu ý về mật khẩu MySQL:**
> - Mặc định file `start.bat` và `config/database.php` cấu hình mật khẩu là `123456`.
> - Nếu máy của bạn dùng **XAMPP mặc định (mật khẩu trống)**:
>   Mở file `start.bat`, sửa dòng:
>   ```bat
>   if not defined EDUCARE_DB_PASS set "EDUCARE_DB_PASS="
>   ```
>   Và trong `config/database.php` sửa `const DB_PASS = '';`.

### Cách 2: Khởi động thủ công qua XAMPP Apache
1. Copy thư mục `customerhub` vào `C:\xampp\htdocs\customerhub`.
2. Mở trình duyệt vào `http://localhost/phpmyadmin`, tạo database `educare_crm` và import file `database/educare_crm.sql`.
3. Truy cập: `http://localhost/customerhub/consultation.html`.
# customerhub
