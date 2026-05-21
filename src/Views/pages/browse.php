<?php

namespace App\Views\pages;

use App\Models\CommentModel;
use App\Models\OrdinanceModel;

session_start();
$loggedIn = isset($_SESSION['user_id']);

// Phase 1: Ingestion and State Management
require_once __DIR__ . '/../../Models/procedures.php'; // Import the newly decoupled data routine

$search_keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$safe_search_keyword = htmlspecialchars($search_keyword, ENT_QUOTES, 'UTF-8');
$results = [];

// Phase 2: Delegating Database Interrogation
if ($search_keyword !== '') {
    // Establish network boundary context
    $pdo = \App\Controllers\DatabaseController::getDatabaseConnection();
    $ordinanceModel = new OrdinanceModel($pdo);
    $results = $ordinanceModel->getOrdinancesByTitle($safe_search_keyword);

    // Debugging payload output
    //  * Refactored: Replaced 'CALL GetOrdinancesByTitle(:key)' statement compilation 
    //  * with an explicit application routine invocation. The connection state ($pdo)
    //  * is passed into the function context directly.
    //  */
    // $commentModel = new CommentModel($pdo);
    echo "<script>console.log(" . json_encode($results) . ")</script>";
}

// HTML Assembly
$pageTitle = "Browse | EncycLawPhilia Valenzuela";
$currentPage = "browse";

require_once VIEWS_ROOT . '/head.php';

use \App\Core\TemplateEngine;

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
        <div class="active-filters" id="active-filters" style="display: none;">
            <div class="active-filters-content">
                <span class="filter-label">Active Filters:</span>
                <div class="filter-chips" id="filter-chips"></div>
                <button class="clear-filters" id="clear-filters">Clear All</button>
            </div>
        </div>
        <div class="columns-split">
            <button class="sidebar-toggle" id="sidebar-toggle" aria-label="Toggle filters">☰ Filters</button>
            <aside class="sidebar-column" id="sidebar-column">
                <div class="sidebar-content">

                    <!-- Mini search -->
                    <form class="search-bar mini" action="/browse" method="get">
                        <input
                            type="search"
                            id="browse-pg-search-bar"
                            name="q"
                            placeholder="Search ordinances..."
                            aria-label="Search within results"
                            autocomplete="off"
                            inputmode="search"
                            value="<?php echo $safe_search_keyword; ?>" />
                    </form>

                    <!-- Filters heading -->
                    <div class="filter-sidebar-header">
                        <h2 class="filter-sidebar-title">Filters</h2>
                        <button class="filter-clear-all" id="filter-clear-all" type="button" aria-label="Clear all filters">
                            Clear all
                        </button>
                    </div>

                    <div class="filter-sidebar-body" id="filter-sidebar-body">

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
                                    name="category"
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
                                <select
                                    class="filter-select"
                                    id="filter-sort-by"
                                    name="sort_by"
                                    data-filter-key="sort_by"
                                    aria-label="Sort results by">
                                    <option value="date_enacted">Date Enacted</option>
                                    <option value="popularity">Popularity</option>
                                    <option value="date_signed">Date Signed</option>
                                    <option value="numerical">Numerically</option>
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

                    </div><!-- /.filter-sidebar-body -->
                </div><!-- /.sidebar-content -->
            </aside>
            <section class="results-content" id="browse-all-ordinances">
                <div class="results-header">
                    <h2 class="results-header-title">Search Results for <?php echo $safe_search_keyword; ?></h2>
                    <span class="result-count" id="result-count">(<?php echo count($results); ?> results)</span>
                </div>
                <div class="flex-grid" id="results-grid">
                    <?php if (!empty($results)): ?>
                        <?php foreach ($results as $row): ?>
                            <div class="content-card">
                                <div class="content-card-header">
                                    <div class="card-label-group">
                                        <span class="card-type">City Ordinance</span>
                                        <span class="numeral-and-series">
                                            No. <?php echo htmlspecialchars($row['ordinance_number'], ENT_QUOTES, 'UTF-8'); ?>
                                            s. <?php echo htmlspecialchars($row['series_year'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                    <div class="date">
                                        <span class="date-day"><?php echo htmlspecialchars($row['enactment_day'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <div class="date-meta">
                                            <span class="date-month"><?php echo htmlspecialchars($row['enactment_month'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span class="date-year"><?php echo htmlspecialchars($row['enactment_year'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-container">
                                    <div class="preview-text">
                                        <?php echo htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <div class="card-engagement">
                                        <span class="engagement-item likes">
                                            <span class="engagement-icon">▲</span>
                                            <span class="engagement-count">
                                                <?php echo htmlspecialchars($row['like_count'], ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </span>
                                        <span class="engagement-divider"></span>
                                        <span class="engagement-item dislikes">
                                            <span class="engagement-icon">▼</span>
                                            <span class="engagement-count">
                                                <?php echo htmlspecialchars($row['dislike_count'], ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </span>
                                    </div>
                                    <?php
                                    $injectedCardHTML = TemplateEngine::compile('partials/ord_link', ['ordinance_id' => $row['ordinance_id']]);
                                    echo $injectedCardHTML;
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-results">
                            <p>No matching items found. Try adjusting your keywords.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
        <button class="back-to-top" id="back-to-top" aria-label="Back to top">↑</button>
    </main>

    <?php require_once __DIR__ . '/../footer.php'; ?>
</body>
<script>
    // ============================================================
    // SEARCH RESULTS INITIALIZATION
    // ============================================================
    const params = new URLSearchParams(window.location.search);
    const query = params.get('q')?.trim();
    const titleEl = document.querySelector('.results-header-title');
    const searchInput = document.querySelector('form.search-bar.mini input[name="q"]');

    if (query != '') {
        if (titleEl) {
            titleEl.textContent = `Search Results for "${query}"`;
        }
        if (searchInput) {
            searchInput.value = query;
        }
        document.title = `${query} | EncycLawPhilia`;
    } else {
        if (titleEl) {
            titleEl.textContent = 'Search Results';
        }
    }

    // ============================================================
    // SCROLL ANIMATIONS & STICKY HEADER
    // ============================================================

    // ============================================================
    // SIDEBAR DRAWER (mobile)
    // ============================================================
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebarColumn = document.getElementById('sidebar-column');

    sidebarToggle.addEventListener('click', () => {
        sidebarColumn.classList.toggle('drawer-open');
        sidebarToggle.classList.toggle('active');
    });

    document.addEventListener('click', (e) => {
        if (!sidebarColumn.contains(e.target) && !sidebarToggle.contains(e.target)) {
            sidebarColumn.classList.remove('drawer-open');
            sidebarToggle.classList.remove('active');
        }
    });

    // ============================================================
    // COLLAPSIBLE FILTER BLOCKS
    // ============================================================
    document.querySelectorAll('.filter-block-toggle').forEach(toggle => {
        toggle.addEventListener('click', () => {
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            const panelId = toggle.getAttribute('aria-controls');
            const panel = document.getElementById(panelId);

            toggle.setAttribute('aria-expanded', String(!isExpanded));
            panel.classList.toggle('is-collapsed', isExpanded);
        });
    });

    // ============================================================
    // CLEAR ALL FILTERS
    // ============================================================
    document.getElementById('filter-clear-all')?.addEventListener('click', () => {
        // Selects
        document.querySelectorAll('.filter-select').forEach(sel => sel.selectedIndex = 0);
        // Date inputs
        document.querySelectorAll('.filter-date-input').forEach(inp => inp.value = '');
        // Checkboxes
        document.querySelectorAll('.filter-checkbox').forEach(cb => cb.checked = false);
        // Radios — reset to first in each group
        document.querySelectorAll('.filter-radio-group').forEach(group => {
            const first = group.querySelector('.filter-radio');
            if (first) first.checked = true;
        });
        // Update active filters display if wired
        updateActiveFilters?.();
    });

    // ============================================================
    // ACTIVE FILTERS DISPLAY
    // ============================================================
    const categoryFilter = document.getElementById('category-filter');
    const yearFilter = document.getElementById('year-filter');
    const statusFilters = document.querySelectorAll('select[id="status-filter"]');
    const filterChips = document.getElementById('filter-chips');
    const clearFiltersBtn = document.getElementById('clear-filters');

    function updateActiveFilters() {
        const activeFilters = [];

        if (categoryFilter.value !== 'All Categories') {
            activeFilters.push({
                label: categoryFilter.value,
                id: 'category'
            });
        }
        if (yearFilter.value !== 'All Years') {
            activeFilters.push({
                label: yearFilter.value,
                id: 'year'
            });
        }
        if (statusFilters[0].value !== 'All Statuses') {
            activeFilters.push({
                label: statusFilters[0].value,
                id: 'status'
            });
        }

        if (activeFilters.length > 0) {
            activeFiltersContainer.style.display = 'block';
            filterChips.innerHTML = activeFilters.map(filter =>
                `<span class="chip" data-filter="${filter.id}">${filter.label} ✕</span>`
            ).join('');

            // Add click handlers to remove individual filters
            document.querySelectorAll('.chip').forEach(chip => {
                chip.addEventListener('click', () => {
                    const filterId = chip.dataset.filter;
                    if (filterId === 'category') categoryFilter.value = 'All Categories';
                    if (filterId === 'year') yearFilter.value = 'All Years';
                    if (filterId === 'status') statusFilters[0].value = 'All Statuses';
                    updateActiveFilters();
                });
            });
        } else {
            activeFiltersContainer.style.display = 'none';
        }
    }

    categoryFilter.addEventListener('change', updateActiveFilters);
    yearFilter.addEventListener('change', updateActiveFilters);
    statusFilters[0].addEventListener('change', updateActiveFilters);

    clearFiltersBtn.addEventListener('click', () => {
        categoryFilter.value = 'All Categories';
        yearFilter.value = 'All Years';
        statusFilters[0].value = 'All Statuses';
        statusFilters[1].value = 'All Statuses';
        updateActiveFilters();
    });

    // ============================================================
    // CARD ENTRANCE ANIMATIONS (Intersection Observer)
    // ============================================================
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                // Add slight delay per card for staggered effect
                setTimeout(() => {
                    entry.target.classList.add('animate-in');
                }, index * 50);
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe all content cards
    document.querySelectorAll('.content-card').forEach(card => {
        observer.observe(card);
    });
</script>

</html>
</body>

</html>