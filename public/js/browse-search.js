// public/js/browse-search.js

const BrowseSearch = (() => {
    // ── Constants ──────────────────────────────────────────────────────
    const PER_PAGE = 20;
    const PAGE_PARAM = 'page';
    const QUERY_PARAM = 'q';

    // ── State ──────────────────────────────────────────────────────────
    let currentQuery = '';
    let currentPage = 1;
    let totalPages = 1;
    let isFetching = false;

    // ── DOM refs ───────────────────────────────────────────────────────
    const searchForm = document.getElementById('browse-search-form');
    const searchInput = document.getElementById('browse-pg-search-bar');
    const resultsGrid = document.getElementById('results-grid');
    const resultCount = document.getElementById('result-count');
    const resultsTitle = document.querySelector('.results-header-title');
    const paginationTop = document.getElementById('pagination-bar-top');
    const paginationBottom = document.getElementById('pagination-bar-bottom');

    // ── URL helpers ────────────────────────────────────────────────────

    function readUrlState() {
        const params = new URLSearchParams(window.location.search);
        return {
            query: params.get(QUERY_PARAM)?.trim() ?? '',
            page: Math.max(1, parseInt(params.get(PAGE_PARAM) ?? '1', 10)),
        };
    }

    function pushUrlState(query, page) {
        const url = new URL(window.location);
        if (query) {
            url.searchParams.set(QUERY_PARAM, query);
        } else {
            url.searchParams.delete(QUERY_PARAM);
        }
        if (page > 1) {
            url.searchParams.set(PAGE_PARAM, String(page));
        } else {
            url.searchParams.delete(PAGE_PARAM);
        }
        window.history.pushState({ query, page }, '', url);
    }

    // ── API ────────────────────────────────────────────────────────────

    async function fetchOrdinances(query, page) {
        const params = new URLSearchParams({ limit: PER_PAGE, page });
        if (query) params.set(QUERY_PARAM, query);

        const res = await fetch(`/api/ordinances/search?${params}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const payload = await res.json();
        if (!payload.ok) throw new Error(payload.message ?? 'API error');

        return payload.data; // { results, pagination }
    }

    // ── HTML templates ─────────────────────────────────────────────────

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function buildCardHTML(row) {
        const day = row.enactment_day != null ? String(row.enactment_day) : '';
        const month = row.enactment_month ?? '';
        const year = row.enactment_year != null ? String(row.enactment_year) : '';

        return `
            <div class="content-card">
                <div class="content-card-header">
                    <div class="card-label-group">
                        <span class="card-type">City Ordinance</span>
                        <span class="numeral-and-series">
                            No. ${escHtml(row.ordinance_number)}
                            s. ${escHtml(row.series_year)}
                        </span>
                    </div>
                    <div class="date">
                        <span class="date-day">${escHtml(day)}</span>
                        <div class="date-meta">
                            <span class="date-month">${escHtml(month)}</span>
                            <span class="date-year">${escHtml(year)}</span>
                        </div>
                    </div>
                </div>
                <div class="preview-container">
                    <div class="preview-text">${escHtml(row.title)}</div>
                </div>
                <div class="card-footer">
                    <div class="card-engagement">
                        <span class="engagement-item likes">
                            <span class="engagement-icon">▲</span>
                            <span class="engagement-count">${escHtml(row.like_count)}</span>
                        </span>
                        <span class="engagement-divider"></span>
                        <span class="engagement-item dislikes">
                            <span class="engagement-icon">▼</span>
                            <span class="engagement-count">${escHtml(row.dislike_count)}</span>
                        </span>
                    </div>
                    <a href="/ordinance?id=${parseInt(row.ordinance_id, 10)}">
                        <p class="link">Read More</p>
                    </a>
                </div>
            </div>`;
    }

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

    // ── Pagination bar builder ─────────────────────────────────────────
    //
    // Renders: « Prev  1 … 4 5 6 … 12  Next »
    // Window of 5 pages centred on currentPage, with leading/trailing
    // ellipsis when the total exceeds the window.

    function buildPaginationHTML(current, total) {
        if (total <= 1) return '';

        const items = [];

        // Previous
        items.push(
            current > 1
                ? `<button class="pg-btn pg-prev" data-page="${current - 1}" aria-label="Previous page">&#8592; Prev</button>`
                : `<span class="pg-btn pg-prev pg-disabled" aria-disabled="true">&#8592; Prev</span>`
        );

        // Page number window
        const pages = buildPageWindow(current, total);

        pages.forEach(p => {
            if (p === '…') {
                items.push(`<span class="pg-ellipsis" aria-hidden="true">…</span>`);
            } else {
                items.push(
                    p === current
                        ? `<button class="pg-btn pg-num pg-current" data-page="${p}" aria-current="page" aria-label="Page ${p}">${p}</button>`
                        : `<button class="pg-btn pg-num" data-page="${p}" aria-label="Go to page ${p}">${p}</button>`
                );
            }
        });

        // Next
        items.push(
            current < total
                ? `<button class="pg-btn pg-next" data-page="${current + 1}" aria-label="Next page">Next &#8594;</button>`
                : `<span class="pg-btn pg-next pg-disabled" aria-disabled="true">Next &#8594;</span>`
        );

        return items.join('');
    }

    // Returns an array of page numbers and '…' ellipsis markers.
    // Always shows first, last, and a window of 5 centred on current.
    function buildPageWindow(current, total) {
        if (total <= 7) {
            return Array.from({ length: total }, (_, i) => i + 1);
        }

        const delta = 2; // pages either side of current
        const window = new Set([1, total]);

        for (let i = Math.max(2, current - delta); i <= Math.min(total - 1, current + delta); i++) {
            window.add(i);
        }

        const sorted = [...window].sort((a, b) => a - b);
        const result = [];

        sorted.forEach((p, i) => {
            if (i > 0 && p - sorted[i - 1] > 1) result.push('…');
            result.push(p);
        });

        return result;
    }

    // ── Render helpers ─────────────────────────────────────────────────

    function renderPaginationBars(current, total) {
        const html = buildPaginationHTML(current, total);
        [paginationTop, paginationBottom].forEach(bar => {
            if (!bar) return;
            bar.innerHTML = html;
            bar.querySelectorAll('.pg-btn[data-page]').forEach(btn => {
                btn.addEventListener('click', () => {
                    navigateToPage(parseInt(btn.dataset.page, 10));
                });
            });
        });
    }

    function clearPaginationBars() {
        [paginationTop, paginationBottom].forEach(bar => {
            if (bar) bar.innerHTML = '';
        });
    }

    function updateHeader(query, total, current, pages) {
        if (resultsTitle) {
            resultsTitle.textContent = query
                ? `Search Results for "${query}"`
                : 'All Ordinances';
        }
        if (resultCount) {
            const from = total === 0 ? 0 : (current - 1) * PER_PAGE + 1;
            const to = Math.min(current * PER_PAGE, total);
            resultCount.textContent = total > 0
                ? `Showing ${from}–${to} of ${total.toLocaleString()} result${total !== 1 ? 's' : ''}`
                : '(0 results)';
        }
    }

    function attachCardAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, i) => {
                if (entry.isIntersecting) {
                    setTimeout(() => entry.target.classList.add('animate-in'), i * 50);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

        resultsGrid.querySelectorAll('.content-card').forEach(c => observer.observe(c));
    }

    // ── Core navigation ────────────────────────────────────────────────

    async function navigateToPage(page) {
        if (isFetching || page === currentPage) return;
        await loadPage(currentQuery, page, true);
    }

    async function loadPage(query, page, scrollToTop = false) {
        if (isFetching) return;

        isFetching = true;

        // Show skeletons and clear pagination during fetch
        resultsGrid.innerHTML = buildSkeletonHTML();
        clearPaginationBars();

        try {
            const { results, pagination } = await fetchOrdinances(query, page);

            currentQuery = query;
            currentPage = pagination.current_page;
            totalPages = pagination.total_pages;

            updateHeader(query, pagination.total, currentPage, totalPages);
            pushUrlState(query, currentPage);

            if (!results.length) {
                resultsGrid.innerHTML = `<div class="no-results">
                    <p>No matching ordinances found. Try adjusting your search.</p>
                </div>`;
            } else {
                resultsGrid.innerHTML = results.map(buildCardHTML).join('');
                attachCardAnimations();
            }

            renderPaginationBars(currentPage, totalPages);

            if (scrollToTop) {
                document.getElementById('browse-all-ordinances')
                    ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        } catch (err) {
            resultsGrid.innerHTML = `<div class="no-results">
                <p>Something went wrong. Please try again.</p>
            </div>`;
            console.error('[BrowseSearch] loadPage:', err);
        } finally {
            isFetching = false;
        }
    }

    // ── Search form handler ────────────────────────────────────────────

    function handleSearchSubmit() {
        const query = searchInput.value.trim();
        // Reset to page 1 whenever a new search is submitted
        loadPage(query, 1, false);
    }

    // ── Browser back/forward support ───────────────────────────────────

    function handlePopState(event) {
        const state = event.state;
        if (state) {
            searchInput.value = state.query ?? '';
            loadPage(state.query ?? '', state.page ?? 1, false);
        } else {
            // Fallback: re-read from URL
            const { query, page } = readUrlState();
            searchInput.value = query;
            loadPage(query, page, false);
        }
    }

    // ── Init ───────────────────────────────────────────────────────────

    function init() {
        if (!searchInput || !resultsGrid) return;

        const { query: initialQuery, page: initialPage } = readUrlState();
        searchInput.value = initialQuery;

        // Search fires only on form submit (Enter key or button click) — not on input event.
        // This prevents mid-navigation grid resets while the user is still typing.
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                handleSearchSubmit();
            });
        }

        // Back/forward navigation
        window.addEventListener('popstate', handlePopState);

        // Initial load — replace current history entry so back-button works correctly
        const url = new URL(window.location);
        window.history.replaceState(
            { query: initialQuery, page: initialPage },
            '',
            url
        );

        loadPage(initialQuery, initialPage, false);
    }

    return { init };
})();

document.addEventListener('DOMContentLoaded', BrowseSearch.init);