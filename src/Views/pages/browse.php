<?php

namespace App\Views\pages;

session_start();
$loggedIn = isset($_SESSION['user_id']);

$pageTitle   = "Browse | EncycLawPhilia Valenzuela";
$currentPage = "browse";

require_once __DIR__ . '/../head.php';
?>

<body>
    <?php require_once VIEWS_ROOT . '/header.php'; ?>

    <main class="browse">
        <div class="subhero" id="browse-subhero">
            <div class="subhero-content">
                <h1>Valenzuela City Ordinances</h1>
                <p>Browse the latest ordinances in Valenzuela City.</p>
            </div>
        </div>
        <div class="columns-split">

            <button class="sidebar-toggle" id="sidebar-toggle" aria-label="Toggle filters">☰ Filters</button>

            <aside class="sidebar-column" id="sidebar-column">
                <div class="sidebar-content">

                    <!-- Mini search -->
                    <form class="search-bar mini" id="search-form" action="/browse" method="get">
                        <input
                            type="search"
                            id="search-input"
                            name="q"
                            placeholder="Search ordinances..."
                            aria-label="Search within results"
                            autocomplete="off"
                            inputmode="search" />
                        <button type="submit" class="search-submit-btn" aria-label="Search">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                width="16" height="16" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452
                     4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                    </form>

                    <!-- Filters heading -->
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

                </div><!-- /.sidebar-content -->
            </aside>
            <section class="results-content" id="browse-all-ordinances">
                <div class="results-header">
                    <h2 class="results-header-title">All Ordinances</h2>
                    <span class="result-count" id="result-count"></span>
                </div>

                <!-- Pagination bar — top -->
                <nav class="pagination-bar" id="pagination-bar-top" aria-label="Pagination top">
                    <!-- Populated by browse.js -->
                </nav>

                <div class="flex-grid" id="results-grid">
                    <!-- Populated by browse.js -->
                </div>

                <!-- Pagination bar — bottom -->
                <nav class="pagination-bar" id="pagination-bar-bottom" aria-label="Pagination bottom">
                    <!-- Populated by browse.js -->
                </nav>
            </section>
        </div>
        <button class="back-to-top" id="back-to-top" aria-label="Back to top">↑</button>
    </main>

    <?php require_once __DIR__ . '/../footer.php'; ?>

    <script src="js/sanitize.js"></script>
    <script src="js/utils/pagination.js"></script>
    <script src="js/components/sidebar.js"></script>
    <script src="js/core/search-engine.js"></script>

    <script src="js/pages/browse.js"></script>
</body>

</html>