<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

// Endpoint công khai vẫn yêu cầu POST và CSRF để hạn chế request giả mạo.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Phương thức không được hỗ trợ.', null, 405);
}

$data = requestData();
verifyCsrf($data);

$fullName = clean($data['full_name'] ?? '');
$phone = preg_replace('/\s+/', '', clean($data['phone_number'] ?? ''));
$email = clean($data['email'] ?? '');
$language = clean($data['language_interest'] ?? 'Tiếng Anh');
$goal = clean($data['learning_goal'] ?? '');

if ($fullName === '' || $phone === '' || $language === '' || $goal === '') {
    jsonResponse(false, 'Vui lòng nhập đầy đủ họ tên, số điện thoại, ngoại ngữ và mục tiêu học tập.', null, 422);
}
if (!preg_match('/^(?:\+84|0)[0-9]{8,10}$/', $phone)) {
    jsonResponse(false, 'Số điện thoại không hợp lệ.', null, 422);
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Email không hợp lệ.', null, 422);
}

try {
    $pdo = db();
    $duplicateSql = 'SELECT id FROM customers WHERE phone_number = :phone';
    $duplicateParams = ['phone' => $phone];
    if ($email !== '') {
        $duplicateSql .= ' OR email = :email';
        $duplicateParams['email'] = $email;
    }
    $duplicateSql .= ' LIMIT 1';
    $duplicate = $pdo->prepare($duplicateSql);
    $duplicate->execute($duplicateParams);
    if ($duplicate->fetch()) {
        jsonResponse(false, 'Số điện thoại hoặc email này đã gửi yêu cầu. Trung tâm sẽ sớm liên hệ với bạn.', null, 409);
    }

    // Gán yêu cầu mới cho Admin đang hoạt động đầu tiên.
    $adminId = $pdo->query("SELECT id FROM users WHERE role = 'admin' AND status = 'active' ORDER BY id LIMIT 1")->fetchColumn();
    if (!$adminId) {
        throw new RuntimeException('Chưa có tài khoản quản trị hoạt động.');
    }

    // Khách gửi từ website luôn bắt đầu ở giai đoạn lead.
    $customerCode = generateCustomerCode($pdo);
    $statement = $pdo->prepare(
        'INSERT INTO customers
            (customer_code, assigned_staff_id, full_name, phone_number, email,
             language_interest, learning_goal, preferred_schedule, learning_format,
             source, customer_type, note, created_by)
         VALUES
            (:code, :assigned_admin, :full_name, :phone, :email, :language, :goal,
             :schedule, :format, :source, \'lead\', :note, :created_by)'
    );
    $statement->execute([
        'code' => $customerCode,
        'assigned_admin' => (int) $adminId,
        'full_name' => $fullName,
        'phone' => $phone,
        'email' => $email ?: null,
        'language' => $language,
        'goal' => $goal,
        'schedule' => clean($data['preferred_schedule'] ?? '') ?: null,
        'format' => clean($data['learning_format'] ?? '') ?: null,
        'source' => clean($data['source'] ?? 'Website'),
        'note' => clean($data['note'] ?? '') ?: null,
        'created_by' => (int) $adminId,
    ]);

    $newCustomerId = (int) $pdo->lastInsertId();
    logActivity(
        $pdo,
        $newCustomerId,
        null,
        'created',
        'Tiếp nhận yêu cầu trực tuyến',
        "Khách hàng đăng ký qua website với mục tiêu: {$goal} (Ngoại ngữ: {$language})"
    );

    jsonResponse(true, 'Gửi yêu cầu thành công. Trung tâm sẽ liên hệ với bạn trong thời gian sớm nhất.', [
        'customer_code' => $customerCode,
    ], 201);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    jsonResponse(false, 'Không thể gửi yêu cầu lúc này. Vui lòng thử lại sau.', null, 500);
}
