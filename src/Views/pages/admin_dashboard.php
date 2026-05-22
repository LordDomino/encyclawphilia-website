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

    <main class="admin-dashboard-page">
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