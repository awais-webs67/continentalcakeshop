<?php
/**
 * Photos of a custom cake request: the cake chosen from the gallery and the photo the customer sent.
 * @var array $photos from custom_request_photos()
 */
if (!$photos) {
    return;
}
?>
<div class="request-photos">
    <?php foreach ($photos as $photo): $link = admin_url('file', ['path' => $photo['path']]); ?>
        <figure>
            <figcaption class="label"><?= e($photo['label']) ?></figcaption>
            <?php if ($photo['is_image']): ?>
                <a class="proof" href="<?= e($link) ?>" target="_blank" data-lightbox="<?= e($photo['label']) ?>" title="See it full size"><img src="<?= e($link) ?>" alt="<?= e($photo['label']) ?>" loading="lazy"></a>
            <?php else: ?>
                <a class="proof proof--file" href="<?= e($link) ?>" target="_blank">Open the uploaded file</a>
            <?php endif; ?>
        </figure>
    <?php endforeach; ?>
</div>
