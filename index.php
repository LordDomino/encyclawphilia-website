<?php
$pageTitle = "EncycLawPhilia Valenzuela";
$currentPage = "home";

require_once 'fragments/head.php';
?>

<body class="<?php echo isset($currentPage) ? htmlspecialchars($currentPage) : 'default'; ?>" id="top">

    <section class="hero-container">
        <?php require_once 'fragments/header.php'; ?>
        <div class="hero">
            <hgroup class="title">
                <?php require_once 'fragments/brand_title.php'; ?>
                <p>Wisdom Beyond Law and Order</p>
            </hgroup>
            <form action="browse.php" method="GET" class="search-bar">
                <input
                    type="search"
                    id="home-pg-hero-search"
                    name="q"
                    placeholder="Search city ordinances..."
                    aria-label="Search for city legislations"
                    autocomplete="off"
                    inputmode="search"
                />
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
                    <div class="card-title">City Ordinance<br /><span class="numeral-and-series">No. 3750 s. 2026</span>
                    </div>
                    <div class="date">15 April 2026</div>
                    <div class="preview-container">
                        <div class="preview-text">
                            AN ORDINANCE AMENDING CITY ORDINANCE NO. 3725, SERIES OF 2024,
                            ENTITLED "AN ORDINANCE REGULATING THE USE OF PUBLIC PARKS AND
                            OPEN SPACES IN THE CITY OF VALENZUELA", BY INCREASING THE PENALTIES
                            FOR VIOLATIONS AND ADDING PROVISIONS FOR ENVIRONMENTAL PROTECTION.
                        </div>
                    </div>
                </div>
                <a>
                    <p class="link">Read More</p>
                </a>
            </div>
            <div class="flex-column">
                <h1>Latest City Ordinances in Valenzuela</h1>
                <p>Browse the latest city ordinances in Valenzuela.</p>
                <div class="vertical-carousel-frame">
                    <div class="vertical-carousel-wrapper">
                        <ul class="vertical-carousel">
                            <li>
                                <div class="card-banner">
                                    <div class="card-header">
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
                                <div class="card-banner">
                                    <div class="card-header">
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
                                <div class="card-banner">
                                    <div class="card-header">
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
                                <div class="card-banner">
                                    <div class="card-header">
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
                                <div class="card-banner">
                                    <div class="card-header">
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
                                <div class="card-banner">
                                    <div class="card-header">
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
                </div>
            </div>

        </section>

        <section class="dashboard" id="browse-category">
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
            <h2>Health and Sanitation</h2>
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
            <h2>Public Safety</h2>
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
            <h2>Environment and Zoning</h2>
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
        </section>

    </main>

    <?php require_once 'fragments/footer.php'; ?>
</body>

<script src="js/search.js"></script>

</html>