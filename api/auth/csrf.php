<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Trang công khai gọi endpoint này để nhận token gắn với PHP Session hiện tại.
jsonResponse(true, '', ['csrf_token' => csrfToken()]);
