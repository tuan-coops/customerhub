<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function isLoggedIn(): bool
{
    ensureAutoAdminSession();
    return isset($_SESSION['user']['id']);
}

function currentUser(): array
{
    ensureAutoAdminSession();
    return $_SESSION['user'] ?? [];
}

// Bản demo local tự tạo phiên Admin, không yêu cầu nhập tài khoản/mật khẩu.
function ensureAutoAdminSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (!isset($_SESSION['user']['id'])) {
        $_SESSION['user'] = [
            'id' => 1,
            'username' => 'admin',
            'full_name' => 'Quản trị hệ thống',
            'email' => 'admin@educare.local',
            'role' => 'admin',
        ];
    }
}

function requireLogin(bool $json = false): void
{
    if (isLoggedIn()) {
        return;
    }

    if ($json) {
        require_once __DIR__ . '/functions.php';
        jsonResponse(false, 'Phiên đăng nhập đã hết hạn.', null, 401);
    }

    header('Location: customers.html');
    exit;
}

function requireRole(array $roles): void
{
    requireLogin(true);
    if (!in_array(currentUser()['role'] ?? '', $roles, true)) {
        require_once __DIR__ . '/functions.php';
        jsonResponse(false, 'Bạn không có quyền thực hiện thao tác này.', null, 403);
    }
}
