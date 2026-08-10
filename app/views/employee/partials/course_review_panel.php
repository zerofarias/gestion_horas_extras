<?php
/** @var object $course */
$stats = $data['review_stats'] ?? null;
$course_reviews = $data['course_reviews'] ?? [];
$user_review = $data['user_review'] ?? null;
if (!$stats) return;
$pct = learning_review_positive_pct($stats);
$myVote = $user_review ? ($user_review->vote ?? '') : '';
?>
<div class="lrn-reviews-panel">
    <div class="lrn-reviews-hero mb-4">
        <div>
            <h3 class="h5 mb-1"><i class="fas fa-star-half-alt me-1"></i> Tu opinión del curso</h3>
            <p class="small text-muted mb-0">Opcional: dejá un comentario para ayudar a tus compañeros y a RRHH.</p>
        </div>
        <?php if ($pct !== null): ?>
        <div class="lrn-reviews-score" title="<?php echo (int)$stats->likes; ?> me gusta · <?php echo (int)$stats->dislikes; ?> no me gusta">
            <span class="lrn-reviews-score-num"><?php echo $pct; ?>%</span>
            <span class="small">recomiendan</span>
        </div>
        <?php endif; ?>
    </div>

    <form method="post" action="<?php echo URLROOT; ?>/training/submitReview/<?php echo (int)$course->id; ?>" class="lrn-review-form card border-0 shadow-sm mb-4" id="reviewForm">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="lesson_position" value="<?php echo (int)($pos ?? $data['position'] ?? 1); ?>">
        <input type="hidden" name="vote" id="reviewVote" value="<?php echo htmlspecialchars($myVote); ?>">

        <div class="card-body">
            <p class="small fw-semibold text-muted mb-2">¿Qué te pareció este curso?</p>
            <div class="lrn-vote-row">
                <button type="button" class="lrn-vote-btn lrn-vote-like <?php echo $myVote === 'like' ? 'is-active' : ''; ?>" data-vote="like" aria-pressed="<?php echo $myVote === 'like' ? 'true' : 'false'; ?>">
                    <i class="fas fa-thumbs-up"></i>
                    <span>Me gusta</span>
                </button>
                <button type="button" class="lrn-vote-btn lrn-vote-dislike <?php echo $myVote === 'dislike' ? 'is-active' : ''; ?>" data-vote="dislike" aria-pressed="<?php echo $myVote === 'dislike' ? 'true' : 'false'; ?>">
                    <i class="fas fa-thumbs-down"></i>
                    <span>No me gusta</span>
                </button>
            </div>
            <label class="form-label small mt-3 mb-1">Comentario <span class="text-muted">(opcional)</span></label>
            <textarea name="comment" class="form-control" rows="3" maxlength="2000" placeholder="Contanos qué te gustó, qué mejorarías o si el contenido fue útil en tu trabajo…"><?php echo htmlspecialchars($user_review->comment ?? ''); ?></textarea>
            <button type="submit" class="btn btn-primary mt-3" id="reviewSubmitBtn" <?php echo $myVote === '' ? 'disabled' : ''; ?>>
                <i class="fas fa-paper-plane me-1"></i> <?php echo $user_review ? 'Actualizar reseña' : 'Publicar reseña'; ?>
            </button>
        </div>
    </form>

    <?php if (!empty($course_reviews)): ?>
    <h4 class="h6 text-muted text-uppercase mb-3">Reseñas del equipo</h4>
    <div class="lrn-review-list">
        <?php foreach ($course_reviews as $r):
            $isMine = (int)$r->user_id === (int)$_SESSION['user_id'];
        ?>
        <article class="lrn-review-card <?php echo $r->vote === 'like' ? 'is-positive' : 'is-negative'; ?> <?php echo $isMine ? 'is-mine' : ''; ?>">
            <div class="lrn-review-card-head">
                <span class="lrn-review-vote-icon" title="<?php echo $r->vote === 'like' ? 'Me gusta' : 'No me gusta'; ?>">
                    <i class="fas fa-thumbs-<?php echo $r->vote === 'like' ? 'up' : 'down'; ?>"></i>
                </span>
                <strong><?php echo htmlspecialchars($isMine ? 'Vos' : ($r->author_name ?? 'Compañero')); ?></strong>
                <?php if ($isMine): ?><span class="badge bg-primary-subtle text-primary">Tu reseña</span><?php endif; ?>
                <time class="small text-muted ms-auto"><?php echo htmlspecialchars(date('d/m/Y', strtotime($r->updated_at ?? $r->created_at))); ?></time>
            </div>
            <?php if (!empty($r->comment)): ?>
            <p class="mb-0 mt-2"><?php echo nl2br(htmlspecialchars($r->comment)); ?></p>
            <?php else: ?>
            <p class="mb-0 mt-2 small text-muted fst-italic">Sin comentario escrito.</p>
            <?php endif; ?>
        </article>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="text-muted small">Todavía no hay reseñas. ¡Sé el primero en valorar este curso!</p>
    <?php endif; ?>
</div>
