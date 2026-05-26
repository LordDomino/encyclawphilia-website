document.addEventListener('DOMContentLoaded', () => {

    function renderBrowseCard(row) {
        const day = row.enactment_day != null ? String(row.enactment_day) : '';
        const month = row.enactment_month ?? '';
        const year = row.enactment_year != null ? String(row.enactment_year) : '';

        return `
            <div class="content-card animate-in">
                <div class="content-card-header">
                    <div class="card-label-group">
                        <span class="card-type">City Ordinance</span>
                        <span class="numeral-and-series">
                            No. ${escapeHtml(row.ordinance_number)}
                            s. ${escapeHtml(row.series_year)}
                        </span>
                    </div>
                    <div class="enactment-date">
                                                    Enacted ${escapeHtml(row.enactment_day ?? '')}
                                                    ${escapeHtml(row.enactment_month ?? '')}
                                                    ${escapeHtml(row.enactment_year ?? '')}
                                                </div>
                </div>
                <div class="preview-container">
                    <div class="preview-text">${escapeHtml(row.title)}</div>
                </div>
                <div class="admin-card-meta-row">
                    <span class="admin-card-meta-item"><span class="admin-card-meta-icon" aria-hidden="true">🖊</span>${escapeHtml(row.author_sponsor ?? '')}</span>
                    <span class="admin-card-meta-item"><span class="admin-card-meta-icon" aria-hidden="true">🏷</span>${escapeHtml(row.category_name ?? '(Uncategorized)')}</span>
                </div>
                <div class="card-footer">
                    <div class="card-engagement">
                        <span class="engagement-item likes">
                            <span class="engagement-icon">▲</span>
                            <span class="engagement-count">${escapeHtml(row.like_count || 0)}</span>
                        </span>
                        <span class="engagement-divider"></span>
                        <span class="engagement-item dislikes">
                            <span class="engagement-icon">▼</span>
                            <span class="engagement-count">${escapeHtml(row.dislike_count || 0)}</span>
                        </span>
                    </div>
                    <a href="/ordinance?id=${parseInt(row.ordinance_id, 10)}">
                        <p class="link">Read More</p>
                    </a>
                </div>
            </div>`;
    }

    // Initialize Engine
    SearchEngine.init({
        filterFormId: 'filter-form',
        renderCard: renderBrowseCard
    });
});