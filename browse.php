<?php
$pageTitle = "Browse | EncycLawPhilia Valenzuela";
$currentPage = "browse";
?>

<!DOCTYPE html>
<html lang="en">

<?php require_once 'fragments/head.php'; ?>

<body>
    <?php require_once 'fragments/header.php'; ?>

    <main class="browse">
        <div class="subhero" id="browse-subhero">
            <div class="subhero-content">
                <h1>Valenzuela City Ordinances</h1>
                <p>Browse the latest Valenzuela City Ordinances</p>
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
                    <form class="search-bar mini" action="browse.html" method="get">
                        <input aria-label="Search within results" autocomplete="off" inputmode="search" type="search"
                            name="q" placeholder="Search ordinances..." />
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
                    <h2 class="results-header-title">Search Results for "ordinance"</h2>
                    <span class="result-count" id="result-count">(4 results)</span>
                </div>
                <div class="flex-grid" id="results-grid">
                    <div class="content-card fixed">
                        <div class="content-card-header">
                            <div class="card-title">City Ordinance<br /><span class="numeral-and-series">No. 3749 s.
                                    2026</span></div>
                            <div class="date">10 April 2026</div>
                        </div>
                        <div class="preview-container">
                            <div class="preview-text">
                                AN ORDINANCE ESTABLISHING A COMPREHENSIVE TRAFFIC MANAGEMENT SYSTEM IN HIGH-DENSITY
                                AREAS OF VALENZUELA CITY, INCLUDING THE INSTALLATION OF TRAFFIC LIGHTS, PEDESTRIAN
                                CROSSINGS, AND PARKING REGULATIONS TO IMPROVE ROAD SAFETY.
                            </div>
                        </div>
                        <a href="results.html?q=Traffic%20Management">
                            <p class="link">Read More</p>
                        </a>
                    </div>
                    <div class="content-card fixed">
                        <div class="content-card-header">
                            <div class="card-title">City Ordinance<br /><span class="numeral-and-series">No. 3751 s.
                                    2026</span></div>
                            <div class="date">12 April 2026</div>
                        </div>
                        <div class="preview-container">
                            <div class="preview-text">
                                AN ORDINANCE REGULATING THE PARKING OF MOTORCYCLES AND BICYCLES IN PUBLIC AREAS,
                                ESTABLISHING DESIGNATED PARKING ZONES, AND IMPOSING FEES FOR PARKING VIOLATIONS.
                            </div>
                        </div>
                        <a href="results.html?q=Parking">
                            <p class="link">Read More</p>
                        </a>
                    </div>
                    <div class="content-card fixed">
                        <div class="content-card-header">
                            <div class="card-title">City Ordinance<br /><span class="numeral-and-series">No. 3751 s.
                                    2026</span></div>
                            <div class="date">12 April 2026</div>
                        </div>
                        <div class="preview-container">
                            <div class="preview-text">
                                AN ORDINANCE REGULATING THE PARKING OF MOTORCYCLES AND BICYCLES IN PUBLIC AREAS,
                                ESTABLISHING DESIGNATED PARKING ZONES, AND IMPOSING FEES FOR PARKING VIOLATIONS.
                            </div>
                        </div>
                        <a href="results.html?q=Parking">
                            <p class="link">Read More</p>
                        </a>
                    </div>
                    <div class="content-card fixed">
                        <div class="content-card-header">
                            <div class="card-title">City Ordinance<br /><span class="numeral-and-series">No. 3751 s.
                                    2026</span></div>
                            <div class="date">12 April 2026</div>
                        </div>
                        <div class="preview-container">
                            <div class="preview-text">
                                AN ORDINANCE REGULATING THE PARKING OF MOTORCYCLES AND BICYCLES IN PUBLIC AREAS,
                                ESTABLISHING DESIGNATED PARKING ZONES, AND IMPOSING FEES FOR PARKING VIOLATIONS.
                            </div>
                        </div>
                        <a href="results.html?q=Parking">
                            <p class="link">Read More</p>
                        </a>
                    </div>
                </div>
            </section>
        </div>
        <button class="back-to-top" id="back-to-top" aria-label="Back to top">↑</button>
    </main>

    <?php require_once 'fragments/footer.php'; ?>
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