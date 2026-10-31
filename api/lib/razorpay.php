<?php
/**
 * Razorpay Standard Checkout helpers (Orders API + signature checks), using PHP cURL.
 * Keys come from RAZORPAY_KEY_ID / RAZORPAY_KEY_SECRET (environment or .env). The secret never leaves the server.
 * Docs: https://razorpay.com/docs/payments/payment-gateway/web-integration/standard/integration-steps/
 */
declare(strict_types=1);

const RZP_MIN_PAISE = 100;

/* ---------- Keys: superadmin settings (admin → Payments) first, then environment / .env ---------- */
/** Settings live in a JSON file one folder above public_html, never in Git or the database. */
function pay_settings_path(): string {
    foreach ([IPI_HOME, upload_dir()] as $dir) { if (is_dir($dir) && is_writable($dir)) return rtrim($dir, '/') . '/ipi-payments.json'; }
    return IPI_HOME . '/ipi-payments.json';
}
function pay_settings(bool $fresh = false): array {
    static $cache = null;
    if ($cache !== null && !$fresh) return $cache;
    $cache = [];
    $f = getenv('IPI_PAYMENTS') ?: pay_settings_path();
    if (is_file($f)) { $j = json_decode((string)file_get_contents($f), true); if (is_array($j)) $cache = $j; }
    return $cache;
}
function save_pay_settings(array $s): bool {
    $f = getenv('IPI_PAYMENTS') ?: pay_settings_path();
    $ok = @file_put_contents($f, json_encode($s, JSON_PRETTY_PRINT), LOCK_EX) !== false;
    if ($ok) { @chmod($f, 0600); pay_settings(true); }
    return $ok;
}
/** 'test' or 'live' */
function rzp_mode(): string {
    $m = pay_settings()['mode'] ?? '';
    if ($m === 'live' || $m === 'test') return $m;
    return strpos((string)env('RAZORPAY_KEY_ID', ''), 'rzp_live_') === 0 ? 'live' : 'test';
}
/** [key_id, key_secret, source] for a mode. Source: 'admin', 'env' or ''. */
function rzp_keys(?string $mode = null): array {
    $mode = $mode === 'live' ? 'live' : ($mode === 'test' ? 'test' : rzp_mode());
    $s = pay_settings();
    $id = (string)($s[$mode . '_key_id'] ?? ''); $sec = (string)($s[$mode . '_key_secret'] ?? '');
    if ($id !== '' && $sec !== '') return [$id, $sec, 'admin'];
    $eid = (string)env('RAZORPAY_KEY_ID', ''); $esec = (string)env('RAZORPAY_KEY_SECRET', '');
    if ($eid !== '' && $esec !== '' && strpos($eid, 'rzp_' . $mode . '_') === 0) return [$eid, $esec, 'env'];
    return ['', '', ''];
}
function rzp_key_id(?string $mode = null): string { return rzp_keys($mode)[0]; }
function rzp_key_secret(?string $mode = null): string { return rzp_keys($mode)[1]; }
function rzp_configured(?string $mode = null): bool { [$i, $k] = rzp_keys($mode); return $i !== '' && $k !== ''; }
function rzp_test_mode(): bool { return rzp_mode() === 'test'; }
function rzp_webhook_secrets(): array {
    $s = pay_settings();
    return array_values(array_filter([(string)($s['live_webhook_secret'] ?? ''), (string)($s['test_webhook_secret'] ?? ''), (string)env('RAZORPAY_WEBHOOK_SECRET', '')]));
}

/** Calls the Razorpay REST API. Returns [httpStatus, decodedJson]. httpStatus 0 = network error. */
function rzp_api(string $method, string $path, ?array $body = null, ?string $mode = null, ?array $keys = null): array {
    $base = rtrim((string)env('RAZORPAY_API_BASE', 'https://api.razorpay.com'), '/');   // override only for local tests
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => ($keys[0] ?? rzp_key_id($mode)) . ':' . ($keys[1] ?? rzp_key_secret($mode)),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 25,
    ]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    $raw = curl_exec($ch);
    $status = $raw === false ? 0 : (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($raw === false) error_log('razorpay curl: ' . curl_error($ch));
    curl_close($ch);
    $json = is_string($raw) ? json_decode($raw, true) : null;
    return [$status, is_array($json) ? $json : []];
}

/** Payment signature from Checkout: HMAC-SHA256(order_id + "|" + payment_id, key_secret). */
function rzp_payment_signature_ok(string $orderId, string $paymentId, string $signature, ?string $mode = null): bool {
    $secret = rzp_key_secret($mode);
    if ($orderId === '' || $paymentId === '' || $signature === '' || $secret === '') return false;
    $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, $secret);
    return hash_equals($expected, $signature);
}

/** Webhook signature: HMAC-SHA256(raw body, webhook_secret). */
function rzp_webhook_signature_ok(string $rawBody, string $signature): bool {
    if ($signature === '') return false;
    foreach (rzp_webhook_secrets() as $secret) { if (hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature)) return true; }
    return false;
}

/** Marks one of our orders as paid. Safe to call twice (checkout + webhook). Returns the order row or null. */
function mark_order_paid(string $rzpOrderId, string $rzpPaymentId, string $via): ?array {
    $pdo = db();
    $st = $pdo->prepare('SELECT o.*, p.name AS pet FROM orders o JOIN pets p ON p.id = o.pet_id WHERE o.rzp_order_id = ?');
    $st->execute([$rzpOrderId]);
    $o = $st->fetch();
    if (!$o) return null;
    if ($o['payment_status'] === 'paid') return $o;
    $pdo->prepare("UPDATE orders SET payment_status = 'paid', rzp_payment_id = ?, paid_at = ?, status = CASE WHEN status = 'pending_payment' THEN 'new' ELSE status END, updated_at = ? WHERE id = ? AND payment_status <> 'paid'")
        ->execute([$rzpPaymentId, now(), now(), $o['id']]);
    $rupees = number_format(((int)$o['amount_paise']) / 100);
    notify('order_paid', 'Paid online ₹' . $rupees . ': kit order ' . $o['order_no'] . ' for ' . $o['pet'], 'Collar ' . substr((string)$o['product'], 4) . ' · ' . $o['ship_name'] . ' · +91 ' . $o['ship_mobile'] . ' · ' . $o['ship_address'] . ', ' . $o['ship_city'] . ' ' . $o['ship_pin'] . ' · ' . $rzpPaymentId . ' (' . $via . ')', 'orders', (int)$o['id']);
    log_activity((int)$o['owner_id'], 'order_paid', $o['order_no'] . ' ' . $rzpPaymentId . ' via ' . $via);
    $o['payment_status'] = 'paid';
    return $o;
}
