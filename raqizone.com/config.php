<?php
// ── Database Config
define('DB_HOST', 'localhost');
define('DB_NAME', 'তোমার_db_name');
define('DB_USER', 'তোমার_db_user');
define('DB_PASS', 'তোমার_db_password');

// ── App Config
define('SECRET_KEY', 'তোমার-random-secret-key-এখানে-দাও');
define('ADMIN_PASSWORD', 'তোমার-admin-password');
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