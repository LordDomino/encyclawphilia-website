<header class="site-header">
    <div class="header-content">
        <a href="/home#top" class="clickable">
            <div class="brand-title">
                <h1>EncycLawPhilia | <span class="title-city">Valenzuela</span></h1>
            </div>
        </a>
        <nav class="main-nav">
            <a href="/browse" class="clickable">Ordinances</a>
            <a href="/about" class="clickable">About</a>
            <a href="/login" class="clickable button">
                <?php
                if (isset($_SESSION['username'])) {
                    echo 'Welcome, ' . htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');
                } else {
                    echo 'Login';
                }
                ?>
            </a>
        </nav>
    </div>
</header>