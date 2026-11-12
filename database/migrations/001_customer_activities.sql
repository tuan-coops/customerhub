-- Migration: Tạo bảng customer_activities để ghi nhận toàn bộ vòng đời và lịch sử hoạt động của khách hàng
-- Thực thi an toàn: không làm ảnh hưởng các bảng hiện có

CREATE TABLE IF NOT EXISTS customer_activities (
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
