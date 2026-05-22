<?php

namespace App\Views\pages;

use App\Models\OrdinanceModel;
use App\Models\CommentModel;

session_start();
$loggedIn = isset($_SESSION['user_id']);

// ============================================================
// ORDINANCE DETAIL PAGE
// Phase 1: Ingestion and State Management
// ============================================================

$ordinance_id = isset($_GET['id']) ? (int) trim($_GET['id']) : 0;

// ============================================================
// Phase 2: Database Interrogation
// ============================================================

$pdo = \App\Controllers\DatabaseController::getDatabaseConnection();
$ordinanceModel = new OrdinanceModel($pdo);
$ordinance = $ordinanceModel->getOrdinanceById($ordinance_id);

// Redirect to the browse page when the requested ordinance
// does not exist or has been soft-deleted, rather than
// rendering a broken detail page.
if ($ordinance === null) {
    header('Location: /browse');
    exit;
}

$userReaction = null;
if ($loggedIn && $ordinance_id > 0) {
    $reactionStmt = $pdo->prepare(
        'SELECT reaction_type FROM Ordinance_Reactions WHERE ordinance_id = :ord AND user_id = :usr LIMIT 1'
    );
    $reactionStmt->execute([
        ':ord' => $ordinance_id,
        ':usr' => (int)$_SESSION['user_id'],
    ]);
    $reactionRow = $reactionStmt->fetch(
        \PDO::FETCH_ASSOC
    );
    $userReaction = $reactionRow !== false ? $reactionRow['reaction_type'] : null;
}

$commentModel   = new CommentModel($pdo);
$comments       = $commentModel->getOrdinanceComments($ordinance_id);

// Bulk-fetch the logged-in user's reactions for all comments in one query
$commentReactions = [];
if ($loggedIn && !empty($comments)) {
    $commentIds       = array_column($comments, 'comment_id');
    $commentReactions = $commentModel->getUserReactionsForComments(
        (int) $_SESSION['user_id'],
        $commentIds
    );
}

// Fix presentation of ordinance infos
$ordinance['summary']           = $ordinance['summary']             ?? 'No summary available.';
$ordinance['full_text']         = $ordinance['full_text']           ?? 'No full text available.';
$ordinance['category_name']     = $ordinance['category_name']       ?? '---';
$ordinance['barangay_name']     = $ordinance['barangay_name']       ?? '---';
$ordinance['enactment_day']     = $ordinance['enactment_day']       ?? '';
$ordinance['enactment_month']   = $ordinance['enactment_month']     ?? '';
$ordinance['enactment_year']    = $ordinance['enactment_year']      ?? '(Pending enactment)';
$ordinance['date_enacted_fmt']  = $ordinance['date_enacted_fmt']    ?? '';

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

require VIEWS_ROOT . '/head.php';
?>

<body class="<?php echo htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8'); ?>">

    <?php require_once VIEWS_ROOT . '/header.php'; ?>

    <main class="ordinance-page">

        <!-- ====================================================
             SUBHERO: Ordinance title + breadcrumb
        ==================================================== -->
        <div class="subhero ordinance-subhero">
            <div class="subhero-content">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="/home">Home</a>
                    <span class="separator" aria-hidden="true">›</span>
                    <a href="/browse">Ordinances</a>
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
                            class="ord-reaction-btn ord-reaction-btn--like<?php echo $userReaction === 'like' ? ' ord-reaction-btn--active' : ''; ?>"
                            id="btn-like"
                            aria-label="Like this ordinance"
                            aria-pressed="<?php echo $userReaction === 'like' ? 'true' : 'false'; ?>"
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
                            class="ord-reaction-btn ord-reaction-btn--dislike<?php echo $userReaction === 'dislike' ? ' ord-reaction-btn--active' : ''; ?>"
                            id="btn-dislike"
                            aria-label="Dislike this ordinance"
                            aria-pressed="<?php echo $userReaction === 'dislike' ? 'true' : 'false'; ?>"
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
                                The official document for this ordinance has not been uploaded yet. Check back later or contact the City Council for a copy.
                            </p>
                            <!-- <a href="mailto:info@encyclawphilia.local" class="ord-pdf-contact-link">
                                Request Document
                            </a> -->
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
            <form id="comment-form" method="post" action="/comments/post">
                <div class="ord-comment-compose">
                    <div class="ord-comment-compose-avatar" aria-hidden="true">
                        <?php echo $loggedIn
                            ? htmlspecialchars(strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)), ENT_QUOTES, 'UTF-8')
                            : '?'; ?>
                    </div>
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
                            <?php if ($loggedIn): ?>
                                <button
                                    class="ord-comment-submit"
                                    id="comment-submit"
                                    type="button"
                                    data-ordinance-id="<?php echo (int)$ordinance['ordinance_id']; ?>"
                                    data-user-id="<?php echo (int)$_SESSION['user_id']; ?>"
                                    disabled>
                                    Post Comment
                                </button>
                            <?php else: ?>
                                <a href="/login" class="ord-comment-login-prompt">
                                    Login to post a comment
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>

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

                                <div class="ord-comment-footer">
                                    <div class="ord-comment-reactions">
                                        <?php
                                        $cid          = (int) $comment['comment_id'];
                                        $userCReaction = $commentReactions[$cid] ?? null;
                                        ?>
                                        <button
                                            class="ord-comment-react-btn ord-comment-react-btn--like<?php echo $userCReaction === 'like' ? ' ord-comment-react-btn--active' : ''; ?>"
                                            aria-label="Like comment by <?php echo htmlspecialchars($comment['username'], ENT_QUOTES, 'UTF-8'); ?>"
                                            aria-pressed="<?php echo $userCReaction === 'like' ? 'true' : 'false'; ?>"
                                            data-comment-id="<?php echo $cid; ?>">
                                            <span aria-hidden="true">▲</span>
                                            <span class="ord-comment-react-count">
                                                <?php echo htmlspecialchars($comment['like_count'], ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </button>
                                        <button
                                            class="ord-comment-react-btn ord-comment-react-btn--dislike<?php echo $userCReaction === 'dislike' ? ' ord-comment-react-btn--active' : ''; ?>"
                                            aria-label="Dislike comment by <?php echo htmlspecialchars($comment['username'], ENT_QUOTES, 'UTF-8'); ?>"
                                            aria-pressed="<?php echo $userCReaction === 'dislike' ? 'true' : 'false'; ?>"
                                            data-comment-id="<?php echo $cid; ?>">
                                            <span aria-hidden="true">▼</span>
                                            <span class="ord-comment-react-count">
                                                <?php echo htmlspecialchars($comment['dislike_count'], ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </button>
                                    </div>
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

        </section><!-- /.ord-comments-section -->

        <!-- ================================================================
         COMMENT POLICY CONFIRMATION MODAL
         Shown once per submit attempt before the POST is dispatched.
    ================================================================ -->
        <div
            class="auth-modal-backdrop"
            id="comment-policy-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="comment-policy-title"
            aria-describedby="comment-policy-desc">

            <div class="auth-modal">
                <div class="auth-modal--header">
                    <div class="modal-icon" aria-hidden="true">💬</div>
                    <span class="modal-title" id="comment-policy-title">Public Comment Policy</span>
                    <button
                        class="modal-close"
                        type="button"
                        id="comment-policy-close"
                        aria-label="Close">✕</button>
                </div>
                <div class="auth-modal--body">
                    <p id="comment-policy-desc">
                        You are about to post a comment on
                        <strong>EncycLawPhilia Valenzuela</strong>, a public civic forum.
                        Your comment will be permanently visible to all visitors and
                        cannot be edited or removed after submission.
                    </p>
                    <p class="modal-sub">
                        By proceeding, you confirm that your comment is respectful,
                        relevant to the ordinance, and does not contain offensive,
                        defamatory, or misleading content. Violations may result in
                        account deactivation.
                    </p>
                    <label class="comment-policy-dont-show-row">
                        <input
                            type="checkbox"
                            id="comment-policy-dont-show"
                            class="comment-policy-dont-show-checkbox" />
                        <span>Don't show this again on this device</span>
                    </label>
                    <div class="auth-modal--actions">
                        <button
                            class="btn-modal-dismiss"
                            type="button"
                            id="comment-policy-cancel">
                            Cancel
                        </button>
                        <button
                            class="btn-modal-retry"
                            type="button"
                            id="comment-policy-confirm">
                            I Understand — Post Comment
                        </button>
                    </div>
                </div>
            </div>

        </div><!-- /#comment-policy-modal -->

    </main>

    </main>

    <?php require_once __DIR__ . '/../footer.php'; ?>

    <script src="js/sanitize.js"></script>
    <script src="js/react_ordinance.js"></script>
    <script src="js/pages/ordinance.js"></script>

</body>

</html>