<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

$name   = trim($_POST['name']   ?? '');
$mobile = trim($_POST['mobile'] ?? '');
$next   = trim($_POST['next']   ?? '/home');

if (!$name || !$mobile) {
    header('Location: /?err=empty');
    exit;
}

try {
    login_user($name, $mobile);
    header('Location: ' . $next);
} catch (Exception $e) {
    header('Location: /?err=failed');
}
exit;