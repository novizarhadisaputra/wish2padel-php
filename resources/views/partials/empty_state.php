<?php
/**
 * Reusable Empty State Partial
 * 
 * @param string $icon (optional) Bootstrap icon class
 * @param string $title (optional) Main heading
 * @param string $description (optional) Subtext message
 * @param string $action_url (optional) URL for CTA button
 * @param string $action_text (optional) Text for CTA button
 * @param string $extra_class (optional) Extra CSS classes for the wrapper
 */

$icon = $icon ?? 'bi-inbox';
$title = $title ?? 'No Data Found';
$description = $description ?? 'We couldn\'t find any records matching your criteria.';
$action_url = $action_url ?? null;
$action_text = $action_text ?? 'Add New';
$extra_class = $extra_class ?? '';
?>

<div class="empty-state-wrapper text-center py-5 <?= $extra_class ?>">
    <div class="empty-state-icon mb-3">
        <i class="bi <?= htmlspecialchars($icon) ?>" style="font-size: 4rem; color: rgba(243, 230, 182, 0.2);"></i>
    </div>
    <h4 class="empty-state-title text-gold mb-2"><?= htmlspecialchars($title) ?></h4>
    <p class="empty-state-description text-muted mb-4 mx-auto" style="max-width: 400px;">
        <?= htmlspecialchars($description) ?>
    </p>
    <?php if ($action_url): ?>
        <a href="<?= htmlspecialchars($action_url) ?>" class="btn btn-admin-gold">
            <?= htmlspecialchars($action_text) ?>
        </a>
    <?php endif; ?>
</div>
