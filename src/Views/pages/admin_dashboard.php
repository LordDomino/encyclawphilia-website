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

                    <div class="filter-sidebar-header">
                        <h2 class="filter-sidebar-title">Filters</h2>
                        <button class="filter-clear-all" id="filter-clear-all" type="button" aria-label="Clear all filters">
                            Clear all
                        </button>
                    </div>

                    <div class="filter-sidebar-body" id="filter-sidebar-body">
                        <form id="filter-form">
                            <!-- ── Category ────────────────────────────────── -->
                            <div class="filter-block" id="filter-block-category">
                                <button
                                    class="filter-block-toggle"
                                    type="button"
                                    aria-expanded="true"
                                    aria-controls="filter-panel-category">
                                    <span class="filter-block-label">Category</span>
                                    <span class="filter-block-chevron" aria-hidden="true">▾</span>
                                </button>
                                <div class="filter-block-panel" id="filter-panel-category">
                                    <select
                                        class="filter-select"
                                        id="filter-category"
                                        name="category_id"
                                        data-filter-key="category_id"
                                        aria-label="Filter by category">
                                        <option value="">All Categories</option>
                                        <!-- JS-injectable: <option value="{category_id}">{category_name}</option> -->
                                        <option value="1">Health</option>
                                        <option value="2">Education</option>
                                        <option value="3">Environment</option>
                                        <option value="4">Public Safety</option>
                                        <option value="5">Infrastructure</option>
                                        <option value="6">Taxation</option>
                                        <option value="7">Social Welfare</option>
                                        <option value="8">Youth Affairs</option>
                                        <option value="9">Sports and Recreation</option>
                                        <option value="10">Cultural Heritage</option>
                                    </select>
                                </div>
                            </div>

                            <!-- ── Date range ──────────────────────────────── -->
                            <div class="filter-block" id="filter-block-date">
                                <button
                                    class="filter-block-toggle"
                                    type="button"
                                    aria-expanded="true"
                                    aria-controls="filter-panel-date">
                                    <span class="filter-block-label">Date Enacted</span>
                                    <span class="filter-block-chevron" aria-hidden="true">▾</span>
                                </button>
                                <div class="filter-block-panel" id="filter-panel-date">
                                    <div class="filter-date-range">
                                        <div class="filter-date-field">
                                            <label class="filter-date-label" for="filter-date-from">From</label>
                                            <input
                                                type="date"
                                                class="filter-date-input"
                                                id="filter-date-from"
                                                name="date_from"
                                                data-filter-key="date_from"
                                                aria-label="Date enacted from" />
                                        </div>
                                        <span class="filter-date-separator" aria-hidden="true">—</span>
                                        <div class="filter-date-field">
                                            <label class="filter-date-label" for="filter-date-to">To</label>
                                            <input
                                                type="date"
                                                class="filter-date-input"
                                                id="filter-date-to"
                                                name="date_to"
                                                data-filter-key="date_to"
                                                aria-label="Date enacted to" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ── Status ──────────────────────────────────── -->
                            <div class="filter-block" id="filter-block-status">
                                <button
                                    class="filter-block-toggle"
                                    type="button"
                                    aria-expanded="true"
                                    aria-controls="filter-panel-status">
                                    <span class="filter-block-label">Status</span>
                                    <span class="filter-block-chevron" aria-hidden="true">▾</span>
                                </button>
                                <div class="filter-block-panel" id="filter-panel-status">
                                    <fieldset class="filter-checkbox-group" id="filter-status-group" data-filter-key="status">
                                        <legend class="sr-only">Filter by status</legend>
                                        <!-- JS-injectable: statuses can be added/removed here -->
                                        <label class="filter-checkbox-item">
                                            <input type="checkbox" class="filter-checkbox" name="status[]" value="Active" data-status-key="Active" />
                                            <span class="filter-checkbox-mark" aria-hidden="true"></span>
                                            <span class="filter-checkbox-text">Active</span>
                                        </label>
                                        <label class="filter-checkbox-item">
                                            <input type="checkbox" class="filter-checkbox" name="status[]" value="Pending" data-status-key="Pending" />
                                            <span class="filter-checkbox-mark" aria-hidden="true"></span>
                                            <span class="filter-checkbox-text">Pending</span>
                                        </label>
                                        <label class="filter-checkbox-item">
                                            <input type="checkbox" class="filter-checkbox" name="status[]" value="Repealed" data-status-key="Repealed" />
                                            <span class="filter-checkbox-mark" aria-hidden="true"></span>
                                            <span class="filter-checkbox-text">Repealed</span>
                                        </label>
                                        <label class="filter-checkbox-item">
                                            <input type="checkbox" class="filter-checkbox" name="status[]" value="Amended" data-status-key="Amended" />
                                            <span class="filter-checkbox-mark" aria-hidden="true"></span>
                                            <span class="filter-checkbox-text">Amended</span>
                                        </label>
                                    </fieldset>
                                </div>
                            </div>

                            <!-- ── Content flags ───────────────────────────── -->
                            <div class="filter-block" id="filter-block-flags">
                                <button
                                    class="filter-block-toggle"
                                    type="button"
                                    aria-expanded="true"
                                    aria-controls="filter-panel-flags">
                                    <span class="filter-block-label">Content</span>
                                    <span class="filter-block-chevron" aria-hidden="true">▾</span>
                                </button>
                                <div class="filter-block-panel" id="filter-panel-flags">
                                    <fieldset class="filter-checkbox-group" data-filter-key="content_flags">
                                        <legend class="sr-only">Filter by content availability</legend>
                                        <label class="filter-checkbox-item">
                                            <input type="checkbox" class="filter-checkbox" name="has_summary" value="1" data-filter-key="has_summary" />
                                            <span class="filter-checkbox-mark" aria-hidden="true"></span>
                                            <span class="filter-checkbox-text">Has summary</span>
                                        </label>
                                        <label class="filter-checkbox-item">
                                            <input type="checkbox" class="filter-checkbox" name="has_full_text" value="1" data-filter-key="has_full_text" />
                                            <span class="filter-checkbox-mark" aria-hidden="true"></span>
                                            <span class="filter-checkbox-text">Has full text</span>
                                        </label>
                                        <label class="filter-checkbox-item">
                                            <input type="checkbox" class="filter-checkbox" name="has_pdf" value="1" data-filter-key="has_pdf" />
                                            <span class="filter-checkbox-mark" aria-hidden="true"></span>
                                            <span class="filter-checkbox-text">Has PDF file</span>
                                        </label>
                                    </fieldset>
                                </div>
                            </div>

                            <!-- ── Sort ────────────────────────────────────── -->
                            <div class="filter-block" id="filter-block-sort">
                                <button
                                    class="filter-block-toggle"
                                    type="button"
                                    aria-expanded="true"
                                    aria-controls="filter-panel-sort">
                                    <span class="filter-block-label">Sort By</span>
                                    <span class="filter-block-chevron" aria-hidden="true">▾</span>
                                </button>
                                <div class="filter-block-panel" id="filter-panel-sort">
                                    <!-- ── Sort ────────────────────────────────────────────────── -->
                                    <select
                                        class="filter-select"
                                        id="filter-sort-by"
                                        name="sort_by"
                                        data-filter-key="sort_by"
                                        aria-label="Sort results by">
                                        <option value="date_enacted" selected>Date Enacted</option>
                                        <option value="series_year">Series Year</option>
                                        <option value="title">Title (A–Z)</option>
                                        <option value="created_at">Date Added</option>
                                    </select>
                                    <fieldset class="filter-radio-group" data-filter-key="sort_dir">
                                        <legend class="sr-only">Sort direction</legend>
                                        <label class="filter-radio-item">
                                            <input type="radio" class="filter-radio" name="sort_dir" value="desc" data-filter-key="sort_dir" checked />
                                            <span class="filter-radio-mark" aria-hidden="true"></span>
                                            <span class="filter-radio-text">Descending</span>
                                        </label>
                                        <label class="filter-radio-item">
                                            <input type="radio" class="filter-radio" name="sort_dir" value="asc" data-filter-key="sort_dir" />
                                            <span class="filter-radio-mark" aria-hidden="true"></span>
                                            <span class="filter-radio-text">Ascending</span>
                                        </label>
                                    </fieldset>
                                </div>
                            </div>
                        </form>
                    </div><!-- /.filter-sidebar-body -->
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

    <script src="js/pages/admin-dashboard.js"></script>
</body>

</html>