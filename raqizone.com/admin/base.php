<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../auth.php';
require_admin();

$cfg = get_all_settings();

function admin_head(string $title): void {
    echo <<<HTML
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$title} — Admin</title>
<link rel="stylesheet" href="/static/css/admin.css">
</head>
<body>
HTML;
}

function admin_nav(string $active = ''): void {
    $links = [
        'products' => ['/admin/products', '📦 পণ্য'],
        'orders'   => ['/admin/orders',   '📋 অর্ডার'],
        'edit-ui'  => ['/admin/edit-ui',  '🎨 Settings'],
    ];
    echo '<nav class="anav"><span class="abr">⚡ Admin</span><div class="anl">';
    foreach ($links as $key => [$url, $label]) {
        $cls = $key === $active ? ' class="on"' : '';
        echo "<a href=\"{$url}\"{$cls}>{$label}</a>";
    }
    echo '<a href="/admin/logout" class="lo">লগআউট</a></div></nav>';
    echo '<div class="amain">';
}

function admin_foot(): void {
    echo '</div></body></html>';
}