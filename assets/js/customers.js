'use strict';

// Chỉ chạy sau khi layout.js đã dựng xong Header và nội dung từ template.
document.addEventListener('app:ready', () => {
    // Khởi tạo hai Bootstrap Modal: một modal Thêm/Sửa và một modal xác nhận Xóa.
    const customerModal = bootstrap.Modal.getOrCreateInstance(document.querySelector('#customerModal'));
    const deleteModal = bootstrap.Modal.getOrCreateInstance(document.querySelector('#deleteCustomerModal'));
    const customerForm = document.querySelector('#customerForm');
    const formAlert = document.querySelector('#formAlert');
    const saveButton = document.querySelector('#saveCustomerButton');

    // Ánh xạ mã ENUM trong MySQL thành nhãn tiếng Việt trên giao diện.
    const typeLabels = {
        lead: 'Tiềm năng',
        consulting: 'Đang tư vấn',
        high_interest: 'Quan tâm cao',
        appointment: 'Đã hẹn',
        registered: 'Đã đăng ký',
        no_demand: 'Chưa có nhu cầu',
        unreachable: 'Không liên hệ được'
    };

    // Ánh xạ lớp CSS cho từng trạng thái badge
    const statusBadgeClasses = {
        lead: 'badge-lead',
        consulting: 'badge-consulting',
        high_interest: 'badge-high_interest',
        appointment: 'badge-appointment',
        registered: 'badge-registered',
        no_demand: 'badge-no_demand',
        unreachable: 'badge-unreachable'
    };

    // Ghi nhớ trang hiện tại để sau khi sửa/xóa vẫn đứng đúng trang.
    let currentPage = 1;
    // Lưu khách hàng chuẩn bị xóa; chỉ xóa sau khi người dùng xác nhận trong Modal.
    let deleteTarget = null;

    // Hàm lấy chữ cái đầu để vẽ Avatar
    function getInitials(name) {
        if (!name) return 'KH';
        const parts = name.trim().split(/\s+/);
        if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }

    // Tải danh sách theo từ khóa, bộ lọc, sắp xếp và số trang hiện tại.
    async function loadCustomers(page = 1) {
        currentPage = page;

        // URLSearchParams mã hóa các điều kiện thành query string gửi cho list.php.
        const params = new URLSearchParams({
            page,
            keyword: document.querySelector('#keyword').value.trim(),
            type: document.querySelector('#typeFilter').value,
            status: document.querySelector('#statusFilter').value,
            source: document.querySelector('#sourceFilter').value,
            sort: document.querySelector('#sortFilter').value
        });

        const rows = document.querySelector('#customerRows');
        rows.innerHTML = '<tr><td colspan="6" class="loading-skeleton"><span class="spinner-border spinner-border-sm text-primary me-2" aria-hidden="true"></span><span>Đang tải danh sách khách hàng...</span></td></tr>';

        try {
            // App.request gọi Fetch API và chuẩn hóa lỗi JSON từ backend.
            const result = await App.request(`api/customers/list.php?${params}`);
            renderCustomers(result.data);
        } catch (error) {
            rows.innerHTML = `<tr><td colspan="6"><div class="alert alert-danger my-3">${App.escape(error.message)}</div></td></tr>`;
        }
    }

    // Chuyển JSON từ list.php thành các dòng bảng và nút phân trang.
    function renderCustomers(data) {
        const rows = document.querySelector('#customerRows');
        document.querySelector('#resultSummary').textContent = `${data.pagination.total_records.toLocaleString('vi-VN')} khách hàng trong hệ thống`;

        // Dữ liệu người dùng luôn đi qua App.escape() trước khi ghép vào innerHTML để chống XSS.
        rows.innerHTML = data.customers.length ? data.customers.map(customer => {
            const initials = getInitials(customer.full_name);
            const badgeClass = statusBadgeClasses[customer.customer_type] || 'badge-lead';

            return `<tr>
                <td>
                    <div class="customer-cell">
                        <div class="customer-avatar">${App.escape(initials)}</div>
                        <div>
                            <div class="customer-name">${App.escape(customer.full_name)}</div>
                            <div class="customer-meta">${App.escape(customer.customer_code)} · ${App.escape(customer.phone_number)}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="fw-medium text-dark">${App.escape(customer.language_interest || 'Ngoại ngữ')}</div>
                    <div class="customer-meta">${App.escape(customer.learning_goal || customer.email || 'Chưa có ghi chú mục tiêu')}</div>
                    <span class="source-chip"><i class="bi bi-tag" style="font-size: .65rem;"></i>${App.escape(customer.source || 'Khác')}</span>
                </td>
                <td>
                    <span class="status-badge ${badgeClass}">
                        ${App.escape(typeLabels[customer.customer_type] || customer.customer_type)}
                    </span>
                </td>
                <td>
                    <span class="text-secondary small d-inline-flex align-items-center">
                        <i class="bi bi-person-check me-1 text-muted"></i>${App.escape(customer.admin_name || 'Admin')}
                    </span>
                </td>
                <td class="customer-meta">${formatDate(customer.updated_at)}</td>
                <td class="text-end">
                    <div class="d-inline-flex gap-1" role="group" aria-label="Thao tác khách hàng">
                        <button class="btn-action btn-action-edit" type="button" data-action="edit" data-id="${customer.id}" title="Sửa thông tin khách hàng">
                            <i class="bi bi-pencil" aria-hidden="true"></i><span>Sửa</span>
                        </button>
                        <button class="btn-action btn-action-delete" type="button" data-action="delete" data-id="${customer.id}" title="Xóa khách hàng">
                            <i class="bi bi-trash" aria-hidden="true"></i><span>Xóa</span>
                        </button>
                    </div>
                </td>
            </tr>`;
        }).join('') : `<tr><td colspan="6"><div class="empty-state"><i class="bi bi-search" aria-hidden="true"></i><strong>Không tìm thấy khách hàng</strong><div>Hãy thử thay đổi từ khóa hoặc bộ lọc tìm kiếm.</div></div></td></tr>`;

        // total_pages do backend tính từ COUNT(*) và per_page.
        document.querySelector('#pagination').innerHTML = Array.from(
            { length: data.pagination.total_pages },
            (_, index) => index + 1
        ).map(page => `
            <li class="page-item ${page === data.pagination.page ? 'active' : ''}">
                <button class="page-link" type="button" data-page="${page}" ${page === data.pagination.page ? 'aria-current="page"' : ''}>${page}</button>
            </li>
        `).join('');
    }

    // Đổi DATETIME MySQL thành ngày theo định dạng Việt Nam.
    function formatDate(value) {
        if (!value) return '—';
        const date = new Date(value.replace(' ', 'T'));
        return Number.isNaN(date.getTime()) ? '—' : new Intl.DateTimeFormat('vi-VN', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        }).format(date);
    }

    // Chuẩn bị Modal ở chế độ CREATE: xóa dữ liệu cũ và gán giá trị mặc định.
    function openCreateModal() {
        customerForm.reset();
        customerForm.classList.remove('was-validated');
        document.querySelector('#customerId').value = '';
        document.querySelector('#languageInterest').value = 'Tiếng Anh';
        document.querySelector('#source').value = 'Website';
        document.querySelector('#customerType').value = 'lead';
        document.querySelector('#customerStatus').value = 'active';
        document.querySelector('#customerModalTitle').textContent = 'Thêm khách hàng';
        saveButton.innerHTML = '<i class="bi bi-check2 me-1"></i>Lưu khách hàng';
        formAlert.classList.add('d-none');
        customerModal.show();
    }

    // Chuẩn bị Modal ở chế độ UPDATE: tải hồ sơ hiện tại và đổ vào từng input theo name.
    async function openEditModal(id, trigger) {
        trigger.disabled = true;
        try {
            const result = await App.request(`api/customers/detail.php?id=${id}`);
            const customer = result.data.customer;
            customerForm.reset();
            customerForm.classList.remove('was-validated');

            // Tên trong mảng phải trùng với thuộc tính JSON và name của input HTML.
            [
                'id', 'full_name', 'phone_number', 'email', 'occupation', 'language_interest',
                'learning_goal', 'current_level', 'preferred_schedule', 'learning_format',
                'source', 'customer_type', 'status', 'note'
            ].forEach(name => {
                const field = customerForm.elements.namedItem(name);
                if (field) field.value = customer[name] ?? '';
            });

            document.querySelector('#customerModalTitle').textContent = 'Sửa thông tin khách hàng';
            saveButton.innerHTML = '<i class="bi bi-check2 me-1"></i>Cập nhật thông tin';
            formAlert.classList.add('d-none');
            customerModal.show();
        } catch (error) {
            App.toast(error.message, 'danger');
        } finally {
            trigger.disabled = false;
        }
    }

    // Debounce ô tìm kiếm để chỉ gọi API sau khi người dùng ngừng gõ 350ms.
    document.querySelector('#keyword').addEventListener('input', App.debounce(() => loadCustomers(1)));
    // Mỗi lần đổi bộ lọc đều quay về trang đầu để tránh số trang cũ không còn hợp lệ.
    document.querySelector('#typeFilter').addEventListener('change', () => loadCustomers(1));
    document.querySelector('#statusFilter').addEventListener('change', () => loadCustomers(1));
    document.querySelector('#sourceFilter').addEventListener('change', () => loadCustomers(1));
    document.querySelector('#sortFilter').addEventListener('change', () => loadCustomers(1));

    document.querySelector('#resetFilters').addEventListener('click', () => {
        document.querySelector('#keyword').value = '';
        document.querySelector('#typeFilter').value = '';
        document.querySelector('#statusFilter').value = 'active';
        document.querySelector('#sourceFilter').value = '';
        document.querySelector('#sortFilter').value = 'updated_desc';
        loadCustomers(1);
    });

    // Event delegation cho các nút phân trang được render động.
    document.querySelector('#pagination').addEventListener('click', event => {
        const button = event.target.closest('[data-page]');
        if (button) loadCustomers(Number(button.dataset.page));
    });

    document.querySelector('#createCustomerButton').addEventListener('click', openCreateModal);

    // Event delegation cho nút Sửa/Xóa trong các dòng bảng được tạo bằng innerHTML.
    document.querySelector('#customerRows').addEventListener('click', event => {
        const button = event.target.closest('[data-action]');
        if (!button) return;

        if (button.dataset.action === 'edit') {
            openEditModal(Number(button.dataset.id), button);
            return;
        }

        // Chỉ ghi nhớ đối tượng cần xóa; request thật chỉ gửi sau khi xác nhận Modal.
        const name = button.closest('tr').querySelector('.customer-name').textContent;
        deleteTarget = { id: Number(button.dataset.id), name };
        document.querySelector('#deleteCustomerName').textContent = name;
        deleteModal.show();
    });

    // Một form dùng chung cho CREATE và UPDATE; hidden customerId quyết định endpoint.
    customerForm.addEventListener('submit', async event => {
        event.preventDefault();
        customerForm.classList.add('was-validated');
        if (!customerForm.checkValidity()) return;

        const isEditing = Boolean(document.querySelector('#customerId').value);
        formAlert.classList.add('d-none');
        App.setButtonLoading(saveButton, true, isEditing ? 'Đang cập nhật...' : 'Đang thêm...');

        try {
            const body = new FormData(customerForm);
            body.append('csrf_token', App.csrfToken);

            // Có ID gọi update.php; không có ID gọi create.php.
            const result = await App.request(
                isEditing ? 'api/customers/update.php' : 'api/customers/create.php',
                { method: 'POST', body }
            );

            customerModal.hide();
            App.toast(result.message, 'success');
            // Thêm mới quay về trang 1; cập nhật giữ nguyên trang đang xem.
            await loadCustomers(isEditing ? currentPage : 1);
        } catch (error) {
            formAlert.textContent = error.message;
            formAlert.classList.remove('d-none');
            formAlert.focus();
        } finally {
            App.setButtonLoading(saveButton, false);
        }
    });

    // Gửi DELETE sau khi người dùng xác nhận; backend dùng cascade xóa dữ liệu liên quan.
    document.querySelector('#confirmDeleteButton').addEventListener('click', async event => {
        if (!deleteTarget) return;
        const button = event.currentTarget;
        App.setButtonLoading(button, true, 'Đang xóa...');

        try {
            const body = new FormData();
            body.append('id', deleteTarget.id);
            body.append('csrf_token', App.csrfToken);
            const result = await App.request('api/customers/delete.php', { method: 'POST', body });

            deleteModal.hide();
            App.toast(result.message, 'success');
            deleteTarget = null;
            await loadCustomers(currentPage);
        } catch (error) {
            App.toast(error.message, 'danger');
        } finally {
            App.setButtonLoading(button, false);
        }
    });

    // Tải dữ liệu lần đầu ngay khi trang quản trị sẵn sàng.
    loadCustomers();
});
