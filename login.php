

<?php
$pageTitle = "Login - EncycLawPhilia Valenzuela";
$currentPage = "login";

require_once 'fragments/head.php';
?>

<body class="<?php echo isset($currentPage) ? htmlspecialchars($currentPage) : 'default'; ?>">
    <main class="auth-page">
        <div class="login-hero">
            <div class="flex-column" id="login-hero">
                <?php include 'fragments/brand_title.php'; ?>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus posuere magna congue consectetur pharetra. Etiam a nibh quis tellus gravida egestas. Ut eget dictum massa, in accumsan nulla.</p>
                <p>Just want anonymous browsing? <a href="index.php" class="clickable emphasis">Go back to home page.</a></p>
            </div>
            <div class="flex-column" id="login-form">
                <section class="auth-hero">
                    <div class="auth-container">
                        <!-- Tab Navigation -->
                        <div class="auth-tabs">
                            <button class="tab-button active" data-tab="login">
                                <span>Login</span>
                            </button>
                            <button class="tab-button" data-tab="signup">
                                <span>Sign Up</span>
                            </button>
                        </div>

                        <!-- Login Form -->
                        <form class="auth-form active" id="login-form" data-form="login">
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
                        <form class="auth-form" id="signup-form" data-form="signup">
                            <div class="form-group">
                                <label for="signup-name">Username</label>
                                <input
                                    type="text"
                                    id="signup-name"
                                    name="name"
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

    <script>
        // Tab switching functionality
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', function() {
                const tabName = this.dataset.tab;

                // Update active tab button
                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');

                // Update active form
                document.querySelectorAll('.auth-form').forEach(form => {
                    form.classList.remove('active');
                });
                document.querySelector(`[data-form="${tabName}"]`).classList.add('active');
            });
        });

        // Switch tab from form buttons
        document.querySelectorAll('.switch-tab').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const tabName = this.dataset.tab;
                document.querySelector(`[data-tab="${tabName}"]`).click();
            });
        });
    </script>
</body>

</html>