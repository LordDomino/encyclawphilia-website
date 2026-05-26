document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('nav-toggle');
    const nav    = document.getElementById('main-nav');

    if (!toggle || !nav) return;

    // Wrap all direct nav children in a single overflow container
    // so the grid-template-rows collapse animation works correctly.
    const wrapper = document.createElement('div');
    wrapper.className = 'nav-inner-wrapper';
    while (nav.firstChild) {
        wrapper.appendChild(nav.firstChild);
    }
    nav.appendChild(wrapper);

    function openNav() {
        nav.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
    }

    function closeNav() {
        nav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
    }

    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        nav.classList.contains('is-open') ? closeNav() : openNav();
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.site-header')) {
            closeNav();
        }
    });

    // Close on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && nav.classList.contains('is-open')) {
            closeNav();
            toggle.focus();
        }
    });

    // Close when a nav link is tapped (single-page navigation)
    wrapper.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', closeNav);
    });
});