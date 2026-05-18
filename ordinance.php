<?php

session_start();
$loggedIn = isset($_SESSION['user_id']);

// ============================================================
// ORDINANCE DETAIL PAGE
// Phase 1: Ingestion and State Management
// ============================================================

require_once 'config/database.php';
require_once 'php/procedures.php';

$ordinance_id = isset($_GET['id']) ? (int) trim($_GET['id']) : 0;

// ============================================================
// Phase 2: Database Interrogation
// ============================================================

$pdo       = getDatabaseConnection();
$ordinance = getOrdinanceById($pdo, $ordinance_id);

// Redirect to the browse page when the requested ordinance
// does not exist or has been soft-deleted, rather than
// rendering a broken detail page.
if ($ordinance === null) {
    header('Location: browse.php');
    exit;
}

$comments = getOrdinanceComments($pdo, $ordinance_id);

// ============================================================
// Phase 3: HTML Presentation
// ============================================================
$pageTitle   = "City Ordinance No. {$ordinance['ordinance_number']} s. {$ordinance['series_year']} | EncycLawPhilia Valenzuela";
$currentPage = "ordinance";

// Map DB status values to display badge classes
$status_map = [
    'enacted'  => ['label' => 'Enacted',  'class' => 'passed'],
    'pending'  => ['label' => 'Pending',  'class' => 'pending'],
    'draft'    => ['label' => 'Draft',    'class' => 'in-progress'],
    'repealed' => ['label' => 'Repealed', 'class' => 'rejected'],
];
$status_display = $status_map[$ordinance['status']] ?? ['label' => ucfirst($ordinance['status']), 'class' => 'pending'];

require_once 'fragments/head.php';
require_once 'php/helpers/view_components.php';
?>

<body class="<?php echo htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8'); ?>">

    <?php require_once 'fragments/header.php'; ?>

    <main class="ordinance-page">

        <!-- ====================================================
             SUBHERO: Ordinance title + breadcrumb
        ==================================================== -->
        <div class="subhero ordinance-subhero">
            <div class="subhero-content">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="index.php">Home</a>
                    <span class="separator" aria-hidden="true">›</span>
                    <a href="browse.php">Ordinances</a>
                    <span class="separator" aria-hidden="true">›</span>
                    <span aria-current="page">
                        No. <?php echo htmlspecialchars($ordinance['ordinance_number'], ENT_QUOTES, 'UTF-8'); ?>
                        s. <?php echo htmlspecialchars($ordinance['series_year'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </nav>
                <div class="ordinance-subhero-meta">
                    <div class="ordinance-subhero-id">
                        <span class="card-type">City Ordinance</span>
                        <span class="numeral-and-series ordinance-numeral">
                            No. <?php echo htmlspecialchars($ordinance['ordinance_number'], ENT_QUOTES, 'UTF-8'); ?>
                            <span class="series-year">s. <?php echo htmlspecialchars($ordinance['series_year'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </span>
                    </div>
                    <span class="status-badge <?php echo htmlspecialchars($status_display['class'], ENT_QUOTES, 'UTF-8'); ?> status-badge--hero">
                        <?php echo htmlspecialchars($status_display['label'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>
                <h1 class="ordinance-title">
                    <?php echo htmlspecialchars($ordinance['title'], ENT_QUOTES, 'UTF-8'); ?>
                </h1>
            </div>
        </div>

        <!-- ====================================================
             SPLIT COLUMNS: Summary (left) + PDF Viewer (right)
        ==================================================== -->
        <div class="ordinance-split">

            <!-- LEFT: Summary & Metadata -->
            <aside class="ordinance-info-col">

                <!-- Date block -->
                <div class="ord-date-block">
                    <span class="date-day"><?php echo htmlspecialchars($ordinance['enactment_day'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <div class="date-meta">
                        <span class="date-month"><?php echo htmlspecialchars($ordinance['enactment_month'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="date-year"><?php echo htmlspecialchars($ordinance['enactment_year'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>

                <hr class="ord-divider" />

                <!-- Metadata list -->
                <dl class="ord-meta-list">
                    <div class="ord-meta-item">
                        <dt>Sponsor</dt>
                        <dd><?php echo htmlspecialchars($ordinance['author_sponsor'], ENT_QUOTES, 'UTF-8'); ?></dd>
                    </div>
                    <div class="ord-meta-item">
                        <dt>Category</dt>
                        <dd><?php echo htmlspecialchars($ordinance['category_name'], ENT_QUOTES, 'UTF-8'); ?></dd>
                    </div>
                    <div class="ord-meta-item">
                        <dt>Barangay</dt>
                        <dd><?php echo htmlspecialchars($ordinance['barangay_name'], ENT_QUOTES, 'UTF-8'); ?></dd>
                    </div>
                    <div class="ord-meta-item">
                        <dt>Status</dt>
                        <dd>
                            <span class="status-badge <?php echo htmlspecialchars($status_display['class'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($status_display['label'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </dd>
                    </div>
                </dl>

                <hr class="ord-divider" />

                <!-- Summary paragraph -->
                <div class="ord-summary">
                    <h2 class="ord-section-label">Summary</h2>
                    <p><?php echo htmlspecialchars($ordinance['summary'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>

                <hr class="ord-divider" />

                <!-- Reaction bar -->
                <div class="ord-reactions" id="ord-reactions">
                    <span class="ord-reactions-label">Community Response</span>
                    <div class="ord-reaction-buttons">
                        <button
                            class="ord-reaction-btn ord-reaction-btn--like"
                            id="btn-like"
                            aria-label="Like this ordinance"
                            data-ordinance-id="<?php echo (int)$ordinance['ordinance_id']; ?>"
                            data-reaction="like">
                            <span class="ord-reaction-icon" aria-hidden="true">▲</span>
                            <span class="ord-reaction-count" id="like-count">
                                <?php echo htmlspecialchars($ordinance['like_count'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                            <span class="ord-reaction-word">Support</span>
                        </button>
                        <span class="ord-reaction-divider" aria-hidden="true"></span>
                        <button
                            class="ord-reaction-btn ord-reaction-btn--dislike"
                            id="btn-dislike"
                            aria-label="Dislike this ordinance"
                            data-ordinance-id="<?php echo (int)$ordinance['ordinance_id']; ?>"
                            data-reaction="dislike">
                            <span class="ord-reaction-icon" aria-hidden="true">▼</span>
                            <span class="ord-reaction-count" id="dislike-count">
                                <?php echo htmlspecialchars($ordinance['dislike_count'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                            <span class="ord-reaction-word">Oppose</span>
                        </button>
                    </div>
                    <!-- Visual ratio bar -->
                    <?php
                    $total_reactions = $ordinance['like_count'] + $ordinance['dislike_count'];
                    $like_pct = $total_reactions > 0 ? round(($ordinance['like_count'] / $total_reactions) * 100) : 50;
                    ?>
                    <div class="ord-reaction-bar" title="<?php echo $like_pct; ?>% support">
                        <div class="ord-reaction-bar-fill" style="width: <?php echo $like_pct; ?>%"></div>
                    </div>
                    <p class="ord-reaction-summary">
                        <?php echo $like_pct; ?>% of respondents support this ordinance
                        <span class="ord-reaction-total">(<?php echo number_format($total_reactions); ?> total)</span>
                    </p>
                </div>

            </aside>

            <!-- RIGHT: Embedded PDF Viewer -->
            <section class="ordinance-pdf-col">
                <div class="ord-pdf-viewer">
                    <div class="ord-pdf-header">
                        <span class="ord-pdf-label">Official Document</span>
                        <?php if (!empty($ordinance['pdf_file'])): ?>
                            <a
                                href="<?php echo htmlspecialchars($ordinance['pdf_file'], ENT_QUOTES, 'UTF-8'); ?>"
                                class="ord-pdf-download"
                                download
                                aria-label="Download PDF">
                                ↓ Download PDF
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($ordinance['pdf_file'])): ?>
                        <iframe
                            class="ord-pdf-frame"
                            src="<?php echo htmlspecialchars($ordinance['pdf_file'], ENT_QUOTES, 'UTF-8'); ?>#toolbar=1&navpanes=0&scrollbar=1"
                            title="Ordinance No. <?php echo htmlspecialchars($ordinance['ordinance_number'], ENT_QUOTES, 'UTF-8'); ?> PDF"
                            aria-label="PDF viewer for City Ordinance No. <?php echo htmlspecialchars($ordinance['ordinance_number'], ENT_QUOTES, 'UTF-8'); ?>">
                            <p>Your browser does not support embedded PDFs.
                                <a href="<?php echo htmlspecialchars($ordinance['pdf_file'], ENT_QUOTES, 'UTF-8'); ?>">Download the PDF</a>
                                to view it instead.
                            </p>
                        </iframe>
                    <?php else: ?>
                        <div class="ord-pdf-unavailable" role="status">
                            <div class="ord-pdf-unavailable-icon" aria-hidden="true">📄</div>
                            <p class="ord-pdf-unavailable-title">PDF Not Yet Available</p>
                            <p class="ord-pdf-unavailable-sub">
                                The official document for this ordinance has not been uploaded yet.
                                Check back later or contact the City Council for a copy.
                            </p>
                            <a href="mailto:info@encyclawphilia.local" class="ord-pdf-contact-link">
                                Request Document
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

        </div><!-- /.ordinance-split -->

        <!-- ====================================================
             COMMENTS SECTION
        ==================================================== -->
        <section class="ord-comments-section" id="comments" aria-labelledby="comments-heading">

            <div class="ord-comments-header">
                <h2 id="comments-heading" class="ord-comments-title">
                    Community Discussion
                    <span class="ord-comments-count"><?php echo count($comments); ?></span>
                </h2>
                <p class="ord-comments-subtitle">
                    Share your thoughts on City Ordinance No.
                    <?php echo htmlspecialchars($ordinance['ordinance_number'], ENT_QUOTES, 'UTF-8'); ?>.
                    All comments are subject to community guidelines.
                </p>
            </div>

            <!-- Comment compose box -->
            <div class="ord-comment-compose">
                <div class="ord-comment-compose-avatar" aria-hidden="true">?</div>
                <div class="ord-comment-compose-body">
                    <textarea
                        class="ord-comment-textarea"
                        id="comment-input"
                        placeholder="Share your perspective on this ordinance..."
                        rows="3"
                        aria-label="Write a comment"
                        maxlength="1000"></textarea>
                    <div class="ord-comment-compose-footer">
                        <span class="ord-comment-char-count" id="char-count">0 / 1000</span>
                        <a href="login.php" class="ord-comment-login-prompt">
                            Login to post a comment
                        </a>
                        <!-- TODO: Replace anchor with submit button once auth is integrated:
                        <button class="ord-comment-submit" id="comment-submit" type="button">
                            Post Comment
                        </button>
                        -->
                    </div>
                </div>
            </div>

            <!-- Comment feed -->
            <div class="ord-comment-feed" id="comment-feed" role="feed" aria-label="Comments">

                <?php if (!empty($comments)): ?>
                    <?php foreach ($comments as $comment): ?>
                        <?php
                        // Derive initials from full_name for avatar
                        $name_parts = explode(' ', $comment['username']);
                        $initials   = strtoupper(
                            substr($name_parts[0], 0, 1) .
                            (count($name_parts) > 1 ? substr(end($name_parts), 0, 1) : '')
                        );
                        // Format relative/absolute date
                        $comment_ts  = strtotime($comment['created_at']);
                        $comment_ago = date('d M Y', $comment_ts);
                        ?>
                        <article
                            class="ord-comment-card"
                            id="comment-<?php echo (int)$comment['comment_id']; ?>"
                            aria-label="Comment by <?php echo htmlspecialchars($comment['username'], ENT_QUOTES, 'UTF-8'); ?>">

                            <div class="ord-comment-avatar" aria-hidden="true">
                                <?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?>
                            </div>

                            <div class="ord-comment-body">
                                <header class="ord-comment-meta">
                                    <span class="ord-comment-author">
                                        <?php echo htmlspecialchars($comment['username'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                    <time
                                        class="ord-comment-time"
                                        datetime="<?php echo htmlspecialchars($comment['created_at'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($comment_ago, ENT_QUOTES, 'UTF-8'); ?>
                                    </time>
                                </header>

                                <p class="ord-comment-text">
                                    <?php echo htmlspecialchars($comment['comment_text'], ENT_QUOTES, 'UTF-8'); ?>
                                </p>

                                <footer class="ord-comment-footer">
                                    <div class="ord-comment-reactions">
                                        <button
                                            class="ord-comment-react-btn ord-comment-react-btn--like"
                                            aria-label="Like comment by <?php echo htmlspecialchars($comment['username'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-comment-id="<?php echo (int)$comment['comment_id']; ?>">
                                            <span aria-hidden="true">▲</span>
                                            <span class="ord-comment-react-count">
                                                <?php echo htmlspecialchars($comment['like_count'], ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </button>
                                        <button
                                            class="ord-comment-react-btn ord-comment-react-btn--dislike"
                                            aria-label="Dislike comment by <?php echo htmlspecialchars($comment['username'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-comment-id="<?php echo (int)$comment['comment_id']; ?>">
                                            <span aria-hidden="true">▼</span>
                                            <span class="ord-comment-react-count">
                                                <?php echo htmlspecialchars($comment['dislike_count'], ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </button>
                                    </div>
                                    <button class="ord-comment-reply-btn" aria-label="Reply to this comment">
                                        Reply
                                    </button>
                                </footer>
                            </div>

                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="ord-comments-empty" role="status">
                        <p>No comments yet. Be the first to share your perspective.</p>
                    </div>
                <?php endif; ?>

            </div><!-- /.ord-comment-feed -->

        </section><!-- /.ord-comments-section -->

    </main>

    <?php require_once 'fragments/footer.php'; ?>

    <script>
        // ============================================================
        // COMMENT TEXTAREA CHAR COUNT
        // ============================================================
        const textarea  = document.getElementById('comment-input');
        const charCount = document.getElementById('char-count');

        textarea?.addEventListener('input', () => {
            const len = textarea.value.length;
            charCount.textContent = `${len} / 1000`;
            charCount.classList.toggle('ord-comment-char-count--warn', len > 900);
        });

        // ============================================================
        // ORDINANCE REACTION BUTTONS (optimistic UI — no auth yet)
        // Replace alert stubs with fetch() calls once the API exists.
        // ============================================================
        document.querySelectorAll('.ord-reaction-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                // TODO: Replace with authenticated POST to react.php
                const reaction = btn.dataset.reaction;
                btn.classList.toggle('ord-reaction-btn--active');

                // Deactivate the sibling button
                const sibling = btn.closest('.ord-reaction-buttons')
                    ?.querySelector(`.ord-reaction-btn:not([data-reaction="${reaction}"])`);
                sibling?.classList.remove('ord-reaction-btn--active');
            });
        });

        // ============================================================
        // COMMENT REACTION BUTTONS (optimistic UI)
        // ============================================================
        document.querySelectorAll('.ord-comment-react-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                // TODO: Replace with authenticated POST to react.php
                btn.classList.toggle('ord-comment-react-btn--active');

                const siblingSelector = btn.classList.contains('ord-comment-react-btn--like')
                    ? '.ord-comment-react-btn--dislike'
                    : '.ord-comment-react-btn--like';
                btn.closest('.ord-comment-reactions')
                    ?.querySelector(siblingSelector)
                    ?.classList.remove('ord-comment-react-btn--active');
            });
        });
    </script>

</body>
</html>