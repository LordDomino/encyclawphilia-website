// Prevent auto-submitting of form
const form = document.getElementById('search-form');
const searchBar = document.getElementById('search-input');

form.addEventListener('submit', (event) => {
    event.preventDefault();
});

console.log("script");
searchBar.addEventListener('search', async function () {
    const query = escapeHtml(this.value);

    // Ensure there's text to prevent unnecessary requests
    if (query.length > 0) {
        window.location.href = "browse?q=" + query; // only do page switching
    }
});
