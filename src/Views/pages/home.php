<?php

namespace App\Views\pages;

// Session tracking for user credentials persistence
session_start();
$loggedIn = isset($_SESSION['user_id']);

// Phase 1: Ingestion and State Management
$search_keyword      = isset($_GET['q']) ? trim($_GET['q']) : '';
$safe_search_keyword = htmlspecialchars($search_keyword, ENT_QUOTES, 'UTF-8');
$featured_ordinance  = null;
$results_latest      = [];

// Phase 2: Delegating Database Interrogation
$pdo = \App\Controllers\DatabaseController::getDatabaseConnection();
$ordinanceModel = new \App\Models\OrdinanceModel($pdo);
$featured_ordinance = $ordinanceModel->getTrending();
$results_latest = $ordinanceModel->get20RecentOrdinances();

// Fetch the full record for the featured card (richer metadata)
$featured_detail = null;
if ($featured_ordinance !== null) {
    $featured_detail = $ordinanceModel->getOrdinanceById($featured_ordinance['ordinance_id']);
}

// Status → CSS badge class map (mirrors ordinance.php)
$featured_status_class = [
    'Active'   => 'passed',
    'Pending'  => 'pending',
    'Repealed' => 'rejected',
    'Amended'  => 'in-progress',
];

// Phase 3: HTML Presentation
$pageTitle = "EncycLawPhilia Valenzuela";
$currentPage = "home";

require_once __DIR__ . '/../head.php';
?>

<body class="<?php echo isset($currentPage) ? htmlspecialchars($currentPage) : 'default'; ?>" id="top">

    <section class="hero-container">
        <?php require_once __DIR__ . '/../header.php'; ?>
        <div class="hero">
            <hgroup class="title">
                <?php require_once __DIR__ . '/../brand_title.php'; ?>
                <p>Wisdom Beyond Law and Order</p>
            </hgroup>
            <form class="search-bar" id="search-form">
                <input
                    type="search"
                    id="search-input"
                    name="q"
                    placeholder="Search city ordinances..."
                    aria-label="Search for city ordinances"
                    autocomplete="off"
                    inputmode="search" />
            </form>
        </div>
    </section>
    <main class="home">
        <section class="dashboard" id="browse-latest">
            <div class="flex-column" id="featured">
                <h2>Featured</h2>
                <?php if ($featured_detail): ?>
                    <?php
                    $f_status_cls = $featured_status_class[$featured_detail['status']] ?? 'pending';
                    $f_like_count    = (int)($featured_detail['like_count']    ?? 0);
                    $f_dislike_count = (int)($featured_detail['dislike_count'] ?? 0);
                    $f_total         = $f_like_count + $f_dislike_count;
                    $f_like_pct      = $f_total > 0 ? round(($f_like_count / $f_total) * 100) : 50;
                    ?>
                    <div class="card-featured">

                        <!-- Top bar: label + status -->
                        <div class="card-featured-topbar">
                            <span class="card-type">City Ordinance</span>
                            <span class="status-badge <?php echo htmlspecialchars($f_status_cls, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($featured_detail['status'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </div>

                        <!-- Ordinance number + decorative background numeral -->
                        <div class="card-featured-numeral-wrap">
                            <span class="card-featured-numeral-bg" aria-hidden="true">
                                <?php echo htmlspecialchars($featured_detail['ordinance_number'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                            <span class="numeral-and-series card-featured-numeral">
                                No. <?php echo htmlspecialchars($featured_detail['ordinance_number'], ENT_QUOTES, 'UTF-8'); ?>
                                <span class="series-year">s. <?php echo htmlspecialchars($featured_detail['series_year'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </span>
                        </div>

                        <!-- Title -->
                        <div class="preview-container">
                            <div class="preview-text">
                                <?php echo htmlspecialchars($featured_detail['title'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        </div>

                        <!-- Author + Category -->
                        <div class="card-featured-meta">
                            <?php if (!empty($featured_detail['author_sponsor'])): ?>
                                <span class="card-featured-meta-item">
                                    <span class="card-featured-meta-icon" aria-hidden="true">🖊</span>
                                    <?php echo htmlspecialchars($featured_detail['author_sponsor'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($featured_detail['category_name'])): ?>
                                <span class="card-featured-meta-item">
                                    <span class="card-featured-meta-icon" aria-hidden="true">🏷</span>
                                    <?php echo htmlspecialchars($featured_detail['category_name'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Footer: engagement + date -->
                        <div class="card-footer card-featured-footer">
                            <div class="card-engagement">
                                <span class="engagement-item likes">
                                    <span class="engagement-icon">▲</span>
                                    <span class="engagement-count"><?php echo $f_like_count; ?></span>
                                </span>
                                <span class="engagement-divider"></span>
                                <span class="engagement-item dislikes">
                                    <span class="engagement-icon">▼</span>
                                    <span class="engagement-count"><?php echo $f_dislike_count; ?></span>
                                </span>
                            </div>
                            <?php if (!empty($featured_detail['enactment_day'])): ?>
                                <div class="date card-featured-date">
                                    <span class="date-day"><?php echo htmlspecialchars($featured_detail['enactment_day'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <div class="date-meta">
                                        <span class="date-month"><?php echo htmlspecialchars($featured_detail['enactment_month'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span class="date-year"><?php echo htmlspecialchars($featured_detail['enactment_year'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Reaction ratio bar -->
                        <div class="card-featured-reaction-bar" title="<?php echo $f_like_pct; ?>% support" aria-hidden="true">
                            <div class="card-featured-reaction-fill" style="width: <?php echo $f_like_pct; ?>%"></div>
                        </div>

                    </div>
                <?php else: ?>
                    <p class="card-featured-empty">No featured ordinance available.</p>
                <?php endif; ?>
                <a href="/ordinance?id=<?= $featured_ordinance['ordinance_id'] ?>">
                    <p class="link">Read More</p>
                </a>
            </div>
            <div class="flex-column">
                <p id="latest-ordinances"></p>
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
                                                <?php
                                                $injectedCardHTML = \App\Core\TemplateEngine::compile('partials/ord_link', ['ordinance_id' => $row['ordinance_id']]);
                                                echo $injectedCardHTML;
                                                ?>
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
    </main>

    <?php require_once __DIR__ . '/../footer.php'; ?>

    <script src="js/sanitize.js"></script>
    <script src="js/search.js"></script>
</body>


</html>