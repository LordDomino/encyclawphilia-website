<?php
session_start();
$loggedIn = isset($_SESSION['user_id']);

// Phase 1: Ingestion and State Management
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../Models/procedures.php'; // Import the newly decoupled data routine

$search_keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$safe_search_keyword = htmlspecialchars($search_keyword, ENT_QUOTES, 'UTF-8');
$results = [];

// Phase 2: Delegating Database Interrogation
if ($search_keyword !== '') {
    // Establish network boundary context
    $pdo = getDatabaseConnection();

    /* 
     * Refactored: Replaced 'CALL GetOrdinancesByTitle(:key)' statement compilation 
     * with an explicit application routine invocation. The connection state ($pdo)
     * is passed into the function context directly.
     */
    $results = getOrdinancesByTitle($pdo, $safe_search_keyword);

    // Debugging payload output
    echo "<script>console.log(" . json_encode($results) . ");</script>";
    echo "<script>console.log('Hello world');</script>";
} else {
    // Establish network boundary context
    $pdo = getDatabaseConnection();

    /* 
     * Refactored: Replaced 'CALL GetOrdinancesByTitle(:key)' statement compilation 
     * with an explicit application routine invocation. The connection state ($pdo)
     * is passed into the function context directly.
     */
    $results = getOrdinancesByTitle($pdo, $safe_search_keyword);
}

// HTML Assembly
$pageTitle = "Browse | EncycLawPhilia Valenzuela";
$currentPage = "browse";

require_once __DIR__ . '/../head.php';
require_once __DIR__ . '/../../view_components.php';
?>

<body>
    <?php require_once __DIR__ . '/../header.php'; ?>

    <main class="browse">
        <div class="subhero" id="browse-subhero">
            <div class="subhero-content">
                <h1>Valenzuela City Ordinances</h1>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque libero diam, imperdiet id leo eu, rutrum elementum orci. Sed eu odio mi. Vivamus porta nunc vitae facilisis aliquet. Sed pellentesque cursus massa, non lacinia odio mollis quis. Ut maximus, nisl id luctus placerat, neque mauris vehicula tortor, a condimentum metus velit at velit. Pellentesque nisl ex, luctus non sollicitudin a, varius efficitur turpis. Quisque nec neque commodo, aliquam odio sit amet, vestibulum nisl. Donec non arcu risus. Donec faucibus aliquam pharetra. Donec tellus erat, viverra feugiat turpis vitae, sodales dignissim erat. In tincidunt nibh eget gravida tincidunt. Mauris vitae ipsum lectus. Nulla cursus, ipsum tincidunt vestibulum facilisis, lorem ex iaculis libero, non tempus purus ligula et augue. Mauris fermentum ligula ac dolor placerat porta. Morbi et rhoncus tortor.</p>
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
                    <form class="search-bar mini" action="browse.php" method="get">
                        <input
                            type="search"
                            id="browse-pg-search-bar"
                            name="q"
                            placeholder="Search ordinances..."
                            aria-label="Search within results"
                            autocomplete="off"
                            inputmode="search" />
                    </form>
                    <h2>Filters</h2>
                    <div class="filter-group">
                        <label for="category-filter">Category</label>
                        <select id="category-filter">
                            <option>All Categories</option>
                            <option>Public Safety</option>
                            <option>Environment</option>
                            <option>Education</option>
                            <option>Transportation</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="year-filter">Year</label>
                        <select id="year-filter">
                            <option>All Years</option>
                            <option>2026</option>
                            <option>2025</option>
                            <option>2024</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="status-filter">Status</label>
                        <select id="status-filter">
                            <option>All Statuses</option>
                            <option>Active</option>
                            <option>Pending</option>
                            <option>Archived</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="status-filter">Status</label>
                        <select id="status-filter">
                            <option>All Statuses</option>
                            <option>Active</option>
                            <option>Pending</option>
                            <option>Archived</option>
                        </select>
                    </div>
                </div>
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
                                    <?php renderOrdinanceRedirectLink((int)$row['ordinance_id']); ?>
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

    if (query) {
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
    // SIDEBAR DRAWER FOR MOBILE
    // ============================================================
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebarColumn = document.getElementById('sidebar-column');

    sidebarToggle.addEventListener('click', () => {
        sidebarColumn.classList.toggle('drawer-open');
        sidebarToggle.classList.toggle('active');
    });

    // Close drawer when clicking outside
    document.addEventListener('click', (e) => {
        if (!sidebarColumn.contains(e.target) && !sidebarToggle.contains(e.target)) {
            sidebarColumn.classList.remove('drawer-open');
            sidebarToggle.classList.remove('active');
        }
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