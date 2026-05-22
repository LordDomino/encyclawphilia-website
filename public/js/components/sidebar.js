document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Sidebar Drawer
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebarColumn = document.getElementById('sidebar-column');

    if (sidebarToggle && sidebarColumn) {
        sidebarToggle.addEventListener('click', () => {
            sidebarColumn.classList.toggle('drawer-open');
            sidebarToggle.classList.toggle('active');
        });

        document.addEventListener('click', (e) => {
            if (!sidebarColumn.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebarColumn.classList.remove('drawer-open');
                sidebarToggle.classList.remove('active');
            }
        });
    }

    // 2. Collapsible Filter Blocks
    document.querySelectorAll('.filter-block-toggle').forEach(toggle => {
        toggle.addEventListener('click', () => {
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            const panelId = toggle.getAttribute('aria-controls');
            const panel = document.getElementById(panelId);

            if (panel) {
                toggle.setAttribute('aria-expanded', String(!isExpanded));
                panel.classList.toggle('is-collapsed', isExpanded);
            }
        });
    });

    // 3. Clear All Filters (Resets DOM only. SearchEngine catches the form change)
    document.getElementById('filter-clear-all')?.addEventListener('click', () => {
        document.querySelectorAll('.filter-select').forEach(sel => sel.selectedIndex = 0);
        document.querySelectorAll('.filter-date-input').forEach(inp => inp.value = '');
        document.querySelectorAll('.filter-checkbox').forEach(cb => cb.checked = false);

        document.querySelectorAll('.filter-radio-group').forEach(group => {
            const defaultRadio = group.querySelector('.filter-radio[value="desc"]') || group.querySelector('.filter-radio');
            if (defaultRadio) defaultRadio.checked = true;
        });

        // Dispatch change event so the SearchEngine triggers a new fetch
        document.getElementById('search-form')?.dispatchEvent(new Event('change', { bubbles: true }));
    });
});