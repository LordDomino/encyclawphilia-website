// Add this helper near the top of the SearchEngine module
function buildSkeletonHTML() {
    return Array.from({ length: 6 }, () => `
            <div class="content-card content-card--skeleton" aria-hidden="true">
                <div class="content-card-header">
                    <div class="card-label-group">
                        <span class="skeleton-line" style="width:40%"></span>
                        <span class="skeleton-line" style="width:55%"></span>
                    </div>
                    <div class="skeleton-block"></div>
                </div>
                <div class="preview-container">
                    <div class="skeleton-line" style="width:90%"></div>
                    <div class="skeleton-line" style="width:70%"></div>
                </div>
                <div class="card-footer">
                    <div class="skeleton-line" style="width:25%"></div>
                </div>
            </div>`
    ).join('');
}

const SearchEngine = (() => {
    let config = {
        formId: 'search-form',
        gridId: 'results-grid',
        perPage: 20,
        renderCard: (row) => '', // Injected by page script
        onComplete: (payload) => { } // Hook for post-render (e.g. updating bulk tools)
    };

    let isFetching = false;
    let form, grid, topPagination, bottomPagination, resultCount, resultsTitle;

    // -- URL & DOM Syncing --
    function applyUrlToForm() {
        const params = new URLSearchParams(window.location.search);
        form.reset(); // Reset DOM to default before applying

        for (const [key, value] of params.entries()) {
            const inputs = form.querySelectorAll(`[name="${key}"]`);
            inputs.forEach(input => {
                if (input.type === 'checkbox' || input.type === 'radio') {
                    if (input.value === value) input.checked = true;
                } else {
                    input.value = value;
                }
            });
        }
    }

    function pushStateFromForm(page) {
        const formData = new FormData(form);
        const params = new URLSearchParams();

        for (const [key, value] of formData.entries()) {
            if (value.trim() !== '' && value !== 'desc') { // Ignore defaults to keep URL clean
                params.append(key, value);
            }
        }
        if (page > 1) params.set('page', page);

        const newUrl = `${window.location.pathname}?${params.toString()}`;
        window.history.pushState({ path: newUrl }, '', newUrl);
        return params; // Return params to pass to API
    }

    // -- API Interaction --
    async function fetchPage(page = 1) {
        if (isFetching) return;
        isFetching = true;

        grid.innerHTML = buildSkeletonHTML();

        const params = pushStateFromForm(page);
        params.set('limit', config.perPage);

        try {
            const res = await fetch(`/api/ordinances/search?${params.toString()}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!res.ok) throw new Error(`HTTP Error ${res.status}`);

            const payload = await res.json();
            if (!payload.ok && payload.success === false) throw new Error(payload.message);

            renderResults(payload.data, params.get('q'));
            if (config.onComplete) config.onComplete(payload.data);

        } catch (err) {
            console.error('[SearchEngine Error]:', err);
            grid.innerHTML = `<div class="no-results"><p>Failed to load records. Please try again.</p></div>`;
        } finally {
            isFetching = false;
        }
    }

    // -- Rendering --
    function renderResults(data, queryStr) {
        const { results, pagination } = data;

        // Render Cards
        if (!results || results.length === 0) {
            grid.innerHTML = `<div class="no-results"><p>No matching ordinances found.</p></div>`;
        } else {
            grid.innerHTML = results.map(row => config.renderCard(row)).join('');
        }

        // Render Headers
        if (resultsTitle) {
            resultsTitle.textContent = queryStr ? `Search Results for "${queryStr}"` : (resultsTitle.dataset.default || 'All Ordinances');
        }

        if (resultCount) {
            const total = pagination.total || 0;
            const from = total === 0 ? 0 : (pagination.current_page - 1) * config.perPage + 1;
            const to = Math.min(pagination.current_page * config.perPage, total);
            resultCount.textContent = total > 0
                ? `Showing ${from}–${to} of ${total.toLocaleString()} result${total !== 1 ? 's' : ''}`
                : '(0 results)';
        }

        // Render Pagination
        const pgHtml = PaginationHelper.buildHTML(pagination.current_page, pagination.total_pages);
        [topPagination, bottomPagination].forEach(bar => {
            if (bar) {
                bar.innerHTML = pgHtml;
                bar.querySelectorAll('.pg-btn[data-page]').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        fetchPage(parseInt(btn.dataset.page, 10));
                        document.querySelector('.results-content').scrollIntoView({ behavior: 'smooth' });
                    });
                });
            }
        });
    }

    // -- Initialization --
    function init(customConfig) {
        config = { ...config, ...customConfig };

        form = document.getElementById(config.formId);
        grid = document.getElementById(config.gridId);
        topPagination = document.getElementById('pagination-bar-top');
        bottomPagination = document.getElementById('pagination-bar-bottom');
        resultCount = document.getElementById('result-count');
        resultsTitle = document.querySelector('.results-header-title');

        if (resultsTitle && !resultsTitle.dataset.default) {
            resultsTitle.dataset.default = resultsTitle.textContent;
        }

        if (!form || !grid) return;

        // Apply URL parameters to form inputs
        applyUrlToForm();

        // Event Listeners (Delegated change listener catches all selects/checkboxes/radios)
        form.addEventListener('submit', (e) => { e.preventDefault(); fetchPage(1); });
        form.addEventListener('change', () => fetchPage(1));

        // Listen to browser back/forward buttons
        window.addEventListener('popstate', () => {
            applyUrlToForm();
            fetchPage(new URLSearchParams(window.location.search).get('page') || 1);
        });

        // Initial Load
        const initialPage = parseInt(new URLSearchParams(window.location.search).get('page'), 10) || 1;
        fetchPage(initialPage);
    }

    return { init, reload: () => fetchPage(1) };
})();