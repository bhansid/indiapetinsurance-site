<?php
/** Owner portal: log in, see your dogs and cats, change what the QR profile shows, track orders and quotes. */
declare(strict_types=1);
require dirname(__DIR__) . '/api/lib/bootstrap.php';
header('X-Robots-Tag: noindex, nofollow');
if (!installed()) { http_response_code(503); exit('Coming soon'); }
start_session();

function q(string $sql, array $args = []): PDOStatement { $st = db()->prepare($sql); $st->execute($args); return $st; }
$me = current_user();
$msg = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$err = '';

if (isset($_GET['logout'])) { logout_user(); header('Location: /account/'); exit; }

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!check_csrf()) { $err = 'Your session expired. Please try again.'; }
    elseif (!$me) {
        if (rate_limited('login', 8, 900)) $err = 'Too many attempts. Please wait 15 minutes.';
        else {
            $u = find_user_by_login((string)($_POST['login'] ?? ''));
            if ($u && $u['status'] === 'active' && $u['password_hash'] && password_verify((string)($_POST['password'] ?? ''), $u['password_hash'])) {
                login_user($u); log_activity((int)$u['id'], 'login', 'account');
                header('Location: ' . (in_array($u['role'], STAFF_ROLES, true) ? '/admin/' : '/account/')); exit;
            }
            $err = 'That mobile/email and password don\'t match.';
        }
    } else {
        $a = $_POST['a'] ?? '';
        if ($a === 'privacy') {
            $pid = (int)($_POST['pet'] ?? 0);
            $own = q('SELECT id, data FROM pets WHERE id = ? AND owner_id = ?', [$pid, $me['id']])->fetch();
            if ($own) {
                $pp = isset($_POST['pub_phone']) ? 1 : 0; $pa = ($_POST['pub_addr'] ?? '') === 'exact' ? 'exact' : 'locality'; $pv = isset($_POST['pub_vet']) ? 1 : 0; $pal = isset($_POST['pub_allergy']) ? 1 : 0;
                $d = json_decode($own['data'], true) ?: [];
                $d['pubPhone'] = (bool)$pp; $d['pubAddr'] = $pa; $d['pubVet'] = (bool)$pv; $d['pubAllergy'] = (bool)$pal;
                q('UPDATE pets SET pub_phone = ?, pub_addr = ?, pub_vet = ?, pub_allergy = ?, data = ?, updated_at = ? WHERE id = ?', [$pp, $pa, $pv, $pal, json_encode($d, JSON_UNESCAPED_UNICODE), now(), $pid]);
                log_activity((int)$me['id'], 'privacy_update', 'pet ' . $pid);
                $_SESSION['flash'] = 'Saved. The QR profile is updated.';
            }
        } elseif ($a === 'password') {
            if (!password_verify((string)($_POST['current'] ?? ''), (string)$me['password_hash'])) $err = 'Current password is wrong.';
            elseif (strlen((string)($_POST['new'] ?? '')) < 8) $err = 'New password must be at least 8 characters.';
            else { q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash((string)$_POST['new'], PASSWORD_DEFAULT), $me['id']]); log_activity((int)$me['id'], 'password_change'); $_SESSION['flash'] = 'Password changed.'; }
        }
        if (!$err) { header('Location: /account/'); exit; }
    }
}
?><!doctype html>
<html lang="en-IN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="robots" content="noindex">
<title>My account · IndiaPetInsurance.com</title><link rel="icon" href="/assets/favicon.svg" type="image/svg+xml"><link rel="stylesheet" href="/admin/admin.css"></head>
<?php if (!$me): ?>
<body class="auth"><main class="auth-card">
  <a class="brand" href="/" style="text-decoration:none;color:inherit"><span class="mk"><svg width="20" height="20" viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><ellipse cx="12" cy="16" rx="5" ry="4.2"/><circle cx="5.5" cy="10" r="2.2"/><circle cx="9.5" cy="6" r="2.2"/><circle cx="14.5" cy="6" r="2.2"/><circle cx="18.5" cy="10" r="2.2"/></svg></span> IndiaPetInsurance<small>.com</small></a>
  <h1>Log in</h1><p class="muted">See your dogs' and cats' IDs, change what the QR tag shows, and track orders.</p>
  <?php if ($err): ?><div class="alert err"><?= e($err) ?></div><?php endif; ?>
  <form method="post" class="form"><?= csrf_field() ?><label>Mobile number or email<input name="login" required autocomplete="username" inputmode="email" placeholder="98xxxxxxxx"></label><label>Password<input type="password" name="password" required autocomplete="current-password"></label><button class="btn">Log in</button></form>
  <p class="muted small">Forgot your password? Message us on WhatsApp from your registered number and we'll reset it.</p>
  <p class="small">New here? <a href="/#make-id">Make a free ID for your dog or cat</a> and your account is created automatically.</p>
</main></body>
<?php else:
  $pets = q('SELECT * FROM pets WHERE owner_id = ? ORDER BY id DESC', [$me['id']])->fetchAll();
  $orders = q('SELECT o.*, p.name AS pet FROM orders o JOIN pets p ON p.id = o.pet_id WHERE o.owner_id = ? ORDER BY o.id DESC', [$me['id']])->fetchAll();
  $quotes = q('SELECT x.*, p.name AS pet FROM quotes x JOIN pets p ON p.id = x.pet_id WHERE x.owner_id = ? ORDER BY x.id DESC', [$me['id']])->fetchAll();
  $photo = function (int $pid): bool { return (bool)q("SELECT 1 FROM documents WHERE pet_id = ? AND kind = 'photo' LIMIT 1", [$pid])->fetchColumn(); };
  $products = ['card' => 'PVC card', 'tag' => 'Steel tag', 'combo' => 'Card + tag', 'kit:S' => 'Safety kit (collar S)', 'kit:M' => 'Safety kit (collar M)', 'kit:L' => 'Safety kit (collar L)', 'kit:XL' => 'Safety kit (collar XL)'];
?>
<body><main class="main" style="max-width:1100px;margin:0 auto">
  <div class="head"><a class="brand" href="/" style="text-decoration:none;color:inherit"><span class="mk"><svg width="20" height="20" viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><ellipse cx="12" cy="16" rx="5" ry="4.2"/><circle cx="5.5" cy="10" r="2.2"/><circle cx="9.5" cy="6" r="2.2"/><circle cx="14.5" cy="6" r="2.2"/><circle cx="18.5" cy="10" r="2.2"/></svg></span> IndiaPetInsurance<small>.com</small></a><div class="inline"><?php if (in_array($me['role'], STAFF_ROLES, true)): ?><a class="btn ghost sm" href="/admin/">Admin panel</a><?php endif; ?><a class="btn ghost sm" href="/account/?logout=1">Log out</a></div></div>
  <?php if ($msg): ?><div class="alert ok"><?= e($msg) ?></div><?php endif; ?><?php if ($err): ?><div class="alert err"><?= e($err) ?></div><?php endif; ?>
  <div class="head"><div><h1>Hi <?= e(explode(' ', $me['name'])[0]) ?></h1><p class="muted">Your family's IDs, all in one place.</p></div><a class="btn" href="/#make-id">+ Add a dog or cat</a></div>
  <div class="pets">
  <?php foreach ($pets as $p): ?>
    <div class="petcard">
      <div class="top"><div class="ph"><?php if ($photo((int)$p['id'])): ?><img src="/p.php?id=<?= e($p['id_no']) ?>&photo=1" alt=""><?php else: ?><?= $p['species'] === 'cat' ? '🐱' : '🐶' ?><?php endif; ?></div>
        <div><h2 style="margin:0"><?= e($p['name']) ?></h2><div class="muted small"><?= e($p['breed']) ?></div><div class="mono small"><?= e($p['id_no']) ?></div></div></div>
      <div class="inline"><a class="btn ghost sm" href="/p/<?= e($p['id_no']) ?>" target="_blank" rel="noopener">Open QR profile ↗</a><span class="muted small"><?= (int)$p['scan_count'] ?> scans<?= $p['last_scan_at'] ? ' · last ' . date('d M', strtotime($p['last_scan_at'])) : '' ?></span></div>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="privacy"><input type="hidden" name="pet" value="<?= (int)$p['id'] ?>">
        <b class="small">When someone scans the tag, show:</b>
        <label class="sw">My phone number<input type="checkbox" name="pub_phone" <?= $p['pub_phone'] ? 'checked' : '' ?>></label>
        <label class="sw">Address<select name="pub_addr"><option value="locality" <?= $p['pub_addr'] === 'locality' ? 'selected' : '' ?>>Locality only</option><option value="exact" <?= $p['pub_addr'] === 'exact' ? 'selected' : '' ?>>Exact address</option></select></label>
        <label class="sw">Vet and clinic<input type="checkbox" name="pub_vet" <?= $p['pub_vet'] ? 'checked' : '' ?>></label>
        <label class="sw">Allergies and notes<input type="checkbox" name="pub_allergy" <?= $p['pub_allergy'] ? 'checked' : '' ?>></label>
        <button class="btn sm" style="margin-top:10px">Save</button></form>
    </div>
  <?php endforeach; ?>
  <?php if (!$pets): ?><div class="card"><p>No IDs yet. <a href="/#make-id">Make your first one</a>.</p></div><?php endif; ?>
  </div>
  <div class="split" style="margin-top:16px">
    <div class="card"><h2>Kit orders</h2><?php if (!$orders): ?><p class="muted">No orders yet.</p><?php endif; ?><?php foreach ($orders as $o): ?><p><b class="mono"><?= e($o['order_no']) ?></b> · <?= e($products[$o['product']] ?? $o['product']) ?> for <?= e($o['pet']) ?> · <span class="pill"><?= e($o['status']) ?></span></p><?php endforeach; ?></div>
    <div class="card"><h2>Insurance quote requests</h2><?php if (!$quotes): ?><p class="muted">No requests yet.</p><?php endif; ?><?php foreach ($quotes as $x): ?><p><b class="mono"><?= e($x['quote_no']) ?></b> · <?= e(ucfirst($x['plan'])) ?> for <?= e($x['pet']) ?> · ₹<?= number_format((int)$x['sum_insured']) ?> · <span class="pill"><?= e($x['status']) ?></span></p><?php endforeach; ?></div>
  </div>
  <div class="card" style="max-width:520px"><h2>Change password</h2><form method="post" class="form"><?= csrf_field() ?><input type="hidden" name="a" value="password"><label>Current password<input type="password" name="current" required autocomplete="current-password"></label><label>New password (8+ characters)<input type="password" name="new" required minlength="8" autocomplete="new-password"></label><button class="btn" style="justify-self:start">Update</button></form></div>
</main></body>
<?php endif; ?>
</html>
