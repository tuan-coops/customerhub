<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';
requireRole(['admin']);

try {
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 10)));
    $keyword = clean($_GET['keyword'] ?? '');
    $type = clean($_GET['type'] ?? '');
    $status = clean($_GET['status'] ?? 'active');
    $source = clean($_GET['source'] ?? '');
    $sort = clean($_GET['sort'] ?? 'updated_desc');
    // Ghép điều kiện động theo bộ lọc nhưng luôn truyền giá trị qua PDO parameters.
    $conditions = ['1 = 1'];
    $params = [];

    if ($keyword !== '') {
        $conditions[] = '(c.full_name LIKE :keyword_name OR c.phone_number LIKE :keyword_phone OR c.email LIKE :keyword_email OR c.customer_code LIKE :keyword_code)';
        $keywordPattern = '%' . $keyword . '%';
        $params['keyword_name'] = $keywordPattern;
        $params['keyword_phone'] = $keywordPattern;
        $params['keyword_email'] = $keywordPattern;
        $params['keyword_code'] = $keywordPattern;
    }
    if ($type !== '') { $conditions[] = 'c.customer_type = :type'; $params['type'] = $type; }
    if ($status !== '') { $conditions[] = 'c.status = :status'; $params['status'] = $status; }
    if ($source !== '') { $conditions[] = 'c.source = :source'; $params['source'] = $source; }

    $orderBy = match ($sort) {
        'updated_asc' => 'c.updated_at ASC',
        'name_asc' => 'c.full_name ASC',
        'name_desc' => 'c.full_name DESC',
        default => 'c.updated_at DESC',
    };
    $where = implode(' AND ', $conditions);
    // Đếm tổng trước để tính số trang, sau đó mới lấy đúng lát dữ liệu LIMIT/OFFSET.
    $count = db()->prepare("SELECT COUNT(*) FROM customers c WHERE {$where}");
    $count->execute($params);
    $total = (int) $count->fetchColumn();
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT c.id, c.customer_code, c.full_name, c.phone_number, c.email, c.learning_goal, c.language_interest, c.source, c.customer_type, c.status, c.updated_at, u.full_name AS admin_name FROM customers c LEFT JOIN users u ON u.id = c.assigned_staff_id WHERE {$where} ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}";
    $statement = db()->prepare($sql);
    $statement->execute($params);

    jsonResponse(true, '', ['customers' => $statement->fetchAll(), 'pagination' => ['page' => $page, 'total_pages' => $totalPages, 'total_records' => $total]]);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    jsonResponse(false, 'Không thể tải danh sách khách hàng.', null, 500);
}
