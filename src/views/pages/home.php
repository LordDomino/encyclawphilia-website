<?php

namespace App\Views\pages;

use App\Models\OrdinanceModel;

// Session tracking for user credentials persistence
session_start();
$loggedIn = isset($_SESSION['user_id']);

// Phase 1: Ingestion and State Management
$search_keyword      = isset($_GET['q']) ? trim($_GET['q']) : '';
$safe_search_keyword = htmlspecialchars($search_keyword, ENT_QUOTES, 'UTF-8');
$featured_ordinance  = null;
$results_latest      = [];

// Phase 2: Delegating Database Interrogation
$pdo = getDatabaseConnection();
$ordinanceModel = new OrdinanceModel($pdo);
$featured_ordinance = $ordinanceModel->getTrending();
$results_latest = $ordinanceModel->get20RecentOrdinances();

// Phase 3: HTML Presentation
$pageTitle = "EncycLawPhilia Valenzuela";
$currentPage = "home";

require_once __DIR__ . '/../head.php';
require_once __DIR__ . '/../../view_components.php';
?>



<body class="<?php echo isset($currentPage) ? htmlspecialchars($currentPage) : 'default'; ?>" id="top">

    <section class="hero-container">
        <?php require_once __DIR__ . '/../header.php'; ?>
        <div class="hero">
            <hgroup class="title">
                <?php require_once __DIR__ . '/../brand_title.php'; ?>
                <p>Wisdom Beyond Law and Order</p>
            </hgroup>
            <form action="/browse" method="GET" class="search-bar">
                <input
                    type="search"
                    id="home-pg-hero-search"
                    name="q"
                    placeholder="Search city ordinances..."
                    aria-label="Search for city legislations"
                    autocomplete="off"
                    inputmode="search" />
            </form>
        </div>
    </section>
    <main class="home">
        <section class="dashboard" id="browse-latest">
            <div class="flex-column" id="featured">
                <h2>Featured</h2>
                <p>
                    Honorable _____ signs City Ordinance No. 3750 s. 2026 for improving the quality education in the
                    Pamantasan ng Lungsod ng Valenzuela.
                </p>
                <div class="card-featured">
                    <div class="card-title">City Ordinance<br /><span class="numeral-and-series">
                            No. <?php echo htmlspecialchars($featured_ordinance['ordinance_number'], ENT_QUOTES, 'UTF-8'); ?>
                            s. <?php echo htmlspecialchars($featured_ordinance['series_year'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>
                    <div class="date">
                        <?php echo htmlspecialchars($featured_ordinance['date_enacted_fmt'], ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <div class="preview-container">
                        <div class="preview-text">
                            <?php echo htmlspecialchars($featured_ordinance['title'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    </div>
                </div>
                <a href="/ordinance?id=<?= $featured_ordinance['ordinance_id'] ?>">
                    <p class="link">Read More</p>
                </a>
            </div>
            <div class="flex-column">
                <h1>Latest City Ordinances in Valenzuela</h1>
                <p>Browse the latest city ordinances in Valenzuela.</p>
                <div class="vertical-carousel-frame">
                    <div class="vertical-carousel-wrapper">
                        <ul class="vertical-carousel">
                            <?php if (!empty($results_latest)): ?>
                                <?php foreach ($results_latest as $row): ?>
                                    <li>
                                        <div class="card-banner">
                                            <div class="card-header">
                                                <div class="card-label-group">
                                                    <span class="card-type">City Ordinance</span>
                                                    <span class="numeral-and-series">
                                                        No. <?php echo htmlspecialchars($row['ordinance_number'], ENT_QUOTES, 'UTF-8'); ?>
                                                        s. <?php echo htmlspecialchars($row['series_year'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </span>
                                                </div>
                                                <div class="date">
                                                    <span class="date-day">
                                                        <?php echo htmlspecialchars($row['enactment_day'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </span>
                                                    <div class="date-meta">
                                                        <span class="date-month">
                                                            <?php echo htmlspecialchars($row['enactment_month'], ENT_QUOTES, 'UTF-8'); ?>
                                                        </span>
                                                        <span class="date-year">
                                                            <?php echo htmlspecialchars($row['enactment_year'], ENT_QUOTES, 'UTF-8'); ?>
                                                        </span>
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
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-results">
                                    <p>There are no latest ordinances.</p>
                                </div>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

        </section>

        <!-- <section class="dashboard" id="browse-category">
            <h1>Browse by Category</h1>
            <h2>Traffic and Transportation</h2>
            <div class="carousel-wrapper">
                <ul class="carousel">
                    <li>
                        <div class="content-card">
                            <div class="content-card-header">
                                <div class="card-label-group">
                                    <span class="card-type">City Ordinance</span>
                                    <span class="numeral-and-series">No. 3749 s. 2026</span>
                                </div>
                                <div class="date">
                                    <span class="date-day">10</span>
                                    <div class="date-meta">
                                        <span class="date-month">April</span>
                                        <span class="date-year">2026</span>
                                    </div>
                                </div>
                            </div>
                            <div class="preview-container">
                                <div class="preview-text">
                                    AN ORDINANCE ESTABLISHING A COMPREHENSIVE TRAFFIC MANAGEMENT
                                    SYSTEM IN HIGH-DENSITY AREAS OF VALENZUELA CITY...
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="card-engagement">
                                    <span class="engagement-item likes">
                                        <span class="engagement-icon">▲</span>
                                        <span class="engagement-count">24</span>
                                    </span>
                                    <span class="engagement-divider"></span>
                                    <span class="engagement-item dislikes">
                                        <span class="engagement-icon">▼</span>
                                        <span class="engagement-count">3</span>
                                    </span>
                                </div>
                                <?php renderOrdinanceRedirectLink((int)$row['ordinance_id']); ?>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="content-card">
                            <div class="content-card-header">
                                <div class="card-label-group">
                                    <span class="card-type">City Ordinance</span>
                                    <span class="numeral-and-series">No. 3749 s. 2026</span>
                                </div>
                                <div class="date">
                                    <span class="date-day">10</span>
                                    <div class="date-meta">
                                        <span class="date-month">April</span>
                                        <span class="date-year">2026</span>
                                    </div>
                                </div>
                            </div>
                            <div class="preview-container">
                                <div class="preview-text">
                                    AN ORDINANCE ESTABLISHING A COMPREHENSIVE TRAFFIC MANAGEMENT
                                    SYSTEM IN HIGH-DENSITY AREAS OF VALENZUELA CITY...
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="card-engagement">
                                    <span class="engagement-item likes">
                                        <span class="engagement-icon">▲</span>
                                        <span class="engagement-count">24</span>
                                    </span>
                                    <span class="engagement-divider"></span>
                                    <span class="engagement-item dislikes">
                                        <span class="engagement-icon">▼</span>
                                        <span class="engagement-count">3</span>
                                    </span>
                                </div>
                                <a href="ordinance.php?id=...">
                                    <p class="link">Read More</p>
                                </a>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="content-card">
                            <div class="content-card-header">
                                <div class="card-label-group">
                                    <span class="card-type">City Ordinance</span>
                                    <span class="numeral-and-series">No. 3749 s. 2026</span>
                                </div>
                                <div class="date">
                                    <span class="date-day">10</span>
                                    <div class="date-meta">
                                        <span class="date-month">April</span>
                                        <span class="date-year">2026</span>
                                    </div>
                                </div>
                            </div>
                            <div class="preview-container">
                                <div class="preview-text">
                                    AN ORDINANCE ESTABLISHING A COMPREHENSIVE TRAFFIC MANAGEMENT
                                    SYSTEM IN HIGH-DENSITY AREAS OF VALENZUELA CITY...
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="card-engagement">
                                    <span class="engagement-item likes">
                                        <span class="engagement-icon">▲</span>
                                        <span class="engagement-count">24</span>
                                    </span>
                                    <span class="engagement-divider"></span>
                                    <span class="engagement-item dislikes">
                                        <span class="engagement-icon">▼</span>
                                        <span class="engagement-count">3</span>
                                    </span>
                                </div>
                                <a href="ordinance.php?id=...">
                                    <p class="link">Read More</p>
                                </a>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="content-card">
                            <div class="content-card-header">
                                <div class="card-label-group">
                                    <span class="card-type">City Ordinance</span>
                                    <span class="numeral-and-series">No. 3749 s. 2026</span>
                                </div>
                                <div class="date">
                                    <span class="date-day">10</span>
                                    <div class="date-meta">
                                        <span class="date-month">April</span>
                                        <span class="date-year">2026</span>
                                    </div>
                                </div>
                            </div>
                            <div class="preview-container">
                                <div class="preview-text">
                                    AN ORDINANCE ESTABLISHING A COMPREHENSIVE TRAFFIC MANAGEMENT
                                    SYSTEM IN HIGH-DENSITY AREAS OF VALENZUELA CITY...
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="card-engagement">
                                    <span class="engagement-item likes">
                                        <span class="engagement-icon">▲</span>
                                        <span class="engagement-count">24</span>
                                    </span>
                                    <span class="engagement-divider"></span>
                                    <span class="engagement-item dislikes">
                                        <span class="engagement-icon">▼</span>
                                        <span class="engagement-count">3</span>
                                    </span>
                                </div>
                                <a href="ordinance.php?id=...">
                                    <p class="link">Read More</p>
                                </a>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </section> -->

    </main>

    <?php require_once __DIR__ . '/../footer.php'; ?>
    
    <script src="js/search.js"></script>
    <script src="js/home.js" defer></script>
</body>


</html>