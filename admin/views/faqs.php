<?php /** @var array $groups @var ?array $editing @var array $categories @var int $count */
$form = $editing ?? ['id' => 0, 'category' => $categories[0] ?? 'Ordering', 'question' => '', 'answer' => '', 'is_active' => 1, 'sort_order' => $count + 1];
?>
<div class="page-head">
    <div><h1>FAQs</h1><p>Questions shown on the FAQ page and in search results. <a href="<?= e(url('faq')) ?>" target="_blank">View the FAQ page</a></p></div>
</div>
<div class="grid-main">
    <div class="stack">
        <?php if (!$groups): ?>
            <section class="card"><div class="empty"><?= icon('search', '', 32) ?><p>No questions yet.</p></div></section>
        <?php endif; ?>
        <?php foreach ($groups as $category => $items): ?>
            <section class="card">
                <div class="card__head"><h2><?= e($category) ?></h2><span class="hint"><?= count($items) ?> questions</span></div>
                <div class="table-wrap"><table class="table">
                    <tbody>
                    <?php foreach ($items as $f): ?>
                        <tr>
                            <td><strong><?= e($f['question']) ?></strong><div class="muted" style="max-width:560px"><?= e(mb_strimwidth(content_text($f['answer']), 0, 140, '...')) ?></div></td>
                            <td style="width:90px"><?= $f['is_active'] ? status_badge('active', 'Visible') : status_badge('inactive', 'Hidden') ?></td>
                            <td class="num">
                                <a class="link-btn" href="<?= e(admin_url('faqs', ['edit' => $f['id']])) ?>#faq-form">Edit</a>
                                <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $f['id'] ?>"><input type="hidden" name="action" value="toggle"><button class="link-btn" type="submit"><?= $f['is_active'] ? 'Hide' : 'Show' ?></button></form>
                                <form method="post" style="display:inline" data-confirm="Delete this question?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $f['id'] ?>"><input type="hidden" name="action" value="delete"><button class="link-btn link-btn--danger" type="submit">Delete</button></form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            </section>
        <?php endforeach; ?>
    </div>
    <section class="card" id="faq-form">
        <div class="card__head"><h2><?= $editing ? 'Edit question' : 'Add a question' ?></h2><?php if ($editing): ?><a href="<?= e(admin_url('faqs')) ?>">Cancel</a><?php endif; ?></div>
        <form class="card__body form-stack" method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int) $form['id'] ?>">
            <label class="field"><span>Group</span><input class="input" name="category" value="<?= e($form['category']) ?>" list="faq-groups" required></label>
            <datalist id="faq-groups"><?php foreach ($categories as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist>
            <label class="field"><span>Question</span><input class="input" name="question" maxlength="255" value="<?= e($form['question']) ?>" required></label>
            <div class="field"><span class="label">Answer</span><textarea class="input" name="answer" rows="6" data-rich-editor="simple"><?= e($form['answer']) ?></textarea></div>
            <label class="field"><span>Sort order</span><input class="input" type="number" name="sort_order" value="<?= (int) $form['sort_order'] ?>"></label>
            <label class="switch"><input type="checkbox" name="is_active" value="1" <?= checked((bool) $form['is_active']) ?>><span class="switch__track"></span><span>Show on the website</span></label>
            <button class="btn btn--primary" type="submit"><?= icon('check', '', 18) ?> <?= $editing ? 'Save question' : 'Add question' ?></button>
        </form>
    </section>
</div>
