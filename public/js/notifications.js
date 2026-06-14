/**
 * Notification Manager for SmartIdle ERP
 * Manages system notifications with read/unread status
 */
class NotificationManager {
    constructor() {
        this.unreadCount = 0;
        this.notifications = [];
        this.pollingInterval = 30000;
        this.init();
    }

    init() {
        this.fetchUnreadCount();
        this.startPolling();
        this.bindEvents();
    }

    // Get auth headers
    getAuthHeaders() {
        return {
            'Authorization': 'Bearer ' + localStorage.getItem('erp_token'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };
    }

    // Fetch unread count
    async fetchUnreadCount() {
        try {
            const res = await fetch('/api/v1/notifications/unread-count', {
                headers: this.getAuthHeaders()
            });
            const result = await res.json();
            if (result.success) {
                this.unreadCount = result.data.count;
                this.updateBadge();
            }
        } catch (e) {
            // Silently fail on polling errors
        }
    }

    // Update badge display
    updateBadge() {
        const badge = document.getElementById('notification-badge');
        if (!badge) return;
        if (this.unreadCount > 0) {
            badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
            badge.style.display = 'inline-flex';
        } else {
            badge.style.display = 'none';
        }
    }

    // Fetch notifications list
    async fetchNotifications(page = 1, type = '', isRead = '') {
        try {
            let url = '/api/v1/notifications?page=' + page;
            if (type) url += '&type=' + encodeURIComponent(type);
            if (isRead) url += '&is_read=' + isRead;

            const res = await fetch(url, { headers: this.getAuthHeaders() });
            return await res.json();
        } catch (e) {
            return null;
        }
    }

    // Mark single notification as read
    async markAsRead(id) {
        try {
            const res = await fetch('/api/v1/notifications/' + id + '/read', {
                method: 'POST',
                headers: this.getAuthHeaders()
            });
            const result = await res.json();
            if (result.success) {
                this.unreadCount = Math.max(0, this.unreadCount - 1);
                this.updateBadge();
                return true;
            }
        } catch (e) {
            // ignore
        }
        return false;
    }

    // Mark all as read
    async markAllAsRead() {
        try {
            const res = await fetch('/api/v1/notifications/read-all', {
                method: 'POST',
                headers: this.getAuthHeaders()
            });
            const result = await res.json();
            if (result.success) {
                this.unreadCount = 0;
                this.updateBadge();
                this.loadNotificationContent(1);
                return true;
            }
        } catch (e) {
            // ignore
        }
        return false;
    }

    // Start polling for unread count
    startPolling() {
        setInterval(() => this.fetchUnreadCount(), this.pollingInterval);
    }

    // Bind click event on badge
    bindEvents() {
        const icon = document.getElementById('notification-icon');
        if (icon) {
            icon.style.cursor = 'pointer';
            icon.addEventListener('click', () => this.openNotificationPanel());
        }
    }

    // Open notification panel modal
    async openNotificationPanel() {
        this.closeModal();
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.id = 'notification-modal';
        overlay.onclick = (e) => {
            if (e.target === overlay) this.closeModal();
        };
        document.body.appendChild(overlay);
        const panel = document.createElement('div');
        panel.className = 'modal notification-panel';
        panel.innerHTML = this.getPanelHTML();
        overlay.appendChild(panel);
        await this.loadNotificationContent(1);
    }

    // Modal HTML
    getPanelHTML() {
        return `
            <div class="modal-header">
                <h3>Notification Center</h3>
                <div style="display:flex;gap:10px;align-items:center;">
                    <button class="btn btn-sm btn-outline" onclick="notificationManager.markAllAsRead()">Mark All Read</button>
                    <span class="modal-close" onclick="notificationManager.closeModal()">&times;</span>
                </div>
            </div>
            <div class="notification-filters">
                <select id="n-type-filter" class="filter-select" onchange="notificationManager.filterNotifications()">
                    <option value="">All Types</option>
                    <option value="order_status">Order Status</option>
                    <option value="approval_result">Approval Result</option>
                    <option value="inventory_warning">Inventory Warning</option>
                    <option value="system">System</option>
                </select>
                <select id="n-read-filter" class="filter-select" onchange="notificationManager.filterNotifications()">
                    <option value="">All</option>
                    <option value="0">Unread</option>
                    <option value="1">Read</option>
                </select>
            </div>
            <div class="notification-list" id="notification-list">
                <div class="loading-overlay"><div class="loading-spinner"></div></div>
            </div>
            <div class="pagination" id="notification-pagination"></div>
        `;
    }

    // Load notification content
    async loadNotificationContent(page = 1) {
        const listEl = document.getElementById('notification-list');
        const paginationEl = document.getElementById('notification-pagination');
        if (!listEl) return;

        const typeFilter = document.getElementById('n-type-filter')?.value || '';
        const readFilter = document.getElementById('n-read-filter')?.value || '';

        listEl.innerHTML = '<div class="loading-overlay"><div class="loading-spinner"></div></div>';

        const result = await this.fetchNotifications(page, typeFilter, readFilter);
        if (result && result.success) {
            // Laravel paginator: result.data is a paginator object, items are in result.data.data
            const notifications = Array.isArray(result.data)
                ? result.data
                : (result.data?.data || []);
            this.renderNotificationList(notifications);
            this.renderPagination(result, paginationEl, page);
        } else {
            listEl.innerHTML = '<div class="empty-state">Failed to load</div>';
        }
    }

    // Render notification list
    renderNotificationList(notifications) {
        const listEl = document.getElementById('notification-list');
        if (!notifications || notifications.length === 0) {
            listEl.innerHTML = '<div class="empty-state"><div class="icon">🔔</div><div>No notifications</div></div>';
            return;
        }

        listEl.innerHTML = notifications.map(n => {
            const icon = this.getLevelIcon(n.level);
            const timeStr = this.formatTime(n.created_at);
            return `
                <div class="notification-item ${n.is_read ? 'read' : 'unread'}" data-id="${n.id}" onclick="notificationManager.handleNotificationClick(${n.id}, '${n.related_type || ''}', ${n.related_id || 0}, '${n.type || ''}')">
                    <div class="notification-icon level-${n.level || 'info'}">${icon}</div>
                    <div class="notification-body">
                        <div class="notification-title">${this.escapeHtml(n.title || '')}</div>
                        <div class="notification-text">${this.escapeHtml(n.content || '')}</div>
                        <div class="notification-time">${timeStr}</div>
                    </div>
                    ${!n.is_read ? '<div class="unread-dot"></div>' : ''}
                </div>
            `;
        }).join('');
    }

    // Render pagination
    renderPagination(result, container, currentPage) {
        const meta = result.meta;
        if (!container || !meta || meta.last_page <= 1) {
            if (container) container.innerHTML = '';
            return;
        }

        const last = meta.last_page;
        let html = '';
        for (let i = 1; i <= last; i++) {
            html += `<button class="btn btn-sm ${i === currentPage ? 'btn-primary' : 'btn-outline'}" onclick="notificationManager.loadNotificationContent(${i})">${i}</button>`;
        }
        container.innerHTML = html;
    }

    // Handle notification click
    async handleNotificationClick(id, relatedType, relatedId, type) {
        await this.markAsRead(id);

        // Highlight the clicked item
        const item = document.querySelector('.notification-item[data-id="' + id + '"]');
        if (item) item.classList.add('read');

        // Navigate to related page
        if (relatedType && relatedId && relatedId > 0) {
            this.navigateToRelated(relatedType, relatedId);
        }
    }

    // Navigate to related business page
    navigateToRelated(type, id) {
        this.closeModal();
        if (typeof showModule === 'function') {
            if (type.includes('SalesOrder') || type.includes('sales')) {
                showModule('sales');
            } else if (type.includes('PurchaseOrder') || type.includes('purchase')) {
                showModule('purchase');
            }
        }
    }

    // Get level icon
    getLevelIcon(level) {
        const icons = { info: 'ℹ️', success: '✅', warning: '⚠️', error: '❌' };
        return icons[level] || icons.info;
    }

    // Format relative time
    formatTime(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        const now = new Date();
        const diff = now - date;
        if (diff < 60000) return 'Just now';
        if (diff < 3600000) return Math.floor(diff / 60000) + ' min ago';
        if (diff < 86400000) return Math.floor(diff / 3600000) + ' hours ago';
        if (diff < 604800000) return Math.floor(diff / 86400000) + ' days ago';
        return date.toLocaleDateString();
    }

    // Escape HTML
    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Filter notifications
    filterNotifications() {
        this.loadNotificationContent(1);
    }

    // Close modal
    closeModal() {
        const modal = document.getElementById('notification-modal');
        if (modal) modal.remove();
    }
}

// Global instance
const notificationManager = new NotificationManager();