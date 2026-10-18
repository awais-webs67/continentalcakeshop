<?php
require_admin();

if (is_post()) {
    $id = post_int('id');
    $action = input('action');
    if ($action === 'save') {
        $data = [
            'category'   => str_limit(trim(input('category')), 60) ?: 'General',
            'question'   => str_limit(trim(input('question')), 255),
            'answer'     => clean_page_html(input('answer')),
            'is_active'  => input('is_active') === '1' ? 1 : 0,
            'sort_order' => (int) input('sort_order', '0'),
        ];
        if ($data['question'] === '' || trim(strip_tags($data['answer'])) === '') {
            flash('error', 'Please enter the question and the answer.');
            redirect(admin_url('faqs', $id ? ['edit' => $id] : []));
        }
        if (db_value('SELECT 1 FROM faqs WHERE question = ? AND id <> ?', [$data['question'], $id])) {
            flash('error', 'That question is already in the list.');
            redirect(admin_url('faqs', $id ? ['edit' => $id] : []));
        }
        if ($id) {
            db_run('UPDATE faqs SET category = ?, question = ?, answer = ?, is_active = ?, sort_order = ? WHERE id = ?', [...array_values($data), $id]);
            flash('success', 'Question saved.');
        } else {
            db_run('INSERT INTO faqs (category, question, answer, is_active, sort_order) VALUES (?, ?, ?, ?, ?)', array_values($data));
            flash('success', 'Question added.');
        }
    } elseif ($action === 'toggle') {
        db_run('UPDATE faqs SET is_active = 1 - is_active WHERE id = ?', [$id]);
        flash('success', 'Question visibility updated.');
    } elseif ($action === 'delete') {
        db_run('DELETE FROM faqs WHERE id = ?', [$id]);
        flash('success', 'Question deleted.');
    }
    redirect(admin_url('faqs'));
}

$faqs = db_all('SELECT * FROM faqs ORDER BY sort_order, id');
$groups = [];
foreach ($faqs as $faq) {
    $groups[$faq['category']][] = $faq;
}
$editing = query_int('edit') ? db_one('SELECT * FROM faqs WHERE id = ?', [query_int('edit')]) : null;
$categories = array_keys($groups);
admin_view('faqs', compact('groups', 'editing', 'categories') + ['title' => 'FAQs', 'count' => count($faqs)]);
