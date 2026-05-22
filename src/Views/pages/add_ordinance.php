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

// ── Page meta ─────────────────────────────────────────────────────────────────
$pageTitle   = "Add Ordinance | EncycLawPhilia Valenzuela";
$currentPage = "admin_dashboard"; // keeps the admin CSS loaded via head.php

$sessionUsername = htmlspecialchars($_SESSION['username'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');
$sessionRoleId   = (int)($_SESSION['role_id'] ?? 1);
$_SESSION['is_admin'] = ($sessionRoleId === 1);

// ── Flash message (set by the POST handler after redirect) ────────────────────
$flashMessage = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

// ── Reference data: Categories and Barangays ──────────────────────────────────
// These are fetched from the DB to populate the <select> dropdowns.
// Both tables are treated as stable reference data (no INSERT/UPDATE in UI).
$categories = [];
$barangays  = [];

try {
    $pdo = \App\Controllers\DatabaseController::getDatabaseConnection();

    $categories = $pdo
        ->query("SELECT category_id, category_name FROM Categories ORDER BY category_name ASC")
        ->fetchAll(\PDO::FETCH_ASSOC);

    $barangays = $pdo
        ->query("SELECT barangay_id, barangay_name FROM Barangays ORDER BY barangay_name ASC")
        ->fetchAll(\PDO::FETCH_ASSOC);

} catch (\PDOException $e) {
    // Non-fatal: the form still renders; dropdowns will be empty.
    error_log("add_ordinance.php reference data fetch failed: " . $e->getMessage());
}

require_once __DIR__ . '/../head.php';
?>

<body class="<?php echo htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8'); ?>">

    <?php require_once __DIR__ . '/../header.php'; ?>

    <!-- ================================================================
         SUBHERO
    ================================================================ -->
    <div class="subhero admin-subhero add-ord-subhero">
        <div class="subhero-content">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="/home">Home</a>
                <span class="separator" aria-hidden="true">›</span>
                <a href="/dashboard">Admin Dashboard</a>
                <span class="separator" aria-hidden="true">›</span>
                <span aria-current="page">Add Ordinance</span>
            </nav>
            <div class="admin-subhero-meta">
                <div>
                    <h1>Add New Ordinance</h1>
                    <p class="admin-subhero-greeting">
                        Logged in as <strong><?php echo $sessionUsername; ?></strong>
                    </p>
                </div>
                <span class="status-badge in-progress status-badge--hero admin-role-indicator">
                    New Record
                </span>
            </div>
        </div>
    </div>

    <!-- ================================================================
         FLASH MESSAGE (set on redirect after a failed/successful POST)
    ================================================================ -->
    <?php if ($flashMessage): ?>
        <div class="add-ord-flash-wrap" role="alert" aria-live="assertive">
            <div class="auth-message-bar auth-message-bar--<?php echo htmlspecialchars($flashMessage['type'], ENT_QUOTES, 'UTF-8'); ?> add-ord-flash-bar">
                <div class="bar-text">
                    <span class="bar-title"><?php echo htmlspecialchars($flashMessage['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="bar-body"><?php  echo htmlspecialchars($flashMessage['body'],  ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <button class="bar-close" type="button" aria-label="Dismiss"
                        onclick="this.closest('.add-ord-flash-wrap').remove()">✕</button>
            </div>
        </div>
    <?php endif; ?>

    <!-- ================================================================
         MAIN CONTENT
    ================================================================ -->
    <main class="add-ord-page">

        <div class="add-ord-layout">

            <!-- ── LEFT: sticky policy sidebar ──────────────────── -->
            <aside class="add-ord-sidebar" aria-label="Write-once policy guide">

                <div class="add-ord-sidebar-inner">

                    <h2 class="add-ord-sidebar-title">Record Policy</h2>
                    <p class="add-ord-sidebar-intro">
                        All ordinance records are governed by the
                        <em>Write Once, Transparent Forever</em> policy.
                        Please review the field constraints before submitting.
                    </p>

                    <!-- Policy groups -->
                    <div class="add-ord-policy-group">
                        <span class="add-ord-policy-badge add-ord-policy-badge--locked">
                            🔒 Write-Once
                        </span>
                        <p class="add-ord-policy-desc">
                            The following fields are <strong>permanently fixed</strong> after creation
                            and cannot be changed through this interface under any circumstance.
                            A DBA must intervene to correct errors.
                        </p>
                        <ul class="add-ord-policy-list">
                            <li>Ordinance Number</li>
                            <li>Title</li>
                            <li>Author / Sponsor</li>
                            <li>Series Year</li>
                        </ul>
                    </div>

                    <div class="add-ord-policy-group">
                        <span class="add-ord-policy-badge add-ord-policy-badge--once-after-null">
                            ✎ Once After Null
                        </span>
                        <p class="add-ord-policy-desc">
                            These fields are <strong>optional at creation</strong> but may only be set
                            <em>once</em> from a null state. They cannot be overwritten later
                            through the application.
                        </p>
                        <ul class="add-ord-policy-list">
                            <li>Barangay</li>
                            <li>Date Enacted</li>
                            <li>PDF File Path</li>
                            <li>Summary</li>
                            <li>Full Text</li>
                        </ul>
                    </div>

                    <div class="add-ord-policy-group">
                        <span class="add-ord-policy-badge add-ord-policy-badge--mutable">
                            ↺ Mutable
                        </span>
                        <p class="add-ord-policy-desc">
                            These fields may be freely updated after creation to reflect
                            the current legislative standing or classification.
                        </p>
                        <ul class="add-ord-policy-list">
                            <li>Category</li>
                            <li>Status</li>
                        </ul>
                    </div>

                    <hr class="ord-divider add-ord-sidebar-divider" />

                    <!-- Quick-action shortcuts -->
                    <div class="add-ord-sidebar-actions">
                        <a href="/dashboard" class="add-ord-sidebar-link">
                            ← Back to Dashboard
                        </a>
                    </div>

                </div><!-- /.add-ord-sidebar-inner -->

            </aside><!-- /.add-ord-sidebar -->


            <!-- ── RIGHT: the form ────────────────────────────────── -->
            <section class="add-ord-form-col" aria-labelledby="form-heading">

                <h2 id="form-heading" class="add-ord-section-heading">Ordinance Details</h2>
                <p class="add-ord-section-sub">
                    Fields marked <span class="add-ord-required-star" aria-hidden="true">*</span>
                    are required before the record can be created.
                    Fields without a star may be left blank and filled in later
                    (subject to the once-after-null constraint).
                </p>

                <!-- ============================================================
                     POST target: /admin/ordinances/store
                     The receiving controller (to be implemented) must:
                       1. Validate all write-once fields are present and non-empty.
                       2. Reject if ordinance_number already exists in DB.
                       3. INSERT the record via OrdinanceModel or a dedicated
                          AdminOrdinanceModel::create() method.
                       4. Set $_SESSION['admin_flash'] and redirect back here
                          (PRG pattern) — or redirect to the new record's detail page.
                ============================================================ -->
                <form
                    class="add-ord-form"
                    id="add-ordinance-form"
                    action="/store-ordinance"
                    method="POST"
                    enctype="multipart/form-data"
                    novalidate
                    aria-labelledby="form-heading">

                    <!-- ── SECTION 1: Identity (Write-Once) ──────────── -->
                    <fieldset class="add-ord-fieldset add-ord-fieldset--locked">
                        <legend class="add-ord-legend">
                            <span class="add-ord-legend-icon" aria-hidden="true">🔒</span>
                            Identity Fields
                            <span class="add-ord-legend-sub">Write-once — verify carefully before submitting</span>
                        </legend>

                        <!-- Ordinance Number -->
                        <div class="form-group add-ord-form-group">
                            <label for="ordinance_number" class="add-ord-label">
                                Ordinance Number
                                <span class="add-ord-required-star" aria-label="required">*</span>
                            </label>
                            <input
                                type="text"
                                id="ordinance_number"
                                name="ordinance_number"
                                class="add-ord-input"
                                placeholder="e.g. ORD-2026-001"
                                maxlength="50"
                                required
                                autocomplete="off"
                                aria-describedby="ordinance-number-hint" />
                            <span class="add-ord-field-hint" id="ordinance-number-hint">
                                Must be unique across all ordinance records. Max 50 characters.
                            </span>
                        </div>

                        <!-- Title -->
                        <div class="form-group add-ord-form-group">
                            <label for="title" class="add-ord-label">
                                Title
                                <span class="add-ord-required-star" aria-label="required">*</span>
                            </label>
                            <textarea
                                id="title"
                                name="title"
                                class="add-ord-textarea add-ord-textarea--title"
                                placeholder="AN ORDINANCE …"
                                rows="3"
                                maxlength="1000"
                                required
                                aria-describedby="title-hint title-char-count"></textarea>
                            <div class="add-ord-textarea-footer">
                                <span class="add-ord-field-hint" id="title-hint">
                                    Full official title of the ordinance. Max 255 characters.
                                </span>
                                <span class="add-ord-char-count" id="title-char-count" aria-live="polite">
                                    0 / 1000
                                </span>
                            </div>
                        </div>

                        <!-- Author / Sponsor -->
                        <div class="form-group add-ord-form-group">
                            <label for="author_sponsor" class="add-ord-label">
                                Author / Sponsor
                                <span class="add-ord-required-star" aria-label="required">*</span>
                            </label>
                            <input
                                type="text"
                                id="author_sponsor"
                                name="author_sponsor"
                                class="add-ord-input"
                                placeholder="e.g. Hon. Juan dela Cruz"
                                maxlength="500"
                                required
                                aria-describedby="sponsor-hint" />
                            <span class="add-ord-field-hint" id="sponsor-hint">
                                Legislator(s) who authored this ordinance. Max 500 characters.
                            </span>
                        </div>

                        <!-- Series Year -->
                        <div class="form-group add-ord-form-group add-ord-form-group--half">
                            <label for="series_year" class="add-ord-label">
                                Series Year
                                <span class="add-ord-required-star" aria-label="required">*</span>
                            </label>
                            <input
                                type="number"
                                id="series_year"
                                name="series_year"
                                class="add-ord-input"
                                placeholder="<?php echo date('Y'); ?>"
                                min="1900"
                                max="<?php echo date('Y') + 5; ?>"
                                step="1"
                                required
                                aria-describedby="year-hint" />
                            <span class="add-ord-field-hint" id="year-hint">
                                Four-digit legislative series year.
                            </span>
                        </div>

                    </fieldset><!-- /.add-ord-fieldset--locked -->


                    <!-- ── SECTION 2: Classification (Mutable) ────────── -->
                    <fieldset class="add-ord-fieldset add-ord-fieldset--mutable">
                        <legend class="add-ord-legend">
                            <span class="add-ord-legend-icon" aria-hidden="true">↺</span>
                            Classification
                            <span class="add-ord-legend-sub">Mutable — may be updated at any time after creation</span>
                        </legend>

                        <div class="add-ord-two-col">

                            <!-- Category -->
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
                                        <option value="<?php echo (int)$cat['category_id']; ?>">
                                            <?php echo htmlspecialchars($cat['category_name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="add-ord-field-hint" id="category-hint">
                                    Classification may be changed freely after creation.
                                </span>
                            </div>

                            <!-- Status -->
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
                                    <option value="Pending" selected>Pending</option>
                                    <option value="Active">Active</option>
                                    <option value="Repealed">Repealed</option>
                                    <option value="Amended">Amended</option>
                                </select>
                                <span class="add-ord-field-hint" id="status-hint">
                                    Reflects the current legislative standing. Mutable at any time.
                                </span>
                            </div>

                        </div><!-- /.add-ord-two-col -->

                    </fieldset><!-- /.add-ord-fieldset--mutable -->


                    <!-- ── SECTION 3: Optional Details (Once After Null) ── -->
                    <fieldset class="add-ord-fieldset add-ord-fieldset--once-after-null">
                        <legend class="add-ord-legend">
                            <span class="add-ord-legend-icon" aria-hidden="true">✎</span>
                            Optional Details
                            <span class="add-ord-legend-sub">Once-after-null — leave blank if unknown; cannot be overwritten once set</span>
                        </legend>

                        <div class="add-ord-two-col">

                            <!-- Barangay -->
                            <div class="form-group add-ord-form-group">
                                <label for="barangay_id" class="add-ord-label">
                                    Barangay
                                    <span class="add-ord-optional-tag">optional</span>
                                </label>
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
                                <span class="add-ord-field-hint" id="barangay-hint">
                                    ⚠ Once set, this cannot be changed through the application.
                                </span>
                            </div>

                            <!-- Date Enacted -->
                            <div class="form-group add-ord-form-group">
                                <label for="date_enacted" class="add-ord-label">
                                    Date Enacted
                                    <span class="add-ord-optional-tag">optional</span>
                                </label>
                                <input
                                    type="date"
                                    id="date_enacted"
                                    name="date_enacted"
                                    class="add-ord-input"
                                    max="<?php echo date('Y-m-d'); ?>"
                                    aria-describedby="date-hint" />
                                <span class="add-ord-field-hint" id="date-hint">
                                    ⚠ Once set, this cannot be changed through the application.
                                </span>
                            </div>

                        </div><!-- /.add-ord-two-col -->

                        <!-- Summary -->
                        <div class="form-group add-ord-form-group">
                            <label for="summary" class="add-ord-label">
                                Summary
                                <span class="add-ord-optional-tag">optional</span>
                            </label>
                            <textarea
                                id="summary"
                                name="summary"
                                class="add-ord-textarea"
                                placeholder="A brief plain-language description of this ordinance…"
                                rows="4"
                                aria-describedby="summary-hint summary-char-count"></textarea>
                            <div class="add-ord-textarea-footer">
                                <span class="add-ord-field-hint" id="summary-hint">
                                    ⚠ Once set, this cannot be changed through the application.
                                </span>
                                <span class="add-ord-char-count" id="summary-char-count" aria-live="polite">
                                    0 chars
                                </span>
                            </div>
                        </div>

                        <!-- Full Text -->
                        <div class="form-group add-ord-form-group">
                            <label for="full_text" class="add-ord-label">
                                Full Text
                                <span class="add-ord-optional-tag">optional</span>
                            </label>
                            <textarea
                                id="full_text"
                                name="full_text"
                                class="add-ord-textarea add-ord-textarea--fulltext"
                                placeholder="WHEREAS, the Sangguniang Panlungsod of Valenzuela City…"
                                rows="12"
                                aria-describedby="fulltext-hint fulltext-char-count"></textarea>
                            <div class="add-ord-textarea-footer">
                                <span class="add-ord-field-hint" id="fulltext-hint">
                                    ⚠ Once set, this cannot be changed through the application.
                                    Paste the complete legislative text here if available.
                                </span>
                                <span class="add-ord-char-count" id="fulltext-char-count" aria-live="polite">
                                    0 chars
                                </span>
                            </div>
                        </div>

                        <!-- PDF File Upload -->
                        <!--
                            NOTE FOR CONTROLLER IMPLEMENTER:
                            The uploaded file should be moved to a permanent storage path on the server
                            and only the resulting path string saved to the `pdf_file` column.
                            The schema stores a VARCHAR(255) path, not binary blob data.
                            Apply write-once-after-null enforcement in the model layer:
                            reject any UPDATE that overwrites a non-null pdf_file value.
                        -->
                        <div class="form-group add-ord-form-group">
                            <label for="pdf_file" class="add-ord-label">
                                PDF Document
                                <span class="add-ord-optional-tag">optional</span>
                            </label>
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
                            <span class="add-ord-field-hint" id="pdf-hint">
                                ⚠ Once uploaded, the document path cannot be changed through the application.
                                Accepts PDF files only.
                            </span>
                        </div>

                    </fieldset><!-- /.add-ord-fieldset--once-after-null -->


                    <!-- ── SECTION 4: Confirmation notice ─────────────── -->
                    <div class="add-ord-confirm-notice" role="note" aria-label="Submission notice">
                        <span class="add-ord-confirm-icon" aria-hidden="true">⚠</span>
                        <p class="add-ord-confirm-text">
                            By submitting this form you acknowledge that the
                            <strong>Identity Fields</strong> (Ordinance Number, Title, Author/Sponsor,
                            and Series Year) are <strong>permanently fixed</strong> after creation and
                            cannot be corrected through this interface. Double-check all values before
                            proceeding.
                        </p>
                    </div>

                    <!-- ── Form actions ───────────────────────────────── -->
                    <div class="add-ord-form-actions">
                        <a href="/dashboard" class="admin-btn admin-btn--ghost add-ord-cancel-btn">
                            Cancel
                        </a>
                        <button
                            type="submit"
                            class="admin-btn admin-btn--primary add-ord-submit-btn"
                            id="add-ord-submit">
                            Create Ordinance Record
                        </button>
                    </div>

                </form><!-- /#add-ordinance-form -->

            </section><!-- /.add-ord-form-col -->

        </div><!-- /.add-ord-layout -->

    </main><!-- /.add-ord-page -->

    <?php require_once __DIR__ . '/../footer.php'; ?>

    <script>
    // ============================================================
    // CHAR COUNT — title, summary, full_text
    // ============================================================
    function attachCharCount(textareaId, counterId, maxLength) {
        const ta  = document.getElementById(textareaId);
        const ctr = document.getElementById(counterId);
        if (!ta || !ctr) return;

        function update() {
            const len = ta.value.length;
            ctr.textContent = maxLength
                ? `${len} / ${maxLength}`
                : `${len} chars`;

            const nearLimit = maxLength && len >= maxLength * 0.90;
            ctr.classList.toggle('add-ord-char-count--warn', nearLimit);
        }

        ta.addEventListener('input', update);
        update(); // initialise
    }

    attachCharCount('title',     'title-char-count',    255);
    attachCharCount('summary',   'summary-char-count',  null);
    attachCharCount('full_text', 'fulltext-char-count', null);


    // ============================================================
    // PDF DRAG-AND-DROP ZONE
    // ============================================================
    const dropZone   = document.getElementById('pdf-drop-zone');
    const fileInput  = document.getElementById('pdf_file');
    const filePrompt = document.getElementById('pdf-file-prompt');

    function setFileName(name) {
        filePrompt.textContent = name || 'Click to choose or drag & drop a PDF';
        dropZone.classList.toggle('add-ord-file-drop--selected', !!name);
    }

    // Click on zone → trigger hidden file input
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

        // Transfer dropped file to the hidden input via DataTransfer
        const dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;
        setFileName(file.name);
    });


    // ============================================================
    // CLIENT-SIDE VALIDATION — Write-once fields confirmation
    // Prompts the user to explicitly confirm before submit.
    // Server-side validation in the controller is the authoritative check.
    // ============================================================
    document.getElementById('add-ordinance-form')
        .addEventListener('submit', function (e) {
            const number = document.getElementById('ordinance_number').value.trim();
            const title  = document.getElementById('title').value.trim();
            const author = document.getElementById('author_sponsor').value.trim();
            const year   = document.getElementById('series_year').value.trim();

            // Basic presence guard (HTML5 `required` already handles this,
            // but we add an explicit check in case novalidate bypasses it)
            if (!number || !title || !author || !year) {
                e.preventDefault();
                alert('Please fill in all required Identity Fields before submitting.');
                return;
            }

            // Explicit acknowledgement dialog for write-once fields
            const confirmed = window.confirm(
                `You are about to permanently record:\n\n` +
                `  Ordinance No.: ${number}\n` +
                `  Series Year:   ${year}\n` +
                `  Title:         ${title.substring(0, 80)}${title.length > 80 ? '…' : ''}\n` +
                `  Sponsor:       ${author}\n\n` +
                `These fields cannot be changed after creation.\n` +
                `Click OK only if all values are correct.`
            );

            if (!confirmed) {
                e.preventDefault();
            }
        });
    </script>

</body>
</html>