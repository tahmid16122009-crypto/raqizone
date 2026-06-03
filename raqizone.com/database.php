<?php
require_once __DIR__ . '/config.php';

class DB {
    private static ?PDO $pdo = null;

    public static function get(): PDO {
        if (self::$pdo === null) {
            try {
                self::$pdo = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
            } catch (PDOException $e) {
                die(json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]));
            }
        }
        return self::$pdo;
    }

    // ── Single row fetch
    public static function row(string $sql, array $params = []): ?array {
        $st = self::get()->prepare($sql);
        $st->execute($params);
        $row = $st->fetch();
        return $row ?: null;
    }

    // ── Multiple rows fetch
    public static function rows(string $sql, array $params = []): array {
        $st = self::get()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    // ── Execute (insert/update/delete)
    public static function exec(string $sql, array $params = []): int {
        $st = self::get()->prepare($sql);
        $st->execute($params);
        return (int) self::get()->lastInsertId();
    }

    // ── Run (no return)
    public static function run(string $sql, array $params = []): bool {
        $st = self::get()->prepare($sql);
        return $st->execute($params);
    }
}

// ── Settings helper
function get_setting(string $key, string $default = ''): string {
    $row = DB::row("SELECT value FROM site_settings WHERE `key` = ?", [$key]);
    return $row ? $row['value'] : $default;
}

function save_setting(string $key, string $value): void {
    $ex = DB::row("SELECT id FROM site_settings WHERE `key` = ?", [$key]);
    if ($ex) {
        DB::run("UPDATE site_settings SET value = ? WHERE `key` = ?", [$value, $key]);
    } else {
        DB::run("INSERT INTO site_settings (`key`, value) VALUES (?, ?)", [$key, $value]);
    }
}

function get_all_settings(): array {
    $rows = DB::rows("SELECT `key`, value FROM site_settings");
    $cfg = [];
    foreach ($rows as $r) {
        $cfg[$r['key']] = $r['value'];
    }
    return $cfg;
}

// ── Image upload helper
function upload_image(array $file, string $prefix = 'img', string $subdir = 'products'): string {
    if ($file['error'] !== UPLOAD_ERR_OK) return '';
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!in_array($file['type'], $allowed)) return '';
    $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . strtolower($ext);
    $path = UPLOAD_DIR . $subdir . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $path)) return '';
    return UPLOAD_URL . $subdir . '/' . $name;
}