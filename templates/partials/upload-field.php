<?php
/**
 * File field that uploads as soon as a file is chosen and shows the progress in percent.
 * Without JavaScript it works as a normal file input sent with the form.
 *
 * @var string $field   input name, e.g. payment_proof
 * @var string $purpose key in ASYNC_UPLOADS
 * @var string $accept
 * @var string $hint    text shown before a file is chosen
 * @var string $invalid aria attributes when the field has an error
 */
$token = old($field . '_token');
$done = $token !== '' ? async_upload($token, $purpose) : null;
?>
<label class="file-drop<?= $done ? ' has-file is-uploaded' : '' ?>" data-file-drop data-async-upload="<?= e($purpose) ?>" data-upload-url="<?= e(url('api/upload')) ?>" data-max-bytes="<?= UPLOAD_MAX_BYTES ?>">
    <input id="<?= e($field) ?>" name="<?= e($field) ?>" type="file" accept="<?= e($accept) ?>" <?= $invalid ?>>
    <span class="file-drop__icon" aria-hidden="true"><?= icon('upload', '', 24) ?><?= icon('check', 'file-drop__done', 24) ?></span>
    <span class="file-drop__text">
        <span data-file-name data-hint="<?= e($hint) ?>"><?= $done ? e($done['name']) . ' uploaded' : e($hint) ?></span>
        <span class="file-drop__progress" data-upload-progress hidden>
            <span class="file-drop__bar"><span data-upload-bar></span></span>
            <span class="file-drop__percent" data-upload-percent>0%</span>
        </span>
    </span>
</label>
<input type="hidden" name="<?= e($field) ?>_token" value="<?= $done ? e($token) : '' ?>" data-upload-token>
<p class="field-error" data-upload-error hidden></p>
