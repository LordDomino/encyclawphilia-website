<?php

namespace App\Views\pages;

session_start();

// Guard: redirect unauthenticated visitors to the login page
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$pageTitle   = "My Account | EncycLawPhilia Valenzuela";
$currentPage = "account";

// Consume any flash messages set by update routines
$flashMessage = $_SESSION['account_flash'] ?? null;
unset($_SESSION['account_flash']);

// ── Safely surface session identity ──────────────────────────────────────────
$sessionUsername = htmlspecialchars($_SESSION['username'] ?? 'User',    ENT_QUOTES, 'UTF-8');
$sessionEmail    = htmlspecialchars($_SESSION['email']    ?? '',         ENT_QUOTES, 'UTF-8');
$sessionRoleId   = (int) ($_SESSION['role_id'] ?? 4);

// Map role_id to a human-readable label (mirrors seed_data.sql roles)
$roleLabels = [
    1 => 'Administrator',
    2 => 'Moderator',
    3 => 'Legislator',
    4 => 'Citizen',
    5 => 'Guest',
];
$roleLabel = $roleLabels[$sessionRoleId] ?? 'Citizen';

// Derive initials for the avatar from username
$nameParts = explode(' ', $_SESSION['username'] ?? 'U');
$initials   = strtoupper(
    substr($nameParts[0], 0, 1) .
    (count($nameParts) > 1 ? substr(end($nameParts), 0, 1) : '')
);

require_once __DIR__ . '/../head.php';
?>

<body class="<?php echo htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8'); ?>">

    <?php require_once __DIR__ . '/../header.php'; ?>

    <!-- ================================================================
         SUBHERO — page banner (mirrors ordinance.php / browse.php)
    ================================================================ -->
    <div class="subhero account-subhero">
        <div class="subhero-content">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="/home">Home</a>
                <span class="separator" aria-hidden="true">›</span>
                <span aria-current="page">My Account</span>
            </nav>
            <h1>My Account</h1>
            <p>Manage your profile, review your credentials, and update your display settings.</p>
        </div>
    </div>

    <!-- ================================================================
         MAIN CONTENT
    ================================================================ -->
    <main class="account-page">

        <?php if ($flashMessage): ?>
            <div class="account-flash-wrap" role="alert" aria-live="assertive">
                <div class="auth-message-bar auth-message-bar--<?php echo htmlspecialchars($flashMessage['type'], ENT_QUOTES, 'UTF-8'); ?> account-flash-bar">
                    <div class="bar-text">
                        <span class="bar-title"><?php echo htmlspecialchars($flashMessage['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="bar-body"><?php  echo htmlspecialchars($flashMessage['body'],  ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <button class="bar-close" type="button" aria-label="Dismiss"
                            onclick="this.closest('.account-flash-wrap').remove()">✕</button>
                </div>
            </div>
        <?php endif; ?>

        <div class="account-layout">

            <!-- ── LEFT: Identity card ──────────────────────────── -->
            <aside class="account-identity-col" aria-label="Account identity">

                <!-- Avatar -->
                <div class="account-avatar" aria-hidden="true">
                    <?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?>
                </div>

                <!-- Display name + role badge -->
                <div class="account-identity-meta">
                    <span class="account-display-name"><?php echo $sessionUsername; ?></span>
                    <span class="account-role-badge account-role-badge--<?php echo strtolower($roleLabel); ?>">
                        <?php echo htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>

                <hr class="ord-divider account-divider" />

                <!-- Immutable email block -->
                <div class="account-field-block">
                    <span class="account-field-label">Email Address</span>
                    <div class="account-email-row">
                        <span class="account-field-value account-field-value--email">
                            <?php
                            // Render the stored email, falling back to a prompt if absent
                            if ($sessionEmail !== '') {
                                echo $sessionEmail;
                            } else {
                                echo '<em class="account-field-empty">Not on record</em>';
                            }
                            ?>
                        </span>
                        <span class="account-lock-badge" title="Email cannot be changed after registration" aria-label="Email is immutable">
                            🔒
                        </span>
                    </div>
                    <p class="account-field-hint">
                        Your email address is permanent and cannot be modified through this interface.
                        Contact an administrator if a correction is required.
                    </p>
                </div>

                <hr class="ord-divider account-divider" />

                <!-- Quick-stat row -->
                <div class="account-stats-row" aria-label="Account statistics">
                    <div class="account-stat">
                        <span class="account-stat-value" id="stat-comments">—</span>
                        <span class="account-stat-label">Comments</span>
                    </div>
                    <div class="account-stat-divider" aria-hidden="true"></div>
                    <div class="account-stat">
                        <span class="account-stat-value" id="stat-reactions">—</span>
                        <span class="account-stat-label">Reactions</span>
                    </div>
                </div>

                <a href="/logout-submit" class="logout">Logout</a>

            </aside>

            <!-- ── RIGHT: Edit panel ─────────────────────────────── -->
            <section class="account-edit-col" aria-labelledby="edit-heading">

                <h2 id="edit-heading" class="account-section-heading">Edit Account</h2>
                <p class="account-section-sub">
                    Update your username or change your password below.
                    Fields left blank will not be modified.
                </p>

                <!-- ── Username update form ──────────────────────── -->
                <div class="account-form-card" id="form-username">
                    <div class="account-form-card-header">
                        <h3 class="account-form-card-title">Username</h3>
                        <span class="account-form-card-badge">Editable</span>
                    </div>

                    <form
                        class="account-form"
                        action="/account/update-username"
                        method="POST"
                        novalidate>

                        <div class="form-group">
                            <label for="new-username">New Username</label>
                            <input
                                type="text"
                                id="new-username"
                                name="username"
                                placeholder="<?php echo $sessionUsername; ?>"
                                autocomplete="username"
                                minlength="3"
                                maxlength="100"
                                aria-describedby="username-hint" />
                            <span class="account-field-hint" id="username-hint">
                                3 – 100 characters. Your current username is
                                <strong><?php echo $sessionUsername; ?></strong>.
                            </span>
                        </div>

                        <div class="account-form-footer">
                            <button
                                type="submit"
                                class="auth-button primary account-submit-btn"
                                id="submit-username">
                                Update Username
                            </button>
                        </div>

                    </form>
                </div>

                <!-- ── Password update form ──────────────────────── -->
                <div class="account-form-card" id="form-password">
                    <div class="account-form-card-header">
                        <h3 class="account-form-card-title">Password</h3>
                        <span class="account-form-card-badge">Editable</span>
                    </div>

                    <form
                        class="account-form"
                        action="/account/update-password"
                        method="POST"
                        novalidate
                        id="password-form">

                        <div class="form-group">
                            <label for="current-password">Current Password</label>
                            <input
                                type="password"
                                id="current-password"
                                name="current_password"
                                placeholder="Enter your current password"
                                autocomplete="current-password"
                                required />
                        </div>

                        <div class="form-group">
                            <label for="new-password">New Password</label>
                            <input
                                type="password"
                                id="new-password"
                                name="new_password"
                                placeholder="Create a new password"
                                autocomplete="new-password"
                                minlength="8"
                                required
                                aria-describedby="password-strength-hint" />

                            <!-- Password strength meter -->
                            <div class="account-pwd-strength" id="pwd-strength" aria-live="polite" aria-label="Password strength">
                                <div class="account-pwd-bar">
                                    <div class="account-pwd-bar-fill" id="pwd-bar-fill"></div>
                                </div>
                                <span class="account-pwd-label" id="pwd-label"></span>
                            </div>
                            <span class="account-field-hint" id="password-strength-hint">
                                Minimum 8 characters. Use a mix of letters, numbers, and symbols.
                            </span>
                        </div>

                        <div class="form-group">
                            <label for="confirm-new-password">Confirm New Password</label>
                            <input
                                type="password"
                                id="confirm-new-password"
                                name="confirm_new_password"
                                placeholder="Repeat your new password"
                                autocomplete="new-password"
                                required />
                            <span class="account-field-hint account-match-hint" id="match-hint" aria-live="polite"></span>
                        </div>

                        <div class="account-form-footer">
                            <button
                                type="submit"
                                class="auth-button primary account-submit-btn"
                                id="submit-password"
                                disabled>
                                Update Password
                            </button>
                        </div>

                    </form>
                </div>

                <!-- ── Danger zone ────────────────────────────────── -->
                <div class="account-danger-zone" id="danger-zone" aria-labelledby="danger-heading">
                    <h3 id="danger-heading" class="account-danger-heading">Danger Zone</h3>
                    <p class="account-danger-sub">
                        Account deactivation is permanent and irreversible.
                        Your public comments and reactions will remain associated with an anonymised record.
                    </p>
                    <button
                        type="button"
                        class="account-deactivate-btn"
                        id="btn-deactivate"
                        aria-haspopup="dialog">
                        Deactivate Account
                    </button>
                </div>

            </section>

        </div><!-- /.account-layout -->

    </main>

    <!-- ================================================================
         DEACTIVATION CONFIRMATION MODAL
    ================================================================ -->
    <div
        class="auth-modal-backdrop"
        id="deactivate-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-title"
        aria-describedby="modal-desc">

        <div class="auth-modal">
            <div class="auth-modal--header">
                <div class="modal-icon" aria-hidden="true">⚠</div>
                <span class="modal-title" id="modal-title">Deactivate Account</span>
                <button class="modal-close" type="button" id="modal-close" aria-label="Close">✕</button>
            </div>
            <div class="auth-modal--body">
                <p id="modal-desc">
                    You are about to permanently deactivate
                    <strong><?php echo $sessionUsername; ?></strong>.
                    This action cannot be undone and your account cannot be restored.
                </p>
                <p class="modal-sub">
                    All ordinance reactions and comments you have submitted will remain
                    on the platform under an anonymised identity.
                </p>
                <div class="auth-modal--actions">
                    <button class="btn-modal-dismiss" type="button" id="modal-cancel">Cancel</button>
                    <form action="/account/deactivate" method="POST" style="display:inline;">
                        <button class="btn-modal-retry account-confirm-deactivate" type="submit">
                            Yes, Deactivate
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <?php require_once __DIR__ . '/../footer.php'; ?>

</body>

<script>
// ================================================================
// PASSWORD STRENGTH METER
// ================================================================
const newPasswordInput  = document.getElementById('new-password');
const confirmInput      = document.getElementById('confirm-new-password');
const pwdBarFill        = document.getElementById('pwd-bar-fill');
const pwdLabel          = document.getElementById('pwd-label');
const matchHint         = document.getElementById('match-hint');
const submitPwdBtn      = document.getElementById('submit-password');

function scorePassword(pwd) {
    if (!pwd) return 0;
    let score = 0;
    if (pwd.length >= 8)  score++;
    if (pwd.length >= 12) score++;
    if (/[A-Z]/.test(pwd) && /[a-z]/.test(pwd)) score++;
    if (/[0-9]/.test(pwd)) score++;
    if (/[^A-Za-z0-9]/.test(pwd)) score++;
    return Math.min(score, 4);
}

const strengthLabels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
const strengthColors = [
    '',
    'var(--clr-danger)',
    'var(--clr-warn-amber)',
    'var(--clr-ok-ink)',
    'var(--clr-ok-ink)'
];

function updatePasswordUI() {
    const pwd    = newPasswordInput.value;
    const score  = scorePassword(pwd);
    const confirm = confirmInput.value;

    // Strength bar
    if (pwd.length > 0) {
        pwdBarFill.dataset.strength = score;
        pwdBarFill.style.background = strengthColors[score];
        pwdLabel.textContent        = strengthLabels[score];
        pwdLabel.style.color        = strengthColors[score];
    } else {
        pwdBarFill.style.width    = '0%';
        pwdBarFill.dataset.strength = '';
        pwdLabel.textContent      = '';
    }

    // Match hint
    validateMatch(pwd, confirm);
}

function validateMatch(pwd, confirm) {
    if (!confirm.length) {
        matchHint.textContent  = '';
        matchHint.className    = 'account-field-hint account-match-hint';
        submitPwdBtn.disabled  = true;
        return;
    }

    if (pwd === confirm) {
        matchHint.textContent  = '✓ Passwords match';
        matchHint.className    = 'account-field-hint account-match-hint account-match-hint--ok';
        submitPwdBtn.disabled  = false;
    } else {
        matchHint.textContent  = '✗ Passwords do not match';
        matchHint.className    = 'account-field-hint account-match-hint account-match-hint--error';
        submitPwdBtn.disabled  = true;
    }
}

newPasswordInput.addEventListener('input',  updatePasswordUI);
confirmInput.addEventListener('input', () => {
    validateMatch(newPasswordInput.value, confirmInput.value);
});

// ================================================================
// CLIENT-SIDE FORM VALIDATION — USERNAME
// ================================================================
document.querySelector('#form-username .account-form')
    .addEventListener('submit', function (e) {
        const val = document.getElementById('new-username').value.trim();
        if (val.length > 0 && val.length < 3) {
            e.preventDefault();
            document.getElementById('new-username').classList.add('input--error');
        }
    });

// ================================================================
// DEACTIVATION MODAL
// ================================================================
const modal        = document.getElementById('deactivate-modal');
const btnOpen      = document.getElementById('btn-deactivate');
const btnClose     = document.getElementById('modal-close');
const btnCancel    = document.getElementById('modal-cancel');

function openModal()  {
    modal.classList.add('active');
    btnClose.focus();
}

function closeModal() {
    modal.classList.remove('active');
    btnOpen.focus();
}

btnOpen.addEventListener('click', openModal);
btnClose.addEventListener('click', closeModal);
btnCancel.addEventListener('click', closeModal);

// Close on backdrop click
modal.addEventListener('click', (e) => {
    if (e.target === modal) closeModal();
});

// Close on Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.classList.contains('active')) closeModal();
});

// ================================================================
// CHAR COUNT — username field (progressive enhancement)
// ================================================================
const usernameInput = document.getElementById('new-username');
usernameInput.addEventListener('input', function () {
    this.classList.remove('input--error');
});
</script>

</html>