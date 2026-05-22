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
                    <form class="search-bar mini" id="browse-search-form" action="/browse" method="get">
                        <input
                            type="search"
                            id="browse-pg-search-bar"
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
                    <h2 class="results-header-title">All Ordinances</h2>
                    <span class="result-count" id="result-count"></span>
                </div>

                <!-- Pagination bar — top -->
                <nav class="pagination-bar" id="pagination-bar-top" aria-label="Pagination top">
                    <!-- Populated by browse-search.js -->
                </nav>

                <div class="flex-grid" id="results-grid">
                    <!-- Populated by browse-search.js -->
                </div>

                <!-- Pagination bar — bottom -->
                <nav class="pagination-bar" id="pagination-bar-bottom" aria-label="Pagination bottom">
                    <!-- Populated by browse-search.js -->
                </nav>
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
    const activeFiltersContainer = document.getElementById('active-filters');
    const filterChips = document.getElementById('filter-chips');

    /**
     * Collects all currently active filter states across every
     * filter control in the sidebar and returns a flat array of
     * { label, id, reset } descriptor objects.
     */
    function collectActiveFilters() {
        const active = [];

        // ── Category (single select) ─────────────────────────
        const categoryEl = document.getElementById('filter-category');
        if (categoryEl && categoryEl.value !== '') {
            active.push({
                label: categoryEl.options[categoryEl.selectedIndex].text,
                id: 'category',
                reset: () => {
                    categoryEl.value = '';
                }
            });
        }

        // ── Date range ───────────────────────────────────────
        const dateFrom = document.getElementById('filter-date-from');
        const dateTo = document.getElementById('filter-date-to');
        if (dateFrom && dateFrom.value) {
            active.push({
                label: `From ${dateFrom.value}`,
                id: 'date_from',
                reset: () => {
                    dateFrom.value = '';
                }
            });
        }
        if (dateTo && dateTo.value) {
            active.push({
                label: `To ${dateTo.value}`,
                id: 'date_to',
                reset: () => {
                    dateTo.value = '';
                }
            });
        }

        // ── Status (multi-checkbox) ──────────────────────────
        document.querySelectorAll('#filter-status-group .filter-checkbox:checked').forEach(cb => {
            active.push({
                label: cb.value,
                id: `status_${cb.value}`,
                reset: () => {
                    cb.checked = false;
                }
            });
        });

        // ── Content flags (multi-checkbox) ──────────────────
        const flagLabels = {
            has_summary: 'Has summary',
            has_full_text: 'Has full text',
            has_pdf: 'Has PDF file'
        };
        document.querySelectorAll('[data-filter-key^="has_"]:checked').forEach(cb => {
            active.push({
                label: flagLabels[cb.name] ?? cb.name,
                id: `flag_${cb.name}`,
                reset: () => {
                    cb.checked = false;
                }
            });
        });

        // ── Sort (only surface when non-default) ────────────
        const sortEl = document.getElementById('filter-sort-by');
        const sortDir = document.querySelector('.filter-radio:checked');
        const defaultSort = 'date_enacted';
        const defaultDir = 'desc';
        if (sortEl && (sortEl.value !== defaultSort || (sortDir && sortDir.value !== defaultDir))) {
            const dirLabel = sortDir ? (sortDir.value === 'asc' ? '↑' : '↓') : '';
            active.push({
                label: `${sortEl.options[sortEl.selectedIndex].text} ${dirLabel}`.trim(),
                id: 'sort',
                reset: () => {
                    sortEl.value = defaultSort;
                    const descRadio = document.querySelector('.filter-radio[value="desc"]');
                    if (descRadio) descRadio.checked = true;
                }
            });
        }

        return active;
    }

    function updateActiveFilters() {
        const active = collectActiveFilters();

        if (active.length > 0) {
            activeFiltersContainer.style.display = 'block';
            filterChips.innerHTML = active.map(f =>
                `<span class="chip" data-filter="${f.id}">${f.label} ✕</span>`
            ).join('');

            // Bind removal to each chip's reset function
            filterChips.querySelectorAll('.chip').forEach(chip => {
                const descriptor = active.find(f => f.id === chip.dataset.filter);
                chip.addEventListener('click', () => {
                    descriptor?.reset();
                    updateActiveFilters();
                });
            });
        } else {
            activeFiltersContainer.style.display = 'none';
            filterChips.innerHTML = '';
        }
    }

    // ── Attach change listeners to all filter controls ────────
    document.getElementById('filter-category')
        ?.addEventListener('change', updateActiveFilters);

    document.getElementById('filter-date-from')
        ?.addEventListener('change', updateActiveFilters);

    document.getElementById('filter-date-to')
        ?.addEventListener('change', updateActiveFilters);

    document.querySelectorAll('#filter-status-group .filter-checkbox')
        .forEach(cb => cb.addEventListener('change', updateActiveFilters));

    document.querySelectorAll('[data-filter-key^="has_"]')
        .forEach(cb => cb.addEventListener('change', updateActiveFilters));

    document.getElementById('filter-sort-by')
        ?.addEventListener('change', updateActiveFilters);

    document.querySelectorAll('.filter-radio')
        .forEach(r => r.addEventListener('change', updateActiveFilters));

    // ── Clear-all wired to the new sidebar button ────────────
    document.getElementById('filter-clear-all')
        ?.addEventListener('click', () => {
            collectActiveFilters().forEach(f => f.reset());
            updateActiveFilters();
        });

    // ── Legacy clear-all button in the active-filters band ───
    document.getElementById('clear-filters')
        ?.addEventListener('click', () => {
            collectActiveFilters().forEach(f => f.reset());
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
<script src="js/browse-search.js"></script>

</html>
</body>

</html>