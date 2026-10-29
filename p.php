<?php
/** Public "found pet" page opened by scanning the QR on the card: /p/IPI-JK-26-123456 */
declare(strict_types=1);
require __DIR__ . '/api/lib/bootstrap.php';
header('X-Robots-Tag: noindex, nofollow');
if (!installed()) { http_response_code(503); exit('Coming soon'); }

$idNo = strtoupper(preg_replace('/[^A-Za-z0-9-]/', '', (string)($_GET['id'] ?? '')));
$st = db()->prepare("SELECT p.*, u.name AS owner_name, u.mobile AS owner_mobile FROM pets p JOIN users u ON u.id = p.owner_id WHERE p.id_no = ? AND p.status = 'active'");
$st->execute([$idNo]);
$p = $st->fetch();

// Profile photo endpoint (the photo is printed on the card, so it is public by design)
if ($p && isset($_GET['photo'])) {
    $ph = db()->prepare("SELECT path, mime FROM documents WHERE pet_id = ? AND kind = 'photo' ORDER BY id DESC LIMIT 1");
    $ph->execute([$p['id']]);
    if ($row = $ph->fetch()) send_file($row['path'], $row['mime'], 'photo');
    http_response_code(404); exit;
}

if ($p) {
    db()->prepare('UPDATE pets SET scan_count = scan_count + 1, last_scan_at = ? WHERE id = ?')->execute([now(), $p['id']]);
    if (!rate_limited('scan_notify_' . $p['id'], 1, 1800)) {
        notify('scan', 'QR scanned: ' . $p['name'] . ' (' . $p['id_no'] . ')', 'Someone opened the public profile. Owner: ' . $p['owner_name'] . ' +91 ' . $p['owner_mobile'], 'pets', (int)$p['id']);
    }
    $d = json_decode($p['data'], true) ?: [];
    $hasPhoto = (bool)db()->query('SELECT 1 FROM documents WHERE pet_id = ' . (int)$p['id'] . " AND kind = 'photo' LIMIT 1")->fetchColumn();
    $area = $p['pub_addr'] === 'exact' ? implode(', ', array_filter([$d['oHouse'] ?? '', $d['oLocality'] ?? '', $d['oCity'] ?? ''])) : implode(', ', array_filter([$d['oLocality'] ?? '', $d['oCity'] ?? '']));
    $first = explode(' ', (string)$p['owner_name'])[0];
}
http_response_code($p ? 200 : 404);
?><!doctype html>
<html lang="en-IN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="robots" content="noindex,nofollow">
<title><?= $p ? e($p['name']) . ' · Found pet' : 'ID not found' ?> · IndiaPetInsurance.com</title>
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/admin/admin.css">
</head><body class="auth">
<main class="auth-card found">
  <div class="brand"><span class="mk"><svg width="20" height="20" viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><ellipse cx="12" cy="16" rx="5" ry="4.2"/><circle cx="5.5" cy="10" r="2.2"/><circle cx="9.5" cy="6" r="2.2"/><circle cx="14.5" cy="6" r="2.2"/><circle cx="18.5" cy="10" r="2.2"/></svg></span> IndiaPetInsurance<small>.com</small></div>
  <?php if (!$p): ?>
    <h1>We couldn't find this ID</h1><p class="muted">Check the code on the tag, or contact us on WhatsApp.</p><a class="btn" href="/">Go to homepage</a>
  <?php else: ?>
    <div class="found-head">
      <?php if ($hasPhoto): ?><img src="/p.php?id=<?= e($p['id_no']) ?>&photo=1" alt="Photo of <?= e($p['name']) ?>"><?php endif; ?>
      <div><span class="pill ok">Found pet profile</span><h1>Hi, I'm <?= e($p['name']) ?>!</h1><p class="muted"><?= e($p['breed']) ?> · <?= $p['gender'] === 'f' ? 'Female' : 'Male' ?><?= !empty($d['colour']) ? ' · ' . e($d['colour']) : '' ?></p></div>
    </div>
    <?php if ($p['pub_phone'] && $p['owner_mobile']): ?>
      <a class="btn big" href="tel:+91<?= e($p['owner_mobile']) ?>">📞 Call <?= e($first) ?> · +91 <?= e($p['owner_mobile']) ?></a>
      <a class="btn ghost" href="https://wa.me/91<?= e($p['owner_mobile']) ?>?text=<?= rawurlencode('Hi, I found ' . $p['name'] . ' (ID ' . $p['id_no'] . ').') ?>" rel="noopener">WhatsApp <?= e($first) ?></a>
    <?php else: ?><div class="alert">The owner chose to hide their number. Please contact IndiaPetInsurance.com support with ID <b><?= e($p['id_no']) ?></b>.</div><?php endif; ?>
    <dl class="kv">
      <?php if ($area): ?><dt>Home area</dt><dd><?= e($area) ?></dd><?php endif; ?>
      <?php if ($p['pub_vet'] && ($p['clinic'] || !empty($d['doctor']))): ?><dt>Vet</dt><dd><?= e(implode(', ', array_filter([!empty($d['doctor']) ? 'Dr. ' . preg_replace('/^dr\.?\s*/i', '', $d['doctor']) : '', $p['clinic']]))) ?><?php if (!empty($d['clinicPh'])): ?> · <a href="tel:+91<?= e($d['clinicPh']) ?>">+91 <?= e($d['clinicPh']) ?></a><?php endif; ?></dd><?php endif; ?>
      <?php if ($p['pub_allergy'] && !empty($d['conditions'])): ?><dt>Important</dt><dd><?= e($d['conditions']) ?></dd><?php endif; ?>
      <?php if (!empty($d['marks'])): ?><dt>Identification marks</dt><dd><?= e($d['marks']) ?></dd><?php endif; ?>
      <dt>ID number</dt><dd class="mono"><?= e($p['id_no']) ?></dd>
    </dl>
    <div class="alert">🔒 Medical records are private. The owner can share them with a vet.</div>
    <p class="muted small">Want an ID like this for your own dog or cat? <a href="/#make-id">Make one free</a>.</p>
  <?php endif; ?>
<p class="muted small" style="text-align:center;margin-top:18px"><a href="/privacy-policy/">Privacy</a> · <a href="/terms/">Terms</a> · <a href="/refund-policy/">Returns &amp; refunds</a></p></main></body></html>
