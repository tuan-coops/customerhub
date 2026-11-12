<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Khởi tạo layout quản trị bằng thông tin Admin và CSRF token cùng một lần gọi.
jsonResponse(true, '', [
    'user' => currentUser(),
    'csrf_token' => csrfToken(),
]);
