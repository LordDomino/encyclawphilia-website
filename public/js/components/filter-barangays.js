/**
 * filter-barangays.js
 *
 * Fetches the Barangays reference list from /api/barangays and
 * populates every <select data-filter-key="barangay_id"> rendered
 * by the filter_sidebar.php partial.
 *
 * Mirrors the structure of filter-categories.js exactly.
 *
 * Load order (add BEFORE the page-specific script on each page):
 *
 *   <script src="js/components/filter-categories.js"></script>
 *   <script src="js/components/filter-barangays.js"></script>
 *   <script src="js/components/filter-statuses.js"></script>
 *   <script src="js/pages/browse.js"></script>
 */

const FilterBarangays = (() => {

    /** @type {Array<{barangay_id: number, barangay_name: string}>|null} */
    let _cache = null;

    function _currentUrlBarangayId() {
        return new URLSearchParams(window.location.search).get('barangay_id') ?? '';
    }

    /**
     * @param {HTMLSelectElement} select
     * @param {Array<{barangay_id: number, barangay_name: string}>} barangays
     * @param {string} selectedValue
     */
    function _populateSelect(select, barangays, selectedValue) {
        // Remove every option after the first placeholder.
        while (select.options.length > 1) {
            select.remove(1);
        }

        barangays.forEach(({ barangay_id, barangay_name }) => {
            const option = document.createElement('option');
            option.value = String(barangay_id);
            option.textContent = barangay_name;

            if (String(barangay_id) === selectedValue) {
                option.selected = true;
            }

            select.appendChild(option);
        });
    }

    function _injectAll(barangays) {
        const selectedValue = _currentUrlBarangayId();

        document
            .querySelectorAll('select[data-filter-key="barangay_id"]')
            .forEach(select => _populateSelect(select, barangays, selectedValue));
    }

    async function init() {
        if (_cache !== null) {
            _injectAll(_cache);
            return;
        }

        try {
            const response = await fetch('/api/barangays', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const payload = await response.json();

            if (!payload.ok || !Array.isArray(payload.data)) {
                throw new Error(payload.message ?? 'Unexpected response shape.');
            }

            _cache = payload.data;
            _injectAll(_cache);

        } catch (err) {
            // Non-fatal: sidebar still renders with only the placeholder option.
            console.warn('[FilterBarangays] Could not load barangays from API:', err);
        }
    }

    /** @returns {Array<{barangay_id: number, barangay_name: string}>|null} */
    function getCache() {
        return _cache;
    }

    return { init, getCache };

})();


document.addEventListener('DOMContentLoaded', () => {
    FilterBarangays.init();
});