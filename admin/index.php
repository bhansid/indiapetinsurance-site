<?php
/**
 * Admin panel. Roles:
 *   superadmin – everything, including creating and managing team users
 *   admin      – everything except team management
 *   staff      – view records, update order/quote status, notifications
 */
declare(strict_types=1);
require dirname(__DIR__) . '/api/lib/bootstrap.php';
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');
if (!installed()) { header('Location: /install.php'); exit; }
start_session();

$page = preg_replace('/[^a-z_]/', '', (string)($_GET['p'] ?? 'dash'));
$me = current_user();
if ($me && !in_array($me['role'], STAFF_ROLES, true)) $me = null; // owners can't use the admin panel

function can(string $perm): bool {
    global $me;
    if (!$me) return false;
    if ($me['role'] === 'superadmin') return true;
    if ($me['role'] === 'admin') return $perm !== 'team';
    return in_array($perm, ['view', 'status'], true);
}
function flash(string $msg, string $type = 'ok'): void { $_SESSION['flash'] = [$msg, $type]; }
function go(string $qs): void { header('Location: /admin/?' . $qs); exit; }
function q(string $sql, array $args = []): PDOStatement { $st = db()->prepare($sql); $st->execute($args); return $st; }
function pill(string $s): string {
    $map = ['active'=>'ok','delivered'=>'ok','converted'=>'ok','quoted'=>'ok','new'=>'warn','unread'=>'warn','confirmed'=>'','contacted'=>'','printed'=>'','shipped'=>'','disabled'=>'bad','cancelled'=>'bad','closed'=>'grey','flagged'=>'bad','read'=>'grey'];
    return '<span class="pill ' . ($map[$s] ?? '') . '">' . e($s) . '</span>';
}
function fmt_dt(?string $d): string { return $d ? date('d M Y, g:i a', strtotime($d)) : '—'; }
const ORDER_STATUS = ['new', 'confirmed', 'printed', 'shipped', 'delivered', 'cancelled'];
const QUOTE_STATUS = ['new', 'contacted', 'quoted', 'converted', 'closed'];
const PRODUCTS = ['card' => 'PVC card', 'tag' => 'Steel tag', 'combo' => 'Card + tag'];
function product_label(string $p): string { if (strpos($p, 'kit:') === 0) return 'Safety kit · collar ' . substr($p, 4) . ' · ₹1,000 COD'; return PRODUCTS[$p] ?? $p; }
function pet_source(string $json): string { $d = json_decode($json, true) ?: []; return (string)($d['src'] ?? ''); }

/* ---------------- Login / logout ---------------- */
if ($page === 'logout') { if ($me) log_activity((int)$me['id'], 'admin_logout'); logout_user(); go('p=login'); }
if (!$me) {
    $err = '';
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        if (!check_csrf()) $err = 'Your session expired. Please try again.';
        elseif (rate_limited('admin_login', 6, 900)) $err = 'Too many attempts. Wait 15 minutes.';
        else {
            $u = find_user_by_login((string)($_POST['login'] ?? ''));
            if ($u && in_array($u['role'], STAFF_ROLES, true) && $u['status'] === 'active' && $u['password_hash'] && password_verify((string)($_POST['password'] ?? ''), $u['password_hash'])) {
                login_user($u); log_activity((int)$u['id'], 'admin_login'); go('p=dash');
            }
            $err = 'Wrong login or password.';
        }
    }
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Admin login · IndiaPetInsurance.com</title><link rel="icon" href="/assets/favicon.svg"><link rel="stylesheet" href="/admin/admin.css"></head>
    <body class="auth"><main class="auth-card"><div class="brand"><span class="mk"><svg width="20" height="20" viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><ellipse cx="12" cy="16" rx="5" ry="4.2"/><circle cx="5.5" cy="10" r="2.2"/><circle cx="9.5" cy="6" r="2.2"/><circle cx="14.5" cy="6" r="2.2"/><circle cx="18.5" cy="10" r="2.2"/></svg></span> IndiaPetInsurance<small>.com</small></div><h1>Admin panel</h1>
    <?php if ($err): ?><div class="alert err"><?= e($err) ?></div><?php endif; ?>
    <form method="post" class="form"><?= csrf_field() ?><label>Email or mobile<input name="login" required autocomplete="username"></label><label>Password<input type="password" name="password" required autocomplete="current-password"></label><button class="btn">Log in</button></form>
    </main></body></html><?php
    exit;
}

/* ---------------- File download (needs login) ---------------- */
if ($page === 'file') {
    $doc = q('SELECT * FROM documents WHERE id = ?', [(int)($_GET['id'] ?? 0)])->fetch();
    if (!$doc) { http_response_code(404); exit('Not found'); }
    if ($doc['kind'] === 'kyc') log_activity((int)$me['id'], 'kyc_viewed', 'doc ' . $doc['id'] . ' pet ' . $doc['pet_id']);
    send_file($doc['path'], $doc['mime'], $doc['original_name'] ?: 'file', !isset($_GET['dl']));
}

/* ---------------- CSV export ---------------- */
if ($page === 'export') {
    if (!can('export')) { http_response_code(403); exit('Not allowed'); }
    $t = $_GET['t'] ?? '';
    $sets = [
        'pets' => 'SELECT p.id_no, p.name, p.species, p.breed, p.gender, p.city, p.state, p.clinic, u.name AS owner, u.mobile, u.email, p.scan_count, p.status, p.created_at FROM pets p JOIN users u ON u.id = p.owner_id ORDER BY p.id DESC',
        'orders' => 'SELECT o.order_no, p.id_no, p.name AS pet, o.product, o.ship_name, o.ship_mobile, o.ship_address, o.ship_city, o.ship_state, o.ship_pin, o.status, o.notes, o.created_at FROM orders o JOIN pets p ON p.id = o.pet_id ORDER BY o.id DESC',
        'quotes' => 'SELECT qq.quote_no, p.id_no, p.name AS pet, p.breed, qq.plan, qq.sum_insured, qq.ill_12m, qq.ongoing, u.name AS owner, u.mobile, qq.status, qq.notes, qq.created_at FROM quotes qq JOIN pets p ON p.id = qq.pet_id JOIN users u ON u.id = qq.owner_id ORDER BY qq.id DESC',
        'owners' => "SELECT u.name, u.mobile, u.email, u.marketing AS marketing_opt_in, u.status, u.created_at, u.last_login_at, (SELECT COUNT(*) FROM pets p WHERE p.owner_id = u.id) AS ids FROM users u WHERE u.role = 'owner' ORDER BY u.id DESC",
        'marketing' => "SELECT u.name, u.mobile, u.email, u.created_at FROM users u WHERE u.role = 'owner' AND u.marketing = 1 AND u.status = 'active' ORDER BY u.id DESC",
    ];
    if (!isset($sets[$t])) { http_response_code(404); exit; }
    log_activity((int)$me['id'], 'export', $t);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $t . '-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    $first = true;
    foreach (db()->query($sets[$t]) as $row) {
        if ($first) { fputcsv($out, array_keys($row)); $first = false; }
        fputcsv($out, array_map(fn($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v, $row));
    }
    exit;
}

/* ---------------- Actions (POST) ---------------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!check_csrf()) { flash('Session expired, please try again.', 'err'); go('p=' . $page); }
    $a = (string)($_POST['a'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    $back = (string)($_POST['back'] ?? ('p=' . $page));
    try {
        switch ($a) {
            case 'notif_read': q("UPDATE notifications SET status = 'read' WHERE id = ? AND channel = 'admin'", [$id]); break;
            case 'notif_all': q("UPDATE notifications SET status = 'read' WHERE status = 'unread' AND channel = 'admin'"); flash('All notifications marked as read.'); break;
            case 'order_status':
                if (!can('status')) throw new RuntimeException('Not allowed');
                $s = in_array($_POST['status'] ?? '', ORDER_STATUS, true) ? $_POST['status'] : 'new';
                q('UPDATE orders SET status = ?, notes = ?, updated_at = ? WHERE id = ?', [$s, mb_substr((string)($_POST['notes'] ?? ''), 0, 2000), now(), $id]);
                log_activity((int)$me['id'], 'order_status', "#$id → $s"); flash('Order updated.'); break;
            case 'quote_status':
                if (!can('status')) throw new RuntimeException('Not allowed');
                $s = in_array($_POST['status'] ?? '', QUOTE_STATUS, true) ? $_POST['status'] : 'new';
                q('UPDATE quotes SET status = ?, notes = ?, updated_at = ? WHERE id = ?', [$s, mb_substr((string)($_POST['notes'] ?? ''), 0, 2000), now(), $id]);
                log_activity((int)$me['id'], 'quote_status', "#$id → $s"); flash('Quote updated.'); break;
            case 'pet_status':
                if (!can('edit')) throw new RuntimeException('Not allowed');
                $s = in_array($_POST['status'] ?? '', ['active', 'flagged', 'disabled'], true) ? $_POST['status'] : 'active';
                q('UPDATE pets SET status = ?, updated_at = ? WHERE id = ?', [$s, now(), $id]);
                log_activity((int)$me['id'], 'pet_status', "#$id → $s"); flash('ID status updated.'); break;
            case 'pet_delete':
                if ($me['role'] !== 'superadmin') throw new RuntimeException('Only the superadmin can delete IDs.');
                if (($_POST['confirm'] ?? '') !== 'DELETE') throw new RuntimeException('Type DELETE to confirm.');
                foreach (q('SELECT path FROM documents WHERE pet_id = ?', [$id])->fetchAll() as $d) { @unlink(upload_dir() . '/' . $d['path']); }
                q('DELETE FROM documents WHERE pet_id = ?', [$id]); q('DELETE FROM orders WHERE pet_id = ?', [$id]); q('DELETE FROM quotes WHERE pet_id = ?', [$id]); q('DELETE FROM pets WHERE id = ?', [$id]);
                log_activity((int)$me['id'], 'pet_delete', "#$id"); flash('ID and its files deleted.'); $back = 'p=pets'; break;
            case 'user_create':
                if (!can('team')) throw new RuntimeException('Only the superadmin can add team members.');
                $role = in_array($_POST['role'] ?? '', ['admin', 'staff'], true) ? $_POST['role'] : 'staff';
                $name = trim((string)$_POST['name']); $email = strtolower(trim((string)$_POST['email'])); $mob = clean_mobile($_POST['mobile'] ?? ''); $pw = (string)$_POST['password'];
                if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a name and valid email.');
                if ($mob && !valid_mobile($mob)) throw new RuntimeException('Mobile must be a 10-digit Indian number.');
                if (strlen($pw) < 10) throw new RuntimeException('Password must be at least 10 characters.');
                if (find_user_by_login($email) || ($mob && find_user_by_login($mob))) throw new RuntimeException('That email or mobile is already in use.');
                q('INSERT INTO users (role, name, email, mobile, password_hash, status, created_by, created_at) VALUES (?,?,?,?,?,?,?,?)', [$role, $name, $email, $mob ?: null, password_hash($pw, PASSWORD_DEFAULT), 'active', $me['id'], now()]);
                log_activity((int)$me['id'], 'user_create', "$email ($role)"); flash("$name added as $role."); break;
            case 'user_role':
                if (!can('team')) throw new RuntimeException('Not allowed');
                $role = in_array($_POST['role'] ?? '', ['admin', 'staff'], true) ? $_POST['role'] : 'staff';
                q("UPDATE users SET role = ? WHERE id = ? AND role IN ('admin','staff')", [$role, $id]); log_activity((int)$me['id'], 'user_role', "#$id → $role"); flash('Role updated.'); break;
            case 'user_toggle':
                $u = q('SELECT * FROM users WHERE id = ?', [$id])->fetch();
                if (!$u || $u['role'] === 'superadmin' || (int)$u['id'] === (int)$me['id']) throw new RuntimeException('Not allowed');
                if ($u['role'] !== 'owner' && !can('team')) throw new RuntimeException('Not allowed');
                if ($u['role'] === 'owner' && !can('edit')) throw new RuntimeException('Not allowed');
                $s = $u['status'] === 'active' ? 'disabled' : 'active';
                q('UPDATE users SET status = ? WHERE id = ?', [$s, $id]); log_activity((int)$me['id'], 'user_status', "#$id → $s"); flash('Account ' . ($s === 'active' ? 'enabled' : 'disabled') . '.'); break;
            case 'user_reset':
                $u = q('SELECT * FROM users WHERE id = ?', [$id])->fetch();
                if (!$u || $u['role'] === 'superadmin') throw new RuntimeException('Not allowed');
                if ($u['role'] !== 'owner' && !can('team')) throw new RuntimeException('Not allowed');
                if ($u['role'] === 'owner' && !can('edit')) throw new RuntimeException('Not allowed');
                $tmp = substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(12))), 0, 12);
                q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($tmp, PASSWORD_DEFAULT), $id]);
                log_activity((int)$me['id'], 'user_reset', "#$id");
                flash('New temporary password for ' . $u['name'] . ': ' . $tmp . ' — share it privately. It will not be shown again.'); break;
            case 'my_password':
                $cur = (string)$_POST['current']; $new = (string)$_POST['new'];
                if (!password_verify($cur, (string)$me['password_hash'])) throw new RuntimeException('Current password is wrong.');
                if (strlen($new) < 10) throw new RuntimeException('New password must be at least 10 characters.');
                q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $me['id']]);
                log_activity((int)$me['id'], 'password_change'); flash('Password changed.'); break;
            default: throw new RuntimeException('Unknown action');
        }
    } catch (Throwable $ex) { flash($ex->getMessage(), 'err'); }
    go(preg_replace('/[^\w=&\-]/', '', $back));
}

/* ---------------- Layout ---------------- */
$unread = (int)q("SELECT COUNT(*) FROM notifications WHERE status = 'unread' AND channel = 'admin'")->fetchColumn();
$newOrders = (int)q("SELECT COUNT(*) FROM orders WHERE status = 'new'")->fetchColumn();
$newQuotes = (int)q("SELECT COUNT(*) FROM quotes WHERE status = 'new'")->fetchColumn();
$nav = ['dash' => ['Dashboard', 0], 'notifications' => ['Notifications', $unread], 'pets' => ['Dog & cat IDs', 0], 'orders' => ['Kit orders', $newOrders], 'quotes' => ['Insurance quotes', $newQuotes], 'owners' => ['Owners', 0]];
if (can('team')) $nav['team'] = ['Team users', 0];
$nav['activity'] = ['Activity log', 0];
$nav['account'] = ['My account', 0];
$titles = ['pet' => 'ID details'];
$title = $nav[$page][0] ?? ($titles[$page] ?? 'Dashboard');
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$navKey = $page === 'pet' ? 'pets' : $page;

function head_html(string $title): void { ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title><?= e($title) ?> · Admin · IndiaPetInsurance.com</title><link rel="icon" href="/assets/favicon.svg"><link rel="stylesheet" href="/admin/admin.css"></head><body><?php }
head_html($title);
?>
<div class="topbar"><button type="button" onclick="document.querySelector('.side').classList.toggle('open')" aria-label="Menu">☰</button><div class="brand"><span class="mk"><svg width="20" height="20" viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><ellipse cx="12" cy="16" rx="5" ry="4.2"/><circle cx="5.5" cy="10" r="2.2"/><circle cx="9.5" cy="6" r="2.2"/><circle cx="14.5" cy="6" r="2.2"/><circle cx="18.5" cy="10" r="2.2"/></svg></span> Admin</div></div>
<div class="shell">
<nav class="side" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="brand"><span class="mk"><svg width="20" height="20" viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><ellipse cx="12" cy="16" rx="5" ry="4.2"/><circle cx="5.5" cy="10" r="2.2"/><circle cx="9.5" cy="6" r="2.2"/><circle cx="14.5" cy="6" r="2.2"/><circle cx="18.5" cy="10" r="2.2"/></svg></span> IndiaPetInsurance<small>.com</small></div>
  <?php foreach ($nav as $k => [$label, $cnt]): ?><a href="/admin/?p=<?= $k ?>" class="<?= $navKey === $k ? 'on' : '' ?>"><?= e($label) ?><?= $cnt ? '<span class="cnt">' . $cnt . '</span>' : '' ?></a><?php endforeach; ?>
  <a href="/" target="_blank" rel="noopener">View website ↗</a>
  <div class="me"><b><?= e($me['name']) ?></b><?= e($me['role']) ?> · <a href="/admin/?p=logout" style="display:inline;padding:0;color:#FFB98F">Log out</a></div>
</nav>
<main class="main">
<?php if ($flash): ?><div class="alert <?= $flash[1] === 'err' ? 'err' : 'ok' ?>"><?= e($flash[0]) ?></div><?php endif; ?>
<?php
$per = 25;
$pg = max(1, (int)($_GET['page'] ?? 1));
$off = ($pg - 1) * $per;
function pager(int $total, int $per, int $pg, string $qs): void {
    $pages = max(1, (int)ceil($total / $per));
    if ($pages < 2) return;
    echo '<div class="pager">';
    if ($pg > 1) echo '<a class="btn ghost sm" href="/admin/?' . e($qs) . '&page=' . ($pg - 1) . '">← Prev</a>';
    echo '<span class="muted small">Page ' . $pg . ' of ' . $pages . ' · ' . $total . ' total</span>';
    if ($pg < $pages) echo '<a class="btn ghost sm" href="/admin/?' . e($qs) . '&page=' . ($pg + 1) . '">Next →</a>';
    echo '</div>';
}
function notif_icon(string $t): string { return ['pet_created' => '🪪', 'owner_signup' => '👤', 'order_new' => '📦', 'quote_new' => '🛡️', 'scan' => '📍'][$t] ?? '🔔'; }
function notif_link(array $n): string {
    if ($n['ref_table'] === 'pets') return '/admin/?p=pet&id=' . (int)$n['ref_id'];
    if ($n['ref_table'] === 'orders') return '/admin/?p=orders';
    if ($n['ref_table'] === 'quotes') return '/admin/?p=quotes';
    if ($n['ref_table'] === 'users') return '/admin/?p=owners';
    return '#';
}
function feed(array $rows, bool $actions = true): void {
    if (!$rows) { echo '<p class="muted">Nothing yet. New IDs, orders, quote requests and QR scans will appear here.</p>'; return; }
    echo '<ul class="feed">';
    foreach ($rows as $n) {
        echo '<li class="' . ($n['status'] === 'unread' ? 'unread' : '') . '"><span class="ic">' . notif_icon($n['type']) . '</span><div><a href="' . e(notif_link($n)) . '" style="text-decoration:none;color:inherit"><b>' . e($n['title']) . '</b></a><br><small>' . e($n['body']) . '</small><br><small>' . fmt_dt($n['created_at']) . '</small></div>';
        if ($actions && $n['status'] === 'unread') echo '<form method="post">' . csrf_field() . '<input type="hidden" name="a" value="notif_read"><input type="hidden" name="id" value="' . (int)$n['id'] . '"><input type="hidden" name="back" value="p=' . e($GLOBALS['page']) . '"><button class="btn ghost sm">Mark read</button></form>';
        else echo '<span></span>';
        echo '</li>';
    }
    echo '</ul>';
}

switch ($page) {
case 'dash':
    $c = fn($sql, $a = []) => (int)q($sql, $a)->fetchColumn();
    $today = date('Y-m-d 00:00:00');
    ?>
    <div class="head"><div><h1>Namaste, <?= e(explode(' ', $me['name'])[0]) ?></h1><p class="muted">Here's what's happening on IndiaPetInsurance.com.</p></div></div>
    <div class="kpis">
      <div class="kpi"><span>IDs created</span><b><?= $c('SELECT COUNT(*) FROM pets') ?></b><small><?= $c('SELECT COUNT(*) FROM pets WHERE created_at >= ?', [$today]) ?> today</small></div>
      <div class="kpi"><span>Owners</span><b><?= $c("SELECT COUNT(*) FROM users WHERE role = 'owner'") ?></b><small>accounts</small></div>
      <div class="kpi"><span>Kit orders</span><b><?= $c('SELECT COUNT(*) FROM orders') ?></b><small><?= $newOrders ?> new</small></div>
      <div class="kpi"><span>Quote requests</span><b><?= $c('SELECT COUNT(*) FROM quotes') ?></b><small><?= $newQuotes ?> new</small></div>
      <div class="kpi"><span>QR scans</span><b><?= $c('SELECT COALESCE(SUM(scan_count),0) FROM pets') ?></b><small>all time</small></div>
    </div>
    <?php
      $src = ['entry' => [], 'via' => []]; $withQuote = 0; $withOrder = 0; $n = 0;
      foreach (q('SELECT p.data, (SELECT COUNT(*) FROM quotes x WHERE x.pet_id = p.id) AS qn, (SELECT COUNT(*) FROM orders o WHERE o.pet_id = p.id) AS onum FROM pets p ORDER BY p.id DESC LIMIT 5000')->fetchAll() as $r) {
          $n++; $dd = json_decode($r['data'], true) ?: [];
          $en = ($dd['entry'] ?? '') === 'insurance' ? 'Insurance quote' : 'Free ID';
          $parts = explode(' · ', (string)($dd['src'] ?? '')); $via = $parts[2] ?? 'unknown';
          $src['entry'][$en] = ($src['entry'][$en] ?? 0) + 1; $src['via'][$via] = ($src['via'][$via] ?? 0) + 1;
          if ($r['qn'] > 0) $withQuote++; if ($r['onum'] > 0) $withOrder++;
      }
      arsort($src['via']);
    ?>
    <div class="card"><div class="head" style="margin-bottom:6px"><h2>Where IDs come from</h2><span class="muted small">Funnel: <?= $n ?> IDs → <?= $withOrder ?> kit orders (<?= $n ? round($withOrder / $n * 100) : 0 ?>%) · <?= $withQuote ?> quote requests (<?= $n ? round($withQuote / $n * 100) : 0 ?>%)</span></div>
      <div class="split"><div><b class="small">Started as</b><?php foreach ($src['entry'] as $k => $v): ?><p class="small" style="margin:6px 0"><?= e($k) ?>: <b><?= $v ?></b></p><?php endforeach; ?><?php if (!$n): ?><p class="muted small">No IDs yet.</p><?php endif; ?></div>
      <div><b class="small">Traffic source</b><?php foreach (array_slice($src['via'], 0, 8, true) as $k => $v): ?><p class="small" style="margin:6px 0"><?= e($k) ?>: <b><?= $v ?></b></p><?php endforeach; ?></div></div></div>
    <div class="split">
      <div class="card"><div class="head" style="margin-bottom:6px"><h2>Latest notifications</h2><a href="/admin/?p=notifications">See all</a></div><?php feed(q("SELECT * FROM notifications WHERE channel = 'admin' ORDER BY id DESC LIMIT 8")->fetchAll()); ?></div>
      <div class="card"><div class="head" style="margin-bottom:6px"><h2>Newest IDs</h2><a href="/admin/?p=pets">See all</a></div>
        <div class="tbl-wrap"><table><tr><th>ID</th><th>Name</th><th>Breed</th><th>City</th><th>Created</th></tr>
        <?php foreach (q('SELECT * FROM pets ORDER BY id DESC LIMIT 8')->fetchAll() as $p): ?><tr><td class="mono nowrap"><a href="/admin/?p=pet&id=<?= (int)$p['id'] ?>"><?= e($p['id_no']) ?></a></td><td><?= e($p['name']) ?></td><td><?= e($p['breed']) ?></td><td><?= e($p['city']) ?></td><td class="nowrap"><?= fmt_dt($p['created_at']) ?></td></tr><?php endforeach; ?>
        </table></div></div>
    </div>
    <?php break;

case 'notifications':
    $total = (int)q("SELECT COUNT(*) FROM notifications WHERE channel = 'admin'")->fetchColumn();
    $rows = q("SELECT * FROM notifications WHERE channel = 'admin' ORDER BY id DESC LIMIT $per OFFSET $off")->fetchAll();
    ?>
    <div class="head"><div><h1>Notifications</h1><p class="muted">Everything happening on the site lands here. Email, WhatsApp and SMS alerts can be switched on later.</p></div>
    <?php if ($unread): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="notif_all"><button class="btn ghost">Mark all as read</button></form><?php endif; ?></div>
    <div class="card"><?php feed($rows); pager($total, $per, $pg, 'p=notifications'); ?></div>
    <?php break;

case 'pets':
    $qq = trim((string)($_GET['q'] ?? '')); $sp = $_GET['sp'] ?? ''; $stt = $_GET['st'] ?? '';
    $where = ['1=1']; $args = [];
    if ($qq !== '') { $where[] = '(p.name LIKE ? OR p.id_no LIKE ? OR u.mobile LIKE ? OR u.name LIKE ? OR p.breed LIKE ? OR p.city LIKE ?)'; $l = "%$qq%"; array_push($args, $l, $l, $l, $l, $l, $l); }
    if (in_array($sp, ['dog', 'cat'], true)) { $where[] = 'p.species = ?'; $args[] = $sp; }
    if (in_array($stt, ['active', 'flagged', 'disabled'], true)) { $where[] = 'p.status = ?'; $args[] = $stt; }
    $w = implode(' AND ', $where);
    $total = (int)q("SELECT COUNT(*) FROM pets p JOIN users u ON u.id = p.owner_id WHERE $w", $args)->fetchColumn();
    $rows = q("SELECT p.*, u.name AS owner, u.mobile FROM pets p JOIN users u ON u.id = p.owner_id WHERE $w ORDER BY p.id DESC LIMIT $per OFFSET $off", $args)->fetchAll();
    ?>
    <div class="head"><div><h1>Dog &amp; cat IDs</h1><p class="muted"><?= $total ?> found</p></div><?php if (can('export')): ?><a class="btn ghost" href="/admin/?p=export&t=pets">Export CSV</a><?php endif; ?></div>
    <form class="filters" method="get"><input type="hidden" name="p" value="pets"><input name="q" value="<?= e($qq) ?>" placeholder="Search name, ID, mobile, breed, city"><select name="sp"><option value="">Dogs and cats</option><option value="dog" <?= $sp === 'dog' ? 'selected' : '' ?>>Dogs</option><option value="cat" <?= $sp === 'cat' ? 'selected' : '' ?>>Cats</option></select><select name="st"><option value="">Any status</option><?php foreach (['active', 'flagged', 'disabled'] as $s): ?><option <?= $stt === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select><button class="btn">Search</button></form>
    <div class="card"><div class="tbl-wrap"><table><tr><th>ID number</th><th>Name</th><th>Breed</th><th>Owner</th><th>Mobile</th><th>City</th><th>Came from</th><th>Scans</th><th>Status</th><th>Created</th></tr>
    <?php foreach ($rows as $p): ?><tr><td class="mono nowrap"><a href="/admin/?p=pet&id=<?= (int)$p['id'] ?>"><?= e($p['id_no']) ?></a></td><td><b><?= e($p['name']) ?></b> <span class="muted small"><?= $p['species'] ?></span></td><td><?= e($p['breed']) ?></td><td><?= e($p['owner']) ?></td><td class="nowrap"><?= e($p['mobile']) ?></td><td><?= e($p['city']) ?></td><td class="small"><?= e(pet_source($p['data'])) ?: '<span class="muted">—</span>' ?></td><td><?= (int)$p['scan_count'] ?></td><td><?= pill($p['status']) ?></td><td class="nowrap"><?= fmt_dt($p['created_at']) ?></td></tr><?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="10" class="muted">No IDs yet.</td></tr><?php endif; ?></table></div>
    <?php pager($total, $per, $pg, 'p=pets&q=' . urlencode($qq) . '&sp=' . urlencode((string)$sp) . '&st=' . urlencode((string)$stt)); ?></div>
    <?php break;

case 'pet':
    $pid = (int)($_GET['id'] ?? 0);
    $p = q('SELECT p.*, u.name AS owner, u.mobile, u.email, u.id AS uid FROM pets p JOIN users u ON u.id = p.owner_id WHERE p.id = ?', [$pid])->fetch();
    if (!$p) { echo '<p>ID not found.</p>'; break; }
    $d = json_decode($p['data'], true) ?: [];
    $docs = q('SELECT * FROM documents WHERE pet_id = ? ORDER BY id', [$pid])->fetchAll();
    $v = fn($k) => e($d[$k] ?? '') ?: '<span class="muted">—</span>';
    $yn = fn($k) => ['yes' => 'Yes', 'no' => 'No', 'applied' => 'Applied', 'some' => 'Some', 'unsure' => 'Not sure'][$d[$k] ?? ''] ?? '<span class="muted">—</span>';
    ?>
    <div class="head"><div><a href="/admin/?p=pets" class="small">← All IDs</a><h1><?= e($p['name']) ?> <span class="mono muted" style="font-size:.6em"><?= e($p['id_no']) ?></span></h1><p class="muted"><?= e($p['breed']) ?> · <?= $p['species'] ?> · created <?= fmt_dt($p['created_at']) ?> · <?= (int)$p['scan_count'] ?> QR scans</p></div>
      <div class="inline"><a class="btn ghost" href="/p/<?= e($p['id_no']) ?>" target="_blank" rel="noopener">Public profile ↗</a>
      <?php if (can('edit')): ?><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="a" value="pet_status"><input type="hidden" name="id" value="<?= $pid ?>"><input type="hidden" name="back" value="p=pet&id=<?= $pid ?>"><select name="status"><?php foreach (['active', 'flagged', 'disabled'] as $s): ?><option <?= $p['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select><button class="btn sm">Save</button></form><?php endif; ?></div></div>
    <div class="split">
      <div class="card"><h2>Dog / cat</h2><dl class="kv">
        <dt>Sex</dt><dd><?= $p['gender'] === 'f' ? 'Female' : 'Male' ?> · neutered: <?= $yn('neutered') ?></dd>
        <dt>Age</dt><dd><?= !empty($d['dob']) ? 'Born ' . e($d['dob']) : e(trim(($d['ageY'] ?? '') . ' yr ' . ($d['ageM'] ?? '') . ' mo')) ?></dd>
        <dt>Weight</dt><dd><?= !empty($d['weight']) ? e($d['weight']) . ' kg' : '<span class="muted">—</span>' ?></dd><dt>Colour</dt><dd><?= $v('colour') ?></dd><dt>Marks</dt><dd><?= $v('marks') ?></dd>
        <dt>Municipal licence</dt><dd><?= $yn('lic') ?> <?= $v('licNo') ?> <?= e($d['licAuth'] ?? '') ?></dd>
        <dt>KCI</dt><dd><?= $yn('kci') ?> <?= e($d['kciNo'] ?? '') ?></dd><dt>Microchip</dt><dd><?= $yn('chip') ?> <span class="mono"><?= e($d['chipNo'] ?? '') ?></span></dd>
      </dl><h2>Health</h2><dl class="kv">
        <dt>Records</dt><dd><?= $yn('records') ?></dd><dt>Anti-rabies</dt><dd><?= $v('rabies') ?></dd><dt>Core vaccine</dt><dd><?= $v('core') ?></dd><dt>Deworming</dt><dd><?= $v('deworm') ?></dd>
        <dt>Conditions</dt><dd><?= $v('conditions') ?></dd><dt>Medicines</dt><dd><?= $v('meds') ?></dd></dl></div>
      <div class="card"><h2>Owner</h2><dl class="kv">
        <dt>Name</dt><dd><?= e($p['owner']) ?></dd><dt>Mobile</dt><dd><a href="tel:+91<?= e($p['mobile']) ?>">+91 <?= e($p['mobile']) ?></a> · <a href="https://wa.me/91<?= e($p['mobile']) ?>" target="_blank" rel="noopener">WhatsApp</a></dd><dt>Email</dt><dd><?= e($p['email']) ?: '—' ?></dd>
        <dt>Address</dt><dd><?= e(implode(', ', array_filter([$d['oHouse'] ?? '', $d['oLocality'] ?? '', $d['oCity'] ?? '', $d['oState'] ?? '', $d['oPin'] ?? '']))) ?></dd>
        <dt>Emergency</dt><dd><?= e(trim(($d['eName'] ?? '') . ' ' . (($d['eMobile'] ?? '') ? '+91 ' . $d['eMobile'] : ''))) ?: '—' ?></dd>
        <dt>ID proof</dt><dd><?= e($d['kycType'] ?? '') ?: '—' ?><?= !empty($d['kycLast4']) ? ' · ending ' . e($d['kycLast4']) : '' ?></dd>
        <dt>WhatsApp reminders</dt><dd><?= !empty($d['cWa']) ? 'Yes' : 'No' ?></dd><dt>Came from</dt><dd><?= e($d['src'] ?? '') ?: '—' ?></dd></dl>
        <h2>Primary clinic</h2><dl class="kv">
        <?php if (!empty($d['clinicNone'])): ?><dt>Clinic</dt><dd>No regular vet. Wants a partner clinic suggested.</dd><?php else: ?>
        <dt>Clinic</dt><dd><?= $v('clinic') ?> <?= e($d['clinicArea'] ?? '') ?></dd><dt>Doctor</dt><dd><?= $v('doctor') ?> <?= !empty($d['doctorPh']) ? '· +91 ' . e($d['doctorPh']) : '' ?></dd>
        <dt>Clinic owner</dt><dd><?= $v('cOwner') ?> <?= !empty($d['cOwnerPh']) ? '· +91 ' . e($d['cOwnerPh']) : '' ?></dd><dt>Reception</dt><dd><?= !empty($d['clinicPh']) ? '+91 ' . e($d['clinicPh']) : '—' ?></dd><?php endif; ?></dl>
        <h2>Public profile shows</h2><p class="small">Phone: <?= $p['pub_phone'] ? 'yes' : 'no' ?> · Address: <?= e($p['pub_addr']) ?> · Vet: <?= $p['pub_vet'] ? 'yes' : 'no' ?> · Allergies: <?= $p['pub_allergy'] ? 'yes' : 'no' ?></p></div>
    </div>
    <div class="card"><h2>Files (<?= count($docs) ?>)</h2><?php if (!$docs): ?><p class="muted">No files uploaded.</p><?php endif; ?><div class="docs">
    <?php foreach ($docs as $doc): ?><a class="doc" href="/admin/?p=file&id=<?= (int)$doc['id'] ?>" target="_blank" rel="noopener"><span class="th"><?php if (strpos($doc['mime'], 'image/') === 0 && $doc['kind'] !== 'kyc'): ?><img src="/admin/?p=file&id=<?= (int)$doc['id'] ?>" alt="" loading="lazy"><?php else: ?><?= $doc['kind'] === 'kyc' ? '🔒 ID proof' : 'PDF' ?><?php endif; ?></span><span><b><?= e(ucfirst($doc['kind'])) ?></b> · <?= e($doc['label']) ?><br><span class="muted"><?= e($doc['original_name']) ?></span></span></a><?php endforeach; ?></div></div>
    <div class="split">
      <div class="card"><h2>Orders</h2><?php $os = q('SELECT * FROM orders WHERE pet_id = ? ORDER BY id DESC', [$pid])->fetchAll(); if (!$os) echo '<p class="muted">None</p>'; foreach ($os as $o): ?><p><b class="mono"><?= e($o['order_no']) ?></b> · <?= e(product_label($o['product'])) ?> · <?= pill($o['status']) ?></p><?php endforeach; ?></div>
      <div class="card"><h2>Quote requests</h2><?php $qs = q('SELECT * FROM quotes WHERE pet_id = ? ORDER BY id DESC', [$pid])->fetchAll(); if (!$qs) echo '<p class="muted">None</p>'; foreach ($qs as $x): ?><p><b class="mono"><?= e($x['quote_no']) ?></b> · <?= e(ucfirst($x['plan'])) ?> · ₹<?= number_format((int)$x['sum_insured']) ?> · <?= pill($x['status']) ?></p><?php endforeach; ?></div>
    </div>
    <?php if ($me['role'] === 'superadmin'): ?><div class="card"><h2>Delete this ID</h2><p class="muted small">Removes the ID, its files, orders and quotes for good. The owner account stays.</p><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="a" value="pet_delete"><input type="hidden" name="id" value="<?= $pid ?>"><input name="confirm" placeholder="Type DELETE" style="max-width:160px"><button class="btn danger sm">Delete</button></form></div><?php endif; ?>
    <?php break;

case 'orders':
case 'quotes':
    $isO = $page === 'orders';
    $stt = $_GET['st'] ?? '';
    $statuses = $isO ? ORDER_STATUS : QUOTE_STATUS;
    $where = '1=1'; $args = [];
    if (in_array($stt, $statuses, true)) { $where = 'x.status = ?'; $args[] = $stt; }
    $tbl = $isO ? 'orders' : 'quotes';
    $total = (int)q("SELECT COUNT(*) FROM $tbl x WHERE $where", $args)->fetchColumn();
    $rows = q("SELECT x.*, p.id_no, p.name AS pet, p.breed, u.name AS owner, u.mobile AS omobile FROM $tbl x JOIN pets p ON p.id = x.pet_id JOIN users u ON u.id = x.owner_id WHERE $where ORDER BY x.id DESC LIMIT $per OFFSET $off", $args)->fetchAll();
    ?>
    <div class="head"><div><h1><?= $isO ? 'Kit orders' : 'Insurance quote requests' ?></h1><p class="muted"><?= $total ?> found<?= $isO ? ' · safety kits, ₹1,000 pay on delivery' : ' · follow up with a licensed advisor' ?></p></div><?php if (can('export')): ?><a class="btn ghost" href="/admin/?p=export&t=<?= $tbl ?>">Export CSV</a><?php endif; ?></div>
    <form class="filters" method="get"><input type="hidden" name="p" value="<?= $page ?>"><select name="st" onchange="this.form.submit()"><option value="">All statuses</option><?php foreach ($statuses as $s): ?><option <?= $stt === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></form>
    <div class="card"><div class="tbl-wrap"><table>
    <tr><th><?= $isO ? 'Order' : 'Request' ?></th><th>For</th><?= $isO ? '<th>Product</th><th>Deliver to</th>' : '<th>Plan</th><th>Health</th><th>Owner</th>' ?><th>Status &amp; notes</th><th>Created</th></tr>
    <?php foreach ($rows as $r): ?><tr>
      <td class="mono nowrap"><?= e($isO ? $r['order_no'] : $r['quote_no']) ?></td>
      <td><a href="/admin/?p=pet&id=<?= (int)$r['pet_id'] ?>"><?= e($r['pet']) ?></a><br><span class="muted small"><?= e($r['breed']) ?></span></td>
      <?php if ($isO): ?><td><?= e(product_label($r['product'])) ?></td><td><?= e($r['ship_name']) ?> · +91 <?= e($r['ship_mobile']) ?><br><span class="small"><?= e($r['ship_address']) ?>, <?= e($r['ship_city']) ?>, <?= e($r['ship_state']) ?> <?= e($r['ship_pin']) ?></span></td>
      <?php else: ?><td><?= e(ucfirst($r['plan'])) ?><br><span class="small">₹<?= number_format((int)$r['sum_insured']) ?> cover</span></td><td class="small">Ill in 12m: <?= e($r['ill_12m']) ?><br>Ongoing: <?= e($r['ongoing']) ?></td><td><?= e($r['owner']) ?><br><a class="small" href="https://wa.me/91<?= e($r['omobile']) ?>" target="_blank" rel="noopener">+91 <?= e($r['omobile']) ?></a></td><?php endif; ?>
      <td style="min-width:220px"><?php if (can('status')): ?><form method="post" class="form" style="gap:6px"><?= csrf_field() ?><input type="hidden" name="a" value="<?= $isO ? 'order_status' : 'quote_status' ?>"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="back" value="p=<?= $page ?>"><select name="status"><?php foreach ($statuses as $s): ?><option <?= $r['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select><input name="notes" value="<?= e($r['notes']) ?>" placeholder="Notes"><button class="btn sm">Save</button></form><?php else: ?><?= pill($r['status']) ?><?php endif; ?></td>
      <td class="nowrap"><?= fmt_dt($r['created_at']) ?></td></tr><?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="7" class="muted">Nothing yet.</td></tr><?php endif; ?></table></div><?php pager($total, $per, $pg, 'p=' . $page . '&st=' . urlencode((string)$stt)); ?></div>
    <?php break;

case 'owners':
    $qq = trim((string)($_GET['q'] ?? ''));
    $where = "u.role = 'owner'"; $args = [];
    if ($qq !== '') { $where .= ' AND (u.name LIKE ? OR u.mobile LIKE ? OR u.email LIKE ?)'; $l = "%$qq%"; array_push($args, $l, $l, $l); }
    $mk = $_GET['mk'] ?? '';
    if ($mk === '1') $where .= ' AND u.marketing = 1';
    $total = (int)q("SELECT COUNT(*) FROM users u WHERE $where", $args)->fetchColumn();
    $mkCount = (int)q("SELECT COUNT(*) FROM users WHERE role = 'owner' AND marketing = 1")->fetchColumn();
    $rows = q("SELECT u.*, (SELECT COUNT(*) FROM pets p WHERE p.owner_id = u.id) AS ids FROM users u WHERE $where ORDER BY u.id DESC LIMIT $per OFFSET $off", $args)->fetchAll();
    ?>
    <div class="head"><div><h1>Owners</h1><p class="muted"><?= $total ?> accounts. Owners log in at <a href="/account/" target="_blank">/account</a>.</p></div><?php if (can('export')): ?><div class="inline"><a class="btn ghost" href="/admin/?p=export&t=marketing">Export marketing list (<?= $mkCount ?>)</a><a class="btn ghost" href="/admin/?p=export&t=owners">Export CSV</a></div><?php endif; ?></div>
    <form class="filters" method="get"><input type="hidden" name="p" value="owners"><input name="q" value="<?= e($qq) ?>" placeholder="Search name, mobile or email"><select name="mk"><option value="">All owners</option><option value="1" <?= $mk === '1' ? 'selected' : '' ?>>Agreed to marketing</option></select><button class="btn">Search</button></form>
    <div class="card"><div class="tbl-wrap"><table><tr><th>Name</th><th>Mobile</th><th>Email</th><th>IDs</th><th>Marketing</th><th>Status</th><th>Joined</th><th>Last login</th><?php if (can('edit')): ?><th></th><?php endif; ?></tr>
    <?php foreach ($rows as $u): ?><tr><td><b><?= e($u['name']) ?></b></td><td class="nowrap"><a href="https://wa.me/91<?= e($u['mobile']) ?>" target="_blank" rel="noopener">+91 <?= e($u['mobile']) ?></a></td><td><?= e($u['email']) ?></td><td><a href="/admin/?p=pets&q=<?= e($u['mobile']) ?>"><?= (int)$u['ids'] ?></a></td><td><?= !empty($u['marketing']) ? '<span class="pill ok">Yes</span>' : '<span class="pill grey">No</span>' ?></td><td><?= pill($u['status']) ?></td><td class="nowrap"><?= fmt_dt($u['created_at']) ?></td><td class="nowrap"><?= fmt_dt($u['last_login_at']) ?></td>
      <?php if (can('edit')): ?><td class="nowrap"><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="back" value="p=owners"><button class="btn ghost sm" name="a" value="user_reset">Reset password</button><button class="btn ghost sm" name="a" value="user_toggle"><?= $u['status'] === 'active' ? 'Disable' : 'Enable' ?></button></form></td><?php endif; ?></tr><?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="9" class="muted">No owners yet.</td></tr><?php endif; ?></table></div><?php pager($total, $per, $pg, 'p=owners&q=' . urlencode($qq) . '&mk=' . urlencode((string)$mk)); ?></div>
    <?php break;

case 'team':
    if (!can('team')) { echo '<p>Only the superadmin can manage the team.</p>'; break; }
    $rows = q("SELECT * FROM users WHERE role IN ('superadmin','admin','staff') ORDER BY CASE role WHEN 'superadmin' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END, id")->fetchAll();
    ?>
    <div class="head"><div><h1>Team users</h1><p class="muted"><b>Admin</b>: everything except managing the team. <b>Staff</b>: view records and update order and quote status.</p></div></div>
    <div class="card"><div class="tbl-wrap"><table><tr><th>Name</th><th>Email</th><th>Mobile</th><th>Role</th><th>Status</th><th>Last login</th><th></th></tr>
    <?php foreach ($rows as $u): $self = (int)$u['id'] === (int)$me['id']; ?><tr><td><b><?= e($u['name']) ?></b><?= $self ? ' <span class="muted small">(you)</span>' : '' ?></td><td><?= e($u['email']) ?></td><td><?= e($u['mobile']) ?></td>
      <td><?php if ($u['role'] === 'superadmin'): ?><span class="pill">superadmin</span><?php else: ?><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="a" value="user_role"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="back" value="p=team"><select name="role" onchange="this.form.submit()"><?php foreach (['admin', 'staff'] as $r): ?><option <?= $u['role'] === $r ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?></select></form><?php endif; ?></td>
      <td><?= pill($u['status']) ?></td><td class="nowrap"><?= fmt_dt($u['last_login_at']) ?></td>
      <td class="nowrap"><?php if ($u['role'] !== 'superadmin'): ?><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="back" value="p=team"><button class="btn ghost sm" name="a" value="user_reset">Reset password</button><button class="btn ghost sm" name="a" value="user_toggle"><?= $u['status'] === 'active' ? 'Disable' : 'Enable' ?></button></form><?php endif; ?></td></tr><?php endforeach; ?>
    </table></div></div>
    <div class="card" style="max-width:720px"><h2>Add a team member</h2><form method="post" class="form"><?= csrf_field() ?><input type="hidden" name="a" value="user_create">
      <div class="g2"><label>Name<input name="name" required></label><label>Email (login)<input type="email" name="email" required></label></div>
      <div class="g3"><label>Mobile (optional)<input name="mobile" inputmode="numeric" maxlength="10"></label><label>Role<select name="role"><option value="staff">Staff</option><option value="admin">Admin</option></select></label><label>Password (10+)<input type="text" name="password" required minlength="10" autocomplete="new-password"></label></div>
      <button class="btn" style="justify-self:start">Add user</button></form></div>
    <?php break;

case 'activity':
    $rows = q('SELECT a.*, u.name, u.role FROM activity_log a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 200')->fetchAll();
    ?>
    <div class="head"><div><h1>Activity log</h1><p class="muted">Last 200 actions, including logins, status changes, exports and ID-proof views.</p></div></div>
    <div class="card"><div class="tbl-wrap"><table><tr><th>When</th><th>Who</th><th>Action</th><th>Details</th><th>IP</th></tr>
    <?php foreach ($rows as $r): ?><tr><td class="nowrap"><?= fmt_dt($r['created_at']) ?></td><td><?= e($r['name'] ?? 'Visitor') ?> <span class="muted small"><?= e($r['role'] ?? '') ?></span></td><td><code><?= e($r['action']) ?></code></td><td><?= e($r['details']) ?></td><td class="mono small"><?= e($r['ip']) ?></td></tr><?php endforeach; ?>
    </table></div></div>
    <?php break;

case 'account':
    ?>
    <div class="head"><div><h1>My account</h1><p class="muted"><?= e($me['name']) ?> · <?= e($me['email']) ?> · <?= e($me['role']) ?></p></div></div>
    <div class="card" style="max-width:520px"><h2>Change password</h2><form method="post" class="form"><?= csrf_field() ?><input type="hidden" name="a" value="my_password"><label>Current password<input type="password" name="current" required autocomplete="current-password"></label><label>New password (10+ characters)<input type="password" name="new" required minlength="10" autocomplete="new-password"></label><button class="btn" style="justify-self:start">Update password</button></form></div>
    <?php break;

default: echo '<p>Page not found.</p>';
}
?>
</main></div></body></html>
