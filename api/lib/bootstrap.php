<?php
/**
 * IndiaPetInsurance.com backend bootstrap.
 * Loads config (kept OUTSIDE public_html), opens the database, starts the session
 * and provides small helpers used by the API, admin panel and owner portal.
 */
declare(strict_types=1);

define('IPI_PUBLIC', dirname(__DIR__, 2));          // .../public_html
define('IPI_HOME', dirname(IPI_PUBLIC));            // one level above public_html

date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', '0');
error_reporting(E_ALL);

function ipi_config_path(): ?string {
    $env = getenv('IPI_CONFIG');
    $paths = array_filter([$env ?: null, IPI_HOME . '/ipi-config.php', IPI_PUBLIC . '/api/config.php']);
    foreach ($paths as $p) { if (is_file($p)) return $p; }
    return null;
}

$GLOBALS['IPI_CFG'] = null;
$__cfgPath = ipi_config_path();
if ($__cfgPath) { $GLOBALS['IPI_CFG'] = require $__cfgPath; }

function cfg(string $k, $default = null) { return $GLOBALS['IPI_CFG'][$k] ?? $default; }
function installed(): bool { return is_array($GLOBALS['IPI_CFG']) && !empty($GLOBALS['IPI_CFG']['installed']); }

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false];
    if (cfg('db_driver') === 'sqlite') {
        $pdo = new PDO('sqlite:' . cfg('db_path'), null, null, $opts);
        $pdo->exec('PRAGMA foreign_keys = ON');
    } else {
        $dsn = 'mysql:host=' . cfg('db_host', 'localhost') . ';dbname=' . cfg('db_name') . ';charset=utf8mb4';
        $pdo = new PDO($dsn, cfg('db_user'), cfg('db_pass'), $opts);
        $pdo->exec("SET time_zone = '+05:30'");
    }
    // One-time upgrades after a deploy (marker file lives next to the uploads, outside the web root)
    $marker = upload_dir() . '/.schema-v2';
    if (!is_file($marker)) {
        require_once __DIR__ . '/schema.php';
        run_migrations($pdo);
        if (!is_dir(upload_dir())) @mkdir(upload_dir(), 0750, true);
        @file_put_contents($marker, date('c'));
    }
    return $pdo;
}

function now(): string { return date('Y-m-d H:i:s'); }
function is_https(): bool { return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'); }
function client_ip(): string { return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45); }
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function start_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name('ipi_sess');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => is_https(), 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

/* ---------- JSON API helpers ---------- */
function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function fail(string $msg, int $code = 400, array $extra = []): void { json_out(['ok' => false, 'error' => $msg] + $extra, $code); }
function require_installed_api(): void { if (!installed()) fail('The site backend is not set up yet.', 503, ['code' => 'not_installed']); }
/** Cross-site requests cannot set this header without a CORS preflight, so it blocks CSRF on the JSON API. */
function require_ajax_post(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('Method not allowed', 405);
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') fail('Bad request', 400);
}
function body_json(): array { $d = json_decode((string)file_get_contents('php://input'), true); return is_array($d) ? $d : []; }

/* ---------- HTML form CSRF (admin + owner portal) ---------- */
function csrf_token(): string { start_session(); if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function check_csrf(): bool { start_session(); return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']); }

/* ---------- Users & roles ---------- */
const STAFF_ROLES = ['superadmin', 'admin', 'staff'];
function current_user(): ?array {
    static $u = false;
    if ($u !== false) return $u;
    start_session();
    $u = null;
    if (!empty($_SESSION['uid'])) {
        $st = db()->prepare('SELECT * FROM users WHERE id = ? AND status = ?');
        $st->execute([(int)$_SESSION['uid'], 'active']);
        $u = $st->fetch() ?: null;
        if (!$u) unset($_SESSION['uid']);
    }
    return $u;
}
function login_user(array $u): void {
    start_session();
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$u['id'];
    db()->prepare('UPDATE users SET last_login_at = ? WHERE id = ?')->execute([now(), $u['id']]);
}
function logout_user(): void {
    start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) { $p = session_get_cookie_params(); setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']); }
    session_destroy();
}
function find_user_by_login(string $login): ?array {
    $login = trim($login);
    if (preg_match('/^\+?(91)?\s*([6-9]\d{9})$/', preg_replace('/[\s-]/', '', $login), $m)) {
        $st = db()->prepare('SELECT * FROM users WHERE mobile = ?'); $st->execute([$m[2]]);
    } else {
        $st = db()->prepare('SELECT * FROM users WHERE LOWER(email) = LOWER(?)'); $st->execute([$login]);
    }
    return $st->fetch() ?: null;
}
function clean_mobile($m): string { $m = preg_replace('/\D/', '', (string)$m); if (strlen($m) === 12 && substr($m, 0, 2) === '91') $m = substr($m, 2); return $m; }
function valid_mobile(string $m): bool { return (bool)preg_match('/^[6-9]\d{9}$/', $m); }

/* ---------- Rate limiting (per IP + key) ---------- */
function rate_limited(string $key, int $max, int $windowSec): bool {
    $k = $key . ':' . client_ip();
    $since = date('Y-m-d H:i:s', time() - $windowSec);
    $pdo = db();
    $pdo->prepare('DELETE FROM rate_limits WHERE created_at < ?')->execute([date('Y-m-d H:i:s', time() - 86400)]);
    $st = $pdo->prepare('SELECT COUNT(*) FROM rate_limits WHERE k = ? AND created_at >= ?');
    $st->execute([$k, $since]);
    if ((int)$st->fetchColumn() >= $max) return true;
    $pdo->prepare('INSERT INTO rate_limits (k, created_at) VALUES (?, ?)')->execute([$k, now()]);
    return false;
}

/* ---------- Logging & notifications ---------- */
function log_activity(?int $uid, string $action, string $details = ''): void {
    db()->prepare('INSERT INTO activity_log (user_id, action, details, ip, created_at) VALUES (?,?,?,?,?)')
        ->execute([$uid, $action, mb_substr($details, 0, 1000), client_ip(), now()]);
}
/**
 * Every event lands in the admin panel now (channel "admin").
 * Later: add rows with channel email / whatsapp / sms and a worker that sends them.
 */
function notify(string $type, string $title, string $body = '', ?string $refTable = null, ?int $refId = null, string $channel = 'admin', ?string $recipient = null): void {
    db()->prepare('INSERT INTO notifications (type, channel, recipient, title, body, ref_table, ref_id, status, created_at) VALUES (?,?,?,?,?,?,?,?,?)')
        ->execute([$type, $channel, $recipient, mb_substr($title, 0, 190), $body, $refTable, $refId, 'unread', now()]);
}

/* ---------- Codes ---------- */
const STATE_CODES = ['Jammu and Kashmir'=>'JK','Delhi'=>'DL','Maharashtra'=>'MH','Karnataka'=>'KA','Tamil Nadu'=>'TN','Telangana'=>'TS','Punjab'=>'PB','Haryana'=>'HR','Uttar Pradesh'=>'UP','West Bengal'=>'WB','Gujarat'=>'GJ','Rajasthan'=>'RJ','Kerala'=>'KL','Himachal Pradesh'=>'HP','Ladakh'=>'LA','Chandigarh'=>'CH','Andhra Pradesh'=>'AP','Assam'=>'AS','Bihar'=>'BR','Goa'=>'GA','Madhya Pradesh'=>'MP','Odisha'=>'OD','Uttarakhand'=>'UK','Jharkhand'=>'JH','Chhattisgarh'=>'CG'];
function unique_code(string $table, string $col, callable $make): string {
    for ($i = 0; $i < 20; $i++) {
        $c = $make();
        $st = db()->prepare("SELECT 1 FROM $table WHERE $col = ?"); $st->execute([$c]);
        if (!$st->fetchColumn()) return $c;
    }
    throw new RuntimeException('Could not generate a unique code');
}
function new_pet_id(string $state): string {
    $sc = STATE_CODES[$state] ?? 'IN';
    return unique_code('pets', 'id_no', function () use ($sc) { return 'IPI-' . $sc . '-' . date('y') . '-' . random_int(100000, 999999); });
}

/* ---------- Uploads (stored outside the web root) ---------- */
const UPLOAD_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/heic' => 'heic', 'application/pdf' => 'pdf'];
const UPLOAD_MAX = 8 * 1024 * 1024;
function upload_dir(): string { $d = (string)cfg('upload_dir', IPI_HOME . '/ipi-uploads'); return rtrim($d, '/'); }
/** @return array|null saved document row, null when the slot was empty */
function save_upload(array $f, int $petId, string $kind, string $label = ''): ?array {
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed for ' . ($f['name'] ?? 'file'));
    if ($f['size'] > UPLOAD_MAX) throw new RuntimeException(($f['name'] ?? 'File') . ' is larger than 8 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) ?: '';
    if (!isset(UPLOAD_TYPES[$mime])) throw new RuntimeException(($f['name'] ?? 'File') . ' must be a JPG, PNG, WEBP, HEIC or PDF.');
    $sub = date('Y/m');
    $dir = upload_dir() . '/' . $sub;
    if (!is_dir($dir) && !mkdir($dir, 0750, true)) throw new RuntimeException('Upload folder is not writable.');
    $name = bin2hex(random_bytes(16)) . '.' . UPLOAD_TYPES[$mime];
    $dest = $dir . '/' . $name;
    $ok = is_uploaded_file($f['tmp_name']) ? move_uploaded_file($f['tmp_name'], $dest) : rename($f['tmp_name'], $dest);
    if (!$ok) throw new RuntimeException('Could not store ' . ($f['name'] ?? 'file'));
    $orig = mb_substr(preg_replace('/[^\w.\- ()]+/u', '_', (string)($f['name'] ?? 'file')), 0, 150);
    db()->prepare('INSERT INTO documents (pet_id, kind, label, original_name, path, mime, size, created_at) VALUES (?,?,?,?,?,?,?,?)')
        ->execute([$petId, $kind, mb_substr($label, 0, 60), $orig, $sub . '/' . $name, $mime, (int)$f['size'], now()]);
    return ['id' => (int)db()->lastInsertId(), 'name' => $orig];
}
/** Normalise $_FILES['x'] (single or multiple) into a list. */
function files_list(string $field): array {
    if (empty($_FILES[$field])) return [];
    $f = $_FILES[$field];
    if (!is_array($f['name'])) return [$f];
    $out = [];
    foreach ($f['name'] as $i => $n) $out[] = ['name' => $n, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    return $out;
}
function send_file(string $relPath, string $mime, string $downloadName, bool $inline = true): void {
    $full = realpath(upload_dir() . '/' . $relPath);
    $base = realpath(upload_dir());
    if (!$full || !$base || strpos($full, $base) !== 0 || !is_file($full)) { http_response_code(404); exit('Not found'); }
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($full));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=3600');
    header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . str_replace('"', '', $downloadName) . '"');
    readfile($full);
    exit;
}

/* ---------- Small utils ---------- */
function str_field(array $d, string $k, int $max = 120): string { return mb_substr(trim((string)($d[$k] ?? '')), 0, $max); }
function date_field(array $d, string $k): ?string { $v = (string)($d[$k] ?? ''); return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null; }
function yn(array $d, string $k, array $allowed): string { $v = (string)($d[$k] ?? ''); return in_array($v, $allowed, true) ? $v : ''; }
function site_url(string $path = ''): string {
    $base = rtrim((string)cfg('app_url', ''), '/');
    if (!$base) $base = (is_https() ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    return $base . '/' . ltrim($path, '/');
}
