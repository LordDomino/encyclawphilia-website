<?php

namespace App\Views\pages;

use App\Models\OrdinanceModel;

session_start();

// ── Guard: administrators only ────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

// Role IDs per seed_data: 1 = Administrator, 2 = Moderator
$adminRoles = [1];
if (!in_array((int)($_SESSION['role_id'] ?? 0), $adminRoles, true)) {
    header('Location: home');
    exit;
}

// ── Page meta ─────────────────────────────────────────────────────────────────
$pageTitle   = "Admin Dashboard | EncycLawPhilia Valenzuela";
$currentPage = "admin_dashboard";

$sessionUsername = htmlspecialchars($_SESSION['username'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');
$sessionRoleId   = (int)($_SESSION['role_id'] ?? 1);
$_SESSION['is_admin'] = ($sessionRoleId === 1);

// Mock stats
$stats = [
    'total_ordinances' => 148,
    'pending'          => 12,
    'active'           => 109,
    'repealed'         => 27,
    'total_users'      => 543,
    'total_comments'   => 1204,
];

require_once __DIR__ . '/../../Models/procedures.php';

// Status map — mirrors ordinance.php
$status_map = [
    'active'   => ['label' => 'Active',   'class' => 'passed'],
    'pending'  => ['label' => 'Pending',  'class' => 'pending'],
    'draft'    => ['label' => 'Draft',    'class' => 'in-progress'],
    'repealed' => ['label' => 'Repealed', 'class' => 'rejected'],
    'amended'  => ['label' => 'Amended',  'class' => 'in-progress'],
];

require_once VIEWS_ROOT . '/head.php';
?>

<!-- <body> — add data-is-admin so admin-search.js can read it -->

<body class="<?php echo htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8'); ?>"
    data-is-admin="<?php echo $_SESSION['is_admin'] ? '1' : '0'; ?>">

    <?php require_once VIEWS_ROOT . '/header.php'; ?>
    <!-- ================================================================
         SUBHERO
    ================================================================ -->
    <div class="subhero admin-subhero">
        <div class="subhero-content">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="/home">Home</a>
                <span class="separator" aria-hidden="true">›</span>
                <span aria-current="page">Admin Dashboard</span>
            </nav>
            <div class="admin-subhero-meta">
                <div>
                    <h1>Admin Dashboard</h1>
                    <p class="admin-subhero-greeting">
                        Welcome back, <strong><?php echo $sessionUsername; ?></strong>
                        — <?php echo $_SESSION['is_admin'] ? 'Super Administrator' : 'Moderator'; ?>
                    </p>
                </div>
                <?php if ($_SESSION['is_admin']): ?>
                    <span class="status-badge passed status-badge--hero admin-role-indicator">
                        Super Admin
                    </span>
                <?php else: ?>
                    <span class="status-badge in-progress status-badge--hero admin-role-indicator">
                        Moderator
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ================================================================
         STATS OVERVIEW ROW
    ================================================================ -->
    <div class="admin-stats-band" aria-label="Platform overview statistics">
        <div class="admin-stats-inner">

            <div class="admin-stat-card">
                <span class="admin-stat-icon" aria-hidden="true">📋</span>
                <div class="admin-stat-body">
                    <span class="admin-stat-value"><?php echo number_format($stats['total_ordinances']); ?></span>
                    <span class="admin-stat-label">Total Ordinances</span>
                </div>
            </div>

            <div class="admin-stat-card admin-stat-card--warn">
                <span class="admin-stat-icon" aria-hidden="true">⏳</span>
                <div class="admin-stat-body">
                    <span class="admin-stat-value"><?php echo number_format($stats['pending']); ?></span>
                    <span class="admin-stat-label">Pending Review</span>
                </div>
            </div>

            <div class="admin-stat-card admin-stat-card--ok">
                <span class="admin-stat-icon" aria-hidden="true">✅</span>
                <div class="admin-stat-body">
                    <span class="admin-stat-value"><?php echo number_format($stats['active']); ?></span>
                    <span class="admin-stat-label">Active</span>
                </div>
            </div>

            <div class="admin-stat-card admin-stat-card--muted">
                <span class="admin-stat-icon" aria-hidden="true">🗂️</span>
                <div class="admin-stat-body">
                    <span class="admin-stat-value"><?php echo number_format($stats['repealed']); ?></span>
                    <span class="admin-stat-label">Repealed</span>
                </div>
            </div>

            <div class="admin-stat-card">
                <span class="admin-stat-icon" aria-hidden="true">👥</span>
                <div class="admin-stat-body">
                    <span class="admin-stat-value"><?php echo number_format($stats['total_users']); ?></span>
                    <span class="admin-stat-label">Registered Users</span>
                </div>
            </div>

            <div class="admin-stat-card">
                <span class="admin-stat-icon" aria-hidden="true">💬</span>
                <div class="admin-stat-body">
                    <span class="admin-stat-value"><?php echo number_format($stats['total_comments']); ?></span>
                    <span class="admin-stat-label">Comments</span>
                </div>
            </div>

        </div>
    </div>

    <main class="admin-dashboard-page">
        <!-- ================================================================
             SECTION HEADER
        ================================================================ -->
        <div class="admin-section-header">
            <div class="admin-section-title-group">
                <h2 class="admin-section-title">Ordinance Records</h2>
                <!-- Section count: JS will overwrite this once cards load -->
                <span class="admin-section-count" id="admin-record-count">
                    Loading…
                </span>
            </div>
            <div class="admin-section-actions">
                <?php if ($_SESSION['is_admin']): ?>
                    <a href="/add-ordinance" class="admin-btn admin-btn--primary">
                        + Add Ordinance
                    </a>
                <?php endif; ?>
                <button class="admin-btn admin-btn--ghost" id="admin-refresh-btn" type="button"
                    aria-label="Refresh records">
                    ↻ Refresh
                </button>
            </div>
        </div>

        <!-- ================================================================
             FILTER & SEARCH BAR
        ================================================================ -->
        <div class="admin-controls-row" role="search">

            <form class="search-bar mini admin-search-form" action="/admin/ordinances" method="GET"
                aria-label="Search ordinances" id="search-form">
                <input
                    type="search"
                    name="q"
                    id="search-input"
                    placeholder="Search by title or ordinance number..."
                    autocomplete="off"
                    inputmode="search"
                    aria-label="Search ordinances" />
            </form>

            <div class="admin-filter-row" role="group" aria-label="Filter controls">

                <div class="admin-filter-group">
                    <label for="filter-status" class="admin-filter-label">Status</label>
                    <select id="filter-status" class="admin-filter-select" aria-label="Filter by status">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="active">Active</option>
                        <option value="repealed">Repealed</option>
                        <option value="amended">Amended</option>
                    </select>
                </div>

                <div class="admin-filter-group">
                    <label for="filter-category" class="admin-filter-label">Category</label>
                    <select id="filter-category" class="admin-filter-select" aria-label="Filter by category">
                        <option value="">All Categories</option>
                        <option value="traffic">Traffic &amp; Transportation</option>
                        <option value="health">Health &amp; Sanitation</option>
                        <option value="safety">Public Safety</option>
                        <option value="environment">Environment &amp; Zoning</option>
                        <option value="education">Education</option>
                    </select>
                </div>

                <div class="admin-filter-group">
                    <label for="filter-year" class="admin-filter-label">Series Year</label>
                    <select id="filter-year" class="admin-filter-select" aria-label="Filter by year">
                        <option value="">All Years</option>
                        <option value="2026">2026</option>
                        <option value="2025">2025</option>
                        <option value="2024">2024</option>
                    </select>
                </div>

                <div class="admin-filter-group">
                    <label for="filter-completeness" class="admin-filter-label">Record State</label>
                    <select id="filter-completeness" class="admin-filter-select" aria-label="Filter by record completeness">
                        <option value="">All Records</option>
                        <option value="incomplete">Incomplete</option>
                        <option value="complete">Complete</option>
                    </select>
                </div>

            </div>

        </div>

        <!-- ================================================================
             BULK ACTION TOOLBAR (shown only when cards are selected)
        ================================================================ -->
        <div class="admin-bulk-toolbar" id="admin-bulk-toolbar" role="toolbar"
            aria-label="Bulk actions" aria-hidden="true">

            <div class="admin-bulk-toolbar-left">
                <label class="admin-checkbox-wrapper admin-select-all-wrap" for="select-all-ordinances">
                    <input
                        type="checkbox"
                        id="select-all-ordinances"
                        class="admin-checkbox"
                        aria-label="Select all visible records" />
                    <span class="admin-checkbox-label">Select All</span>
                </label>
                <span class="admin-bulk-count" id="bulk-selected-count" aria-live="polite">
                    0 selected
                </span>
            </div>

            <div class="admin-bulk-toolbar-actions">

                <div class="admin-bulk-action-group">
                    <label for="bulk-status-select" class="admin-filter-label">
                        Change Status
                    </label>
                    <select id="bulk-status-select" class="admin-filter-select"
                        aria-label="Change status of selected records">
                        <option value="">— Pick status —</option>
                        <option value="active">Set Active</option>
                        <option value="pending">Set Pending</option>
                        <option value="repealed">Set Repealed</option>
                        <option value="amended">Set Amended</option>
                    </select>
                </div>

                <button class="admin-btn admin-btn--action" id="bulk-apply-status"
                    type="button" disabled aria-label="Apply status change to selected records">
                    Apply
                </button>

                <span class="admin-bulk-divider" aria-hidden="true"></span>

                <button class="admin-btn admin-btn--danger" id="bulk-archive-btn"
                    type="button" disabled aria-label="Archive selected records">
                    🗂 Archive Selected
                </button>

                <button class="admin-btn admin-btn--ghost admin-bulk-clear"
                    id="bulk-clear-btn" type="button" aria-label="Clear selection">
                    ✕ Clear
                </button>

            </div>

        </div>

        <!-- ================================================================
             ORDINANCE MODERATION GRID
        ================================================================ -->
        <section class="admin-grid-section" aria-labelledby="admin-section-title">
            <div class="admin-content-grid" id="admin-content-grid">
                <!-- Populated by admin-search.js -->
            </div>
        </section>

        <!-- ================================================================
             ARCHIVE CONFIRMATION MODAL
             Reuses .auth-modal pattern from account.php
        ================================================================ -->
        <div
            class="auth-modal-backdrop"
            id="admin-archive-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="archive-modal-title"
            aria-describedby="archive-modal-desc">

            <div class="auth-modal">
                <div class="auth-modal--header">
                    <div class="modal-icon" aria-hidden="true">🗂</div>
                    <span class="modal-title" id="archive-modal-title">Archive Ordinance</span>
                    <button
                        class="modal-close"
                        type="button"
                        id="archive-modal-close"
                        aria-label="Close">✕</button>
                </div>
                <div class="auth-modal--body">
                    <p id="archive-modal-desc">
                        You are about to archive
                        <strong id="archive-modal-target">this ordinance</strong>.
                        The record will no longer be publicly visible but will
                        remain permanently in the database per platform policy.
                    </p>
                    <p class="modal-sub">
                        All associated reactions and comments will be retained
                        and will reappear if the record is ever restored by a
                        database administrator.
                    </p>
                    <div class="auth-modal--actions">
                        <button
                            class="btn-modal-dismiss"
                            type="button"
                            id="archive-modal-cancel">
                            Cancel
                        </button>
                        <button
                            class="btn-modal-retry admin-confirm-archive"
                            type="button"
                            id="archive-modal-confirm"
                            data-id="">
                            Archive Record
                        </button>
                    </div>
                </div>
            </div>

        </div><!-- /#admin-archive-modal -->

    </main><!-- /.admin-dashboard-page -->

    <?php require_once __DIR__ . '/../footer.php'; ?>

    <!-- ================================================================
         ADMIN DASHBOARD SCRIPTS
    ================================================================ -->
    <script>
        // ============================================================
        // BULK SELECTION STATE MACHINE
        // ============================================================
        const toolbar = document.getElementById('admin-bulk-toolbar');
        const selectAllCb = document.getElementById('select-all-ordinances');
        const bulkCount = document.getElementById('bulk-selected-count');
        const bulkApplyBtn = document.getElementById('bulk-apply-status');
        const bulkArchiveBtn = document.getElementById('bulk-archive-btn');
        const bulkClearBtn = document.getElementById('bulk-clear-btn');

        function getCardCheckboxes() {
            return [...document.querySelectorAll('.admin-card-checkbox')];
        }

        function updateBulkToolbar() {
            const all = getCardCheckboxes();
            const selected = all.filter(cb => cb.checked);
            const count = selected.length;

            bulkCount.textContent = `${count} selected`;

            if (count > 0) {
                toolbar.classList.add('is-active');
                toolbar.setAttribute('aria-hidden', 'false');
                bulkArchiveBtn.disabled = false;
            } else {
                toolbar.classList.remove('is-active');
                toolbar.setAttribute('aria-hidden', 'true');
                bulkArchiveBtn.disabled = true;
            }

            const statusPick = document.getElementById('bulk-status-select');
            bulkApplyBtn.disabled = (count === 0 || !statusPick.value);

            selectAllCb.indeterminate = (count > 0 && count < all.length);
            selectAllCb.checked = (count === all.length && all.length > 0);
        }

        selectAllCb.addEventListener('change', () => {
            getCardCheckboxes().forEach(cb => {
                cb.checked = selectAllCb.checked;
                cb.closest('.admin-content-card')
                    ?.classList.toggle('admin-content-card--selected', selectAllCb.checked);
            });
            updateBulkToolbar();
        });

        document.getElementById('bulk-status-select')
            .addEventListener('change', updateBulkToolbar);

        bulkClearBtn.addEventListener('click', () => {
            getCardCheckboxes().forEach(cb => {
                cb.checked = false;
                cb.closest('.admin-content-card')
                    ?.classList.remove('admin-content-card--selected');
            });
            selectAllCb.checked = false;
            selectAllCb.indeterminate = false;
            updateBulkToolbar();
        });

        bulkApplyBtn.addEventListener('click', () => {
            const ids = getCardCheckboxes().filter(cb => cb.checked).map(cb => cb.dataset.id);
            const status = document.getElementById('bulk-status-select').value;
            // TODO: POST /admin/ordinances/bulk-status { ids, status }
            console.log('[TODO] Bulk status →', status, 'IDs:', ids);
        });

        bulkArchiveBtn.addEventListener('click', () => {
            const ids = getCardCheckboxes().filter(cb => cb.checked).map(cb => cb.dataset.id);
            // Surface the modal with a generic multi-record label
            openArchiveModal(ids.join(', '), `${ids.length} selected record(s)`);
        });


        // ============================================================
        // PER-CARD CHECKBOX → SELECTION STATE  (Batch 3)
        // ============================================================
        document.getElementById('admin-content-grid')
            ?.addEventListener('change', (e) => {
                if (!e.target.matches('.admin-card-checkbox')) return;
                e.target
                    .closest('.admin-content-card')
                    ?.classList.toggle('admin-content-card--selected', e.target.checked);
                updateBulkToolbar();
            });


        // ============================================================
        // PER-CARD ACTION DROPDOWN  (Batch 3)
        // ============================================================
        function closeAllDropdowns(except = null) {
            document.querySelectorAll('.admin-card-action-dropdown').forEach(dd => {
                if (dd === except) return;
                dd.classList.remove('is-open');
                dd.previousElementSibling?.setAttribute('aria-expanded', 'false');
            });
        }

        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('.admin-card-action-trigger');
            if (trigger) {
                const dropdown = trigger.nextElementSibling;
                const isOpen = dropdown.classList.contains('is-open');
                closeAllDropdowns();
                dropdown.classList.toggle('is-open', !isOpen);
                trigger.setAttribute('aria-expanded', String(!isOpen));
                return;
            }
            if (!e.target.closest('.admin-card-action-menu')) {
                closeAllDropdowns();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeAllDropdowns();
                closeArchiveModal();
            }
        });


        // ============================================================
        // ARCHIVE MODAL
        // ============================================================
        const archiveModal = document.getElementById('admin-archive-modal');
        const archiveModalTarget = document.getElementById('archive-modal-target');
        const archiveConfirmBtn = document.getElementById('archive-modal-confirm');
        const archiveCloseBtn = document.getElementById('archive-modal-close');
        const archiveCancelBtn = document.getElementById('archive-modal-cancel');

        function openArchiveModal(id, label) {
            archiveModalTarget.textContent = label;
            archiveConfirmBtn.dataset.id = id;
            archiveModal.classList.add('active');
            archiveCloseBtn.focus();
        }

        function closeArchiveModal() {
            archiveModal.classList.remove('active');
        }

        // Single-card trigger (from per-card dropdown)
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.admin-archive-trigger');
            if (!btn) return;
            closeAllDropdowns();
            const id = btn.dataset.id;
            const number = btn.dataset.number;
            openArchiveModal(id, `City Ordinance No. ${number}`);
        });

        archiveCloseBtn.addEventListener('click', closeArchiveModal);
        archiveCancelBtn.addEventListener('click', closeArchiveModal);

        archiveModal.addEventListener('click', (e) => {
            if (e.target === archiveModal) closeArchiveModal();
        });

        archiveConfirmBtn.addEventListener('click', () => {
            const id = archiveConfirmBtn.dataset.id;
            // TODO: POST /admin/ordinances/archive { id }
            console.log('[TODO] Archive ordinance id(s):', id);
            closeArchiveModal();
        });


        // ============================================================
        // FILTER CONTROLS — client-side card filtering  (Batch 3)
        // ============================================================
        const filterStatus = document.getElementById('filter-status');
        const filterCategory = document.getElementById('filter-category');
        const filterYear = document.getElementById('filter-year');
        const filterCompleteness = document.getElementById('filter-completeness');
        const recordCountEl = document.getElementById('admin-record-count');
        const adminSearchInput = document.getElementById('admin-search-input');

        function applyFilters() {
            const status = filterStatus.value.toLowerCase();
            const year = filterYear.value;
            const completeness = filterCompleteness.value;

            let visible = 0;

            document.querySelectorAll('.admin-content-card').forEach(card => {
                const cardStatus = card.dataset.status?.toLowerCase() ?? '';
                const cardYear = card.querySelector('.date-year')?.textContent?.trim() ?? '';
                const cardIncomplete = card.classList.contains('admin-content-card--incomplete');

                const statusMatch = !status || cardStatus === status;
                const yearMatch = !year || cardYear === year;
                const compMatch = !completeness ||
                    (completeness === 'incomplete' && cardIncomplete) ||
                    (completeness === 'complete' && !cardIncomplete);

                const show = statusMatch && yearMatch && compMatch;
                card.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            if (recordCountEl) {
                recordCountEl.textContent = `${visible} record${visible !== 1 ? 's' : ''}`;
            }

            document.querySelectorAll('.admin-content-card').forEach(card => {
                if (card.style.display === 'none') {
                    const cb = card.querySelector('.admin-card-checkbox');
                    if (cb) cb.checked = false;
                    card.classList.remove('admin-content-card--selected');
                }
            });
            updateBulkToolbar();
        }

        [filterStatus, filterCategory, filterYear, filterCompleteness]
        .forEach(el => el?.addEventListener('change', applyFilters));

        // Live search on the admin search input
        // adminSearchInput?.addEventListener('input', applyFilters);
    </script>

    <script src="js/sanitize.js"></script>
    <script src="js/admin-search.js"></script>
    <script src="js/admin-dashboard.js"></script>

</body>

</html>