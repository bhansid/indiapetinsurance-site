<?php
/** Insurance quote request. multipart: data (JSON {id_no, plan, si, ill, pre}) + angle_Front/Back/Left/Right/Top photos. */
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';
require_installed_api();
require_ajax_post();
$u = current_user();
if (!$u) fail('Please log in first.', 401, ['code' => 'login_required']);
if (rate_limited('quote', 20, 3600)) fail('Too many requests. Please try again later.', 429);

$d = json_decode((string)($_POST['data'] ?? ''), true);
if (!is_array($d)) fail('Missing form data.');
$st = db()->prepare('SELECT id, name, breed FROM pets WHERE id_no = ? AND owner_id = ?');
$st->execute([str_field($d, 'id_no', 32), $u['id']]);
$pet = $st->fetch();
if (!$pet) fail('ID not found on your account.', 404);

$plan = in_array($d['plan'] ?? '', ['essential', 'complete'], true) ? $d['plan'] : '';
$si = (int)($d['si'] ?? 0);
$ill = yn($d, 'ill', ['No', 'Yes']);
$pre = yn($d, 'pre', ['No', 'Yes']);
if (!$plan || !in_array($si, [25000, 50000, 100000], true) || !$ill || !$pre) fail('Please answer all the questions.', 422);

$pdo = db();
$photos = 0;
try {
    $pdo->beginTransaction();
    $no = unique_code('quotes', 'quote_no', fn() => 'Q-' . date('ymd') . '-' . random_int(1000, 9999));
    $pdo->prepare('INSERT INTO quotes (quote_no, pet_id, owner_id, plan, sum_insured, ill_12m, ongoing, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)')
        ->execute([$no, $pet['id'], $u['id'], $plan, $si, $ill, $pre, 'new', now(), now()]);
    $qid = (int)$pdo->lastInsertId();
    foreach (['Front', 'Back', 'Left', 'Right', 'Top'] as $a) { foreach (files_list('angle_' . $a) as $f) { if (save_upload($f, (int)$pet['id'], 'angle', $a . ' (quote ' . $no . ')')) $photos++; } }
    $pdo->commit();
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('quote: ' . $ex->getMessage());
    fail($ex instanceof RuntimeException ? $ex->getMessage() : 'Something went wrong. Please try again.', 500);
}
notify('quote_new', 'Quote request ' . $no . ': ' . ucfirst($plan) . ' for ' . $pet['name'], $pet['breed'] . ' · ₹' . number_format($si) . ' cover · ill 12m: ' . $ill . ' · ongoing: ' . $pre . " · $photos photo(s)", 'quotes', $qid);
log_activity((int)$u['id'], 'quote_new', $no);
json_out(['ok' => true, 'quote_no' => $no, 'photos' => $photos]);
