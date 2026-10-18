<?php
require_admin();

$status = (string) ($_GET['status'] ?? '');
$q = trim((string) ($_GET['q'] ?? ''));
$where = ['1 = 1'];
$params = [];
if (isset(CUSTOM_STATUSES[$status])) {
    $where[] = 'status = ?';
    $params[] = $status;
}
if ($q !== '') {
    $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ? OR occasion LIKE ?)';
    array_push($params, ...array_fill(0, 4, '%' . $q . '%'));
}
$whereSql = implode(' AND ', $where);
$result = paginate(
    "SELECT * FROM custom_requests WHERE {$whereSql} ORDER BY FIELD(status, 'new', 'quoted', 'confirmed', 'closed'), event_date, id DESC",
    "SELECT COUNT(*) FROM custom_requests WHERE {$whereSql}",
    $params
);
$counts = array_column(db_all('SELECT status, COUNT(*) AS n FROM custom_requests GROUP BY status'), 'n', 'status');
admin_view('custom-requests', compact('result', 'status', 'q', 'counts') + ['title' => 'Custom cake requests']);
