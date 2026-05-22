/**
 * filter-statuses.js
 *
 * Injects status filter checkboxes into every
 * <fieldset data-filter-key="status"> rendered by filter_sidebar.php.
 *
 * Unlike categories and barangays, statuses are a fixed ENUM
 * ('Pending', 'Active', 'Repealed', 'Amended') defined in the schema
 * and never change at runtime, so no API fetch is needed — the list
 * is defined statically here. If the ENUM ever gains a new value,
 * update STATUSES below and redeploy.
 *
 * Reads the current URL's status[] params and pre-checks the
 * matching checkboxes so browser back/forward preserves filter state.
 *
 * Load order (add BEFORE the page-specific script on each page):
 *
 *   <script src="js/components/filter-categories.js"></script>
 *   <script src="js/components/filter-barangays.js"></script>
 *   <script src="js/components/filter-statuses.js"></script>
 *   <script src="js/pages/browse.js"></script>
 */

const FilterStatuses = (() => {

    /**
     * Canonical status list — mirrors the Ordinances.status ENUM exactly.
     * Each entry's `value` must match the ENUM string case-for-case.
     *
     * @type {Array<{value: string, label: string}>}
     */
    const STATUSES = [
        { value: 'Active', label: 'Active' },
        { value: 'Pending', label: 'Pending' },
        { value: 'Repealed', label: 'Repealed' },
        { value: 'Amended', label: 'Amended' },
    ];

    /**
     * Reads all `status[]` values from the current URL query string.
     *
     * @returns {Set<string>}
     */
    function _currentUrlStatuses() {
        const params = new URLSearchParams(window.location.search);
        return new Set(params.getAll('status[]'));
    }

    /**
     * Builds and injects checkbox markup into a single <fieldset>.
     * Removes any children first so the function is safely idempotent.
     *
     * @param {HTMLFieldSetElement} fieldset
     * @param {Set<string>}         activeValues
     */
    function _populateFieldset(fieldset, activeValues) {
        // Preserve the <legend> (first child); clear everything else.
        const legend = fieldset.querySelector('legend');
        fieldset.innerHTML = '';
        if (legend) fieldset.appendChild(legend);

        STATUSES.forEach(({ value, label }) => {
            /*
             * Replicates the existing HTML structure from filter_sidebar.php
             * so all existing CSS rules (.filter-checkbox-item, etc.) apply
             * without any stylesheet changes.
             */
            const labelEl = document.createElement('label');
            labelEl.className = 'filter-checkbox-item';

            const input = document.createElement('input');
            input.type = 'checkbox';
            input.className = 'filter-checkbox';
            input.name = 'status[]';
            input.value = value;
            input.dataset.statusKey = value;
            input.checked = activeValues.has(value);

            const mark = document.createElement('span');
            mark.className = 'filter-checkbox-mark';
            mark.setAttribute('aria-hidden', 'true');

            const text = document.createElement('span');
            text.className = 'filter-checkbox-text';
            text.textContent = label;

            labelEl.append(input, mark, text);
            fieldset.appendChild(labelEl);
        });
    }

    /**
     * Finds every status fieldset on the page and populates it.
     */
    function _injectAll() {
        const activeValues = _currentUrlStatuses();

        document
            .querySelectorAll('fieldset[data-filter-key="status"]')
            .forEach(fieldset => _populateFieldset(fieldset, activeValues));
    }

    /**
     * Populates all status fieldsets. Safe to call multiple times
     * (e.g. on popstate) — re-reads the URL state each time so the
     * checked state stays in sync with browser navigation.
     */
    function init() {
        _injectAll();
    }

    /**
     * Returns the canonical STATUSES array for use by other modules.
     *
     * @returns {Array<{value: string, label: string}>}
     */
    function getStatuses() {
        return STATUSES;
    }

    return { init, getStatuses };

})();


document.addEventListener('DOMContentLoaded', () => {
    FilterStatuses.init();
});