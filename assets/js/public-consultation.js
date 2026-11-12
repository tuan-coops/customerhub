'use strict';

// Trang công khai tự lấy CSRF token rồi gửi form tư vấn bằng AJAX.
document.addEventListener('DOMContentLoaded', async () => {
    const form = document.querySelector('#consultationForm');
    const alertBox = document.querySelector('#publicAlert');
    const button = document.querySelector('#submitConsultation');

    try {
        const response = await fetch('api/auth/csrf.php', { headers: { Accept: 'application/json' } });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error();
        App.csrfToken = result.data.csrf_token;
    } catch (_) {
        alertBox.className = 'alert alert-danger';
        alertBox.textContent = 'Không thể kết nối máy chủ. Vui lòng thử lại sau.';
        button.disabled = true;
    }

    // Kiểm tra HTML5 validation, khóa nút và gửi FormData mà không reload trang.
    form.addEventListener('submit', async event => {
        event.preventDefault();
        form.classList.add('was-validated');
        if (!form.checkValidity()) return;

        alertBox.classList.add('d-none');
        App.setButtonLoading(button, true, 'Đang gửi yêu cầu...');
        try {
            const body = new FormData(form);
            body.append('csrf_token', App.csrfToken);
            const result = await App.request('api/customers/public-create.php', { method: 'POST', body });
            alertBox.className = 'alert alert-success';
            const code = result.data?.customer_code ? ` Mã yêu cầu: ${result.data.customer_code}.` : '';
            alertBox.textContent = result.message + code;
            alertBox.focus();
            form.reset();
            form.classList.remove('was-validated');
        } catch (error) {
            alertBox.className = 'alert alert-danger';
            alertBox.textContent = error.message;
            alertBox.focus();
        } finally {
            App.setButtonLoading(button, false);
        }
    });
});
