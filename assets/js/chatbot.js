'use strict';

/**
 * chatbot.js: Tạo widget chat tư vấn tự động, gửi câu hỏi đến backend PHP
 * sử dụng AJAX Fetch API mà không cần tải lại trang.
 */
document.addEventListener('DOMContentLoaded', () => {
    const quickQuestions = [
        'Trung tâm có khóa học nào?',
        'Học phí như thế nào?',
        'Lịch học ra sao?',
        'Địa chỉ trung tâm ở đâu?'
    ];
    let sending = false;

    const root = document.createElement('div');
    root.className = 'chatbot';
    root.innerHTML = `
        <button class="chatbot-toggle" type="button" aria-label="Mở trợ lý tư vấn" aria-expanded="false" aria-controls="chatbotPanel">
            <i class="bi bi-chat-dots-fill" aria-hidden="true"></i>
            <span class="chatbot-notification" aria-hidden="true">1</span>
        </button>
        <section class="chatbot-panel" id="chatbotPanel" aria-label="Trợ lý tư vấn EduCare" hidden>
            <header class="chatbot-header">
                <div class="chatbot-avatar" aria-hidden="true"><i class="bi bi-chat-heart-fill"></i></div>
                <div><strong>Trợ lý Tư vấn EduCare</strong><span>Hỗ trợ trực tuyến 24/7</span></div>
                <button class="chatbot-close" type="button" aria-label="Đóng cửa sổ chat"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </header>
            <div class="chatbot-messages" aria-live="polite" aria-relevant="additions"></div>
            <div class="chatbot-suggestions" aria-label="Câu hỏi gợi ý"></div>
            <form class="chatbot-form">
                <label class="visually-hidden" for="chatbotInput">Nhập câu hỏi</label>
                <input class="chatbot-input" id="chatbotInput" type="text" maxlength="500" autocomplete="off" placeholder="Nhập câu hỏi..." required>
                <button class="chatbot-send" type="submit" aria-label="Gửi câu hỏi"><i class="bi bi-send-fill" aria-hidden="true"></i></button>
            </form>
            <div class="chatbot-note">Trợ lý tư vấn tự động EduCare Language Center</div>
        </section>
    `;
    document.body.appendChild(root);

    const toggle = root.querySelector('.chatbot-toggle');
    const panel = root.querySelector('.chatbot-panel');
    const closeButton = root.querySelector('.chatbot-close');
    const messages = root.querySelector('.chatbot-messages');
    const suggestions = root.querySelector('.chatbot-suggestions');
    const form = root.querySelector('.chatbot-form');
    const input = root.querySelector('.chatbot-input');
    const sendButton = root.querySelector('.chatbot-send');
    const notification = root.querySelector('.chatbot-notification');

    function addMessage(text, sender, extraClass = '') {
        const message = document.createElement('div');
        message.className = `chatbot-message chatbot-message-${sender} ${extraClass}`.trim();
        message.textContent = text;
        messages.appendChild(message);
        messages.scrollTop = messages.scrollHeight;
        return message;
    }

    function setSending(value) {
        sending = value;
        input.disabled = value;
        sendButton.disabled = value;
        sendButton.innerHTML = value
            ? '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>'
            : '<i class="bi bi-send-fill" aria-hidden="true"></i>';
    }

    async function ensureCsrfToken() {
        if (App.csrfToken) return;
        const response = await fetch('api/auth/csrf.php', { headers: { Accept: 'application/json' } });
        const result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Không thể khởi tạo phiên trò chuyện.');
        }
        App.csrfToken = result.data.csrf_token;
    }

    // Gửi câu hỏi đến backend PHP bằng AJAX Fetch API
    async function answer(question) {
        const cleanQuestion = question.trim();
        if (!cleanQuestion || sending) return;

        addMessage(cleanQuestion, 'user');
        input.value = '';
        setSending(true);
        const loadingMessage = addMessage('Đang trả lời…', 'bot', 'chatbot-message-loading');

        try {
            await ensureCsrfToken();
            const result = await App.request('api/chatbot/message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: cleanQuestion
                })
            });

            const reply = String(result.data?.reply || '').trim();
            if (!reply) throw new Error('Trợ lý không trả về nội dung.');

            loadingMessage.remove();
            addMessage(reply, 'bot');
        } catch (error) {
            loadingMessage.remove();
            addMessage(error.message || 'Không thể kết nối trợ lý. Vui lòng thử lại.', 'bot', 'chatbot-message-error');
        } finally {
            setSending(false);
            input.focus();
        }
    }

    quickQuestions.forEach(question => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'chatbot-suggestion';
        button.textContent = question;
        button.addEventListener('click', () => answer(question));
        suggestions.appendChild(button);
    });

    function openChat() {
        panel.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        toggle.setAttribute('aria-label', 'Đóng trợ lý tư vấn');
        notification.hidden = true;
        window.setTimeout(() => input.focus(), 0);
    }

    function closeChat() {
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Mở trợ lý tư vấn');
        toggle.focus();
    }

    toggle.addEventListener('click', () => panel.hidden ? openChat() : closeChat());
    closeButton.addEventListener('click', closeChat);
    form.addEventListener('submit', event => {
        event.preventDefault();
        answer(input.value);
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !panel.hidden) closeChat();
    });

    // Lời chào mở đầu
    addMessage('Xin chào! Mình là Trợ lý tư vấn EduCare. Bạn đang quan tâm đến khóa học, học phí hay lịch học nào ạ?', 'bot');
});
