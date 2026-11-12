<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Phương thức không được hỗ trợ.', null, 405);
}

$data = requestData();
verifyCsrf($data);

$message = clean($data['message'] ?? '');
$history = $data['history'] ?? [];

if ($message === '') {
    jsonResponse(false, 'Vui lòng nhập câu hỏi.', null, 422);
}
if (mb_strlen($message) > 500) {
    jsonResponse(false, 'Câu hỏi không được dài quá 500 ký tự.', null, 422);
}
if (!is_array($history)) {
    $history = [];
}

$apiKey = getenv('GEMINI_API_KEY');
if ($apiKey === false || trim($apiKey) === '') {
    jsonResponse(false, 'Chatbot AI chưa được cấu hình API key.', null, 503);
}

// Chỉ gửi 8 tin nhắn gần nhất để giữ ngữ cảnh mà không làm request quá lớn.
$contents = [];
foreach (array_slice($history, -8) as $item) {
    if (!is_array($item)) {
        continue;
    }
    $role = ($item['role'] ?? '') === 'model' ? 'model' : 'user';
    $text = clean($item['text'] ?? '');
    if ($text === '' || mb_strlen($text) > 1000) {
        continue;
    }
    $contents[] = [
        'role' => $role,
        'parts' => [['text' => $text]],
    ];
}
$contents[] = [
    'role' => 'user',
    'parts' => [['text' => $message]],
];

// System Instruction giới hạn Gemini vào phạm vi tư vấn EduCare, tránh tự bịa thông tin.
$payload = [
    'systemInstruction' => [
        'parts' => [[
            'text' => 'Bạn là trợ lý tư vấn của EduCare Language Center. '
                . 'Chỉ trả lời bằng tiếng Việt, thân thiện, rõ ràng và tối đa 120 từ. '
                . 'EduCare tư vấn Tiếng Anh (IELTS, TOEIC, giao tiếp), Tiếng Nhật JLPT, '
                . 'Tiếng Hàn TOPIK và Tiếng Trung HSK. Có hình thức offline, online và hybrid. '
                . 'Tư vấn lộ trình và kiểm tra đầu vào miễn phí. Lịch học có ban ngày, buổi tối và cuối tuần. '
                . 'Địa chỉ tại khu vực Học viện Công nghệ Bưu chính Viễn thông, Hà Nội; hỗ trợ 08:00–21:00 từ Thứ 2 đến Chủ nhật. '
                . 'Khi người dùng muốn đăng ký, hướng dẫn họ điền biểu mẫu ở đầu trang. '
                . 'Không tự bịa học phí, cam kết đầu ra hoặc thông tin chưa được cung cấp; hãy đề nghị đăng ký tư vấn khi cần thông tin cụ thể. '
                . 'Không yêu cầu hoặc hiển thị mật khẩu, API key và dữ liệu nhạy cảm.'
        ]],
    ],
    'contents' => $contents,
    'generationConfig' => [
        'maxOutputTokens' => 300,
    ],
];

try {
    // API key chỉ nằm ở backend; trình duyệt không gọi trực tiếp Google Gemini.
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent';
    $curl = curl_init($url);
    if ($curl === false) {
        throw new RuntimeException('Không thể khởi tạo kết nối Gemini.');
    }

    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-goog-api-key: ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);

    $rawResponse = curl_exec($curl);
    $curlError = curl_error($curl);
    $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);

    if ($rawResponse === false) {
        throw new RuntimeException('Gemini connection error: ' . $curlError);
    }

    $response = json_decode($rawResponse, true);
    if (!is_array($response)) {
        throw new RuntimeException('Gemini returned an invalid response.');
    }

    if ($statusCode < 200 || $statusCode >= 300) {
        $providerMessage = clean($response['error']['message'] ?? '');
        throw new RuntimeException('Gemini API error ' . $statusCode . ($providerMessage !== '' ? ': ' . $providerMessage : ''));
    }

    if (!empty($response['promptFeedback']['blockReason'])) {
        jsonResponse(false, 'Câu hỏi không thể được xử lý. Vui lòng hỏi nội dung khác.', null, 422);
    }

    $parts = $response['candidates'][0]['content']['parts'] ?? [];
    $replyParts = [];
    foreach ($parts as $part) {
        if (isset($part['text']) && is_string($part['text'])) {
            $replyParts[] = trim($part['text']);
        }
    }
    $reply = trim(implode("\n", array_filter($replyParts)));
    if ($reply === '') {
        throw new RuntimeException('Gemini response did not contain text.');
    }

    jsonResponse(true, '', ['reply' => $reply]);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    jsonResponse(false, 'Trợ lý AI đang bận. Vui lòng thử lại sau.', null, 502);
}
