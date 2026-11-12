'use strict';

// Tiện ích dùng chung cho toàn bộ frontend.
const App = {
    // Token được gửi kèm các request thay đổi dữ liệu để chống CSRF.
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',

    // Hàm AJAX chuẩn: gắn header, giới hạn 15 giây, đọc JSON và chuẩn hóa lỗi.
    async request(url, options = {}) {
        const headers = new Headers(options.headers || {});
        headers.set('Accept', 'application/json');
        if (options.method && options.method.toUpperCase() !== 'GET') {
            headers.set('X-CSRF-Token', this.csrfToken);
        }

        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 15000);

        try {
            const response = await fetch(url, { ...options, headers, signal: controller.signal });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Không thể xử lý yêu cầu.');
            }
            return result;
        } catch (error) {
            if (error.name === 'AbortError') {
                throw new Error('Yêu cầu quá thời gian. Vui lòng thử lại.');
            }
            throw error;
        } finally {
            clearTimeout(timeout);
        }
    },

    // Hiển thị phản hồi ngắn bằng Bootstrap Toast.
    toast(message, type = 'primary') {
        const element = document.querySelector('#appToast');
        if (!element) return;
        element.classList.remove('text-bg-primary', 'text-bg-success', 'text-bg-danger', 'text-bg-warning');
        element.classList.add(`text-bg-${type}`);
        element.querySelector('.toast-body').textContent = message;
        bootstrap.Toast.getOrCreateInstance(element, { delay: 4000 }).show();
    },

    // Mã hóa dữ liệu trước khi ghép vào innerHTML để chống XSS.
    escape(value) {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    },

    // Chỉ gọi callback sau khi người dùng ngừng thao tác, giúp giảm request tìm kiếm.
    debounce(callback, delay = 350) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => callback(...args), delay);
        };
    },

    // Khóa nút và hiện spinner để tránh người dùng gửi lặp request.
    setButtonLoading(button, loading, label = 'Đang xử lý...') {
        if (!button) return;
        if (loading) {
            button.dataset.originalHtml = button.innerHTML;
            button.disabled = true;
            button.innerHTML = `<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>${label}`;
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalHtml || button.innerHTML;
        }
    }
};

const dateElement = document.querySelector('#currentDate');
if (dateElement) {
    dateElement.textContent = new Intl.DateTimeFormat('vi-VN', { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' }).format(new Date());
}
