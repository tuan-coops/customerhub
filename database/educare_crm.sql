-- CẢNH BÁO: Script cài đặt mới hoàn toàn, dòng dưới sẽ xóa database cũ.
DROP DATABASE IF EXISTS educare_crm;
-- utf8mb4 lưu đầy đủ tiếng Việt và ký tự Unicode.
CREATE DATABASE educare_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE educare_crm;

-- Khách hàng sử dụng trang công khai và không có tài khoản.
-- Bảng users chỉ lưu tài khoản quản trị viên.
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    role ENUM('admin') NOT NULL DEFAULT 'admin',
    status ENUM('active', 'locked') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Bảng trung tâm: lưu hồ sơ và giai đoạn tư vấn của từng khách hàng.
CREATE TABLE customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_code VARCHAR(20) NOT NULL UNIQUE,
    assigned_staff_id INT UNSIGNED NULL,
    full_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(120) NULL UNIQUE,
    date_of_birth DATE NULL,
    gender ENUM('male', 'female', 'other') NULL,
    address VARCHAR(255) NULL,
    occupation VARCHAR(100) NULL,
    organization VARCHAR(150) NULL,
    language_interest VARCHAR(50) NOT NULL DEFAULT 'Tiếng Anh',
    learning_goal VARCHAR(100) NULL,
    current_level VARCHAR(50) NULL,
    preferred_schedule VARCHAR(100) NULL,
    learning_format ENUM('offline', 'online', 'hybrid') NULL,
    source ENUM('Facebook', 'Website', 'Zalo', 'Giới thiệu', 'Hotline', 'Khác') NOT NULL DEFAULT 'Website',
    customer_type ENUM('lead', 'consulting', 'high_interest', 'appointment', 'registered', 'no_demand', 'unreachable') NOT NULL DEFAULT 'lead',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    note TEXT NULL,
    last_contact_at DATETIME NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_search (full_name, phone_number),
    INDEX idx_customer_filter (customer_type, status, assigned_staff_id),
    CONSTRAINT fk_customer_admin FOREIGN KEY (assigned_staff_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_customer_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Mỗi dòng là một lần gọi điện, nhắn tin hoặc tư vấn trực tiếp.
CREATE TABLE interactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    staff_id INT UNSIGNED NOT NULL,
    contact_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    channel ENUM('phone', 'email', 'zalo', 'facebook', 'message', 'meeting', 'online') NOT NULL DEFAULT 'phone',
    content TEXT NOT NULL,
    result VARCHAR(255) NULL,
    customer_feedback TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_interaction_customer_date (customer_id, contact_date),
    CONSTRAINT fk_interaction_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    CONSTRAINT fk_interaction_admin FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Lịch chăm sóc cần thực hiện trong tương lai, có ưu tiên và trạng thái.
CREATE TABLE followups (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    staff_id INT UNSIGNED NOT NULL,
    followup_date DATETIME NOT NULL,
    content VARCHAR(500) NOT NULL,
    priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    status ENUM('pending', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    result TEXT NULL,
    completed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_followup_admin_date (staff_id, followup_date, status),
    INDEX idx_followup_customer (customer_id),
    CONSTRAINT fk_followup_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    CONSTRAINT fk_followup_admin FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Audit Timeline: tổng hợp mọi sự kiện quan trọng trong vòng đời khách hàng.
CREATE TABLE customer_activities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    activity_type ENUM('created', 'status_changed', 'interaction', 'followup_created', 'followup_completed', 'updated') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    old_value VARCHAR(100) NULL,
    new_value VARCHAR(100) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_activity_customer (customer_id, created_at),
    INDEX idx_activity_type (activity_type),
    CONSTRAINT fk_activity_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tài khoản quản trị demo: admin / password
INSERT INTO users (username, password_hash, full_name, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'Quản trị hệ thống', 'admin@educare.local', 'admin');

INSERT INTO customers (customer_code, assigned_staff_id, full_name, phone_number, email, occupation, language_interest, learning_goal, current_level, preferred_schedule, learning_format, source, customer_type, created_by, last_contact_at) VALUES
('KH2026100001', 1, 'Lê Hoàng Nam', '0912345678', 'hoangnam@example.com', 'Sinh viên', 'Tiếng Anh', 'IELTS 6.5 để du học', 'Intermediate', 'Tối thứ 2, 4, 6', 'offline', 'Facebook', 'high_interest', 1, '2026-10-25 15:00:00'),
('KH2026100002', 1, 'Phạm Thu Hà', '0987654321', 'thuha@example.com', 'Nhân viên văn phòng', 'Tiếng Anh', 'TOEIC 750', 'Pre-Intermediate', 'Cuối tuần', 'online', 'Website', 'consulting', 1, '2026-10-22 09:30:00'),
('KH2026100003', 1, 'Đỗ Gia Bảo', '0905123456', NULL, 'Học sinh', 'Tiếng Anh', 'Giao tiếp và củng cố ngữ pháp', 'Beginner', 'Chiều cuối tuần', 'offline', 'Giới thiệu', 'lead', 1, NULL),
('KH2026100004', 1, 'Vũ Minh Châu', '0936123456', 'minhchau@example.com', 'Kế toán', 'Tiếng Anh', 'Giao tiếp công việc', 'Elementary', 'Buổi tối', 'hybrid', 'Zalo', 'appointment', 1, '2026-10-26 08:15:00');

INSERT INTO interactions (customer_id, staff_id, contact_date, channel, content, result, customer_feedback) VALUES
(1, 1, '2026-10-25 15:00:00', 'phone', 'Tư vấn lộ trình IELTS 6.5 và lịch học buổi tối.', 'Khách hàng quan tâm, cần xem lại lịch.', 'Muốn học thử trước khi đăng ký.'),
(2, 1, '2026-10-22 09:30:00', 'zalo', 'Gửi thông tin lộ trình TOEIC 750 và bài kiểm tra đầu vào.', 'Đã nhận tài liệu.', 'Sẽ hoàn thành bài kiểm tra trong tuần.'),
(4, 1, '2026-10-26 08:15:00', 'phone', 'Xác nhận lịch tư vấn trực tiếp tại trung tâm.', 'Đã xác nhận lịch hẹn.', 'Có thể đến lúc 18:30.');

INSERT INTO followups (customer_id, staff_id, followup_date, content, priority, status) VALUES
(1, 1, '2026-10-31 19:00:00', 'Gọi lại xác nhận lịch học thử IELTS.', 'high', 'pending'),
(2, 1, '2026-11-01 09:00:00', 'Nhắc hoàn thành bài kiểm tra đầu vào TOEIC.', 'medium', 'pending'),
(4, 1, '2026-10-30 18:30:00', 'Tiếp đón và tư vấn lộ trình giao tiếp.', 'high', 'pending');
