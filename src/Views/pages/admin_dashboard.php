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

    <main class="admin-dashboard-page">
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
                        </p>
                    </div>
                </div>

                <!-- KPI strip — values populated by JS; data-kpi used as hook -->
                <div class="admin-kpi-strip" aria-label="Platform overview statistics">

                    <div class="admin-kpi-card" data-kpi="total_ordinances">
                        <span class="admin-kpi-icon" aria-hidden="true">
                            <!-- Document / clipboard outline -->
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" />
                                <rect x="9" y="3" width="6" height="4" rx="1" />
                                <line x1="9" y1="12" x2="15" y2="12" />
                                <line x1="9" y1="16" x2="13" y2="16" />
                            </svg>
                        </span>
                        <div class="admin-kpi-body">
                            <span class="admin-kpi-value" id="kpi-total-ordinances">12</span>
                            <span class="admin-kpi-label">Total Ordinances</span>
                        </div>
                    </div>

                    <span class="admin-kpi-divider" aria-hidden="true"></span>

                    <div class="admin-kpi-card admin-kpi-card--warn" data-kpi="pending">
                        <span class="admin-kpi-icon" aria-hidden="true">
                            <!-- Hourglass outline -->
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 2h14" />
                                <path d="M5 22h14" />
                                <path d="M5 2c0 7 7 8 7 10S5 15 5 22" />
                                <path d="M19 2c0 7-7 8-7 10s7 5 7 10" />
                            </svg>
                        </span>
                        <div class="admin-kpi-body">
                            <span class="admin-kpi-value" id="kpi-pending">0</span>
                            <span class="admin-kpi-label">Pending</span>
                        </div>
                    </div>

                    <span class="admin-kpi-divider" aria-hidden="true"></span>

                    <div class="admin-kpi-card admin-kpi-card--ok" data-kpi="active">
                        <span class="admin-kpi-icon" aria-hidden="true">
                            <!-- Shield / check outline -->
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                <polyline points="9 12 11 14 15 10" />
                            </svg>
                        </span>
                        <div class="admin-kpi-body">
                            <span class="admin-kpi-value" id="kpi-active">9</span>
                            <span class="admin-kpi-label">Active</span>
                        </div>
                    </div>

                    <span class="admin-kpi-divider" aria-hidden="true"></span>

                    <div class="admin-kpi-card admin-kpi-card--muted" data-kpi="repealed">
                        <span class="admin-kpi-icon" aria-hidden="true">
                            <!-- Archive / box outline -->
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="21 8 21 21 3 21 3 8" />
                                <rect x="1" y="3" width="22" height="5" />
                                <line x1="10" y1="12" x2="14" y2="12" />
                            </svg>
                        </span>
                        <div class="admin-kpi-body">
                            <span class="admin-kpi-value" id="kpi-repealed">0</span>
                            <span class="admin-kpi-label">Repealed</span>
                        </div>
                    </div>

                    <span class="admin-kpi-divider" aria-hidden="true"></span>

                    <div class="admin-kpi-card" data-kpi="total_users">
                        <span class="admin-kpi-icon" aria-hidden="true">
                            <!-- Users / people outline -->
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                        </span>
                        <div class="admin-kpi-body">
                            <span class="admin-kpi-value" id="kpi-total-users">248</span>
                            <span class="admin-kpi-label">Registered Users</span>
                        </div>
                    </div>

                    <span class="admin-kpi-divider" aria-hidden="true"></span>

                    <div class="admin-kpi-card" data-kpi="total_comments">
                        <span class="admin-kpi-icon" aria-hidden="true">
                            <!-- Speech bubble / comment outline -->
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                            </svg>
                        </span>
                        <div class="admin-kpi-body">
                            <span class="admin-kpi-value" id="kpi-total-comments">0</span>
                            <span class="admin-kpi-label">Comments</span>
                        </div>
                    </div>

                </div><!-- /.admin-kpi-strip -->

            </div><!-- /.subhero-content -->
        </div><!-- /.subhero.admin-subhero -->
        <!-- ================================================================
             FILTER & SEARCH BAR
        ================================================================ -->
        <div class="columns-split">

            <button class="sidebar-toggle" id="sidebar-toggle" aria-label="Toggle filters">☰ Filters</button>

            <aside class="sidebar-column" id="sidebar-column">
                <div class="sidebar-content">

                    <form class="search-bar mini" id="search-form" action="/api/ordinances/search" method="GET">
                        <input
                            type="search"
                            id="search-input"
                            name="q"
                            placeholder="Search ordinances..."
                            aria-label="Search within results"
                            autocomplete="off"
                            inputmode="search" />
                        <button type="submit" class="search-submit-btn" aria-label="Search">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true">
                                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </form>

                    <?php require_once __DIR__ . '/../partials/filter_sidebar.php' ?>
                </div>
            </aside>
            <section class="results-content" id="admin-results-section">

                <div class="results-header" style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem;">
                    <div class="results-header-left">
                        <h2 class="results-header-title">Ordinance Management</h2>
                        <span class="result-count" id="result-count">Loading...</span>
                    </div>

                    <div class="admin-section-actions" style="display: flex; gap: 0.5rem;">
                        <?php if ($_SESSION['is_admin']): ?>
                            <a href="/add-ordinance" class="admin-btn admin-btn--primary">
                                + Add Ordinance
                            </a>
                        <?php endif; ?>
                        <button class="admin-btn admin-btn--ghost" id="admin-refresh-btn" type="button" aria-label="Refresh records">
                            ↻ Refresh
                        </button>
                    </div>
                </div>

                <nav class="pagination-bar" id="pagination-bar-top" aria-label="Pagination top">
                </nav>


                <!-- Bulk Action Toolbar -->
                <div class="admin-bulk-toolbar" id="admin-bulk-toolbar" role="toolbar" aria-label="Bulk actions" aria-hidden="true">
                    <div class="admin-bulk-toolbar-left">
                        <label class="admin-checkbox-wrapper admin-select-all-wrap" for="select-all-ordinances">
                            <input type="checkbox" id="select-all-ordinances" class="admin-checkbox" aria-label="Select all" />
                            <span class="admin-checkbox-label">Select All</span>
                        </label>
                        <span class="admin-bulk-count" id="bulk-selected-count">0 selected</span>
                    </div>

                    <div class="admin-bulk-toolbar-actions">
                        <div class="admin-bulk-action-group">
                            <select id="bulk-status-select" class="admin-filter-select">
                                <option value="">— Pick status —</option>
                                <option value="active">Set Active</option>
                                <option value="pending">Set Pending</option>
                                <option value="repealed">Set Repealed</option>
                                <option value="amended">Set Amended</option>
                            </select>
                        </div>
                        <button class="admin-btn admin-btn--action" id="bulk-apply-status" type="button" disabled>Apply</button>
                        <span class="admin-bulk-divider" aria-hidden="true"></span>
                        <button class="admin-btn admin-btn--danger" id="bulk-archive-btn" type="button" disabled>🗂 Archive</button>
                        <button class="admin-btn admin-btn--ghost admin-bulk-clear" id="bulk-clear-btn" type="button">✕ Clear</button>
                    </div>
                </div>

                <div class="admin-content-grid" id="results-grid">
                </div>

                <nav class="pagination-bar" id="pagination-bar-bottom" aria-label="Pagination bottom" style="margin-top: 1.5rem;">
                </nav>

            </section>
        </div>

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

    <script src="js/sanitize.js"></script>
    <script src="js/utils/pagination.js"></script>
    <script src="js/components/sidebar.js"></script>
    <script src="js/core/search-engine.js"></script>

    <script src="js/components/filter-categories.js"></script>
    <script src="js/components/filter-barangays.js"></script>
    <script src="js/components/filter-statuses.js"></script>
    <script src="js/pages/admin-dashboard.js"></script> <!-- or admin-dashboard.js -->
</body>

</html>