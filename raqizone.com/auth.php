<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

// ── User session
function get_current_user(): ?array {
    if (!isset($_SESSION['user_id'])) return null;
    $u = DB::row("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
    if (!$u) {
        unset($_SESSION['user_id']);
        return null;
    }
    return [
        'user_id' => $u['id'],
        'name'    => $u['name'],
        'mobile'  => $u['mobile'],
    ];
}

function login_user(string $name, string $mobile): array {
    $name   = trim($name);
    $mobile = trim($mobile);
    $u = DB::row("SELECT * FROM users WHERE mobile = ?", [$mobile]);
    if (!$u) {
        $id = DB::exec(
            "INSERT INTO users (name, mobile, created_at) VALUES (?, ?, NOW())",
            [$name, $mobile]
        );
        $u = DB::row("SELECT * FROM users WHERE id = ?", [$id]);
    }
    $_SESSION['user_id']   = $u['id'];
    $_SESSION['user_name'] = $u['name'];
    return $u;
}

function logout_user(): void {
    unset($_SESSION['user_id'], $_SESSION['user_name']);
}

// ── Admin session
function is_admin(): bool {
    return !empty($_SESSION['is_admin']);
}

function admin_login(string $password): bool {
    if ($password === ADMIN_PASSWORD) {
        $_SESSION['is_admin'] = true;
        return true;
    }
    return false;
}

function admin_logout(): void {
    unset($_SESSION['is_admin']);
}

function require_admin(): void {
    if (!is_admin()) {
        header('Location: /admin/login');
        exit;
    }
}

function require_user(): ?array {
    $u = get_current_user();
    if (!$u) {
        header('Location: /');
        exit;
    }
    return $u;
}