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
                <p id="ordinances"></p>
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

                    <?php require_once __DIR__ . '/../partials/filter_sidebar.php' ?>
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

    <script src="js/components/filter-categories.js"></script>
    <script src="js/components/filter-barangays.js"></script>
    <script src="js/components/filter-statuses.js"></script>
    <script src="js/pages/browse.js"></script> <!-- or admin-dashboard.js -->
</body>

</html>