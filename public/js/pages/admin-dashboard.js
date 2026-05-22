document.addEventListener('DOMContentLoaded', () => {

    const isAdmin = document.body.dataset.isAdmin === '1';

    const STATUS_MAP = {
        active: { label: 'Active', cls: 'passed' },
        pending: { label: 'Pending', cls: 'pending' },
        draft: { label: 'Draft', cls: 'in-progress' },
        repealed: { label: 'Repealed', cls: 'rejected' },
        amended: { label: 'Amended', cls: 'in-progress' },
    };

    function statusDisplay(status) {
        return STATUS_MAP[status?.toLowerCase()] ?? { label: status ?? 'Unknown', cls: 'pending' };
    }

    function renderAdminCard(row) {
        const sd = statusDisplay(row.status);
        const missing = [];
        if (!row.summary) missing.push('Summary');
        if (!row.pdf_file) missing.push('PDF');

        const complete = missing.length === 0;

        const editLink = isAdmin
            ? `<li role="none"><a href="/edit-ordinance?id=${row.ordinance_id}" class="admin-dropdown-item" role="menuitem">✏️ Edit Record</a></li>`
            : '';

        const completenessHtml = complete
            ? `<span class="admin-completeness-pill admin-completeness-pill--ok">✓ Complete</span>`
            : `<span class="admin-completeness-pill admin-completeness-pill--warn">⚠ Incomplete</span>
               ${missing.map(f => `<span class="admin-missing-field-tag">${escapeHtml(f)} missing</span>`).join('')}`;

        return `
            <article class="admin-content-card${!complete ? ' admin-content-card--incomplete' : ''} animate-in" data-id="${row.ordinance_id}">
                <div class="admin-card-topbar">
                    <label class="admin-checkbox-wrapper">
                        <input type="checkbox" class="admin-checkbox admin-card-checkbox" data-id="${row.ordinance_id}" />
                    </label>
                    <span class="status-badge ${sd.cls} admin-card-status-badge">${escapeHtml(sd.label)}</span>
                    <div class="admin-card-action-menu">
                        <button class="admin-card-action-trigger" type="button" aria-haspopup="true" aria-expanded="false">⋯</button>
                        <ul class="admin-card-action-dropdown" role="menu">
                            <li role="none"><a href="/ordinance?id=${row.ordinance_id}" class="admin-dropdown-item" role="menuitem" target="_blank">👁 View Public Page</a></li>
                            ${editLink}
                            <li role="none"><button class="admin-dropdown-item admin-dropdown-item--status-trigger" type="button" role="menuitem" data-id="${row.ordinance_id}">🔄 Change Status</button></li>
                            <li role="separator" class="admin-dropdown-divider" aria-hidden="true"></li>
                            <li role="none"><button class="admin-dropdown-item admin-dropdown-item--danger admin-archive-trigger" type="button" role="menuitem" data-id="${row.ordinance_id}" data-number="${escapeHtml(row.ordinance_number ?? '')}">🗂 Archive Record</button></li>
                        </ul>
                    </div>
                </div>
                <div class="admin-card-identity">
                    <div class="card-label-group">
                        <span class="card-type">City Ordinance</span>
                        <span class="numeral-and-series admin-card-numeral">No. ${escapeHtml(row.ordinance_number ?? '')} s. ${escapeHtml(String(row.series_year ?? ''))}</span>
                    </div>
                </div>
                <div class="preview-container"><div class="preview-text admin-card-preview">${escapeHtml(row.title ?? '')}</div></div>
                <div class="admin-card-meta-row">
                    <span class="admin-card-meta-item"><span class="admin-card-meta-icon" aria-hidden="true">🖊</span>${escapeHtml(row.author_sponsor ?? '')}</span>
                    <span class="admin-card-meta-item"><span class="admin-card-meta-icon" aria-hidden="true">🏷</span>${escapeHtml(row.category_name ?? '')}</span>
                </div>
                <div class="admin-card-completeness">${completenessHtml}</div>
            </article>`;
    }

    // --- Bulk Action Tools ---
    const toolbar = document.getElementById('admin-bulk-toolbar');
    const selectAllCb = document.getElementById('select-all-ordinances');
    const bulkCount = document.getElementById('bulk-selected-count');

    function updateBulkToolbar() {
        const all = [...document.querySelectorAll('.admin-card-checkbox')];
        const selected = all.filter(cb => cb.checked);
        const count = selected.length;

        bulkCount.textContent = `${count} selected`;
        toolbar.classList.toggle('is-active', count > 0);
        toolbar.setAttribute('aria-hidden', count === 0 ? 'true' : 'false');

        document.getElementById('bulk-archive-btn').disabled = count === 0;
        document.getElementById('bulk-apply-status').disabled = (count === 0 || !document.getElementById('bulk-status-select').value);

        selectAllCb.indeterminate = (count > 0 && count < all.length);
        selectAllCb.checked = (count === all.length && all.length > 0);
    }

    // Connect bulk UI
    selectAllCb?.addEventListener('change', () => {
        document.querySelectorAll('.admin-card-checkbox').forEach(cb => {
            cb.checked = selectAllCb.checked;
            cb.closest('.admin-content-card')?.classList.toggle('admin-content-card--selected', selectAllCb.checked);
        });
        updateBulkToolbar();
    });

    document.getElementById('bulk-status-select')?.addEventListener('change', updateBulkToolbar);
    document.getElementById('bulk-clear-btn')?.addEventListener('click', () => {
        selectAllCb.checked = false;
        selectAllCb.dispatchEvent(new Event('change'));
    });

    // Row selection toggle delegation
    document.getElementById('results-grid')?.addEventListener('change', (e) => {
        if (e.target.matches('.admin-card-checkbox')) {
            e.target.closest('.admin-content-card')?.classList.toggle('admin-content-card--selected', e.target.checked);
            updateBulkToolbar();
        }
    });

    // --- Dropdowns ---
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('.admin-card-action-trigger');
        if (trigger) {
            const dropdown = trigger.nextElementSibling;
            const isOpen = dropdown.classList.contains('is-open');
            document.querySelectorAll('.admin-card-action-dropdown.is-open').forEach(dd => {
                dd.classList.remove('is-open');
                dd.previousElementSibling?.setAttribute('aria-expanded', 'false');
            });
            dropdown.classList.toggle('is-open', !isOpen);
            trigger.setAttribute('aria-expanded', String(!isOpen));
            return;
        }
        if (!e.target.closest('.admin-card-action-menu')) {
            document.querySelectorAll('.admin-card-action-dropdown').forEach(dd => dd.classList.remove('is-open'));
        }
    });

    // --- Archive Modal ---
    const archiveModal = document.getElementById('admin-archive-modal');
    function closeArchiveModal() { archiveModal.classList.remove('active'); }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.admin-archive-trigger');
        if (btn) {
            document.getElementById('archive-modal-target').textContent = `City Ordinance No. ${btn.dataset.number}`;
            document.getElementById('archive-modal-confirm').dataset.id = btn.dataset.id;
            archiveModal.classList.add('active');
            document.querySelectorAll('.admin-card-action-dropdown').forEach(dd => dd.classList.remove('is-open'));
        }
    });

    document.getElementById('archive-modal-close')?.addEventListener('click', closeArchiveModal);
    document.getElementById('archive-modal-cancel')?.addEventListener('click', closeArchiveModal);
    document.getElementById('admin-refresh-btn')?.addEventListener('click', () => SearchEngine.reload());

    // Initialize Engine
    SearchEngine.init({
        renderCard: renderAdminCard,
        onComplete: () => {
            // Reset bulk toolbar when fresh records load
            if (selectAllCb) {
                selectAllCb.checked = false;
                selectAllCb.indeterminate = false;
                updateBulkToolbar();
            }
        }
    });
});