const ReactionUI = {
    // ── Optimistic update ─────────────────────────────────────────────
    applyOptimistic(clickedType) {
        const likeBtn = document.getElementById('btn-like');
        const dislikeBtn = document.getElementById('btn-dislike');
        const wasActive = likeBtn.classList.contains('ord-reaction-btn--active') &&
            clickedType === 'like' ||
            dislikeBtn.classList.contains('ord-reaction-btn--active') &&
            clickedType === 'dislike';

        // Immediately reflect what we expect to happen
        if (clickedType === 'like') {
            likeBtn.classList.toggle('ord-reaction-btn--active', !wasActive);
            dislikeBtn.classList.remove('ord-reaction-btn--active');
        } else {
            dislikeBtn.classList.toggle('ord-reaction-btn--active', !wasActive);
            likeBtn.classList.remove('ord-reaction-btn--active');
        }
    },

    // ── Reconcile with server truth ───────────────────────────────────
    reconcile(data) {
        document.getElementById('like-count').textContent = data.likes;
        document.getElementById('dislike-count').textContent = data.dislikes;

        document.getElementById('btn-like')
            .classList.toggle('ord-reaction-btn--active', data.userReaction === 'like');
        document.getElementById('btn-dislike')
            .classList.toggle('ord-reaction-btn--active', data.userReaction === 'dislike');

        const total = data.likes + data.dislikes;
        const likePct = total > 0 ? Math.round((data.likes / total) * 100) : 50;
        document.querySelector('.ord-reaction-bar-fill').style.width = likePct + '%';
        document.querySelector('.ord-reaction-summary').textContent =
            `${likePct}% of respondents support this ordinance (${total.toLocaleString()} total)`;
    },

    // ── Roll back on failure ──────────────────────────────────────────
    rollback(previousLikeActive, previousDislikeActive) {
        document.getElementById('btn-like')
            .classList.toggle('ord-reaction-btn--active', previousLikeActive);
        document.getElementById('btn-dislike')
            .classList.toggle('ord-reaction-btn--active', previousDislikeActive);
    }
};

document.querySelectorAll('.ord-reaction-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const ordinanceId = btn.dataset.ordinanceId;
        const reactionType = btn.dataset.reaction; // 'like' or 'dislike'

        // Snapshot state before mutation for rollback
        const prevLike = document.getElementById('btn-like').classList.contains('ord-reaction-btn--active');
        const prevDislike = document.getElementById('btn-dislike').classList.contains('ord-reaction-btn--active');

        ReactionUI.applyOptimistic(reactionType);

        const form = new FormData();
        form.append('ordinance_id', ordinanceId);
        form.append('reaction_type', reactionType);

        try {
            const res = await fetch('/react', {
                method: 'POST',
                body: form
            });
            const payload = await res.json(); // always { ok, message, data, [code] }

            if (payload.ok) {
                ReactionUI.reconcile(payload.data);
            } else {
                ReactionUI.rollback(prevLike, prevDislike);

                if (payload.code === 401) {
                    // Unauthenticated — redirect instead of an inline error
                    window.location.href = '/login';
                } else {
                    // Surface the server's message directly — no re-inventing error text
                    showToast(payload.message);
                }
            }
        } catch (networkError) {
            // fetch() itself threw — no response at all
            ReactionUI.rollback(prevLike, prevDislike);
            showToast('Network error. Please check your connection.');
        }
    });
});

function showToast(message) {
    // Replace with whatever toast/notification system you already use
    const t = document.createElement('div');
    t.className = 'reaction-toast';
    t.textContent = message;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}