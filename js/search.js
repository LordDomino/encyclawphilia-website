const searchBar = document.getElementById('home-pg-hero-search');
const resultsDiv = document.getElementById('results');

searchBar.addEventListener('input', async function () {
    const query = this.value;

    // Ensure there's text to prevent unnecessary requests
    if (query.length > 2) {
        // Send request to server-side PHP script
        const response = await fetch(`search.php?q=${encodeURIComponent(query)}`);

        // Get the text or JSON output from PHP
        const data = await response.text();

        // Update the HTML
        resultsDiv.innerHTML = data;
    } else {
        resultsDiv.innerHTML = '';
    }
});