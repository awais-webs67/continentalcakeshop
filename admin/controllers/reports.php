<?php
require_admin();

[$from, $to] = report_range();
$summary = report_summary($from, $to);
$days = report_by_day($from, $to);
$methods = report_by_method($from, $to);
$products = report_top_products($from, $to);
$categories = report_by_category($from, $to);
admin_view('reports', compact('from', 'to', 'summary', 'days', 'methods', 'products', 'categories') + ['title' => 'Sales reports']);
