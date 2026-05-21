// ============================================================
// REFRESH BUTTON
// ============================================================
document.getElementById('admin-refresh-btn')
    ?.addEventListener('click', () => {
        // TODO: replace with fetch-based grid refresh
        window.location.reload();
    });