<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';
requireRole(['admin']);

// ID khách hàng được lấy từ query string: detail.php?id=...
$id = max(0, (int) ($_GET['id'] ?? 0));

try {
    // Truy vấn hồ sơ chính và tên người đang phụ trách.
    $statement = db()->prepare(
        'SELECT c.*, u.full_name AS admin_name
         FROM customers c
         LEFT JOIN users u ON u.id = c.assigned_staff_id
         WHERE c.id = :id
         LIMIT 1'
    );
    $statement->execute(['id' => $id]);
    $customer = $statement->fetch();

    if (!$customer) {
        jsonResponse(false, 'Không tìm thấy khách hàng hoặc bạn không có quyền truy cập.', null, 404);
    }

    // Lấy toàn bộ lịch sử tư vấn, mới nhất hiển thị trước.
    $statement = db()->prepare(
        'SELECT i.*, u.full_name AS admin_name
         FROM interactions i
         JOIN users u ON u.id = i.staff_id
         WHERE i.customer_id = :id
         ORDER BY i.contact_date DESC'
    );
    $statement->execute(['id' => $id]);
    $interactions = $statement->fetchAll();

    // Lấy các lịch chăm sóc của đúng khách hàng này.
    $statement = db()->prepare(
        'SELECT f.*, u.full_name AS admin_name
         FROM followups f
         JOIN users u ON u.id = f.staff_id
         WHERE f.customer_id = :id
         ORDER BY f.followup_date DESC'
    );
    $statement->execute(['id' => $id]);

    jsonResponse(true, '', [
        'customer' => $customer,
        'interactions' => $interactions,
        'followups' => $statement->fetchAll(),
    ]);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    jsonResponse(false, 'Không thể tải hồ sơ khách hàng.', null, 500);
}
