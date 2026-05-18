<?php
session_start();

$pageTitle   = "Login - EncycLawPhilia Valenzuela";
$currentPage = "login";

// Consume the flash message and determine which tab to activate
$authError  = $_SESSION['auth_error'] ?? null;
$activeTab  = $authError['tab'] ?? 'login';
unset($_SESSION['auth_error']);   // clear it so it doesn't persist on refresh

require_once __DIR__ . '/../head.php';
?>

<body class="<?php echo isset($currentPage) ? htmlspecialchars($currentPage) : 'default'; ?>">
    <main class="auth-page">
        <div class="login-hero">
            <div class="flex-column" id="login-hero">
                <?php require_once __DIR__ . '/../brand_title.php'; ?>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus posuere magna congue consectetur pharetra. Etiam a nibh quis tellus gravida egestas. Ut eget dictum massa, in accumsan nulla.</p>
                <p>Just want anonymous browsing? <a href="/home" class="clickable emphasis">Go back to home page.</a></p>
            </div>
            <div class="flex-column">
                <section class="auth-hero">
                    <div class="auth-container">
                        <!-- Tab Navigation -->
                        <div class="auth-tabs">
                            <button class="tab-button <?= $activeTab === 'login'  ? 'active' : '' ?>" data-tab="login">
                                <span>Login</span>
                            </button>
                            <button class="tab-button <?= $activeTab === 'signup' ? 'active' : '' ?>" data-tab="signup">
                                <span>Sign Up</span>
                            </button>
                        </div>

                        <!-- Login Form -->
                        <form class="auth-form <?= $activeTab === 'login'  ? 'active' : '' ?>"
                            id="login-form"
                            action="/login-submit"
                            method="POST"
                            data-form="login">

                            <?php if ($authError && $authError['tab'] === 'login'): ?>
                                <div class="auth-message-bar auth-message-bar--<?= htmlspecialchars($authError['type'], ENT_QUOTES, 'UTF-8') ?>"
                                    role="alert" aria-live="assertive">
                                    <div class="bar-text">
                                        <span class="bar-title"><?= htmlspecialchars($authError['title'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="bar-body"><?= htmlspecialchars($authError['body'],  ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <button class="bar-close" type="button" aria-label="Dismiss"
                                        onclick="this.parentElement.remove()">✕</button>
                                </div>
                            <?php endif; ?>

                            <div class="form-group">
                                <label for="login-email">Email Address</label>
                                <input
                                    type="email"
                                    id="login-email"
                                    name="email"
                                    placeholder="Enter your email"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="login-password">Password</label>
                                <input
                                    type="password"
                                    id="login-password"
                                    name="password"
                                    placeholder="Enter your password"
                                    required>
                            </div>

                            <div class="form-options">
                                <label class="remember-me">
                                    <input type="checkbox" name="remember">
                                    <span>Remember me</span>
                                </label>
                                <a href="#" class="forgot-password">Forgot password?</a>
                            </div>

                            <button type="submit" class="auth-button primary">
                                Login
                            </button>

                            <p class="form-footer">
                                Don't have an account?
                                <button type="button" class="switch-tab" data-tab="signup">Sign up here</button>
                            </p>
                        </form>

                        <!-- Sign Up Form -->
                        <form class="auth-form <?= $activeTab === 'signup'  ? 'active' : '' ?>"
                            id="signup-form"
                            action="php/register_process.php"
                            method="POST"
                            data-form="signup">

                            <?php if ($authError && $authError['tab'] === 'signup'): ?>
                                <div class="auth-message-bar auth-message-bar--<?= htmlspecialchars($authError['type'], ENT_QUOTES, 'UTF-8') ?>"
                                    role="alert" aria-live="assertive">
                                    <div class="bar-text">
                                        <span class="bar-title"><?= htmlspecialchars($authError['title'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="bar-body"><?= htmlspecialchars($authError['body'],  ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <button class="bar-close" type="button" aria-label="Dismiss"
                                        onclick="this.parentElement.remove()">✕</button>
                                </div>
                            <?php endif; ?>

                            <div class="form-group">
                                <label for="signup-name">Username</label>
                                <input
                                    type="text"
                                    id="signup-name"
                                    name="username"
                                    placeholder="Enter your username"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="signup-email">Email Address</label>
                                <input
                                    type="email"
                                    id="signup-email"
                                    name="email"
                                    placeholder="Enter your email"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="signup-password">Password</label>
                                <input
                                    type="password"
                                    id="signup-password"
                                    name="password"
                                    placeholder="Create a password"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="signup-confirm">Confirm Password</label>
                                <input
                                    type="password"
                                    id="signup-confirm"
                                    name="confirm_password"
                                    placeholder="Confirm your password"
                                    required>
                            </div>

                            <label class="terms-agreement">
                                <input type="checkbox" name="terms" required>
                                <span>I agree to the Terms of Service and Privacy Policy</span>
                            </label>

                            <button type="submit" class="auth-button primary">
                                Create Account
                            </button>

                            <p class="form-footer">
                                Already have an account?
                                <button type="button" class="switch-tab" data-tab="login">Login here</button>
                            </p>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
<script>
    // Tab switching functionality
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', function() {
            const tabName = this.dataset.tab;
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            document.querySelectorAll('.auth-form').forEach(form => form.classList.remove('active'));
            document.querySelector(`[data-form="${tabName}"]`).classList.add('active');
        });
    });

    // Switch tab from form footer buttons
    document.querySelectorAll('.switch-tab').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector(`[data-tab="${this.dataset.tab}"]`).click();
        });
    });
</script>

</html>