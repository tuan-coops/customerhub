<?php

declare(strict_types=1);

// Chỉ cho phép chạy bằng CLI để không thể nạp dữ liệu mẫu qua trình duyệt.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This demo seed can only run from the command line.');
}

require_once __DIR__ . '/../config/database.php';

/**
 * Tạo mốc thời gian tương đối để dữ liệu luôn phù hợp với ngày chạy demo.
 */
function demoDate(string $modifier, string $time = '10:00:00'): string
{
    return (new DateTimeImmutable($modifier))->format('Y-m-d') . ' ' . $time;
}

try {
    $pdo = db();

    // Chạy lặp an toàn: nếu bộ mã DEMO đã tồn tại thì không chèn hoặc sửa dữ liệu lần nữa.
    $existingDemoCount = (int) $pdo->query(
        "SELECT COUNT(*) FROM customers WHERE customer_code LIKE 'DEMO%'"
    )->fetchColumn();
    if ($existingDemoCount > 0) {
        echo "[INFO] Dữ liệu DEMO đã tồn tại ({$existingDemoCount} khách hàng), không nạp lại.\n";
        exit(0);
    }

    $pdo->beginTransaction();

    // Bản demo tự tạo Session Admin id=1 nên database phải có user tương ứng.
    $admin = $pdo->query("SELECT id FROM users WHERE id = 1 AND role = 'admin' LIMIT 1")->fetchColumn();
    if (!$admin) {
        throw new RuntimeException('Không tìm thấy tài khoản Admin id=1. Hãy kiểm tra bảng users trước khi seed.');
    }
    $adminId = (int) $admin;

    // 20 hồ sơ phủ đủ nguồn, ngoại ngữ, hình thức học, trạng thái pipeline và trạng thái hồ sơ.
    $customers = [
        ['DEMO0001', 'Nguyễn Minh Anh', '0876000001', 'demo01@educare.test', 'Sinh viên', 'Tiếng Anh', 'IELTS 6.5 để du học', 'Intermediate', 'Tối thứ 2-4-6', 'offline', 'Website', 'lead', 'active', '-1 day', null],
        ['DEMO0002', 'Trần Quốc Bảo', '0876000002', 'demo02@educare.test', 'Kỹ sư', 'Tiếng Nhật', 'JLPT N3 để làm việc tại Nhật', 'N4', 'Cuối tuần', 'online', 'Facebook', 'lead', 'active', '-3 days', null],
        ['DEMO0003', 'Lê Thu Chi', '0876000003', 'demo03@educare.test', 'Nhân viên văn phòng', 'Tiếng Anh', 'TOEIC 750 trong 4 tháng', 'Pre-Intermediate', 'Tối thứ 3-5', 'hybrid', 'Zalo', 'consulting', 'active', '-10 days', '-1 day'],
        ['DEMO0004', 'Phạm Đức Duy', '0876000004', 'demo04@educare.test', 'Kinh doanh', 'Tiếng Trung', 'Giao tiếp thương mại và HSK 4', 'HSK 2', 'Buổi tối', 'offline', 'Hotline', 'consulting', 'active', '-20 days', '-2 days'],
        ['DEMO0005', 'Hoàng Ngọc Hà', '0876000005', 'demo05@educare.test', 'Điều dưỡng', 'Tiếng Hàn', 'TOPIK II để du học', 'Sơ cấp', 'Chiều cuối tuần', 'online', 'Giới thiệu', 'high_interest', 'active', '-35 days', '-1 day'],
        ['DEMO0006', 'Vũ Khánh Linh', '0876000006', 'demo06@educare.test', 'Kế toán', 'Tiếng Anh', 'Giao tiếp công việc quốc tế', 'Elementary', 'Tối thứ 2-4', 'hybrid', 'Website', 'high_interest', 'active', '-45 days', '-3 days'],
        ['DEMO0007', 'Đỗ Gia Huy', '0876000007', 'demo07@educare.test', 'Học sinh', 'Tiếng Anh', 'IELTS 7.0 xét tuyển đại học', '5.5 IELTS', 'Chiều thứ 7', 'offline', 'Facebook', 'appointment', 'active', '-60 days', '-1 day'],
        ['DEMO0008', 'Bùi Mai Hương', '0876000008', 'demo08@educare.test', 'Nhân sự', 'Tiếng Nhật', 'N2 phục vụ thăng tiến', 'N3', 'Chủ nhật', 'online', 'Zalo', 'appointment', 'active', '-75 days', '-5 days'],
        ['DEMO0009', 'Nguyễn Tuấn Kiệt', '0876000009', 'demo09@educare.test', 'Lập trình viên', 'Tiếng Anh', 'IELTS 7.5 để định cư', '6.0 IELTS', 'Tối thứ 3-5-7', 'hybrid', 'Giới thiệu', 'registered', 'active', '-90 days', '-7 days'],
        ['DEMO0010', 'Trịnh Quỳnh Lan', '0876000010', 'demo10@educare.test', 'Giáo viên', 'Tiếng Trung', 'HSK 5 và giao tiếp', 'HSK 3', 'Sáng cuối tuần', 'offline', 'Website', 'registered', 'active', '-110 days', '-10 days'],
        ['DEMO0011', 'Lương Thành Long', '0876000011', 'demo11@educare.test', 'Tự do', 'Tiếng Hàn', 'Giao tiếp cơ bản', 'Mới bắt đầu', 'Linh hoạt', 'online', 'Hotline', 'no_demand', 'active', '-130 days', '-30 days'],
        ['DEMO0012', 'Mai Bảo Ngọc', '0876000012', 'demo12@educare.test', 'Sinh viên', 'Tiếng Nhật', 'N5 để đọc tài liệu', 'Mới bắt đầu', 'Buổi tối', 'offline', 'Khác', 'unreachable', 'active', '-150 days', null],
        ['DEMO0013', 'Ngô Hải Nam', '0876000013', 'demo13@educare.test', 'Bác sĩ', 'Tiếng Anh', 'IELTS Y khoa 7.0', 'Upper-Intermediate', 'Cuối tuần', 'online', 'Facebook', 'registered', 'active', '-5 days', '-1 day'],
        ['DEMO0014', 'Phan Yến Nhi', '0876000014', 'demo14@educare.test', 'Marketing', 'Tiếng Trung', 'HSKK giao tiếp', 'Sơ cấp', 'Tối thứ 2-6', 'hybrid', 'Website', 'lead', 'active', 'first day of -1 month', null],
        ['DEMO0015', 'Đặng Công Phúc', '0876000015', 'demo15@educare.test', 'Xây dựng', 'Tiếng Nhật', 'JLPT N3', 'N4', 'Chủ nhật', 'offline', 'Facebook', 'consulting', 'active', 'first day of -2 months', '-12 days'],
        ['DEMO0016', 'Hồ Thanh Quỳnh', '0876000016', 'demo16@educare.test', 'Du lịch', 'Tiếng Hàn', 'TOPIK 4 và giao tiếp du lịch', 'TOPIK 2', 'Buổi tối', 'online', 'Zalo', 'high_interest', 'active', 'first day of -3 months', '-8 days'],
        ['DEMO0017', 'Dương Minh Sơn', '0876000017', 'demo17@educare.test', 'Xuất nhập khẩu', 'Tiếng Trung', 'HSK 4 thương mại', 'HSK 2', 'Tối thứ 3-5', 'offline', 'Giới thiệu', 'appointment', 'active', 'first day of -4 months', '-6 days'],
        ['DEMO0018', 'Tạ Thảo Trang', '0876000018', 'demo18@educare.test', 'Ngân hàng', 'Tiếng Anh', 'TOEIC 850', '650 TOEIC', 'Cuối tuần', 'hybrid', 'Hotline', 'registered', 'active', 'first day of -5 months', '-20 days'],
        ['DEMO0019', 'Cao Văn Vinh', '0876000019', 'demo19@educare.test', 'Kinh doanh', 'Tiếng Anh', 'Giao tiếp cơ bản', 'Beginner', 'Chưa xác định', 'online', 'Khác', 'no_demand', 'inactive', '-15 days', '-10 days'],
        ['DEMO0020', 'Lý Hoài Vy', '0876000020', 'demo20@educare.test', 'Sinh viên', 'Tiếng Hàn', 'TOPIK 3 để trao đổi sinh viên', 'TOPIK 1', 'Chiều thứ 7', 'offline', 'Website', 'registered', 'active', 'today', 'today'],
    ];

    $selectCustomer = $pdo->prepare('SELECT id FROM customers WHERE customer_code = :code LIMIT 1');
    $insertCustomer = $pdo->prepare(
        'INSERT INTO customers
            (customer_code, assigned_staff_id, full_name, phone_number, email, occupation,
             language_interest, learning_goal, current_level, preferred_schedule,
             learning_format, source, customer_type, status, note, last_contact_at,
             created_by, created_at, updated_at)
         VALUES
            (:code, :admin, :name, :phone, :email, :occupation,
             :language, :goal, :level, :schedule,
             :format, :source, :type, :status, :note, :last_contact,
             :created_by, :created_at, :updated_at)'
    );
    $updateCustomer = $pdo->prepare(
        'UPDATE customers SET
            assigned_staff_id = :admin, full_name = :name, phone_number = :phone,
            email = :email, occupation = :occupation, language_interest = :language,
            learning_goal = :goal, current_level = :level, preferred_schedule = :schedule,
            learning_format = :format, source = :source, customer_type = :type,
            status = :status, note = :note, last_contact_at = :last_contact,
            created_by = :created_by, created_at = :created_at, updated_at = :updated_at
         WHERE customer_code = :code'
    );

    $customerIds = [];
    foreach ($customers as $index => $customer) {
        [$code, $name, $phone, $email, $occupation, $language, $goal, $level,
            $schedule, $format, $source, $type, $status, $createdModifier, $contactModifier] = $customer;

        $createdAt = demoDate($createdModifier, sprintf('%02d:15:00', 8 + ($index % 10)));
        $lastContact = $contactModifier ? demoDate($contactModifier, '15:30:00') : null;
        $params = [
            'code' => $code,
            'admin' => $adminId,
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'occupation' => $occupation,
            'language' => $language,
            'goal' => $goal,
            'level' => $level,
            'schedule' => $schedule,
            'format' => $format,
            'source' => $source,
            'type' => $type,
            'status' => $status,
            'note' => 'Dữ liệu mẫu phục vụ demo đầy đủ chức năng EduCare CRM.',
            'last_contact' => $lastContact,
            'created_by' => $adminId,
            'created_at' => $createdAt,
            'updated_at' => $lastContact ?? $createdAt,
        ];

        $selectCustomer->execute(['code' => $code]);
        $existingId = $selectCustomer->fetchColumn();
        if ($existingId) {
            $updateCustomer->execute($params);
            $customerIds[$code] = (int) $existingId;
        } else {
            $insertCustomer->execute($params);
            $customerIds[$code] = (int) $pdo->lastInsertId();
        }
    }

    // Phủ đủ các kênh tư vấn: phone, email, zalo, facebook, message, meeting và online.
    $interactions = [
        ['DEMO0003', 'phone', 'Tư vấn lộ trình TOEIC và kiểm tra đầu vào.', 'Khách muốn nhận báo giá.', '-1 day', '16:00:00'],
        ['DEMO0004', 'zalo', 'Gửi giáo trình mẫu HSK và lịch khai giảng.', 'Đã xem tài liệu.', '-2 days', '19:00:00'],
        ['DEMO0005', 'meeting', 'Tư vấn trực tiếp lộ trình TOPIK II.', 'Đồng ý làm bài test.', '-1 day', '14:00:00'],
        ['DEMO0006', 'email', 'Gửi đề xuất khóa Business English.', 'Đã phản hồi email.', '-3 days', '09:00:00'],
        ['DEMO0007', 'facebook', 'Trao đổi học thử IELTS qua Fanpage.', 'Đã đặt lịch học thử.', '-4 days', '20:00:00'],
        ['DEMO0008', 'online', 'Tư vấn JLPT qua Google Meet.', 'Đã hẹn test trình độ.', '-5 days', '18:30:00'],
        ['DEMO0009', 'phone', 'Chốt lớp IELTS tối thứ 3-5-7.', 'Đã đăng ký và đóng học phí.', '-7 days', '17:00:00'],
        ['DEMO0010', 'message', 'Xác nhận lớp HSK 5 qua tin nhắn.', 'Đã xếp lớp.', '-10 days', '11:00:00'],
        ['DEMO0013', 'phone', 'Tư vấn IELTS Y khoa.', 'Đã chọn lớp online.', '-1 day', '10:30:00'],
        ['DEMO0015', 'zalo', 'Trao đổi lộ trình N3.', 'Cần sắp xếp công việc.', '-12 days', '20:00:00'],
        ['DEMO0016', 'meeting', 'Kiểm tra trình độ TOPIK trực tiếp.', 'Quan tâm cao.', '-8 days', '15:00:00'],
        ['DEMO0017', 'online', 'Tư vấn HSK thương mại qua Zoom.', 'Đã hẹn tới trung tâm.', '-6 days', '19:30:00'],
        ['DEMO0020', 'phone', 'Xác nhận nhập học TOPIK 3.', 'Đã đăng ký.', 'today', '09:30:00'],
    ];
    $insertInteraction = $pdo->prepare(
        'INSERT INTO interactions
            (customer_id, staff_id, contact_date, channel, content, result, customer_feedback, created_at, updated_at)
         VALUES
            (:customer, :staff, :contact_date, :channel, :content, :result, :feedback, :created_at, :updated_at)'
    );
    $interactionRows = [];
    foreach ($interactions as $interaction) {
        [$code, $channel, $content, $result, $modifier, $time] = $interaction;
        $contactDate = demoDate($modifier, $time);
        $insertInteraction->execute([
            'customer' => $customerIds[$code],
            'staff' => $adminId,
            'contact_date' => $contactDate,
            'channel' => $channel,
            'content' => $content,
            'result' => $result,
            'feedback' => 'Phản hồi mẫu để minh họa hồ sơ 360 độ.',
            'created_at' => $contactDate,
            'updated_at' => $contactDate,
        ]);
        $interactionRows[] = [
            'customer_id' => $customerIds[$code],
            'channel' => $channel,
            'content' => $content,
            'result' => $result,
            'created_at' => $contactDate,
        ];
    }

    // Dữ liệu lịch bao phủ: quá hạn, hôm nay, sắp tới, hoàn thành và đã hủy.
    $followups = [
        ['DEMO0003', '-3 days', '09:00:00', 'Gọi lại xác nhận lịch thi thử TOEIC.', 'high', 'pending', null],
        ['DEMO0004', '-1 day', '16:30:00', 'Gửi báo giá khóa HSK 4.', 'medium', 'pending', null],
        ['DEMO0005', 'today', '08:30:00', 'Nhắc hoàn thành bài test TOPIK.', 'high', 'pending', null],
        ['DEMO0006', 'today', '23:00:00', 'Gọi xác nhận lịch học Business English.', 'medium', 'pending', null],
        ['DEMO0007', '+1 day', '18:30:00', 'Đón học viên tới học thử IELTS.', 'high', 'pending', null],
        ['DEMO0008', '+3 days', '19:00:00', 'Tổ chức kiểm tra đầu vào JLPT.', 'medium', 'pending', null],
        ['DEMO0016', '+6 days', '10:00:00', 'Gửi kết quả đánh giá TOPIK.', 'low', 'pending', null],
        ['DEMO0009', '-5 days', '17:00:00', 'Xác nhận xếp lớp IELTS.', 'high', 'completed', 'Đã hoàn tất xếp lớp'],
        ['DEMO0010', '-8 days', '09:30:00', 'Gửi tài liệu nhập học HSK 5.', 'medium', 'completed', 'Học viên đã nhận tài liệu'],
        ['DEMO0011', '+2 days', '15:00:00', 'Liên hệ lại khi khách sẵn sàng.', 'low', 'cancelled', 'Khách yêu cầu tạm hoãn'],
    ];
    $insertFollowup = $pdo->prepare(
        'INSERT INTO followups
            (customer_id, staff_id, followup_date, content, priority, status, result, completed_at, created_at, updated_at)
         VALUES
            (:customer, :staff, :followup_date, :content, :priority, :status, :result, :completed_at, :created_at, :updated_at)'
    );
    $followupRows = [];
    foreach ($followups as $followup) {
        [$code, $modifier, $time, $content, $priority, $status, $result] = $followup;
        $followupDate = demoDate($modifier, $time);
        $createdAt = demoDate('-12 days', '08:00:00');
        $completedAt = $status === 'completed' ? $followupDate : null;
        $insertFollowup->execute([
            'customer' => $customerIds[$code],
            'staff' => $adminId,
            'followup_date' => $followupDate,
            'content' => $content,
            'priority' => $priority,
            'status' => $status,
            'result' => $result,
            'completed_at' => $completedAt,
            'created_at' => $createdAt,
            'updated_at' => $completedAt ?? $createdAt,
        ]);
        $followupRows[] = [
            'customer_id' => $customerIds[$code],
            'content' => $content,
            'status' => $status,
            'followup_date' => $followupDate,
            'created_at' => $createdAt,
            'completed_at' => $completedAt,
        ];
    }

    // Timeline: mỗi khách có sự kiện created; các giai đoạn khác lead có thêm status_changed.
    $insertActivity = $pdo->prepare(
        'INSERT INTO customer_activities
            (customer_id, user_id, activity_type, title, description, old_value, new_value, created_at)
         VALUES
            (:customer, :user, :type, :title, :description, :old_value, :new_value, :created_at)'
    );
    foreach ($customers as $index => $customer) {
        [$code, $name, , , , $language, $goal, , , , $source, $type, , $createdModifier] = $customer;
        $createdAt = demoDate($createdModifier, sprintf('%02d:15:00', 8 + ($index % 10)));
        $insertActivity->execute([
            'customer' => $customerIds[$code],
            'user' => $adminId,
            'type' => 'created',
            'title' => 'Tạo hồ sơ khách hàng mẫu',
            'description' => "Nhu cầu: {$goal} (Ngoại ngữ: {$language}, Nguồn: {$source})",
            'old_value' => null,
            'new_value' => null,
            'created_at' => $createdAt,
        ]);

        if ($type !== 'lead') {
            $insertActivity->execute([
                'customer' => $customerIds[$code],
                'user' => $adminId,
                'type' => 'status_changed',
                'title' => 'Thay đổi trạng thái tư vấn',
                'description' => "Chuyển dữ liệu mẫu từ lead sang {$type}",
                'old_value' => 'lead',
                'new_value' => $type,
                'created_at' => demoDate('-4 days', '14:00:00'),
            ]);
        }
    }

    // Đồng bộ các interaction vào Timeline.
    foreach ($interactionRows as $row) {
        $insertActivity->execute([
            'customer' => $row['customer_id'],
            'user' => $adminId,
            'type' => 'interaction',
            'title' => 'Tư vấn qua ' . $row['channel'],
            'description' => $row['content'] . ' — Kết quả: ' . $row['result'],
            'old_value' => null,
            'new_value' => null,
            'created_at' => $row['created_at'],
        ]);
    }

    // Đồng bộ lịch chăm sóc và sự kiện hoàn thành vào Timeline.
    foreach ($followupRows as $row) {
        $insertActivity->execute([
            'customer' => $row['customer_id'],
            'user' => $adminId,
            'type' => 'followup_created',
            'title' => 'Tạo lịch chăm sóc',
            'description' => $row['content'] . ' (Hạn: ' . (new DateTimeImmutable($row['followup_date']))->format('d/m/Y H:i') . ')',
            'old_value' => null,
            'new_value' => null,
            'created_at' => $row['created_at'],
        ]);
        if ($row['status'] === 'completed') {
            $insertActivity->execute([
                'customer' => $row['customer_id'],
                'user' => $adminId,
                'type' => 'followup_completed',
                'title' => 'Hoàn thành lịch chăm sóc',
                'description' => 'Đã xử lý: ' . $row['content'],
                'old_value' => 'pending',
                'new_value' => 'completed',
                'created_at' => $row['completed_at'],
            ]);
        }
    }

    // Thêm activity updated để bộ lọc Timeline có đủ mọi loại sự kiện.
    foreach (['DEMO0003', 'DEMO0006', 'DEMO0013'] as $code) {
        $insertActivity->execute([
            'customer' => $customerIds[$code],
            'user' => $adminId,
            'type' => 'updated',
            'title' => 'Cập nhật thông tin hồ sơ',
            'description' => 'Admin cập nhật nhu cầu học và lịch học mong muốn.',
            'old_value' => null,
            'new_value' => null,
            'created_at' => demoDate('-2 days', '11:00:00'),
        ]);
    }

    $pdo->commit();

    // Tóm tắt kết quả để người chạy biết dữ liệu demo đã đủ các nhóm chức năng.
    $summary = [];
    foreach (['customers', 'interactions', 'followups', 'customer_activities'] as $table) {
        $summary[$table] = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    }
    echo "[OK] Đã nạp 20 khách hàng demo phủ đủ 7 trạng thái và 6 nguồn.\n";
    echo "[OK] Đã tạo tương tác đủ 7 kênh; lịch quá hạn, hôm nay, sắp tới, hoàn thành và hủy.\n";
    foreach ($summary as $table => $count) {
        echo "- {$table}: {$count} bản ghi\n";
    }
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, '[ERROR] ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
