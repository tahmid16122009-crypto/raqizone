<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$u = get_current_user();
if ($u) {
    header('Location: /home');
} else {
    header('Location: /pages/welcome.php');
}
exit;