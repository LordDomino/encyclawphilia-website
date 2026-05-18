<?php

session_start();
$loggedIn = isset($_SESSION['user_id']);

$pageTitle = "About EncycLawPhilia Valenzuela";
$currentPage = "about";

require_once __DIR__ . '/../head.php';
?>

<body>
    <?php require_once __DIR__ . '/../header.php'; ?>

    <main class="about-page">
        <div class="subhero">
            <h1>About EncycLawPhilia</h1>
            <p>Empowering citizens through transparent access to Valenzuela City ordinances</p>
        </div>

        <section class="about-mission">
            <div class="mission-container">
                <div class="mission-content">
                    <h2>Our Mission</h2>
                    <p>
                        EncycLawPhilia is dedicated to democratizing access to Valenzuela City's legislative information.
                        We believe that an informed citizenry is essential for a functioning democracy. Our platform
                        provides residents, businesses, and government officials with a centralized, searchable repository
                        of city ordinances, making it easier to understand the rules and regulations that govern our community.
                    </p>
                </div>
            </div>
        </section>

        <section class="about-values">
            <h2>Our Core Values</h2>
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon">📋</div>
                    <h3>Transparency</h3>
                    <p>We believe in open and transparent governance. All city ordinances are freely accessible to the public.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">🔍</div>
                    <h3>Accessibility</h3>
                    <p>Our platform is designed to be user-friendly and searchable, making it easy for anyone to find information.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">⚖️</div>
                    <h3>Accuracy</h3>
                    <p>We maintain up-to-date records of all active, pending, and archived ordinances in Valenzuela City.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">🤝</div>
                    <h3>Community</h3>
                    <p>We serve all members of the Valenzuela community—residents, businesses, and government officials alike.</p>
                </div>
            </div>
        </section>

        <section class="about-features">
            <h2>What We Offer</h2>
            <div class="features-container">
                <div class="feature-item">
                    <h3>Comprehensive Database</h3>
                    <p>
                        Browse through thousands of ordinances organized by category, year, and status.
                        Our extensive database covers Traffic & Transportation, Health & Sanitation,
                        Public Safety, Environment & Zoning, and more.
                    </p>
                </div>
                <div class="feature-item">
                    <h3>Advanced Search</h3>
                    <p>
                        Use our powerful search functionality to quickly locate ordinances by keyword,
                        ordinance number, or date. Filter results by category, year, and status to narrow
                        your search.
                    </p>
                </div>
                <div class="feature-item">
                    <h3>Latest Updates</h3>
                    <p>
                        Stay informed about the most recent ordinances passed by the Valenzuela City Council.
                        Our homepage features the latest ordinances and highlights important legislative updates.
                    </p>
                </div>
                <div class="feature-item">
                    <h3>Easy Navigation</h3>
                    <p>
                        Whether you're looking for information about traffic regulations, environmental
                        policies, or public safety measures, our intuitive interface makes finding what you
                        need simple and straightforward.
                    </p>
                </div>
            </div>
        </section>

        <section class="about-how-to">
            <h2>How to Use EncycLawPhilia</h2>
            <div class="steps-container">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Search or Browse</h3>
                    <p>Use the search bar on our homepage to look for specific ordinances, or browse by category to explore ordinances by topic.</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Apply Filters</h3>
                    <p>Narrow your results using our filtering options: category, year, and status (Active, Pending, or Archived).</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Review Details</h3>
                    <p>Click on any ordinance to view its full text, effective date, and status information.</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h3>Stay Updated</h3>
                    <p>Check back regularly for the latest ordinances, or contact us for inquiries about specific regulations.</p>
                </div>
            </div>
        </section>

        <section class="about-cta">
            <h3>Ready to Explore Valenzuela's Ordinances?</h3>
            <p>Start by browsing our latest ordinances or use the search feature to find specific regulations.</p>
            <div class="cta-buttons">
                <a href="index.php" class="cta-link primary">Go to Home</a>
                <a href="browse.php" class="cta-link secondary">Browse Ordinances</a>
            </div>
        </section>
    </main>

    <?php require_once __DIR__ . '/../src/Views/footer.php'; ?>
</body>

</html>