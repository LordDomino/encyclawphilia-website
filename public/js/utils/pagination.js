const PaginationHelper = (() => {
    function buildPageWindow(current, total) {
        if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);

        const delta = 2;
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

    function buildHTML(current, total) {
        if (total <= 1) return '';

        const items = [];
        items.push(
            current > 1
                ? `<button class="pg-btn pg-prev" data-page="${current - 1}" aria-label="Previous page">&#8592; Prev</button>`
                : `<span class="pg-btn pg-prev pg-disabled" aria-disabled="true">&#8592; Prev</span>`
        );

        buildPageWindow(current, total).forEach(p => {
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

        items.push(
            current < total
                ? `<button class="pg-btn pg-next" data-page="${current + 1}" aria-label="Next page">Next &#8594;</button>`
                : `<span class="pg-btn pg-next pg-disabled" aria-disabled="true">Next &#8594;</span>`
        );

        return items.join('');
    }

    return { buildHTML };
})();