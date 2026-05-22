// ── Status display map (mirrors PHP $status_map) ──────────────────────────
const STATUS_MAP = {
    active: { label: 'Active', cls: 'passed' },
    pending: { label: 'Pending', cls: 'pending' },
    draft: { label: 'Draft', cls: 'in-progress' },
    repealed: { label: 'Repealed', cls: 'rejected' },
    amended: { label: 'Amended', cls: 'in-progress' },
};

const form = document.getElementById('search-form');
const searchBar = document.getElementById('search-input');
const grid = document.getElementById('admin-content-grid');
// const recordCountEl = document.getElementById('admin-record-count');

// Read admin flag injected by PHP onto <body data-is-admin="1|0">
const isAdmin = document.body.dataset.isAdmin === '1';

// ── Prevent native form submission ────────────────────────────────────────
form.addEventListener('submit', (e) => e.preventDefault());

// ── Helpers ───────────────────────────────────────────────────────────────
function statusDisplay(status) {
    return STATUS_MAP[status?.toLowerCase()] ?? { label: status ?? 'Unknown', cls: 'pending' };
}

function getMissingFields(row) {
    const missing = [];
    if (!row.summary) missing.push('Summary');
    if (!row.pdf_file) missing.push('PDF');
    return missing;
}

// ── Card renderer (mirrors the PHP foreach template) ──────────────────────
function renderCard(row) {
    const sd = statusDisplay(row.status);
    const missing = getMissingFields(row);
    const complete = missing.length === 0;

    const editLink = isAdmin
        ? `<li role="none">
               <a href="/edit-ordinance?id=${row.ordinance_id}"
                  class="admin-dropdown-item" role="menuitem">
                   ✏️ Edit Record
               </a>
           </li>`
        : '';

    const completenessHtml = complete
        ? `<span class="admin-completeness-pill admin-completeness-pill--ok">✓ Record Complete</span>`
        : `<span class="admin-completeness-pill admin-completeness-pill--warn">⚠ Incomplete</span>
           ${missing.map(f => `<span class="admin-missing-field-tag">${escapeHtml(f)} missing</span>`).join('')}`;

    return `
        <article
            class="admin-content-card${!complete ? ' admin-content-card--incomplete' : ''}"
            id="admin-card-${row.ordinance_id}"
            data-id="${row.ordinance_id}"
            data-status="${escapeHtml(row.status ?? '')}"
            aria-label="Ordinance No. ${escapeHtml(row.ordinance_number ?? '')}">

            <!-- ── Top bar ── -->
            <div class="admin-card-topbar">
                <label class="admin-checkbox-wrapper"
                       for="card-cb-${row.ordinance_id}"
                       aria-label="Select ordinance ${escapeHtml(row.ordinance_number ?? '')}">
                    <input type="checkbox"
                           id="card-cb-${row.ordinance_id}"
                           class="admin-checkbox admin-card-checkbox"
                           data-id="${row.ordinance_id}"
                           aria-label="Select this record" />
                </label>

                <span class="status-badge ${sd.cls} admin-card-status-badge">
                    ${escapeHtml(sd.label)}
                </span>

                <div class="admin-card-action-menu" data-id="${row.ordinance_id}">
                    <button class="admin-card-action-trigger" type="button"
                            aria-haspopup="true" aria-expanded="false"
                            aria-label="Actions for ordinance ${escapeHtml(row.ordinance_number ?? '')}">
                        ⋯
                    </button>
                    <ul class="admin-card-action-dropdown" role="menu" aria-label="Card actions">
                        <li role="none">
                            <a href="/ordinance?id=${row.ordinance_id}"
                               class="admin-dropdown-item" role="menuitem" target="_blank">
                                👁 View Public Page
                            </a>
                        </li>
                        ${editLink}
                        <li role="none">
                            <button class="admin-dropdown-item admin-dropdown-item--status-trigger"
                                    type="button" role="menuitem"
                                    data-id="${row.ordinance_id}">
                                🔄 Change Status
                            </button>
                        </li>
                        <li role="separator" class="admin-dropdown-divider" aria-hidden="true"></li>
                        <li role="none">
                            <button class="admin-dropdown-item admin-dropdown-item--danger admin-archive-trigger"
                                    type="button" role="menuitem"
                                    data-id="${row.ordinance_id}"
                                    data-number="${escapeHtml(row.ordinance_number ?? '')}">
                                🗂 Archive Record
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- ── Identity ── -->
            <div class="admin-card-identity">
                <div class="card-label-group">
                    <span class="card-type">City Ordinance</span>
                    <span class="numeral-and-series admin-card-numeral">
                        No. ${escapeHtml(row.ordinance_number ?? '')}
                        s. ${escapeHtml(String(row.series_year ?? ''))}
                    </span>
                </div>
                <div class="date">
                    <span class="date-day">${escapeHtml(String(row.enactment_day ?? ''))}</span>
                    <div class="date-meta">
                        <span class="date-month">${escapeHtml(row.enactment_month ?? '')}</span>
                        <span class="date-year">${escapeHtml(String(row.enactment_year ?? ''))}</span>
                    </div>
                </div>
            </div>

            <!-- ── Title ── -->
            <div class="preview-container">
                <div class="preview-text admin-card-preview">
                    ${escapeHtml(row.title ?? '')}
                </div>
            </div>

            <!-- ── Metadata row ── -->
            <div class="admin-card-meta-row">
                <span class="admin-card-meta-item" title="Sponsor">
                    <span class="admin-card-meta-icon" aria-hidden="true">🖊</span>
                    ${escapeHtml(row.author_sponsor ?? '')}
                </span>
                <span class="admin-card-meta-item" title="Category">
                    <span class="admin-card-meta-icon" aria-hidden="true">🏷</span>
                    ${escapeHtml(row.category_name ?? '')}
                </span>
            </div>

            <!-- ── Completeness ── -->
            <div class="admin-card-completeness" aria-label="Record completeness">
                ${completenessHtml}
            </div>

            <!-- ── Footer ── -->
            <div class="card-footer admin-card-footer">
                <div class="card-engagement">
                    <span class="engagement-item likes">
                        <span class="engagement-icon">▲</span>
                        <span class="engagement-count">${row.like_count ?? 0}</span>
                    </span>
                    <span class="engagement-divider"></span>
                    <span class="engagement-item dislikes">
                        <span class="engagement-icon">▼</span>
                        <span class="engagement-count">${row.dislike_count ?? 0}</span>
                    </span>
                </div>
                <a href="/ordinance?id=${row.ordinance_id}">
                    <p class="link">Read More</p>
                </a>
            </div>

        </article>`;
}

// ── Grid renderer ─────────────────────────────────────────────────────────
function renderGrid(records) {
    if (!grid) return;

    if (!records || records.length === 0) {
        grid.innerHTML = `
            <div class="ord-comments-empty" role="status">
                <p>No ordinance records found. Try adjusting your search or filters.</p>
            </div>`;
    } else {
        grid.innerHTML = records.map(renderCard).join('');
    }

    if (recordCountEl) {
        const n = records?.length ?? 0;
        recordCountEl.textContent = `${n} record${n !== 1 ? 's' : ''}`;
    }
}

// ── Fetch from API ────────────────────────────────────────────────────────
// Expects the backend to return { ok, message, data: { records: [...] } }
// when the request carries Accept: application/json.
async function fetchOrdinances(query = '') {
    grid.innerHTML = `<div class="ord-comments-empty" role="status"><p>Loading records…</p></div>`;

    const url = new URL('/admin/ordinances', window.location.origin);
    if (query.length > 0) url.searchParams.set('q', query);

    try {
        const res = await fetch(url.toString(), {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
        });
        const payload = await res.json();

        if (payload.ok) {
            renderGrid(payload.data.records);
        } else {
            renderGrid([]);
            showToast(payload.message ?? 'Failed to load ordinances.');
        }
    } catch (networkError) {
        renderGrid([]);
        showToast('Network error. Please check your connection.');
    }
}

// ── Search bar: re-fetch on every input change ────────────────────────────
searchBar.addEventListener('search', function () {
    if (this.value.trim() != '') {
        fetchOrdinances(this.value.trim());
    }
});

// ── Initial page load ─────────────────────────────────────────────────────
fetchOrdinances();

// ── Toast helper ──────────────────────────────────────────────────────────
function showToast(message) {
    const t = document.createElement('div');
    t.className = 'reaction-toast';
    t.textContent = message;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}