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
        } elseif ($a === 'prefs') {
            q('UPDATE users SET marketing = ? WHERE id = ?', [isset($_POST['marketing']) ? 1 : 0, $me['id']]);
            log_activity((int)$me['id'], 'prefs_update', isset($_POST['marketing']) ? 'marketing on' : 'marketing off');
            $_SESSION['flash'] = 'Your message preferences are saved.';
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
<title><?= $me ? 'My account' : 'Log in' ?> · IndiaPetInsurance.com</title>
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap">
<link rel="stylesheet" href="/assets/style.css?v=13">
</head>
<?php
$paw = '<svg width="22" height="22" viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><ellipse cx="12" cy="16" rx="5" ry="4.2"/><circle cx="5.5" cy="10" r="2.2"/><circle cx="9.5" cy="6" r="2.2"/><circle cx="14.5" cy="6" r="2.2"/><circle cx="18.5" cy="10" r="2.2"/></svg>';
$legal = '<div class="wrap"><div class="legalbar"><span>© 2026 Scram Technologies Pvt. Ltd.</span><nav aria-label="Legal"><a href="/privacy-policy/">Privacy Policy</a><a href="/terms/">Terms of Use</a><a href="/refund-policy/">Returns &amp; Refunds</a><a href="mailto:support@indiapetinsurance.com">support@indiapetinsurance.com</a></nav></div></div>';
// Result of a Razorpay payment made on Razorpay's own page (redirect mode)
$payBanner = '';
$pay = (string)($_GET['pay'] ?? '');
if ($pay === 'success') {
    $payBanner = '<div class="done-msg" style="margin-bottom:16px">✓ Payment received' . (!empty($_GET['order']) ? ' for order ' . e($_GET['order']) : '') . '. ' . (!empty($_GET['pid']) ? 'Payment ID ' . e($_GET['pid']) . '. ' : '') . 'We\'ll confirm dispatch on WhatsApp.</div>';
} elseif ($pay === 'failed') {
    $payBanner = '<div class="note" style="background:var(--red-soft);color:#8F1D23;margin-bottom:16px">Payment not completed' . (!empty($_GET['order']) ? ' for order ' . e($_GET['order']) : '') . ': ' . e(mb_substr((string)($_GET['msg'] ?? ''), 0, 160)) . ' Nothing extra was charged. You can order the safety kit again below and pay online or on delivery.</div>';
}
if (!$me): ?>
<body style="padding-bottom:0">
<nav class="top" aria-label="Main"><div class="wrap"><a class="logo" href="/"><span class="mk"><?= $paw ?></span><span>IndiaPetInsurance<small>.com</small></span></a><a class="btn btn-blue btn-sm" href="/#make-id">Get free ID</a></div></nav>
<main class="wrap acct-login">
  <div class="acct-card">
    <?= $payBanner ?>
    <span class="eyebrow">Owner account</span>
    <h1>Log in</h1>
    <p class="lede" style="font-size:1rem">Download your dog or cat's ID card, order the safety kit, get insurance quotes and manage what the QR tag shows.</p>
    <?php if ($err): ?><div class="note" style="background:var(--red-soft);color:#8F1D23"><?= e($err) ?></div><?php endif; ?>
    <form method="post" style="display:grid;gap:14px"><?= csrf_field() ?>
      <div class="fld"><label for="lg1">Mobile number or email</label><input class="inp" id="lg1" name="login" required autocomplete="username" placeholder="98xxxxxxxx"></div>
      <div class="fld"><label for="lg2">Password</label><input class="inp" id="lg2" type="password" name="password" required autocomplete="current-password"></div>
      <button class="btn btn-blue">Log in</button>
    </form>
    <p class="hint">Forgot your password? Email <a href="mailto:support@indiapetinsurance.com">support@indiapetinsurance.com</a> with your registered mobile number and we'll reset it.</p>
    <p style="font-size:.92rem">New here? <a href="/#make-id">Make a free ID for your dog or cat</a>. Your account is created in the same step.</p>
  </div>
</main>
<?= $legal ?>
</body>
<?php else:
  $pets = q('SELECT * FROM pets WHERE owner_id = ? ORDER BY id DESC', [$me['id']])->fetchAll();
  $orders = q('SELECT o.*, p.name AS pet FROM orders o JOIN pets p ON p.id = o.pet_id WHERE o.owner_id = ? ORDER BY o.id DESC', [$me['id']])->fetchAll();
  $quotes = q('SELECT x.*, p.name AS pet FROM quotes x JOIN pets p ON p.id = x.pet_id WHERE x.owner_id = ? ORDER BY x.id DESC', [$me['id']])->fetchAll();
  $docsFor = function (int $pid): array { return q('SELECT id, kind, label, original_name, mime FROM documents WHERE pet_id = ? ORDER BY id', [$pid])->fetchAll(); };
  $prodLabel = function (string $p): string { return strpos($p, 'kit:') === 0 ? 'Safety kit · collar ' . substr($p, 4) : (['card' => 'PVC card', 'tag' => 'Steel tag', 'combo' => 'Card + tag'][$p] ?? $p); };
  $statusText = ['pending_payment' => 'Awaiting payment', 'new' => 'Received', 'confirmed' => 'Confirmed', 'printed' => 'Being made', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled', 'contacted' => 'Advisor in touch', 'quoted' => 'Quote sent', 'converted' => 'Policy issued', 'closed' => 'Closed'];
  $kindText = ['photo' => 'Photo', 'record' => 'Health record', 'kyc' => 'ID proof', 'angle' => 'Insurance photo'];
  $first = explode(' ', $me['name'])[0];
?>
<body data-page="account" style="padding-bottom:0">
<nav class="top" aria-label="Main"><div class="wrap">
  <a class="logo" href="/"><span class="mk"><?= $paw ?></span><span>IndiaPetInsurance<small>.com</small></span></a>
  <div class="navlinks"><a href="/">Home</a><a href="/insurance/">Insurance</a><?php if (in_array($me['role'], STAFF_ROLES, true)): ?><a href="/admin/">Admin panel</a><?php endif; ?></div>
  <div class="navright"><a class="navlogin" href="/account/?logout=1" style="display:inline">Log out</a></div>
</div></nav>
<main class="wrap acct">
  <?php if ($msg): ?><div class="done-msg" style="margin-bottom:16px">✓ <?= e($msg) ?></div><?php endif; ?>
  <?= $payBanner ?>
  <?php if ($err): ?><div class="note" style="background:var(--red-soft);color:#8F1D23;margin-bottom:16px"><?= e($err) ?></div><?php endif; ?>
  <div class="acct-head">
    <div><span class="eyebrow">My account</span><h1>Hi <?= e($first) ?></h1><p class="lede">Your family's IDs, records, orders and insurance quotes, all in one place.</p></div>
    <div class="acct-actions"><button class="btn btn-blue" type="button" data-start="dog" data-new="1">+ Add a dog or cat</button><a class="btn btn-ghost" href="/account/download.php">⬇ Download all my data</a></div>
  </div>

  <div class="acct-pets">
  <?php foreach ($pets as $p): $docs = $docsFor((int)$p['id']); $photo = null; foreach ($docs as $dd) { if ($dd['kind'] === 'photo') $photo = $dd['id']; } ?>
    <article class="acct-pet">
      <div class="ap-top">
        <div class="ap-ph"><?php if ($photo): ?><img src="/account/file.php?id=<?= (int)$photo ?>" alt="Photo of <?= e($p['name']) ?>"><?php else: ?><?= $p['species'] === 'cat' ? '🐱' : '🐶' ?><?php endif; ?></div>
        <div style="min-width:0"><h2><?= e($p['name']) ?></h2><div class="ap-sub"><?= e($p['breed']) ?></div><div class="ap-id"><?= e($p['id_no']) ?></div></div>
      </div>
      <div class="ap-acts">
        <button class="btn btn-blue btn-sm" type="button" data-pet="<?= e($p['id_no']) ?>" data-act="card">View &amp; download ID card</button>
        <button class="btn btn-saffron btn-sm" type="button" data-pet="<?= e($p['id_no']) ?>" data-act="kit">Order safety kit · ₹1,000</button>
        <button class="btn btn-ghost btn-sm" type="button" data-pet="<?= e($p['id_no']) ?>" data-act="quote">Protect <?= e($p['name']) ?> now</button>
        <a class="btn btn-ghost btn-sm" href="/p/<?= e($p['id_no']) ?>" target="_blank" rel="noopener">QR profile ↗</a>
      </div>
      <p class="hint" style="margin:0"><?= (int)$p['scan_count'] ?> QR scans<?= $p['last_scan_at'] ? ' · last on ' . date('j M Y', strtotime($p['last_scan_at'])) : '' ?> · made on <?= date('j M Y', strtotime($p['created_at'])) ?></p>
      <?php if ($docs): ?><details class="ap-more"><summary>Files (<?= count($docs) ?>)</summary><ul class="ap-files"><?php foreach ($docs as $dd): ?><li><span><?= e($kindText[$dd['kind']] ?? $dd['kind']) ?> · <?= e($dd['original_name']) ?></span><span><a href="/account/file.php?id=<?= (int)$dd['id'] ?>" target="_blank" rel="noopener">Open</a> · <a href="/account/file.php?id=<?= (int)$dd['id'] ?>&amp;dl=1">Download</a></span></li><?php endforeach; ?></ul></details><?php endif; ?>
      <details class="ap-more"><summary>What strangers see when they scan the tag</summary>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="privacy"><input type="hidden" name="pet" value="<?= (int)$p['id'] ?>">
          <label class="sw"><span><b>My phone number</b></span><input type="checkbox" name="pub_phone" <?= $p['pub_phone'] ? 'checked' : '' ?>></label>
          <label class="sw"><span><b>My address</b></span><select class="inp" name="pub_addr" style="width:auto"><option value="locality" <?= $p['pub_addr'] === 'locality' ? 'selected' : '' ?>>Locality only</option><option value="exact" <?= $p['pub_addr'] === 'exact' ? 'selected' : '' ?>>Exact address</option></select></label>
          <label class="sw"><span><b>Vet and clinic</b></span><input type="checkbox" name="pub_vet" <?= $p['pub_vet'] ? 'checked' : '' ?>></label>
          <label class="sw"><span><b>Allergies and notes</b></span><input type="checkbox" name="pub_allergy" <?= $p['pub_allergy'] ? 'checked' : '' ?>></label>
          <button class="btn btn-dark btn-sm" style="margin-top:12px">Save</button>
        </form>
      </details>
    </article>
  <?php endforeach; ?>
  <?php if (!$pets): ?><div class="blk"><h3>No IDs yet</h3><p class="lede">Make your first one in 3 minutes.</p><button class="btn btn-blue" type="button" data-start="dog" data-new="1" style="justify-self:start">Make my dog's ID</button></div><?php endif; ?>
  </div>

  <div class="acct-two">
    <section class="blk"><h3>Kit orders</h3>
      <?php if (!$orders): ?><p class="hint">No orders yet. Order a safety kit from any ID above.</p><?php endif; ?>
      <?php foreach ($orders as $o): ?><div class="acct-row"><div><b class="mono"><?= e($o['order_no']) ?></b><span><?= e($prodLabel($o['product'])) ?> for <?= e($o['pet']) ?> · <?= date('j M Y', strtotime($o['created_at'])) ?></span></div><span style="display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end"><?php if (($o['payment_status'] ?? '') === 'paid'): ?><span class="chip" style="background:var(--green-soft);color:var(--green)">Paid online</span><?php elseif (($o['payment_status'] ?? '') === 'cod'): ?><span class="chip">Cash on delivery</span><?php endif; ?><span class="chip"><?= e($statusText[$o['status']] ?? $o['status']) ?></span></span></div><?php endforeach; ?>
    </section>
    <section class="blk"><h3>Insurance quote requests</h3>
      <?php if (!$quotes): ?><p class="hint">No quote requests yet. Get one from any ID above.</p><?php endif; ?>
      <?php foreach ($quotes as $x): ?><div class="acct-row"><div><b class="mono"><?= e($x['quote_no']) ?></b><span><?= e(ucfirst($x['plan'])) ?> for <?= e($x['pet']) ?> · ₹<?= number_format((int)$x['sum_insured']) ?> cover</span></div><span class="chip"><?= e($statusText[$x['status']] ?? $x['status']) ?></span></div><?php endforeach; ?>
    </section>
  </div>

  <div class="acct-two">
    <section class="blk"><h3>Messages from us</h3>
      <form method="post" style="display:grid;gap:12px"><?= csrf_field() ?><input type="hidden" name="a" value="prefs">
        <label class="check"><input type="checkbox" checked disabled><span>Reminders and order updates on WhatsApp <span class="hint">(needed for the service)</span></span></label>
        <label class="check"><input type="checkbox" name="marketing" <?= !empty($me['marketing']) ? 'checked' : '' ?>><span>Offers, care tips and news by WhatsApp, SMS or email</span></label>
        <button class="btn btn-dark btn-sm" style="justify-self:start">Save preferences</button>
      </form>
    </section>
    <section class="blk"><h3>Password</h3>
      <form method="post" style="display:grid;gap:12px"><?= csrf_field() ?><input type="hidden" name="a" value="password">
        <div class="fld"><label for="pw1">Current password</label><input class="inp" id="pw1" type="password" name="current" required autocomplete="current-password"></div>
        <div class="fld"><label for="pw2">New password (8+ characters)</label><input class="inp" id="pw2" type="password" name="new" required minlength="8" autocomplete="new-password"></div>
        <button class="btn btn-dark btn-sm" style="justify-self:start">Change password</button>
      </form>
      <p class="hint" style="margin-top:14px">Want your account and data deleted? Email <a href="mailto:support@indiapetinsurance.com?subject=Delete%20my%20account">support@indiapetinsurance.com</a> from your registered details.</p>
    </section>
  </div>
</main>
<?= $legal ?>
<?php
  // Same ID builder as the home page, used here to add another dog or cat and to open existing IDs
  $home = (string)@file_get_contents(dirname(__DIR__) . '/index.html');
  $a = strpos($home, '<!-- WIZARD -->'); $b = strpos($home, '<script src="https://cdnjs');
  if ($a !== false && $b !== false) echo substr($home, $a, $b - $a);
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js" defer></script>
<script src="/assets/app.js?v=13" defer></script>
</body>
<?php endif; ?>
</html>
