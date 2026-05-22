<div class="filter-sidebar-header">
    <h2 class="filter-sidebar-title">Filters</h2>
    <button class="filter-clear-all" id="filter-clear-all" type="button" aria-label="Clear all filters">
        Clear all
    </button>
</div>

<div class="filter-sidebar-body" id="filter-sidebar-body">
    <form class="filter-sidebar-body" id="filter-form">
        <!-- ── Category ────────────────────────────────── -->
        <div class="filter-block" id="filter-block-category">
            <button
                class="filter-block-toggle"
                type="button"
                aria-expanded="true"
                aria-controls="filter-panel-category">
                <span class="filter-block-label">Category</span>
                <span class="filter-block-chevron" aria-hidden="true">▾</span>
            </button>
            <div class="filter-block-panel" id="filter-panel-category">
                <select
                    class="filter-select"
                    id="filter-category"
                    name="category_id"
                    data-filter-key="category_id"
                    aria-label="Filter by category">
                    <!-- JS-injectable: statuses can be added/removed here -->
                    <option value="">All Categories</option>
                </select>
            </div>
        </div>

        <!-- ── Barangays (TO DO) ─────────────────────────── -->
        <div class="filter-block" id="filter-block-barangay">
            <button
                class="filter-block-toggle"
                type="button"
                aria-expanded="true"
                aria-controls="filter-panel-barangay">
                <span class="filter-block-label">Barangay</span>
                <span class="filter-block-chevron" aria-hidden="true">▾</span>
            </button>
            <div class="filter-block-panel" id="filter-panel-barangay">
                <select
                    class="filter-select"
                    id="filter-barangay"
                    name="barangay_id"
                    data-filter-key="barangay_id"
                    aria-label="Filter by Barangay">
                    <!-- JS-injectable: statuses can be added/removed here -->
                    <option value="">Unspecified / City-wide</option>
                </select>
            </div>
        </div>

        <!-- ── Date range ──────────────────────────────── -->
        <div class="filter-block" id="filter-block-date">
            <button
                class="filter-block-toggle"
                type="button"
                aria-expanded="true"
                aria-controls="filter-panel-date">
                <span class="filter-block-label">Date Enacted</span>
                <span class="filter-block-chevron" aria-hidden="true">▾</span>
            </button>
            <div class="filter-block-panel" id="filter-panel-date">
                <div class="filter-date-range">
                    <div class="filter-date-field">
                        <label class="filter-date-label" for="filter-date-from">From</label>
                        <input
                            type="date"
                            class="filter-date-input"
                            id="filter-date-from"
                            name="date_from"
                            data-filter-key="date_from"
                            aria-label="Date enacted from" />
                    </div>
                    <span class="filter-date-separator" aria-hidden="true">—</span>
                    <div class="filter-date-field">
                        <label class="filter-date-label" for="filter-date-to">To</label>
                        <input
                            type="date"
                            class="filter-date-input"
                            id="filter-date-to"
                            name="date_to"
                            data-filter-key="date_to"
                            aria-label="Date enacted to" />
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Status ──────────────────────────────────── -->
        <div class="filter-block" id="filter-block-status">
            <button
                class="filter-block-toggle"
                type="button"
                aria-expanded="true"
                aria-controls="filter-panel-status">
                <span class="filter-block-label">Status</span>
                <span class="filter-block-chevron" aria-hidden="true">▾</span>
            </button>
            <div class="filter-block-panel" id="filter-panel-status">
                <fieldset class="filter-checkbox-group" id="filter-status-group" data-filter-key="status">
                    <legend class="sr-only">Filter by status</legend>
                    <!-- Populated by filter-statuses.js -->
                </fieldset>
            </div>
        </div>

        <!-- ── Content flags ───────────────────────────── -->
        <div class="filter-block" id="filter-block-flags">
            <button
                class="filter-block-toggle"
                type="button"
                aria-expanded="true"
                aria-controls="filter-panel-flags">
                <span class="filter-block-label">Content</span>
                <span class="filter-block-chevron" aria-hidden="true">▾</span>
            </button>
            <div class="filter-block-panel" id="filter-panel-flags">
                <fieldset class="filter-checkbox-group" data-filter-key="content_flags">
                    <legend class="sr-only">Filter by content availability</legend>
                    <label class="filter-checkbox-item">
                        <input type="checkbox" class="filter-checkbox" name="has_summary" value="1" data-filter-key="has_summary" />
                        <span class="filter-checkbox-mark" aria-hidden="true"></span>
                        <span class="filter-checkbox-text">Has summary</span>
                    </label>
                    <label class="filter-checkbox-item">
                        <input type="checkbox" class="filter-checkbox" name="has_full_text" value="1" data-filter-key="has_full_text" />
                        <span class="filter-checkbox-mark" aria-hidden="true"></span>
                        <span class="filter-checkbox-text">Has full text</span>
                    </label>
                    <label class="filter-checkbox-item">
                        <input type="checkbox" class="filter-checkbox" name="has_pdf" value="1" data-filter-key="has_pdf" />
                        <span class="filter-checkbox-mark" aria-hidden="true"></span>
                        <span class="filter-checkbox-text">Has PDF file</span>
                    </label>
                </fieldset>
            </div>
        </div>

        <!-- ── Sort ────────────────────────────────────── -->
        <div class="filter-block" id="filter-block-sort">
            <button
                class="filter-block-toggle"
                type="button"
                aria-expanded="true"
                aria-controls="filter-panel-sort">
                <span class="filter-block-label">Sort By</span>
                <span class="filter-block-chevron" aria-hidden="true">▾</span>
            </button>
            <div class="filter-block-panel" id="filter-panel-sort">
                <!-- ── Sort ────────────────────────────────────────────────── -->
                <select
                    class="filter-select"
                    id="filter-sort-by"
                    name="sort_by"
                    data-filter-key="sort_by"
                    aria-label="Sort results by">
                    <option value="date_enacted" selected>Date Enacted</option>
                    <option value="series_year">Series Year</option>
                    <option value="title">Title (A–Z)</option>
                    <option value="created_at">Date Added</option>
                </select>
                <fieldset class="filter-radio-group" data-filter-key="sort_dir">
                    <legend class="sr-only">Sort direction</legend>
                    <label class="filter-radio-item">
                        <input type="radio" class="filter-radio" name="sort_dir" value="desc" data-filter-key="sort_dir" checked />
                        <span class="filter-radio-mark" aria-hidden="true"></span>
                        <span class="filter-radio-text">Descending</span>
                    </label>
                    <label class="filter-radio-item">
                        <input type="radio" class="filter-radio" name="sort_dir" value="asc" data-filter-key="sort_dir" />
                        <span class="filter-radio-mark" aria-hidden="true"></span>
                        <span class="filter-radio-text">Ascending</span>
                    </label>
                </fieldset>
            </div>
        </div>
    </form>
</div><!-- /.filter-sidebar-body -->