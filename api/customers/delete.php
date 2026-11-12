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

if ($id < 1) {
    jsonResponse(false, 'Khách hàng không hợp lệ.', null, 422);
}

try {
    // Foreign key ON DELETE CASCADE tự xóa tương tác, lịch và Timeline liên quan.
    $statement = db()->prepare('DELETE FROM customers WHERE id = :id');
    $statement->execute(['id' => $id]);

    if ($statement->rowCount() === 0) {
        jsonResponse(false, 'Không tìm thấy khách hàng.', null, 404);
    }

    jsonResponse(true, 'Đã xóa khách hàng và dữ liệu chăm sóc liên quan.');
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    jsonResponse(false, 'Không thể xóa khách hàng.', null, 500);
}
