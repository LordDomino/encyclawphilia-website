document.addEventListener('DOMContentLoaded', () => {
    const textarea = document.getElementById('comment-input');
    const charCount = document.getElementById('char-count');
    const submitBtn = document.getElementById('comment-submit');
    const feed = document.getElementById('comment-feed');
    const countBadge = document.querySelector('.ord-comments-count');
    const composeAvatar = document.querySelector('.ord-comment-compose-avatar');

    const MAX_LENGTH = 1000;

    // ── Char counter ──────────────────────────────────────────────────────
    textarea?.addEventListener('input', () => {
        const len = textarea.value.length;
        charCount.textContent = `${len} / ${MAX_LENGTH}`;
        charCount.classList.toggle('ord-comment-char-count--warn', len >= MAX_LENGTH * 0.9);
        if (submitBtn) submitBtn.disabled = len === 0 || len > MAX_LENGTH;
    });

    // ── Submit ────────────────────────────────────────────────────────────
    submitBtn?.addEventListener('click', async () => {
        const text = textarea.value.trim();
        const ordinanceId = submitBtn.dataset.ordinanceId;

        if (!text || !ordinanceId) return;

        submitBtn.disabled = true;
        submitBtn.textContent = 'Posting...';

        const form = new FormData();
        form.append('ordinance_id', ordinanceId);
        form.append('comment_text', text);

        try {
            const res = await fetch('/comment', { method: 'POST', body: form });
            const payload = await res.json();

            if (payload.ok) {
                textarea.value = '';
                charCount.textContent = `0 / ${MAX_LENGTH}`;
                prependComment(payload.data);
                updateCommentCount(1);
                submitBtn.textContent = 'Post Comment';
            } else {
                if (payload.code === 401) {
                    window.location.href = '/login';
                    return;
                }
                showCommentError(payload.message);
                submitBtn.disabled = false;
                submitBtn.textContent = 'Post Comment';
            }
        } catch {
            showCommentError('Network error. Please check your connection.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Post Comment';
        }
    });

    // ── DOM helpers ───────────────────────────────────────────────────────
    function prependComment(data) {
        // Remove "no comments yet" placeholder if present
        feed.querySelector('.ord-comments-empty')?.remove();

        const initials = data.username
            .split(' ')
            .map(w => w[0] ?? '')
            .slice(0, 2)
            .join('')
            .toUpperCase();

        const dateStr = new Date(data.created_at)
            .toLocaleDateString('en-PH', { day: '2-digit', month: 'short', year: 'numeric' });

        const article = document.createElement('article');
        article.className = 'ord-comment-card animate-in';
        article.id = `comment-${data.comment_id}`;
        article.setAttribute('aria-label', `Comment by ${escapeHtml(data.username)}`);

        article.innerHTML = `
            <div class="ord-comment-avatar" aria-hidden="true">${escapeHtml(initials)}</div>
            <div class="ord-comment-body">
                <header class="ord-comment-meta">
                    <span class="ord-comment-author">${escapeHtml(data.username)}</span>
                    <time class="ord-comment-time" datetime="${escapeHtml(data.created_at)}">${escapeHtml(dateStr)}</time>
                </header>
                <p class="ord-comment-text">${escapeHtml(data.comment_text)}</p>
                <div class="ord-comment-footer">
                    <div class="ord-comment-reactions">
                        <button class="ord-comment-react-btn ord-comment-react-btn--like"
                            aria-label="Like comment by ${escapeHtml(data.username)}"
                            data-comment-id="${data.comment_id}">
                            <span aria-hidden="true">▲</span>
                            <span class="ord-comment-react-count">0</span>
                        </button>
                        <button class="ord-comment-react-btn ord-comment-react-btn--dislike"
                            aria-label="Dislike comment by ${escapeHtml(data.username)}"
                            data-comment-id="${data.comment_id}">
                            <span aria-hidden="true">▼</span>
                            <span class="ord-comment-react-count">0</span>
                        </button>
                    </div>
                </div>
            </div>`;

        feed.insertBefore(article, feed.firstChild);
    }

    function updateCommentCount(delta) {
        if (!countBadge) return;
        countBadge.textContent = (parseInt(countBadge.textContent, 10) || 0) + delta;
    }

    function showCommentError(message) {
        // Reuse the same toast pattern from react_ordinance.js
        const existing = document.getElementById('comment-error-toast');
        existing?.remove();

        const t = document.createElement('div');
        t.id = 'comment-error-toast';
        t.className = 'reaction-toast';
        t.textContent = message;
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 3500);
    }

    // ── Comment reactions (delegated — covers prepended comments too) ─────
    feed?.addEventListener('click', async (e) => {
        const btn = e.target.closest('.ord-comment-react-btn');
        if (!btn) return;

        const commentId = btn.dataset.commentId;
        const reactionType = btn.classList.contains('ord-comment-react-btn--like') ? 'like' : 'dislike';

        const card = btn.closest('.ord-comment-card');
        const likeBtn = card.querySelector('.ord-comment-react-btn--like');
        const dislikeBtn = card.querySelector('.ord-comment-react-btn--dislike');

        // Snapshot for rollback
        const prevLikeActive = likeBtn.classList.contains('ord-comment-react-btn--active');
        const prevDislikeActive = dislikeBtn.classList.contains('ord-comment-react-btn--active');

        // Optimistic update
        applyCommentOptimistic(likeBtn, dislikeBtn, reactionType);

        const form = new FormData();
        form.append('comment_id', commentId);
        form.append('reaction_type', reactionType);

        try {
            const res = await fetch('/react-comment', { method: 'POST', body: form });
            const payload = await res.json();

            if (payload.ok) {
                reconcileComment(likeBtn, dislikeBtn, payload.data);
            } else {
                rollbackComment(likeBtn, dislikeBtn, prevLikeActive, prevDislikeActive);
                if (payload.code === 401) {
                    window.location.href = '/login';
                } else {
                    showCommentError(payload.message);
                }
            }
        } catch {
            rollbackComment(likeBtn, dislikeBtn, prevLikeActive, prevDislikeActive);
            showCommentError('Network error. Please check your connection.');
        }
    });

    function applyCommentOptimistic(likeBtn, dislikeBtn, reactionType) {
        const isLike = reactionType === 'like';
        const activeBtn = isLike ? likeBtn : dislikeBtn;
        const otherBtn = isLike ? dislikeBtn : likeBtn;

        const wasActive = activeBtn.classList.contains('ord-comment-react-btn--active');
        activeBtn.classList.toggle('ord-comment-react-btn--active', !wasActive);
        activeBtn.setAttribute('aria-pressed', String(!wasActive));
        otherBtn.classList.remove('ord-comment-react-btn--active');
        otherBtn.setAttribute('aria-pressed', 'false');
    }

    function reconcileComment(likeBtn, dislikeBtn, data) {
        likeBtn.querySelector('.ord-comment-react-count').textContent = data.likes;
        dislikeBtn.querySelector('.ord-comment-react-count').textContent = data.dislikes;

        likeBtn.classList.toggle('ord-comment-react-btn--active', data.userReaction === 'like');
        likeBtn.setAttribute('aria-pressed', String(data.userReaction === 'like'));
        dislikeBtn.classList.toggle('ord-comment-react-btn--active', data.userReaction === 'dislike');
        dislikeBtn.setAttribute('aria-pressed', String(data.userReaction === 'dislike'));
    }

    function rollbackComment(likeBtn, dislikeBtn, prevLike, prevDislike) {
        likeBtn.classList.toggle('ord-comment-react-btn--active', prevLike);
        likeBtn.setAttribute('aria-pressed', String(prevLike));
        dislikeBtn.classList.toggle('ord-comment-react-btn--active', prevDislike);
        dislikeBtn.setAttribute('aria-pressed', String(prevDislike));
    }
});