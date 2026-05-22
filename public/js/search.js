// ============================================================
// HERO SEARCH BAR — redirects to /browse with keyword query
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    const searchForm = document.getElementById('search-form');
    const searchInput = document.getElementById('search-input');

    if (!searchForm || !searchInput) return;

    searchForm.addEventListener('submit', (e) => {
        e.preventDefault();

        const query = searchInput.value.trim();
        const target = query
            ? `/browse?q=${encodeURIComponent(query)}`
            : '/browse';

        window.location.href = target;
    });
});