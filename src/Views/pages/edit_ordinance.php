<?php

namespace App\Views\pages;

session_start();

// ── Guard: administrators only ────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

$adminRoles = [1]; // role_id 1 = Administrator per seed_data.sql
if (!in_array((int)($_SESSION['role_id'] ?? 0), $adminRoles, true)) {
    header('Location: home');
    exit;
}

// ── Resolve target ordinance_id from query string ─────────────────────────────
$ordinanceId = isset($_GET['id']) ? (int) trim($_GET['id']) : 0;

if ($ordinanceId < 1) {
    header('Location: /dashboard');
    exit;
}

// ── Page meta ─────────────────────────────────────────────────────────────────
$pageTitle   = "Edit Ordinance | EncycLawPhilia Valenzuela";
$currentPage = "admin_dashboard"; // keeps the admin CSS loaded via head.php

$sessionUsername = htmlspecialchars($_SESSION['username'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');
$sessionRoleId   = (int)($_SESSION['role_id'] ?? 1);
$_SESSION['is_admin'] = ($sessionRoleId === 1);

// ── Flash message (set on redirect after a POST) ──────────────────────────────
$flashMessage = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

// ── Fetch the ordinance record + reference data ───────────────────────────────
$ordinance  = null;
$categories = [];
$barangays  = [];

try {

    $pdo = \App\Controllers\DatabaseController::getDatabaseConnection();

    // ── Ordinance record (archived records are still editable by admins) ──
    $stmt = $pdo->prepare("
        SELECT
            o.ordinance_id,
            o.ordinance_number,
            o.title,
            o.author_sponsor,
            o.series_year,
            o.category_id,
            o.barangay_id,
            o.status,
            o.date_enacted,
            o.pdf_file,
            o.summary,
            o.full_text,
            o.archived_at,
            o.created_at,
            o.updated_at,
            c.category_name,
            b.barangay_name
        FROM  Ordinances o
        LEFT JOIN Categories c ON c.category_id = o.category_id
        LEFT JOIN Barangays   b ON b.barangay_id = o.barangay_id
        WHERE o.ordinance_id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $ordinanceId]);
    $ordinance = $stmt->fetch(\PDO::FETCH_ASSOC);

    if (!$ordinance) {
        // Ordinance not found — bounce back to dashboard
        $_SESSION['admin_flash'] = [
            'type'  => 'error',
            'title' => 'Record Not Found',
            'body'  => "Ordinance ID {$ordinanceId} does not exist in the database.",
        ];
        header('Location: /dashboard');
        exit;
    }

    // ── Reference dropdowns (stable reference data) ───────────────────────
    $categories = $pdo
        ->query("SELECT category_id, category_name FROM Categories ORDER BY category_name ASC")
        ->fetchAll(\PDO::FETCH_ASSOC);

    $barangays = $pdo
        ->query("SELECT barangay_id, barangay_name FROM Barangays ORDER BY barangay_name ASC")
        ->fetchAll(\PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    error_log("edit_ordinance.php fetch failed: " . $e->getMessage());
    $_SESSION['admin_flash'] = [
        'type'  => 'error',
        'title' => 'Database Error',
        'body'  => 'Could not load the ordinance record. Please try again.',
    ];
    header('Location: /dashboard');
    exit;
}

// ── Determine mutability of each once-after-null field ────────────────────────
// A field is editable only when its current DB value is NULL (or empty string).
// Once set, these fields are locked and displayed read-only.
$canEditBarangay   = ($ordinance['barangay_id']   === null);
$canEditDateEnacted = ($ordinance['date_enacted']  === null);
$canEditPdfFile    = ($ordinance['pdf_file']       === null || $ordinance['pdf_file'] === '');
$canEditSummary    = ($ordinance['summary']        === null || $ordinance['summary']  === '');
$canEditFullText   = ($ordinance['full_text']      === null || $ordinance['full_text'] === '');

// ── Convenience booleans / display helpers ────────────────────────────────────
$isArchived    = ($ordinance['archived_at'] !== null);
$ordinanceNum  = htmlspecialchars($ordinance['ordinance_number'], ENT_QUOTES, 'UTF-8');
$seriesYear    = htmlspecialchars($ordinance['series_year'],      ENT_QUOTES, 'UTF-8');

// Format dates for <input type="date"> (Y-m-d) and display (d M Y)
$dateEnactedValue   = $ordinance['date_enacted']
    ? date('Y-m-d', strtotime($ordinance['date_enacted']))
    : '';
$dateEnactedDisplay = $ordinance['date_enacted']
    ? date('d F Y', strtotime($ordinance['date_enacted']))
    : '—';

$updatedAtDisplay = $ordinance['updated_at']
    ? date('d F Y, g:i A', strtotime($ordinance['updated_at']))
    : '—';
$createdAtDisplay = $ordinance['created_at']
    ? date('d F Y, g:i A', strtotime($ordinance['created_at']))
    : '—';

require_once __DIR__ . '/../head.php';
?>

<body class="<?php echo htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8'); ?>">

    <?php require_once __DIR__ . '/../header.php'; ?>

    <!-- ================================================================
         SUBHERO
    ================================================================ -->
    <div class="subhero admin-subhero edit-ord-subhero">
        <div class="subhero-content">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="/home">Home</a>
                <span class="separator" aria-hidden="true">›</span>
                <a href="/dashboard">Admin Dashboard</a>
                <span class="separator" aria-hidden="true">›</span>
                <a href="/ordinance?id=<?php echo $ordinanceId; ?>">
                    No. <?php echo $ordinanceNum; ?> s. <?php echo $seriesYear; ?>
                </a>
                <span class="separator" aria-hidden="true">›</span>
                <span aria-current="page">Edit</span>
            </nav>
            <div class="admin-subhero-meta">
                <div>
                    <h1>Edit Ordinance Record</h1>
                    <p class="admin-subhero-greeting">
                        Editing <strong>No. <?php echo $ordinanceNum; ?> s. <?php echo $seriesYear; ?></strong>
                        — logged in as <strong><?php echo $sessionUsername; ?></strong>
                    </p>
                </div>
                <div class="edit-ord-subhero-badges">
                    <?php if ($isArchived): ?>
                        <span class="status-badge rejected status-badge--hero" title="This record has been archived and is not publicly visible.">
                            Archived
                        </span>
                    <?php else: ?>
                        <?php
                        $statusMap = [
                            'Pending'  => 'pending',
                            'Active'   => 'passed',
                            'Repealed' => 'rejected',
                            'Amended'  => 'in-progress',
                        ];
                        $statusClass = $statusMap[$ordinance['status']] ?? 'pending';
                        ?>
                        <span class="status-badge <?php echo $statusClass; ?> status-badge--hero">
                            <?php echo htmlspecialchars($ordinance['status'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    <?php endif; ?>
                    <span class="status-badge in-progress status-badge--hero admin-role-indicator">
                        Editing
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================
         ARCHIVED NOTICE BANNER
         Shown only when the record has been soft-deleted (archived).
         Admins may still edit mutable/once-after-null fields on
         archived records, but they should be aware of the state.
    ================================================================ -->
    <?php if ($isArchived): ?>
        <div class="edit-ord-archived-banner" role="alert">
            <span class="edit-ord-archived-icon" aria-hidden="true">🗂</span>
            <div class="edit-ord-archived-text">
                <strong>This record is archived.</strong>
                It is no longer publicly visible on the platform.
                You may still update fillable fields below, but archiving cannot be undone
                through this interface — contact a DBA to restore the record if needed.
            </div>
        </div>
    <?php endif; ?>

    <!-- ================================================================
         FLASH MESSAGE
    ================================================================ -->
    <?php if ($flashMessage): ?>
        <div class="add-ord-flash-wrap" role="alert" aria-live="assertive">
            <div class="auth-message-bar auth-message-bar--<?php echo htmlspecialchars($flashMessage['type'], ENT_QUOTES, 'UTF-8'); ?> add-ord-flash-bar">
                <div class="bar-text">
                    <span class="bar-title"><?php echo htmlspecialchars($flashMessage['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="bar-body"><?php echo htmlspecialchars($flashMessage['body'],  ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <button class="bar-close" type="button" aria-label="Dismiss"
                    onclick="this.closest('.add-ord-flash-wrap').remove()">✕</button>
            </div>
        </div>
    <?php endif; ?>

    <!-- ================================================================
         MAIN CONTENT
    ================================================================ -->
    <main class="add-ord-page edit-ord-page">

        <div class="add-ord-layout edit-ord-layout">

            <!-- ── LEFT: sticky policy + record audit sidebar ────── -->
            <aside class="add-ord-sidebar edit-ord-sidebar" aria-label="Record policy and audit information">

                <div class="add-ord-sidebar-inner">

                    <h2 class="add-ord-sidebar-title">Field Policy</h2>
                    <p class="add-ord-sidebar-intro">
                        This record is governed by the
                        <em>Write Once, Transparent Forever</em> policy.
                        Each section below explains which fields can still be changed.
                    </p>

                    <!-- Policy: Write-Once (locked entirely) -->
                    <div class="add-ord-policy-group">
                        <span class="add-ord-policy-badge add-ord-policy-badge--locked">
                            🔒 Permanently Fixed
                        </span>
                        <p class="add-ord-policy-desc">
                            These fields were set at creation and are
                            <strong>immutable</strong> through this interface.
                            Contact a DBA to correct any errors.
                        </p>
                        <ul class="add-ord-policy-list">
                            <li>Ordinance Number</li>
                            <li>Title</li>
                            <li>Author / Sponsor</li>
                            <li>Series Year</li>
                        </ul>
                    </div>

                    <!-- Policy: Once-after-null -->
                    <div class="add-ord-policy-group">
                        <span class="add-ord-policy-badge add-ord-policy-badge--once-after-null">
                            ✎ Once After Null
                        </span>
                        <p class="add-ord-policy-desc">
                            Fields that are still <code>NULL</code> can be filled in
                            exactly <em>once</em>. Once saved, they are permanently
                            locked. Greyed-out fields have already been set.
                        </p>
                        <ul class="add-ord-policy-list">
                            <li class="<?php echo !$canEditBarangay   ? 'edit-ord-policy-list-item--locked' : ''; ?>">
                                Barangay
                                <?php echo !$canEditBarangay   ? '<span class="edit-ord-lock-icon" aria-label="Already set">🔒</span>' : ''; ?>
                            </li>
                            <li class="<?php echo !$canEditDateEnacted ? 'edit-ord-policy-list-item--locked' : ''; ?>">
                                Date Enacted
                                <?php echo !$canEditDateEnacted ? '<span class="edit-ord-lock-icon" aria-label="Already set">🔒</span>' : ''; ?>
                            </li>
                            <li class="<?php echo !$canEditPdfFile    ? 'edit-ord-policy-list-item--locked' : ''; ?>">
                                PDF File
                                <?php echo !$canEditPdfFile    ? '<span class="edit-ord-lock-icon" aria-label="Already set">🔒</span>' : ''; ?>
                            </li>
                            <li class="<?php echo !$canEditSummary    ? 'edit-ord-policy-list-item--locked' : ''; ?>">
                                Summary
                                <?php echo !$canEditSummary    ? '<span class="edit-ord-lock-icon" aria-label="Already set">🔒</span>' : ''; ?>
                            </li>
                            <li class="<?php echo !$canEditFullText   ? 'edit-ord-policy-list-item--locked' : ''; ?>">
                                Full Text
                                <?php echo !$canEditFullText   ? '<span class="edit-ord-lock-icon" aria-label="Already set">🔒</span>' : ''; ?>
                            </li>
                        </ul>
                    </div>

                    <!-- Policy: Mutable -->
                    <div class="add-ord-policy-group">
                        <span class="add-ord-policy-badge add-ord-policy-badge--mutable">
                            ↺ Always Editable
                        </span>
                        <p class="add-ord-policy-desc">
                            These secondary fields may be changed at any time
                            and do not affect the ordinance's factual content.
                        </p>
                        <ul class="add-ord-policy-list">
                            <li>Category</li>
                            <li>Status</li>
                        </ul>
                    </div>

                    <hr class="ord-divider add-ord-sidebar-divider" />

                    <!-- Audit trail -->
                    <div class="edit-ord-audit-block">
                        <h3 class="edit-ord-audit-title">Record Audit</h3>
                        <dl class="edit-ord-audit-list">
                            <div class="edit-ord-audit-item">
                                <dt>Record ID</dt>
                                <dd><?php echo $ordinanceId; ?></dd>
                            </div>
                            <div class="edit-ord-audit-item">
                                <dt>Created</dt>
                                <dd><?php echo $createdAtDisplay; ?></dd>
                            </div>
                            <div class="edit-ord-audit-item">
                                <dt>Last Updated</dt>
                                <dd><?php echo $updatedAtDisplay; ?></dd>
                            </div>
                            <?php if ($isArchived): ?>
                                <div class="edit-ord-audit-item edit-ord-audit-item--warn">
                                    <dt>Archived On</dt>
                                    <dd><?php echo date('d F Y, g:i A', strtotime($ordinance['archived_at'])); ?></dd>
                                </div>
                            <?php endif; ?>
                        </dl>
                    </div>

                    <hr class="ord-divider add-ord-sidebar-divider" />

                    <!-- Quick-action shortcuts -->
                    <div class="add-ord-sidebar-actions">
                        <a href="/ordinance?id=<?php echo $ordinanceId; ?>"
                            class="add-ord-sidebar-link"
                            target="_blank"
                            rel="noopener noreferrer">
                            👁 View Public Page
                        </a>
                        <a href="/dashboard" class="add-ord-sidebar-link">
                            ← Back to Dashboard
                        </a>
                    </div>

                </div><!-- /.add-ord-sidebar-inner -->

            </aside><!-- /.add-ord-sidebar -->


            <!-- ── RIGHT: the edit form ───────────────────────────── -->
            <section class="add-ord-form-col" aria-labelledby="form-heading">

                <h2 id="form-heading" class="add-ord-section-heading">Ordinance Details</h2>
                <p class="add-ord-section-sub">
                    Fields shown in a <span class="edit-ord-inline-locked-label">locked style</span>
                    cannot be changed. Fields with
                    <span class="add-ord-required-star" aria-hidden="true">*</span>
                    are required for the mutable sections. Blank once-after-null fields
                    may be filled in exactly once.
                </p>

                <!--
                    POST target: /admin/ordinances/update
                    The receiving controller must:
                      1. Verify session and role.
                      2. Retrieve the current DB state for this ordinance_id.
                      3. Reject any attempt to change write-once fields
                         (ordinance_number, title, author_sponsor, series_year).
                      4. For once-after-null fields: only apply the UPDATE
                         if the current DB value IS NULL. Silently skip or
                         return an error if already set.
                      5. Always allow updates to category_id and status.
                      6. Touch updated_at (handled automatically by MariaDB
                         ON UPDATE CURRENT_TIMESTAMP).
                      7. PRG: set $_SESSION['admin_flash'] and redirect back
                         to this page (GET /admin/ordinances/edit?id=X).
                -->
                <form
                    class="add-ord-form edit-ord-form"
                    id="edit-ordinance-form"
                    action="/admin/ordinances/update"
                    method="POST"
                    enctype="multipart/form-data"
                    novalidate
                    aria-labelledby="form-heading">

                    <!-- Hidden: pass the ordinance ID to the controller -->
                    <input type="hidden" name="ordinance_id" value="<?php echo $ordinanceId; ?>">

                    <!-- ── SECTION 2: Classification (Always Mutable) ─── -->
                    <fieldset class="add-ord-fieldset add-ord-fieldset--mutable">
                        <legend class="add-ord-legend">
                            <span class="add-ord-legend-icon" aria-hidden="true">↺</span>
                            Classification
                            <span class="add-ord-legend-sub">Mutable — may be updated at any time</span>
                        </legend>

                        <div class="add-ord-two-col">

                            <!-- Category (always editable) -->
                            <div class="form-group add-ord-form-group">
                                <label for="category_id" class="add-ord-label">
                                    Category
                                    <span class="add-ord-optional-tag">optional</span>
                                </label>
                                <select
                                    id="category_id"
                                    name="category_id"
                                    class="add-ord-select"
                                    aria-describedby="category-hint">
                                    <option value="">— Unclassified —</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option
                                            value="<?php echo (int)$cat['category_id']; ?>"
                                            <?php echo ((int)$cat['category_id'] === (int)$ordinance['category_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['category_name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="add-ord-field-hint" id="category-hint">
                                    Classification may be changed freely at any time.
                                </span>
                            </div>

                            <!-- Status (always editable) -->
                            <div class="form-group add-ord-form-group">
                                <label for="status" class="add-ord-label">
                                    Status
                                    <span class="add-ord-required-star" aria-label="required">*</span>
                                </label>
                                <select
                                    id="status"
                                    name="status"
                                    class="add-ord-select"
                                    required
                                    aria-describedby="status-hint">
                                    <?php
                                    $statuses = ['Pending', 'Active', 'Repealed', 'Amended'];
                                    foreach ($statuses as $s):
                                    ?>
                                        <option
                                            value="<?php echo $s; ?>"
                                            <?php echo ($ordinance['status'] === $s) ? 'selected' : ''; ?>>
                                            <?php echo $s; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="add-ord-field-hint" id="status-hint">
                                    Reflects the current legislative standing. Always mutable.
                                </span>
                            </div>

                        </div><!-- /.add-ord-two-col -->

                    </fieldset><!-- /.add-ord-fieldset--mutable -->


                    <!-- ── SECTION 3: Optional Details (Once After Null) ── -->
                    <fieldset class="add-ord-fieldset add-ord-fieldset--once-after-null">
                        <legend class="add-ord-legend">
                            <span class="add-ord-legend-icon" aria-hidden="true">✎</span>
                            Optional Details
                            <span class="add-ord-legend-sub">Once-after-null — greyed fields are permanently locked</span>
                        </legend>

                        <div class="add-ord-two-col">

                            <!-- Barangay -->
                            <div class="form-group add-ord-form-group">
                                <label for="barangay_id" class="add-ord-label <?php echo !$canEditBarangay ? 'edit-ord-label--locked' : ''; ?>">
                                    Barangay
                                    <?php if (!$canEditBarangay): ?>
                                        <span class="edit-ord-lock-icon" aria-label="Already set">🔒</span>
                                    <?php else: ?>
                                        <span class="add-ord-optional-tag">optional</span>
                                    <?php endif; ?>
                                </label>

                                <?php if ($canEditBarangay): ?>
                                    <select
                                        id="barangay_id"
                                        name="barangay_id"
                                        class="add-ord-select"
                                        aria-describedby="barangay-hint">
                                        <option value="">— City-wide / Unspecified —</option>
                                        <?php foreach ($barangays as $brgy): ?>
                                            <option value="<?php echo (int)$brgy['barangay_id']; ?>">
                                                <?php echo htmlspecialchars($brgy['barangay_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="add-ord-field-hint edit-ord-field-hint--warn" id="barangay-hint">
                                        ⚠ Once saved, this cannot be changed through the application.
                                    </span>
                                <?php else: ?>
                                    <div class="edit-ord-locked-field" aria-label="Barangay: <?php echo htmlspecialchars($ordinance['barangay_name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>" role="text">
                                        <?php echo htmlspecialchars($ordinance['barangay_name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <input type="hidden" name="barangay_id" value="<?php echo (int)$ordinance['barangay_id']; ?>">
                                    <span class="add-ord-field-hint" id="barangay-hint">
                                        This field has already been set and is permanently locked.
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Date Enacted -->
                            <div class="form-group add-ord-form-group">
                                <label for="date_enacted" class="add-ord-label <?php echo !$canEditDateEnacted ? 'edit-ord-label--locked' : ''; ?>">
                                    Date Enacted
                                    <?php if (!$canEditDateEnacted): ?>
                                        <span class="edit-ord-lock-icon" aria-label="Already set">🔒</span>
                                    <?php else: ?>
                                        <span class="add-ord-optional-tag">optional</span>
                                    <?php endif; ?>
                                </label>

                                <?php if ($canEditDateEnacted): ?>
                                    <input
                                        type="date"
                                        id="date_enacted"
                                        name="date_enacted"
                                        class="add-ord-input"
                                        max="<?php echo date('Y-m-d'); ?>"
                                        aria-describedby="date-hint" />
                                    <span class="add-ord-field-hint edit-ord-field-hint--warn" id="date-hint">
                                        ⚠ Once saved, this cannot be changed through the application.
                                    </span>
                                <?php else: ?>
                                    <div class="edit-ord-locked-field" role="text"
                                        aria-label="Date enacted: <?php echo $dateEnactedDisplay; ?>">
                                        <?php echo $dateEnactedDisplay; ?>
                                    </div>
                                    <input type="hidden" name="date_enacted" value="<?php echo htmlspecialchars($dateEnactedValue, ENT_QUOTES, 'UTF-8'); ?>">
                                    <span class="add-ord-field-hint" id="date-hint">
                                        This field has already been set and is permanently locked.
                                    </span>
                                <?php endif; ?>
                            </div>

                        </div><!-- /.add-ord-two-col -->

                        <!-- Summary -->
                        <div class="form-group add-ord-form-group">
                            <label for="summary" class="add-ord-label <?php echo !$canEditSummary ? 'edit-ord-label--locked' : ''; ?>">
                                Summary
                                <?php if (!$canEditSummary): ?>
                                    <span class="edit-ord-lock-icon" aria-label="Already set">🔒</span>
                                <?php else: ?>
                                    <span class="add-ord-optional-tag">optional</span>
                                <?php endif; ?>
                            </label>

                            <?php if ($canEditSummary): ?>
                                <textarea
                                    id="summary"
                                    name="summary"
                                    class="add-ord-textarea"
                                    placeholder="A brief plain-language description of this ordinance…"
                                    rows="4"
                                    aria-describedby="summary-hint summary-char-count"></textarea>
                                <div class="add-ord-textarea-footer">
                                    <span class="add-ord-field-hint edit-ord-field-hint--warn" id="summary-hint">
                                        ⚠ Once saved, this cannot be changed through the application.
                                    </span>
                                    <span class="add-ord-char-count" id="summary-char-count" aria-live="polite">
                                        0 chars
                                    </span>
                                </div>
                            <?php else: ?>
                                <div class="edit-ord-locked-field edit-ord-locked-field--multiline edit-ord-locked-field--tall"
                                    role="text"
                                    aria-label="Summary text">
                                    <?php echo htmlspecialchars($ordinance['summary'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <input type="hidden" name="summary" value="<?php echo htmlspecialchars($ordinance['summary'], ENT_QUOTES, 'UTF-8'); ?>">
                                <span class="add-ord-field-hint" id="summary-hint">
                                    This field has already been set and is permanently locked.
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Full Text -->
                        <div class="form-group add-ord-form-group">
                            <label for="full_text" class="add-ord-label <?php echo !$canEditFullText ? 'edit-ord-label--locked' : ''; ?>">
                                Full Text
                                <?php if (!$canEditFullText): ?>
                                    <span class="edit-ord-lock-icon" aria-label="Already set">🔒</span>
                                <?php else: ?>
                                    <span class="add-ord-optional-tag">optional</span>
                                <?php endif; ?>
                            </label>

                            <?php if ($canEditFullText): ?>
                                <textarea
                                    id="full_text"
                                    name="full_text"
                                    class="add-ord-textarea add-ord-textarea--fulltext"
                                    placeholder="WHEREAS, the Sangguniang Panlungsod ng Valenzuela City…"
                                    rows="12"
                                    aria-describedby="fulltext-hint fulltext-char-count"></textarea>
                                <div class="add-ord-textarea-footer">
                                    <span class="add-ord-field-hint edit-ord-field-hint--warn" id="fulltext-hint">
                                        ⚠ Once saved, this cannot be changed through the application.
                                        Paste the complete legislative text here if available.
                                    </span>
                                    <span class="add-ord-char-count" id="fulltext-char-count" aria-live="polite">
                                        0 chars
                                    </span>
                                </div>
                            <?php else: ?>
                                <!--
                                    Full text can be very long; we render a scrollable preview
                                    rather than a massive locked field. The hidden input passes
                                    the existing value back so the controller can verify nothing
                                    was tampered with on the server side.
                                -->
                                <div class="edit-ord-locked-field edit-ord-locked-field--multiline edit-ord-locked-field--scrollable"
                                    role="text"
                                    aria-label="Full legislative text (read-only)"
                                    tabindex="0">
                                    <?php echo nl2br(htmlspecialchars($ordinance['full_text'], ENT_QUOTES, 'UTF-8')); ?>
                                </div>
                                <input type="hidden" name="full_text_exists" value="1">
                                <span class="add-ord-field-hint" id="fulltext-hint">
                                    This field has already been set and is permanently locked.
                                    The full text is scrollable above.
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- PDF File -->
                        <div class="form-group add-ord-form-group">
                            <label for="pdf_file" class="add-ord-label <?php echo !$canEditPdfFile ? 'edit-ord-label--locked' : ''; ?>">
                                PDF Document
                                <?php if (!$canEditPdfFile): ?>
                                    <span class="edit-ord-lock-icon" aria-label="Already set">🔒</span>
                                <?php else: ?>
                                    <span class="add-ord-optional-tag">optional</span>
                                <?php endif; ?>
                            </label>

                            <?php if ($canEditPdfFile): ?>
                                <!--
                                    NOTE FOR CONTROLLER IMPLEMENTER:
                                    Move the uploaded file to permanent storage, save only the
                                    server path to the `pdf_file` column. Apply write-once-after-null:
                                    reject any UPDATE that overwrites an existing non-null pdf_file.
                                -->
                                <div class="add-ord-file-drop" id="pdf-drop-zone" role="button"
                                    tabindex="0" aria-describedby="pdf-hint"
                                    aria-label="Click or drag to upload a PDF file">
                                    <span class="add-ord-file-icon" aria-hidden="true">📄</span>
                                    <span class="add-ord-file-prompt" id="pdf-file-prompt">
                                        Click to choose or drag &amp; drop a PDF
                                    </span>
                                    <span class="add-ord-file-sub">Max file size: 20 MB</span>
                                    <input
                                        type="file"
                                        id="pdf_file"
                                        name="pdf_file"
                                        class="add-ord-file-input"
                                        accept="application/pdf"
                                        aria-hidden="true"
                                        tabindex="-1" />
                                </div>
                                <span class="add-ord-field-hint edit-ord-field-hint--warn" id="pdf-hint">
                                    ⚠ Once uploaded, the document path cannot be changed through the application.
                                    Accepts PDF files only.
                                </span>
                            <?php else: ?>
                                <div class="edit-ord-pdf-present" role="group" aria-label="Existing PDF document">
                                    <span class="edit-ord-pdf-icon" aria-hidden="true">📄</span>
                                    <div class="edit-ord-pdf-info">
                                        <span class="edit-ord-pdf-name">
                                            <?php echo htmlspecialchars(basename($ordinance['pdf_file']), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                        <a
                                            href="<?php echo htmlspecialchars($ordinance['pdf_file'], ENT_QUOTES, 'UTF-8'); ?>"
                                            class="edit-ord-pdf-link"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            aria-label="View the existing PDF in a new tab">
                                            View PDF ↗
                                        </a>
                                    </div>
                                    <span class="edit-ord-lock-icon edit-ord-pdf-lock" aria-label="Document locked">🔒</span>
                                </div>
                                <input type="hidden" name="pdf_file_exists" value="1">
                                <span class="add-ord-field-hint" id="pdf-hint">
                                    A document has already been uploaded and is permanently linked to this record.
                                </span>
                            <?php endif; ?>
                        </div>

                    </fieldset><!-- /.add-ord-fieldset--once-after-null -->


                    <!-- ── SECTION 4: Confirmation notice ─────────────── -->
                    <?php
                    // Determine whether any once-after-null fields are still editable
                    $hasEditableNullFields = $canEditBarangay || $canEditDateEnacted
                        || $canEditPdfFile  || $canEditSummary
                        || $canEditFullText;
                    ?>
                    <?php if ($hasEditableNullFields): ?>
                        <div class="add-ord-confirm-notice" role="note" aria-label="Submission notice">
                            <span class="add-ord-confirm-icon" aria-hidden="true">⚠</span>
                            <p class="add-ord-confirm-text">
                                One or more <strong>Once-After-Null</strong> fields on this record
                                are still empty and can be filled in. Once you save a value into
                                any of these fields, it <strong>cannot be overwritten</strong>
                                through this interface. Double-check all values before saving.
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- ── Form actions ───────────────────────────────── -->
                    <div class="add-ord-form-actions edit-ord-form-actions">
                        <a href="/dashboard" class="admin-btn admin-btn--ghost add-ord-cancel-btn">
                            Cancel
                        </a>
                        <a
                            href="/ordinance?id=<?php echo $ordinanceId; ?>"
                            class="admin-btn admin-btn--ghost"
                            target="_blank"
                            rel="noopener noreferrer">
                            👁 Preview
                        </a>
                        <button
                            type="submit"
                            class="admin-btn admin-btn--primary add-ord-submit-btn"
                            id="edit-ord-submit">
                            Save Changes
                        </button>
                    </div>

                </form><!-- /#edit-ordinance-form -->

            </section><!-- /.add-ord-form-col -->

        </div><!-- /.add-ord-layout -->

    </main><!-- /.edit-ord-page -->

    <?php require_once __DIR__ . '/../footer.php'; ?>

    <script>
        // ============================================================
        // CHAR COUNT — summary, full_text (only when editable)
        // ============================================================
        function attachCharCount(textareaId, counterId, maxLength) {
            const ta = document.getElementById(textareaId);
            const ctr = document.getElementById(counterId);
            if (!ta || !ctr) return;

            function update() {
                const len = ta.value.length;
                ctr.textContent = maxLength ?
                    `${len} / ${maxLength}` :
                    `${len} chars`;
                const nearLimit = maxLength && len >= maxLength * 0.90;
                ctr.classList.toggle('add-ord-char-count--warn', nearLimit);
            }

            ta.addEventListener('input', update);
            update();
        }

        attachCharCount('summary', 'summary-char-count', null);
        attachCharCount('full_text', 'fulltext-char-count', null);


        // ============================================================
        // PDF DRAG-AND-DROP ZONE (only rendered when field is editable)
        // ============================================================
        const dropZone = document.getElementById('pdf-drop-zone');
        const fileInput = document.getElementById('pdf_file');

        if (dropZone && fileInput) {
            const filePrompt = document.getElementById('pdf-file-prompt');

            function setFileName(name) {
                filePrompt.textContent = name || 'Click to choose or drag & drop a PDF';
                dropZone.classList.toggle('add-ord-file-drop--selected', !!name);
            }

            dropZone.addEventListener('click', () => fileInput.click());
            dropZone.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    fileInput.click();
                }
            });

            fileInput.addEventListener('change', () => {
                setFileName(fileInput.files[0]?.name ?? '');
            });

            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('add-ord-file-drop--dragover');
            });

            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('add-ord-file-drop--dragover');
            });

            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('add-ord-file-drop--dragover');

                const file = e.dataTransfer.files[0];
                if (!file) return;

                if (file.type !== 'application/pdf') {
                    alert('Only PDF files are accepted.');
                    return;
                }

                const dt = new DataTransfer();
                dt.items.add(file);
                fileInput.files = dt.files;
                setFileName(file.name);
            });
        }


        // ============================================================
        // CLIENT-SIDE CONFIRMATION — warn before saving once-after-null
        // fields. Server-side enforcement in the controller is authoritative.
        // ============================================================
        const editableNullFields = <?php echo json_encode([
                                        'barangay'     => $canEditBarangay,
                                        'date_enacted' => $canEditDateEnacted,
                                        'pdf_file'     => $canEditPdfFile,
                                        'summary'      => $canEditSummary,
                                        'full_text'    => $canEditFullText,
                                    ]); ?>;

        document.getElementById('edit-ordinance-form')
            .addEventListener('submit', function(e) {
                const filledNullFields = [];

                if (editableNullFields.barangay) {
                    const sel = document.getElementById('barangay_id');
                    if (sel && sel.value) filledNullFields.push('Barangay');
                }
                if (editableNullFields.date_enacted) {
                    const inp = document.getElementById('date_enacted');
                    if (inp && inp.value) filledNullFields.push('Date Enacted');
                }
                if (editableNullFields.pdf_file) {
                    const fi = document.getElementById('pdf_file');
                    if (fi && fi.files.length > 0) filledNullFields.push('PDF Document');
                }
                if (editableNullFields.summary) {
                    const ta = document.getElementById('summary');
                    if (ta && ta.value.trim()) filledNullFields.push('Summary');
                }
                if (editableNullFields.full_text) {
                    const ta = document.getElementById('full_text');
                    if (ta && ta.value.trim()) filledNullFields.push('Full Text');
                }

                if (filledNullFields.length > 0) {
                    const list = filledNullFields.join(', ');
                    const confirmed = window.confirm(
                        `You are about to permanently set the following field(s):\n\n  • ${filledNullFields.join('\n  • ')}\n\n` +
                        `These values cannot be changed through this interface after saving.\n` +
                        `Click OK only if all values are correct.`
                    );
                    if (!confirmed) {
                        e.preventDefault();
                    }
                }
            });
    </script>

</body>

</html>