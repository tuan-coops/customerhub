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

if ($message === '') {
    jsonResponse(false, 'Vui lòng nhập câu hỏi.', null, 422);
}
if (mb_strlen($message) > 500) {
    jsonResponse(false, 'Câu hỏi không được dài quá 500 ký tự.', null, 422);
}

/**
 * Chatbot tư vấn quy tắc (Rule-based / Keyword matching).
 * Phân tích nội dung câu hỏi và phản hồi thông tin phù hợp theo nghiệp vụ trung tâm.
 */
$text = mb_strtolower($message, 'UTF-8');

function containsAny(string $haystack, array $needles): bool
{
    foreach ($needles as $needle) {
        if (mb_strpos($haystack, $needle) !== false) {
            return true;
        }
    }
    return false;
}

$reply = '';

// 1. Chào hỏi & Giới thiệu bản thân
if (containsAny($text, ['xin chào', 'chào bạn', 'chào ad', 'hello', 'hi', 'bạn là ai', 'tư vấn giúp'])) {
    $reply = "Xin chào! Mình là Trợ lý tư vấn EduCare. Mình có thể hỗ trợ bạn thông tin về các khóa học (Tiếng Anh, Nhật, Hàn, Trung), học phí, lịch học, hình thức học và bài test kiểm tra trình độ miễn phí. Bạn đang quan tâm đến ngoại ngữ nào ạ?";
}
// 2. Học phí, giá tiền, chi phí
elseif (containsAny($text, ['học phí', 'chi phí', 'giá', 'bao nhiêu tiền', 'bao tiền', 'đắt không', 'hết bao nhiêu', 'tiền học'])) {
    $reply = "Học phí tại EduCare rất hợp lý cho học sinh, sinh viên và người đi làm (dao động từ 1.500.000đ – 3.500.000đ/khóa tùy lộ trình và mục tiêu). Đặc biệt, khi bạn đăng ký tư vấn trong hôm nay sẽ nhận ưu đãi giảm 15% học phí và 01 buổi học thử miễn phí!";
}
// 3. Khóa học tổng quan / chương trình đào tạo
elseif (containsAny($text, ['khóa học', 'chương trình', 'đào tạo', 'dạy những gì', 'có những môn gì', 'ngoại ngữ nào', 'dạy tiếng gì'])) {
    $reply = "Hiện EduCare có 4 chương trình ngoại ngữ trọng điểm:\n"
        . "• Tiếng Anh: Luyện thi IELTS (5.0 - 7.5+), TOEIC và Giao tiếp công việc.\n"
        . "• Tiếng Nhật: JLPT từ N5 đến N2, tiếng Nhật du học/doanh nghiệp.\n"
        . "• Tiếng Hàn: TOPIK I & II, tiếng Hàn thương mại.\n"
        . "• Tiếng Trung: HSK 1 đến HSK 5 và HSKK giao tiếp.\n"
        . "Bạn muốn tìm hiểu chi tiết hơn về ngôn ngữ nào?";
}
// 4. Tiếng Anh (IELTS, TOEIC, Giao tiếp)
elseif (containsAny($text, ['tiếng anh', 'ielts', 'toeic', 'giao tiếp', 'english'])) {
    $reply = "Khóa Tiếng Anh tại EduCare có lộ trình bài bản từ căn bản đến nâng cao, cam kết đầu ra IELTS 6.5+ hoặc TOEIC 750+. Giảng viên có chứng chỉ quốc tế và kinh nghiệm dày dặn. Bạn có thể điền biểu mẫu tư vấn ở trên để được làm bài kiểm tra trình độ đầu vào miễn phí nhé!";
}
// 5. Tiếng Nhật (JLPT)
elseif (containsAny($text, ['tiếng nhật', 'jlpt', 'n5', 'n4', 'n3', 'n2', 'nhật bản'])) {
    $reply = "Khóa Tiếng Nhật đào tạo cấp tốc từ bảng chữ cái Hiragana/Katakana đến JLPT N2, phục vụ du học, kỹ sư và làm việc tại doanh nghiệp Nhật Bản. Có các lớp học trực tiếp tại trung tâm hoặc online tương tác.";
}
// 6. Tiếng Hàn (TOPIK)
elseif (containsAny($text, ['tiếng hàn', 'topik', 'hàn quốc', 'tiếng triều tiên'])) {
    $reply = "Khóa Tiếng Hàn tập trung rèn luyện phát âm chuẩn, ngữ pháp thực hành và luyện đề thi TOPIK I - II. Phù hợp cho mục tiêu du học, xin học bổng và làm việc tại các tập đoàn Hàn Quốc.";
}
// 7. Tiếng Trung (HSK)
elseif (containsAny($text, ['tiếng trung', 'hsk', 'hskk', 'pinyin', 'trung quốc'])) {
    $reply = "Khóa Tiếng Trung giúp bạn chuẩn hóa phát âm Pinyin, giao tiếp đời sống tự tin và luyện thi đỗ HSK 1 đến HSK 5 trong thời gian nhanh nhất cùng giáo viên nhiều năm kinh nghiệm.";
}
// 8. Lịch học, ca học, thời gian
elseif (containsAny($text, ['lịch học', 'thời gian', 'khi nào', 'mấy giờ', 'buổi tối', 'cuối tuần', 'ban ngày', 'ca học'])) {
    $reply = "Lịch học tại EduCare rất linh hoạt:\n"
        . "• Ca tối: Thứ 2-4-6 hoặc Thứ 3-5-7 (18h00 - 19h30, 19h30 - 21h00).\n"
        . "• Ca cuối tuần: Sáng/Chiều Thứ 7 & Chủ nhật.\n"
        . "Nếu bận việc đột xuất, bạn sẽ được trung tâm hỗ trợ học bù hoặc xem lại video bài giảng ghi hình chất lượng cao.";
}
// 9. Hình thức học (Online, Offline, Hybrid)
elseif (containsAny($text, ['online', 'trực tuyến', 'offline', 'tại lớp', 'tại trung tâm', 'từ xa', 'ở nhà', 'hybrid', 'hình thức'])) {
    $reply = "EduCare hỗ trợ 3 hình thức học linh hoạt:\n"
        . "1. Offline: Học trực tiếp tại trung tâm cùng giáo viên và bạn học.\n"
        . "2. Online: Học tương tác trực tiếp 2 chiều qua Zoom/Google Meet.\n"
        . "3. Hybrid: Kết hợp linh hoạt giữa học tại lớp và online tùy theo lịch bận của bạn.";
}
// 10. Địa chỉ, vị trí, ở đâu, map
elseif (containsAny($text, ['địa chỉ', 'ở đâu', 'chỗ nào', 'vị trí', 'cơ sở', 'bản đồ', 'map', 'đường đi'])) {
    $reply = "Cơ sở EduCare tọa lạc tại khu vực Học viện Công nghệ Bưu chính Viễn thông (PTIT), Hà Nội. Thời gian mở cửa hỗ trợ học viên từ 08:00 đến 21:00 từ Thứ 2 đến Chủ nhật. Bạn có thể cuộn xuống xem bản đồ Google Maps ở cuối trang web nhé!";
}
// 11. Đăng ký, nhập học, học thử, kiểm tra trình độ
elseif (containsAny($text, ['đăng ký', 'nhập học', 'học thử', 'test', 'kiểm tra', 'đầu vào', 'liên hệ'])) {
    $reply = "Để đăng ký học thử hoặc nhận tư vấn lộ trình 1-1 miễn phí, bạn chỉ cần điền thông tin vào Form 'Đăng ký nhận tư vấn' ngay ở đầu trang web này. Chuyên viên của trung tâm sẽ liên hệ hỗ trợ bạn trong vòng 2-4 giờ làm việc!";
}
// 12. Giảng viên, giáo viên, thầy cô
elseif (containsAny($text, ['giáo viên', 'giảng viên', 'thầy cô', 'bản ngữ', 'người nước ngoài', 'đội ngũ'])) {
    $reply = "Đội ngũ giảng viên tại EduCare 100% có chứng chỉ sư phạm và chứng chỉ ngoại ngữ quốc tế (IELTS 8.0+, JLPT N1, HSK 6). Với các lớp giao tiếp nâng cao sẽ có giáo viên bản ngữ đồng hành rèn luyện phản xạ.";
}
// 13. Mất gốc, người mới bắt đầu
elseif (containsAny($text, ['mất gốc', 'chưa biết gì', 'mới bắt đầu', 'yếu', 'kém', 'từ đầu', 'con số 0'])) {
    $reply = "Bạn hoàn toàn yên tâm nhé! EduCare có các lớp thiết kế riêng cho người mất gốc hoặc bắt đầu từ con số 0. Giáo viên giảng chậm, kèm cặp tận tình từng bước giúp bạn lấy lại tự tin chỉ sau 2-3 tháng.";
}
// 14. Cảm ơn, tạm biệt
elseif (containsAny($text, ['cảm ơn', 'thank', 'tạm biệt', 'bye', 'ok', 'được rồi', 'hay quá'])) {
    $reply = "Cảm ơn bạn đã quan tâm đến EduCare! Chúc bạn sớm đạt được mục tiêu ngoại ngữ của mình. Nếu có bất kỳ thắc mắc nào khác, bạn cứ gửi tin nhắn cho mình nhé!";
}
// 15. Mặc định (Fallback)
else {
    $reply = "EduCare là trung tâm đào tạo Tiếng Anh, Tiếng Nhật, Tiếng Hàn và Tiếng Trung với lịch học linh hoạt và hỗ trợ test đầu vào miễn phí. Bạn có thể hỏi mình về 'khóa học', 'học phí', 'lịch học', 'địa chỉ', hoặc điền thông tin vào biểu mẫu tư vấn ở trên để chuyên viên liên hệ trực tiếp nhé!";
}

jsonResponse(true, '', ['reply' => $reply]);
