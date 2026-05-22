// ============================================================
// TAB SWITCHING
// ============================================================
document.querySelectorAll('.tab-button').forEach(button => {
    button.addEventListener('click', function () {
        const tabName = this.dataset.tab;
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
        document.querySelectorAll('.auth-form').forEach(form => form.classList.remove('active'));
        document.querySelector(`[data-form="${tabName}"]`).classList.add('active');
    });
});

document.querySelectorAll('.switch-tab').forEach(button => {
    button.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelector(`[data-tab="${this.dataset.tab}"]`).click();
    });
});


// ============================================================
// MESSAGE BAR
// ============================================================

/**
 * Injects or replaces the auth message bar inside a given form.
 *
 * @param {HTMLElement} formEl   - The <form> element to target.
 * @param {'error'|'warning'|'info'|'success'} type
 * @param {string} title
 * @param {string} body
 */
function showMessageBar(formEl, type, title, body) {
    // Remove any existing bar in this form first
    formEl.querySelector('.auth-message-bar')?.remove();

    const bar = document.createElement('div');
    bar.className = `auth-message-bar auth-message-bar--${type}`;
    bar.setAttribute('role', 'alert');
    bar.setAttribute('aria-live', 'assertive');
    bar.innerHTML = `
        <div class="bar-text">
            <span class="bar-title">${escapeHtml(title)}</span>
            <span class="bar-body">${escapeHtml(body)}</span>
        </div>
        <button class="bar-close" type="button" aria-label="Dismiss"
                onclick="this.parentElement.remove()">✕</button>
    `;

    // Insert at the top of the form, before the first child
    formEl.insertBefore(bar, formEl.firstChild);
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}


// ============================================================
// LOGIN
// ============================================================
const loginForm = document.getElementById('login-form');

loginForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    const email    = document.getElementById('login-email').value.trim();
    const password = document.getElementById('login-password').value;

    await submitLogin(email, password);
});

async function submitLogin(email, password) {
    const body = new FormData();
    body.append('email', email);
    body.append('password', password);

    try {
        const res     = await fetch('/login-submit', { method: 'POST', body });
        const payload = await res.json();

        if (payload.ok) {
            window.location.href = '/home';
            return;
        }

        showMessageBar(
            loginForm,
            'error',
            'Login failed',
            payload.message ?? 'Incorrect email or password. Please try again.'
        );

    } catch {
        showMessageBar(loginForm, 'error', 'Login failed', 'Network error. Please check your connection.');
    }
}


// ============================================================
// SIGN UP
// ============================================================
const signupForm = document.getElementById('signup-form');

signupForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    const username        = document.getElementById('signup-name').value.trim();
    const email           = document.getElementById('signup-email').value.trim();
    const password        = document.getElementById('signup-password').value;
    const confirmPassword = document.getElementById('signup-confirm').value;
    const terms           = document.querySelector('[name="terms"]').checked;

    // Client-side guard: password match
    if (password !== confirmPassword) {
        showMessageBar(signupForm, 'error', 'Signup failed', 'Password confirmation does not match.');
        return;
    }

    if (!terms) {
        showMessageBar(signupForm, 'error', 'Signup failed', 'You must accept the Terms of Service to proceed.');
        return;
    }

    await submitSignup(username, email, password, confirmPassword);
});

async function submitSignup(username, email, password, confirmPassword) {
    const body = new FormData();
    body.append('username',         username);
    body.append('email',            email);
    body.append('password',         password);
    body.append('confirm_password', confirmPassword);
    body.append('terms',            '1');

    try {
        const res     = await fetch('/signup-submit', { method: 'POST', body });
        const payload = await res.json();

        if (payload.ok) {
            // Switch to the login tab and surface a success banner there
            document.querySelector('[data-tab="login"]').click();
            showMessageBar(
                loginForm,
                'success',
                'Registration successful',
                'You may now log in with your credentials.'
            );
            return;
        }

        showMessageBar(
            signupForm,
            'error',
            'Signup failed',
            payload.message ?? 'An unexpected error occurred during registration.'
        );

    } catch {
        showMessageBar(signupForm, 'error', 'Signup failed', 'Network error. Please check your connection.');
    }
}