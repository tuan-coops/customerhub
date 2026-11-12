<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Phương thức không được hỗ trợ.', null, 405);
}

$data = requestData();
verifyCsrf($data);
$id = (int) ($data['id'] ?? 0);
$fullName = clean($data['full_name'] ?? '');
$phone = preg_replace('/\s+/', '', clean($data['phone_number'] ?? ''));
$email = clean($data['email'] ?? '');
$language = clean($data['language_interest'] ?? '');

if ($id < 1 || $fullName === '' || $phone === '' || $language === '') {
    jsonResponse(false, 'Vui lòng nhập đầy đủ các trường bắt buộc.', null, 422);
}
if (!preg_match('/^(?:\+84|0)[0-9]{8,10}$/', $phone)) {
    jsonResponse(false, 'Số điện thoại không hợp lệ.', null, 422);
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Email không hợp lệ.', null, 422);
}

try {
    $pdo = db();
    $duplicateSql = 'SELECT id FROM customers WHERE id <> :id AND (phone_number = :phone';
    $duplicateParams = ['id' => $id, 'phone' => $phone];
    if ($email !== '') {
        $duplicateSql .= ' OR email = :email';
        $duplicateParams['email'] = $email;
    }
    $duplicateSql .= ') LIMIT 1';
    $duplicate = $pdo->prepare($duplicateSql);
    $duplicate->execute($duplicateParams);
    if ($duplicate->fetch()) {
        jsonResponse(false, 'Số điện thoại hoặc email đã thuộc về khách hàng khác.', null, 409);
    }

    // Đọc trạng thái hiện tại trước khi UPDATE để xác định loại log Timeline.
    $existingStmt = $pdo->prepare('SELECT id, customer_type, status, full_name FROM customers WHERE id = :id');
    $existingStmt->execute(['id' => $id]);
    $currentCustomer = $existingStmt->fetch();
    if (!$currentCustomer) {
        jsonResponse(false, 'Không tìm thấy khách hàng.', null, 404);
    }

    $newType = clean($data['customer_type'] ?? 'lead');
    $statement = $pdo->prepare(
        'UPDATE customers SET
            full_name = :full_name,
            phone_number = :phone,
            email = :email,
            occupation = :occupation,
            language_interest = :language,
            learning_goal = :goal,
            current_level = :level,
            preferred_schedule = :schedule,
            learning_format = :format,
            source = :source,
            customer_type = :type,
            status = :status,
            note = :note
         WHERE id = :id'
    );
    $statement->execute([
        'full_name' => $fullName,
        'phone' => $phone,
        'email' => $email ?: null,
        'occupation' => clean($data['occupation'] ?? '') ?: null,
        'language' => $language,
        'goal' => clean($data['learning_goal'] ?? '') ?: null,
        'level' => clean($data['current_level'] ?? '') ?: null,
        'schedule' => clean($data['preferred_schedule'] ?? '') ?: null,
        'format' => clean($data['learning_format'] ?? '') ?: null,
        'source' => clean($data['source'] ?? 'Website'),
        'type' => $newType,
        'status' => clean($data['status'] ?? 'active'),
        'note' => clean($data['note'] ?? '') ?: null,
        'id' => $id,
    ]);

    // Nếu giai đoạn tư vấn đổi, lưu cả giá trị cũ và mới vào activity log.
    $oldType = $currentCustomer['customer_type'];
    $user = currentUser();
    if ($oldType !== $newType) {
        $oldLabel = customerTypeLabel($oldType);
        $newLabel = customerTypeLabel($newType);
        logActivity(
            $pdo,
            $id,
            $user['id'],
            'status_changed',
            'Thay đổi trạng thái tư vấn',
            "Chuyển trạng thái: {$oldLabel} → {$newLabel}",
            $oldType,
            $newType
        );
    } else {
        logActivity(
            $pdo,
            $id,
            $user['id'],
            'updated',
            'Cập nhật thông tin hồ sơ',
            "Admin {$user['full_name']} đã cập nhật thông tin khách hàng"
        );
    }

    jsonResponse(true, 'Cập nhật khách hàng thành công.');
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    jsonResponse(false, 'Không thể cập nhật khách hàng.', null, 500);
}
