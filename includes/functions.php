<?php

declare(strict_types=1);

// Chuẩn hóa mọi phản hồi API theo cấu trúc: success, message và data.
function jsonResponse(bool $success, string $message = '', mixed $data = null, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');

    $response = ['success' => $success, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Đọc được cả JSON body lẫn FormData ($_POST).
function requestData(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($contentType, 'application/json')) {
        $payload = json_decode(file_get_contents('php://input'), true);
        return is_array($payload) ? $payload : [];
    }

    return $_POST;
}

function clean(?string $value): string
{
    return trim((string) $value);
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Mỗi session có một token ngẫu nhiên dùng để bảo vệ request thay đổi dữ liệu.
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

// Từ chối request nếu token gửi lên không khớp token trong PHP Session.
function verifyCsrf(array $data): void
{
    $token = $data['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'] ?? '', (string) $token)) {
        jsonResponse(false, 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.', null, 419);
    }
}

// Sinh mã theo mẫu KH + YYYYMM + số thứ tự 4 chữ số.
function generateCustomerCode(PDO $pdo): string
{
    $prefix = 'KH' . date('Ym');
    $statement = $pdo->prepare('SELECT customer_code FROM customers WHERE customer_code LIKE :prefix ORDER BY id DESC LIMIT 1');
    $statement->execute(['prefix' => $prefix . '%']);
    $lastCode = $statement->fetchColumn();
    $sequence = $lastCode ? ((int) substr((string) $lastCode, -4)) + 1 : 1;

    return $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
}

function customerTypeLabel(?string $type): string
{
    return match ($type) {
        'lead' => 'Tiềm năng',
        'consulting' => 'Đang tư vấn',
        'high_interest' => 'Quan tâm cao',
        'appointment' => 'Đã hẹn',
        'registered' => 'Đã đăng ký',
        'no_demand' => 'Không có nhu cầu',
        'unreachable' => 'Không liên hệ được',
        default => (string) $type,
    };
}

// Ghi một sự kiện vào Timeline vòng đời của khách hàng.
function logActivity(
    PDO $pdo,
    int $customerId,
    ?int $userId,
    string $activityType,
    string $title,
    ?string $description = null,
    ?string $oldValue = null,
    ?string $newValue = null
): void {
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO customer_activities (customer_id, user_id, activity_type, title, description, old_value, new_value)
             VALUES (:customer_id, :user_id, :type, :title, :description, :old_val, :new_val)'
        );
        $stmt->execute([
            'customer_id' => $customerId,
            'user_id' => $userId,
            'type' => $activityType,
            'title' => $title,
            'description' => $description,
            'old_val' => $oldValue,
            'new_val' => $newValue,
        ]);
    } catch (Throwable $e) {
        // Ghi log lỗi nội bộ, không làm gián đoạn luồng chính
        error_log('logActivity error: ' . $e->getMessage());
    }
}

