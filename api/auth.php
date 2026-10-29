<?php
/** Owner/staff session API used by the website. GET ?action=me · POST {action: login|logout} */
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';
require_installed_api();
start_session();

$action = $_GET['action'] ?? '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' && $action === 'me') {
    $u = current_user();
    if (!$u) json_out(['ok' => true, 'user' => null]);
    $st = db()->prepare('SELECT id_no, name, species, breed FROM pets WHERE owner_id = ? ORDER BY id DESC');
    $st->execute([$u['id']]);
    $pets = $st->fetchAll();
    // Address etc. from the latest ID, so a second ID doesn't need retyping
    $profile = [];
    $lp = db()->prepare('SELECT data FROM pets WHERE owner_id = ? ORDER BY id DESC LIMIT 1');
    $lp->execute([$u['id']]);
    if ($row = $lp->fetch()) { $dd = json_decode($row['data'], true) ?: []; foreach (['oName','oMobile','oEmail','oHouse','oLocality','oCity','oState','oPin','eName','eMobile'] as $k) { if (isset($dd[$k])) $profile[$k] = $dd[$k]; } }
    json_out(['ok' => true, 'user' => ['name' => $u['name'], 'role' => $u['role'], 'mobile' => $u['mobile']], 'pets' => $pets, 'profile' => $profile]);
}

require_ajax_post();
$d = body_json();
$action = (string)($d['action'] ?? $action);

if ($action === 'login') {
    if (rate_limited('login', 8, 900)) fail('Too many attempts. Please wait 15 minutes and try again.', 429);
    $u = find_user_by_login((string)($d['login'] ?? ''));
    if (!$u || !$u['password_hash'] || !password_verify((string)($d['password'] ?? ''), $u['password_hash'])) fail('That mobile/email and password don\'t match.', 401);
    if ($u['status'] !== 'active') fail('This account is disabled. Contact support.', 403);
    login_user($u);
    log_activity((int)$u['id'], 'login', 'website');
    json_out(['ok' => true, 'user' => ['name' => $u['name'], 'role' => $u['role']]]);
}
if ($action === 'logout') { logout_user(); json_out(['ok' => true]); }
fail('Unknown action');
