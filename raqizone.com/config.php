<?php
// ── Database Config
define('DB_HOST', 'localhost');
define('DB_NAME', 'cpanel_username_raqizone_db');
define('DB_USER', 'raqizone_tahmid');
define('DB_PASS', 'tahmid2009');

// ── App Config
define('SECRET_KEY', 'RaqiZone_2026_X7kP9mQ2L8vN5');
define('ADMIN_PASSWORD', 'tahmid2009');
define('SITE_URL', 'https://raqizone.com');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

// ── Session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400 * 30,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ── Upload folder তৈরি করো
$uploadDirs = [
    UPLOAD_DIR,
    UPLOAD_DIR . 'products/',
    UPLOAD_DIR . 'banners/',
    UPLOAD_DIR . 'designs/',
    UPLOAD_DIR . 'backgrounds/',
];
foreach ($uploadDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}