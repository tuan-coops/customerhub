<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

// Chỉ Admin và request POST có CSRF hợp lệ mới được tạo hồ sơ.
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Phương thức không được hỗ trợ.', null, 405);
$data = requestData();
verifyCsrf($data);

$fullName = clean($data['full_name'] ?? '');
$phone = preg_replace('/\s+/', '', clean($data['phone_number'] ?? ''));
$email = clean($data['email'] ?? '');
$language = clean($data['language_interest'] ?? '');

if ($fullName === '' || $phone === '' || $language === '') jsonResponse(false, 'Vui lòng nhập đầy đủ các trường bắt buộc.', null, 422);
if (!preg_match('/^(?:\+84|0)[0-9]{8,10}$/', $phone)) jsonResponse(false, 'Số điện thoại không hợp lệ.', null, 422);
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(false, 'Email không hợp lệ.', null, 422);

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
    if ($duplicate->fetch()) jsonResponse(false, 'Số điện thoại hoặc email đã tồn tại.', null, 409);

    // Prepared Statement tách dữ liệu khỏi câu SQL để chống SQL Injection.
    $sql = 'INSERT INTO customers (customer_code, assigned_staff_id, full_name, phone_number, email, occupation, language_interest, learning_goal, current_level, preferred_schedule, learning_format, source, customer_type, note, created_by) VALUES (:code, :admin, :full_name, :phone, :email, :occupation, :language, :goal, :level, :schedule, :format, :source, :type, :note, :created_by)';
    $statement = $pdo->prepare($sql);
    $statement->execute([
        'code' => generateCustomerCode($pdo), 'admin' => currentUser()['id'],
        'full_name' => $fullName, 'phone' => $phone, 'email' => $email ?: null,
        'occupation' => clean($data['occupation'] ?? '') ?: null, 'language' => $language,
        'goal' => clean($data['learning_goal'] ?? '') ?: null, 'level' => clean($data['current_level'] ?? '') ?: null,
        'schedule' => clean($data['preferred_schedule'] ?? '') ?: null, 'format' => clean($data['learning_format'] ?? '') ?: null,
        'source' => clean($data['source'] ?? 'Website'), 'type' => clean($data['customer_type'] ?? 'lead'),
        'note' => clean($data['note'] ?? '') ?: null, 'created_by' => currentUser()['id'],
    ]);
    $newId = (int) $pdo->lastInsertId();
    $creator = currentUser();
    // Mỗi thao tác quan trọng được ghi lại để hiển thị trong Timeline.

    logActivity(
        $pdo,
        $newId,
        $creator['id'],
        'created',
        'Tạo hồ sơ khách hàng',
        "Admin {$creator['full_name']} tạo hồ sơ (Ngoại ngữ: {$language}, Nguồn: " . clean($data['source'] ?? 'Website') . ')'
    );
    jsonResponse(true, 'Thêm khách hàng thành công.', ['id' => $newId], 201);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    jsonResponse(false, 'Không thể thêm khách hàng.', null, 500);
}
