'use strict';

/**
 * layout.js: Dựng khung giao diện quản trị (Header navbar trên cùng, Toast container)
 * và tự động khởi tạo phiên Admin kèm CSRF token.
 */
const Layout = {
    async init() {
        const pageTitle = document.body.dataset.pageTitle || 'Quản lý khách hàng';

        try {
            // Lấy thông tin phiên Admin và CSRF token từ backend PHP
            const response = await fetch('api/auth/session.php', {
                headers: { Accept: 'application/json' }
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Không thể khởi tạo phiên quản trị.');
            }

            const user = result.data.user;
            App.csrfToken = result.data.csrf_token;

            // Render khung giao diện
            this.render(pageTitle, user);

            // Báo hiệu layout đã dựng xong, các script nghiệp vụ (như customers.js) bắt đầu chạy
            document.dispatchEvent(new CustomEvent('app:ready', { detail: { user } }));
        } catch (error) {
            document.querySelector('#app').innerHTML = `
                <main class="container py-5">
                    <div class="alert alert-danger shadow-sm border-danger-subtle">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        ${App.escape(error.message || 'Không thể khởi tạo trang quản trị. Hãy kiểm tra PHP và MySQL rồi thử lại.')}
                    </div>
                </main>
            `;
        }
    },

    render(pageTitle, user) {
        const template = document.querySelector('#pageContent');
        const pageHtml = template ? template.innerHTML : '';

        document.title = `${pageTitle} | EduCare CRM`;

        document.querySelector('#app').innerHTML = `
            <a class="visually-hidden-focusable skip-link" href="#mainContent">Bỏ qua điều hướng</a>
            <div class="admin-layout">
                <!-- Thanh điều hướng Header phía trên -->
                <header class="admin-navbar">
                    <div class="admin-container px-3 px-md-4 d-flex align-items-center justify-content-between">
                        <a class="admin-brand" href="customers.html">
                            <span class="admin-brand-logo">
                                <i class="bi bi-mortarboard-fill"></i>
                            </span>
                            <div>
                                <h1 class="admin-brand-title">
                                    EduCare
                                    <span class="admin-brand-badge">CRM</span>
                                </h1>
                                <span class="admin-brand-subtitle">Hệ thống Quản lý Khách hàng</span>
                            </div>
                        </a>

                        <div class="d-flex align-items-center gap-2 gap-md-3">
                            <span class="badge bg-light text-secondary border px-3 py-2 d-none d-lg-inline-flex align-items-center fw-normal" id="currentDate">
                                <i class="bi bi-calendar3 me-2 text-primary"></i>
                            </span>
                            <a class="btn btn-sm btn-outline-primary" href="consultation.html">
                                <i class="bi bi-box-arrow-up-right me-1"></i>Trang Khách hàng (Công khai)
                            </a>
                        </div>
                    </div>
                </header>

                <!-- Vùng nội dung nghiệp vụ chính -->
                <main id="mainContent" class="admin-container px-3 px-md-4 py-4" tabindex="-1">
                    ${pageHtml}
                </main>
            </div>

            <!-- Toast thông báo kết quả AJAX -->
            <div class="toast-container position-fixed bottom-0 end-0 p-3" aria-live="polite" aria-atomic="true">
                <div id="appToast" class="toast" role="status">
                    <div class="toast-header">
                        <i class="bi bi-info-circle-fill text-primary me-2" aria-hidden="true"></i>
                        <strong class="me-auto">EduCare CRM</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Đóng"></button>
                    </div>
                    <div class="toast-body"></div>
                </div>
            </div>
        `;

        const dateEl = document.querySelector('#currentDate');
        if (dateEl) {
            const formattedDate = new Intl.DateTimeFormat('vi-VN', {
                weekday: 'long',
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            }).format(new Date());
            dateEl.innerHTML = `<i class="bi bi-calendar3 me-2 text-primary"></i>${formattedDate}`;
        }
    }
};

document.addEventListener('DOMContentLoaded', () => Layout.init());
