/**
 * filter-categories.js
 *
 * Fetches the Categories reference list from /api/categories and
 * populates every <select> in the filter sidebar whose
 * data-filter-key="category_id" attribute is present.
 *
 * Works on both Browse (/browse) and Admin Dashboard (/dashboard)
 * because both pages share the filter_sidebar.php partial, which
 * renders the same <select id="filter-category"> element.
 *
 * Load order (add to each page BEFORE the page-specific script):
 *
 *   Browse:
 *     <script src="js/components/filter-categories.js"></script>
 *     <script src="js/pages/browse.js"></script>
 *
 *   Admin Dashboard:
 *     <script src="js/components/filter-categories.js"></script>
 *     <script src="js/pages/admin-dashboard.js"></script>
 *
 * The module preserves any value the user has already selected (e.g.
 * when the browser restores state via popstate / URL params) by
 * re-applying the current URL's category_id param after injection.
 *
 * Dependencies: none (vanilla JS, no libraries required).
 */

const FilterCategories = (() => {

    // ------------------------------------------------------------------
    // Internal state
    // ------------------------------------------------------------------

    /** @type {Array<{category_id: number, category_name: string}>} */
    let _cache = null;

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Reads the `category_id` param from the current URL so the
     * selected option can be restored after the <select> is rebuilt.
     *
     * @returns {string} The raw param value, or '' when absent.
     */
    function _currentUrlCategoryId() {
        return new URLSearchParams(window.location.search).get('category_id') ?? '';
    }

    /**
     * Builds and injects <option> elements into a single <select>.
     *
     * @param {HTMLSelectElement} select
     * @param {Array<{category_id: number, category_name: string}>} categories
     * @param {string} selectedValue  The category_id string to pre-select.
     */
    function _populateSelect(select, categories, selectedValue) {
        // Preserve the leading "All Categories" placeholder already in the DOM.
        // Remove every option except the first (index 0) before re-injecting.
        while (select.options.length > 1) {
            select.remove(1);
        }

        categories.forEach(({ category_id, category_name }) => {
            const option = document.createElement('option');
            option.value = String(category_id);
            option.textContent = category_name;

            if (String(category_id) === selectedValue) {
                option.selected = true;
            }

            select.appendChild(option);
        });
    }

    /**
     * Finds every category <select> rendered by filter_sidebar.php and
     * populates each one using the supplied categories array.
     *
     * Targets: <select data-filter-key="category_id">
     *
     * @param {Array<{category_id: number, category_name: string}>} categories
     */
    function _injectAll(categories) {
        const selectedValue = _currentUrlCategoryId();

        const selects = document.querySelectorAll(
            'select[data-filter-key="category_id"]'
        );

        if (selects.length === 0) {
            // Sidebar not present on this page — nothing to do.
            return;
        }

        selects.forEach(select => _populateSelect(select, categories, selectedValue));
    }

    // ------------------------------------------------------------------
    // Public API
    // ------------------------------------------------------------------

    /**
     * Fetches /api/categories (with in-memory caching) and populates
     * all category filter selects on the current page.
     *
     * Safe to call multiple times — after the first successful fetch
     * the result is served from the module-level cache without a
     * second network request.
     *
     * @returns {Promise<void>}
     */
    async function init() {
        // Serve from cache after the first successful load.
        if (_cache !== null) {
            _injectAll(_cache);
            return;
        }

        try {
            const response = await fetch('/api/categories', {
                headers: {
                    'Accept': 'application/json',
                    // The assertXhr() guard in OrdinanceApiController requires this.
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const payload = await response.json();

            if (!payload.ok || !Array.isArray(payload.data)) {
                throw new Error(payload.message ?? 'Unexpected response shape.');
            }

            _cache = payload.data;
            _injectAll(_cache);

        } catch (err) {
            // Non-fatal: the sidebar already contains hard-coded fallback options
            // from filter_sidebar.php, so the filter still works even if this
            // fetch fails (e.g. during a network outage or first offline load).
            console.warn('[FilterCategories] Could not load categories from API:', err);
        }
    }

    /**
     * Returns the cached category list, or null when init() has not
     * yet completed successfully. Useful for other modules that need
     * the category list without triggering a second fetch.
     *
     * @returns {Array<{category_id: number, category_name: string}>|null}
     */
    function getCache() {
        return _cache;
    }

    return { init, getCache };

})();


// ------------------------------------------------------------------
// Auto-initialise on DOMContentLoaded.
//
// Because the module is loaded synchronously via a <script> tag
// placed before the page-specific script, the sidebar's <select>
// elements are guaranteed to be in the DOM at this point.
// ------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    FilterCategories.init();
});