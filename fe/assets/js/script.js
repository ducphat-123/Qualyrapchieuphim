const apiPrefix = window.location.pathname.includes('/admin/') ? '../' : '';

document.addEventListener('DOMContentLoaded', () => {
    // Auto-wrap tables with responsive container to prevent horizontal page overflow
    document.querySelectorAll('table.data-table').forEach(table => {
        const parent = table.parentElement;
        if (parent && !parent.classList.contains('table-responsive') && parent.style.overflowX !== 'auto' && parent.tagName !== 'FORM') {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            wrapper.style.overflowX = 'auto';
            wrapper.style.webkitOverflowScrolling = 'touch';
            wrapper.style.width = '100%';
            wrapper.style.marginBottom = '1.5rem';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });

    // Determine which page we are on
    const path = window.location.pathname;
    
    if (path.includes('index.php') || path.endsWith('/') || path.endsWith('Qualyrapchieuphim')) {
        fetchDashboardData();
    } else if (path.includes('logs.php')) {
        fetchLogsData();
    } else if (path.includes('users.php')) {
        fetchUsersData();
    }
});

function getInitials(name) {
    if (!name) return 'U';
    const parts = name.split(' ');
    if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
}

function getRoleClass(role) {
    if (!role) return '';
    const r = role.toLowerCase();
    if (r.includes('admin')) return 'role-admin';
    if (r.includes('toán')) return 'role-ke-toan';
    if (r.includes('manager')) return 'role-manager';
    if (r.includes('hr')) return 'role-hr';
    if (r.includes('thuật')) return 'role-ky-thuat';
    if (r.includes('marketing')) return 'role-marketing';
    if (r.includes('cskh')) return 'role-cskh';
    return '';
}

function getActionIcon(actionType) {
    const t = actionType.toLowerCase();
    if (t.includes('cập nhật') || t.includes('thay đổi')) return '<div class="action-icon update"><i class="fa-solid fa-pen"></i></div>';
    if (t.includes('xóa')) return '<div class="action-icon delete"><i class="fa-solid fa-trash"></i></div>';
    if (t.includes('phê duyệt')) return '<div class="action-icon approve"><i class="fa-solid fa-check"></i></div>';
    if (t.includes('đăng nhập')) return '<div class="action-icon login"><i class="fa-solid fa-arrow-right-to-bracket"></i></div>';
    return '<div class="action-icon update"><i class="fa-solid fa-clock-rotate-left"></i></div>';
}

async function fetchDashboardData() {
    try {
        const response = await fetch(apiPrefix + '../be/controllers/admin/AdminRevenueController.php?action=dashboard_data');
        const data = await response.json();
        
        // Update KPIs
        if (data.kpis) {
            document.getElementById('kpi-tickets').textContent = data.kpis.total_tickets.toLocaleString('de-DE');
            document.getElementById('kpi-checkins').textContent = data.kpis.total_checkins.toLocaleString('de-DE');
            const kpiRev = document.getElementById('kpi-revenue');
            if (kpiRev) {
                kpiRev.textContent = data.kpis.total_revenue.toLocaleString('vi-VN') + '₫';
            }
            document.getElementById('kpi-staff').textContent = data.kpis.active_staff;
            document.getElementById('kpi-locked').textContent = data.kpis.locked_accounts < 10 ? '0' + data.kpis.locked_accounts : data.kpis.locked_accounts;
            
            // Update dynamic trend elements
            const updateTrend = (elId, text, trendClass) => {
                const el = document.getElementById(elId);
                if (el && text) {
                    const arrow = trendClass === 'positive' 
                        ? '<i class="fa-solid fa-arrow-up"></i>' 
                        : (trendClass === 'negative' ? '<i class="fa-solid fa-arrow-down"></i>' : '<i class="fa-solid fa-minus"></i>');
                    el.className = `kpi-trend ${trendClass}`;
                    el.innerHTML = `${arrow} ${text}`;
                }
            };
            updateTrend('trend-tickets', data.kpis.tickets_trend, data.kpis.tickets_trend_class);
            updateTrend('trend-checkins', data.kpis.checkins_trend, data.kpis.checkins_trend_class);
            updateTrend('trend-revenue', data.kpis.revenue_trend, data.kpis.revenue_trend_class);
        }

        // Render Logs Table
        const logsBody = document.getElementById('logsTableBody');
        if (logsBody) {
            logsBody.innerHTML = '';
            if (data.logs && data.logs.length > 0) {
                data.logs.forEach(log => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${log.log_time}</td>
                        <td>
                            <div class="user-cell">
                                <div class="user-initials">${getInitials(log.user_name)}</div>
                                <div class="user-info-text">
                                    <strong>${log.user_name}</strong>
                                    <span>${log.role}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center;">
                                ${getActionIcon(log.action_type)} ${log.action_type}
                            </div>
                        </td>
                        <td><span style="color: var(--text-muted); font-size: 12px;">${log.action_desc}</span></td>
                    `;
                    logsBody.appendChild(tr);
                });
            }
        }

        // Update checkin footer note with actual peak
        const noteEl = document.getElementById('checkin-footer-note');
        if (noteEl && data.checkin_peak) {
            if (data.checkin_peak.count > 0) {
                noteEl.innerHTML = `<i class="fa-solid fa-circle-info"></i> Cao điểm thực tế hôm nay lúc <strong>${data.checkin_peak.hour}</strong> với <strong>${data.checkin_peak.count}</strong> lượt check-in.`;
            } else {
                noteEl.innerHTML = `<i class="fa-solid fa-circle-info"></i> Chưa có lượt check-in nào trong ngày hôm nay.`;
            }
        }

        initCharts(data.sales_trend, data.checkin_hourly);

    } catch (error) {
        console.error('Error:', error);
    }
}

let allLogs = [];
let logsCurrentPage = 1;
const logsPerPage = 15;

async function fetchLogsData() {
    try {
        const response = await fetch(apiPrefix + '../be/controllers/admin/AdminRevenueController.php?action=get_logs');
        const json = await response.json();
        if (json.success && json.data) {
            allLogs = json.data;
            filterLogs(true);
        }
    } catch(err) {
        console.error('Lỗi tải nhật ký:', err);
    }
}

function filterLogs(resetPage = false) {
    if (resetPage) {
        logsCurrentPage = 1;
    }

    const query = document.getElementById('search-logs') ? document.getElementById('search-logs').value.toLowerCase().trim() : '';
    const startDate = document.getElementById('filter-start-date') ? document.getElementById('filter-start-date').value : '';
    const endDate = document.getElementById('filter-end-date') ? document.getElementById('filter-end-date').value : '';
    const actionFilter = document.getElementById('filter-action') ? document.getElementById('filter-action').value : '';

    const matched = allLogs.filter(log => {
        // 1. Text Search: username, description or action type
        const matchesQuery = !query || 
            (log.user_name && log.user_name.toLowerCase().includes(query)) || 
            (log.action_desc && log.action_desc.toLowerCase().includes(query)) ||
            (log.action_type && log.action_type.toLowerCase().includes(query));

        // 2. Action Type Filter
        const matchesAction = !actionFilter || 
            (log.action_type && log.action_type.toLowerCase().includes(actionFilter.toLowerCase()));

        // 3. Date range filter
        let matchesDate = true;
        if (log.log_time) {
            const logDateOnly = log.log_time.substring(0, 10); // YYYY-MM-DD
            if (startDate && logDateOnly < startDate) {
                matchesDate = false;
            }
            if (endDate && logDateOnly > endDate) {
                matchesDate = false;
            }
        } else {
            if (startDate || endDate) matchesDate = false;
        }

        return matchesQuery && matchesAction && matchesDate;
    });

    const totalRows = matched.length;
    const totalPages = Math.ceil(totalRows / logsPerPage) || 1;

    if (logsCurrentPage > totalPages) {
        logsCurrentPage = totalPages;
    }
    if (logsCurrentPage < 1) {
        logsCurrentPage = 1;
    }

    // Render table rows
    const tbody = document.getElementById('fullLogsBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (matched.length === 0) {
        tbody.innerHTML = `<tr>
            <td colspan="5" style="text-align:center; padding: 48px 20px;">
                <div style="display:flex; flex-direction:column; align-items:center; gap:12px; color:var(--text-muted);">
                    <div style="width:56px;height:56px;border-radius:16px;background:#F1F5F9;display:flex;align-items:center;justify-content:center;font-size:22px;">
                        <i class="fa-solid fa-clock-rotate-left" style="opacity:.4;"></i>
                    </div>
                    <div>
                        <div style="font-size:15px;font-weight:700;color:var(--text-main);margin-bottom:4px;">Không tìm thấy hoạt động nào</div>
                        <div style="font-size:13px;">Thử thay đổi từ khoá hoặc bộ lọc để xem kết quả khác.</div>
                    </div>
                </div>
            </td>
        </tr>`;
        updateLogsPaginationInfo(0, 0, 0);
        renderLogsPaginationControls(1);
        return;
    }

    const startIdx = (logsCurrentPage - 1) * logsPerPage;
    const endIdx = Math.min(startIdx + logsPerPage, totalRows);
    const visibleLogs = matched.slice(startIdx, endIdx);

    visibleLogs.forEach(log => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="font-weight:600; color:var(--text-main); font-size:13.5px;">${log.log_time}</td>
            <td>
                <div class="user-cell">
                    <div class="user-initials">${getInitials(log.user_name)}</div>
                    <div class="user-info-text">
                        <strong>${log.user_name}</strong>
                        <span><span class="badge-role ${getRoleClass(log.role)}">${log.role}</span></span>
                    </div>
                </div>
            </td>
            <td>
                <div style="display: flex; align-items: center;">
                    ${getActionIcon(log.action_type)} ${log.action_type}
                </div>
            </td>
            <td style="font-size:13.5px; color:#4B5563; max-width:400px; word-wrap:break-word; white-space:normal;">${log.action_desc}</td>
            <td><button class="btn-icon"><i class="fa-solid fa-ellipsis-vertical"></i></button></td>
        `;
        tbody.appendChild(tr);
    });

    updateLogsPaginationInfo(startIdx + 1, endIdx, totalRows);
    renderLogsPaginationControls(totalPages);
}

function updateLogsPaginationInfo(start, end, total) {
    const info = document.getElementById('logs-page-info');
    if (info) {
        if (total === 0) {
            info.textContent = 'HIỂN THỊ 0 CỦA 0';
        } else {
            info.textContent = `HIỂN THỊ ${start}-${end} CỦA ${total}`;
        }
    }
}

function renderLogsPaginationControls(totalPages) {
    const container = document.getElementById('logs-pagination');
    if (!container) return;

    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = '';
    
    // Previous Button
    if (logsCurrentPage === 1) {
        html += `<div class="page-item disabled" style="opacity: 0.5; cursor: not-allowed;"><i class="fa-solid fa-chevron-left"></i></div>`;
    } else {
        html += `<div class="page-item" onclick="changeLogsPage(${logsCurrentPage - 1})"><i class="fa-solid fa-chevron-left"></i></div>`;
    }

    // Page Numbers
    const maxVisible = 5;
    let startPage = Math.max(1, logsCurrentPage - Math.floor(maxVisible / 2));
    let endPage = Math.min(totalPages, startPage + maxVisible - 1);

    if (endPage - startPage + 1 < maxVisible) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }

    if (startPage > 1) {
        html += `<div class="page-item" onclick="changeLogsPage(1)">1</div>`;
        if (startPage > 2) {
            html += `<div class="page-item disabled" style="cursor:default; border:none; background:none;">...</div>`;
        }
    }

    for (let p = startPage; p <= endPage; p++) {
        const activeClass = p === logsCurrentPage ? 'active' : '';
        html += `<div class="page-item ${activeClass}" onclick="changeLogsPage(${p})">${p}</div>`;
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            html += `<div class="page-item disabled" style="cursor:default; border:none; background:none;">...</div>`;
        }
        html += `<div class="page-item" onclick="changeLogsPage(${totalPages})">${totalPages}</div>`;
    }

    // Next Button
    if (logsCurrentPage === totalPages) {
        html += `<div class="page-item disabled" style="opacity: 0.5; cursor: not-allowed;"><i class="fa-solid fa-chevron-right"></i></div>`;
    } else {
        html += `<div class="page-item" onclick="changeLogsPage(${logsCurrentPage + 1})"><i class="fa-solid fa-chevron-right"></i></div>`;
    }

    container.innerHTML = html;
}

function changeLogsPage(page) {
    logsCurrentPage = page;
    filterLogs(false);
    
    // Smooth scroll to top of table
    const card = document.querySelector('.data-table');
    if (card) {
        card.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

async function fetchUsersData() {
    try {
        const response = await fetch(apiPrefix + '../be/controllers/admin/AdminRevenueController.php?action=get_employees');
        const json = await response.json();
        
        if (json.success && json.data) {
            // Update stats
            document.getElementById('stat-total').textContent = json.stats.total;
            document.getElementById('stat-active').textContent = json.stats.active;
            document.getElementById('stat-pending').textContent = json.stats.pending;
            
            const tbody = document.getElementById('usersBody');
            if (!tbody) return;
            tbody.innerHTML = '';
            
            json.data.forEach(emp => {
                const statusHtml = emp.status === 'active' 
                    ? `<span class="badge-status status-active"><i class="fa-solid fa-circle" style="font-size: 8px;"></i> Hoạt động</span>`
                    : `<span class="badge-status status-locked"><i class="fa-solid fa-lock" style="font-size: 10px;"></i> Đã khóa</span>`;
                    
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${emp.emp_code}</strong></td>
                    <td>
                        <div class="user-cell">
                            <div class="user-initials">${getInitials(emp.full_name)}</div>
                            <div class="user-info-text">
                                <strong>${emp.full_name}</strong>
                                <span>${emp.email}</span>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge-role ${getRoleClass(emp.role)}">${emp.role}</span></td>
                    <td>${statusHtml}</td>
                    <td>${emp.created_at.split(' ')[0]}</td>
                    <td>
                        <button class="btn-icon"><i class="fa-solid fa-pen"></i></button>
                        <button class="btn-icon"><i class="fa-solid fa-trash"></i></button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
    } catch(err) {
        console.error(err);
    }
}

// Charts logic remains same
let salesChartInst = null;
let checkinChartInst = null;
function initCharts(salesData, checkinData) {
    if (!salesData || !checkinData || !document.getElementById('salesChart')) return;
    const ctxSales = document.getElementById('salesChart').getContext('2d');
    if (salesChartInst) salesChartInst.destroy();
    salesChartInst = new Chart(ctxSales, {
        type: 'line',
        data: {
            labels: salesData.map(i => i.day_name),
            datasets: [{
                label: 'Vé đã bán', data: salesData.map(i => i.tickets_sold),
                borderColor: '#0F4C81', backgroundColor: 'rgba(15, 76, 129, 0.1)', borderWidth: 3, tension: 0.4, fill: true,
                pointBackgroundColor: '#fff', pointBorderColor: '#0F4C81', pointBorderWidth: 2, pointRadius: 4,
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#F1F3F5' }, border: { display: false } }, x: { grid: { display: false }, border: { display: false } } } }
    });

    const ctxCheckin = document.getElementById('checkinChart').getContext('2d');
    if (checkinChartInst) checkinChartInst.destroy();
    checkinChartInst = new Chart(ctxCheckin, {
        type: 'bar',
        data: {
            labels: checkinData.map(i => i.hour_label),
            datasets: [{ label: 'Lượt Check-in', data: checkinData.map(i => i.checkins), backgroundColor: '#0F4C81', borderRadius: 4, barThickness: 32, }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#F1F3F5' }, border: { display: false }, ticks: { display: false } }, x: { grid: { display: false }, border: { display: false } } } }
    });
}


// ─────────────────────────────────────────────────────────────────────────────
// MOVIFLEX GLOBAL UI HELPERS
// Replace native confirm() / alert() with beautiful modal dialogs & toasts
// ─────────────────────────────────────────────────────────────────────────────

// Ensure the dialog overlay exists in DOM
function _ensureDialogDOM() {
  if (document.getElementById('mf-dialog-overlay')) return;
  const html = `
    <div id="mf-dialog-overlay">
      <div class="mf-dialog">
        <div class="mf-dialog-icon-wrap">
          <div class="mf-dialog-icon" id="mf-dialog-icon-el"><i id="mf-dialog-icon-i"></i></div>
        </div>
        <div class="mf-dialog-body">
          <div class="mf-dialog-title" id="mf-dialog-title"></div>
          <div class="mf-dialog-desc" id="mf-dialog-desc"></div>
        </div>
        <div class="mf-dialog-footer">
          <button class="mf-btn-cancel" id="mf-btn-cancel">Hủy bỏ</button>
          <button class="mf-btn-confirm" id="mf-btn-confirm"><i id="mf-confirm-icon"></i><span id="mf-confirm-text">Xác nhận</span></button>
        </div>
      </div>
    </div>`;
  document.body.insertAdjacentHTML('beforeend', html);
}

// Ensure toast container exists
function _ensureToastDOM() {
  if (document.getElementById('mf-toast-container')) return;
  const el = document.createElement('div');
  el.id = 'mf-toast-container';
  document.body.appendChild(el);
}

/**
 * Beautiful Confirm Dialog – replaces native confirm()
 * @param {Object} opts
 *   title    {string}  - Dialog headline
 *   desc     {string}  - Supporting description
 *   type     {string}  - 'danger' | 'warning' | 'info' | 'success'
 *   confirmText {string} - Text on confirm button
 *   confirmIcon {string} - FA icon class e.g. 'fa-trash-can'
 *   cancelText  {string} - Text on cancel button
 * @returns {Promise<boolean>}
 */
function mfConfirm({ title = 'Xác nhận hành động', desc = '', type = 'danger', confirmText = 'Xác nhận', confirmIcon = 'fa-check', cancelText = 'Hủy bỏ' } = {}) {
  _ensureDialogDOM();

  const icons = { danger: 'fa-triangle-exclamation', warning: 'fa-circle-exclamation', info: 'fa-circle-info', success: 'fa-circle-check' };
  const overlay    = document.getElementById('mf-dialog-overlay');
  const iconWrap   = document.getElementById('mf-dialog-icon-el');
  const iconI      = document.getElementById('mf-dialog-icon-i');
  const titleEl    = document.getElementById('mf-dialog-title');
  const descEl     = document.getElementById('mf-dialog-desc');
  const btnCancel  = document.getElementById('mf-btn-cancel');
  const btnConfirm = document.getElementById('mf-btn-confirm');
  const confirmI   = document.getElementById('mf-confirm-icon');
  const confirmSpan= document.getElementById('mf-confirm-text');

  // Reset
  iconWrap.className = `mf-dialog-icon ${type}`;
  iconI.className    = `fa-solid ${icons[type] || 'fa-triangle-exclamation'}`;
  titleEl.textContent = title;
  descEl.innerHTML    = desc;
  btnCancel.textContent = cancelText;
  confirmI.className  = `fa-solid ${confirmIcon}`;
  confirmSpan.textContent = confirmText;
  btnConfirm.className = `mf-btn-confirm ${type}`;

  overlay.classList.add('active');

  return new Promise((resolve) => {
    function cleanup() {
      overlay.classList.remove('active');
      btnConfirm.removeEventListener('click', onConfirm);
      btnCancel.removeEventListener('click', onCancel);
      overlay.removeEventListener('click', onOverlay);
    }
    function onConfirm() { cleanup(); resolve(true); }
    function onCancel()  { cleanup(); resolve(false); }
    function onOverlay(e) { if (e.target === overlay) { cleanup(); resolve(false); } }

    btnConfirm.addEventListener('click', onConfirm);
    btnCancel.addEventListener('click', onCancel);
    overlay.addEventListener('click', onOverlay);
  });
}

/**
 * Beautiful Toast Notification – replaces native alert()
 * @param {string} title
 * @param {string} desc
 * @param {string} type - 'success' | 'error' | 'warning' | 'info'
 * @param {number} duration - ms before auto-dismiss (default 4000)
 */
function mfToast(title, desc = '', type = 'success', duration = 4000) {
  _ensureToastDOM();
  const container = document.getElementById('mf-toast-container');

  const icons = { success: 'fa-circle-check', error: 'fa-circle-xmark', warning: 'fa-triangle-exclamation', info: 'fa-circle-info' };
  const toast = document.createElement('div');
  toast.className = `mf-toast ${type}`;
  toast.innerHTML = `
    <div class="mf-toast-icon"><i class="fa-solid ${icons[type] || 'fa-circle-info'}"></i></div>
    <div class="mf-toast-content">
      <div class="mf-toast-title">${title}</div>
      ${desc ? `<div class="mf-toast-desc">${desc}</div>` : ''}
    </div>
    <button class="mf-toast-close" onclick="this.closest('.mf-toast').remove()"><i class="fa-solid fa-xmark"></i></button>`;

  container.appendChild(toast);
  requestAnimationFrame(() => requestAnimationFrame(() => toast.classList.add('show')));

  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 400);
  }, duration);
}
