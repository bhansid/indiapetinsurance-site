<?php
/**
 * Superadmin-only ₹1 test payment (Admin → Payments).
 * Runs a real Razorpay payment of ₹1 in the current mode (test or live) through the same checkout,
 * signature check and webhook as kit orders, then lets you refund it. It never touches kit orders.
 * POST JSON {a, csrf, ...}:
 *   create                                  → new Razorpay order for ₹1
 *   verify  {order_id, payment_id, signature} → checks the signature, then asks Razorpay for the payment status
 *   status  {order_id, payment_id}          → captured? webhook arrived?
 *   refund  {payment_id}                    → refunds the ₹1 (only payments made by this tool)
 */
declare(strict_types=1);
require dirname(__DIR__) . '/api/lib/bootstrap.php';
require dirname(__DIR__) . '/api/lib/razorpay.php';
header('X-Robots-Tag: noindex, nofollow');
require_installed_api();
require_ajax_post();
start_session();
$me = current_user();
if (!$me || $me['role'] !== 'superadmin') fail('Only the superadmin can run a test payment.', 403);
$d = body_json();
if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string)($d['csrf'] ?? ''))) fail('Session expired. Reload the page and try again.', 400);
if (rate_limited('rzp_admin_test', 60, 3600)) fail('Too many attempts. Try again in an hour.', 429);

const TEST_PAISE = 100;
const TEST_PURPOSE = 'admin_test';
$mode = rzp_mode();
if (!rzp_configured($mode)) fail('Add the ' . $mode . ' keys first.', 400);
$id = fn(string $k) => preg_replace('/[^A-Za-z0-9_]/', '', substr((string)($d[$k] ?? ''), 0, 60));

/** Asks Razorpay about one payment and whether the webhook for it has arrived. */
function payment_report(string $paymentId, string $orderId): array {
    [$st, $p] = rzp_api('GET', '/v1/payments/' . rawurlencode($paymentId));
    if ($st !== 200) return ['ok' => false, 'error' => 'Razorpay could not find this payment (HTTP ' . $st . ').'];
    $hook = q1("SELECT created_at FROM activity_log WHERE action = 'rzp_webhook_test' AND details LIKE ? ORDER BY id DESC LIMIT 1", ['%' . $orderId . '%']);
    return [
        'ok' => true,
        'status' => (string)($p['status'] ?? ''),            // created | authorized | captured | refunded | failed
        'method' => (string)($p['method'] ?? ''),
        'amount' => (int)($p['amount'] ?? 0),
        'refund_status' => (string)($p['refund_status'] ?? ''),
        'amount_refunded' => (int)($p['amount_refunded'] ?? 0),
        'webhook_at' => $hook ?: null,
        'webhook_set' => count(rzp_webhook_secrets()) > 0,
    ];
}
function q1(string $sql, array $args) { $s = db()->prepare($sql); $s->execute($args); return $s->fetchColumn(); }

switch ((string)($d['a'] ?? '')) {
    case 'create':
        [$st, $res] = rzp_api('POST', '/v1/orders', [
            'amount' => TEST_PAISE, 'currency' => 'INR', 'receipt' => 'test-' . date('ymd-His'),
            'notes' => ['purpose' => TEST_PURPOSE, 'by' => (string)$me['email']],
        ]);
        if ($st === 401) fail('Razorpay rejected the ' . $mode . ' keys. Check the Key ID and Secret.', 401);
        if ($st < 200 || $st >= 300 || empty($res['id'])) fail('Razorpay could not create the order (HTTP ' . $st . '): ' . ($res['error']['description'] ?? 'unknown'), 502);
        log_activity((int)$me['id'], 'rzp_test_created', $res['id'] . ' ' . $mode);
        json_out(['ok' => true, 'order_id' => $res['id'], 'amount' => TEST_PAISE, 'currency' => 'INR', 'key_id' => rzp_key_id($mode), 'mode' => $mode,
            'prefill' => ['name' => (string)$me['name'], 'email' => (string)($me['email'] ?? ''), 'contact' => $me['mobile'] ? '+91' . $me['mobile'] : '']]);

    case 'verify':
        $o = $id('order_id'); $p = $id('payment_id'); $sig = preg_replace('/[^a-f0-9]/', '', strtolower((string)($d['signature'] ?? '')));
        if (!rzp_payment_signature_ok($o, $p, $sig, $mode)) { log_activity((int)$me['id'], 'rzp_test_bad_signature', $o . ' ' . $p); fail('Signature check FAILED. The payment could not be verified with your key secret.', 400, ['step' => 'signature']); }
        log_activity((int)$me['id'], 'rzp_test_paid', $o . ' ' . $p . ' ' . $mode);
        notify('payment_test', '₹1 test payment received (' . strtoupper($mode) . ')', $p . ' · order ' . $o . ' · by ' . $me['email']);
        json_out(['signature' => true] + payment_report($p, $o));

    case 'status':
        json_out(payment_report($id('payment_id'), $id('order_id')));

    case 'refund':
        $p = $id('payment_id');
        [$st, $pay] = rzp_api('GET', '/v1/payments/' . rawurlencode($p));
        if ($st !== 200) fail('Razorpay could not find this payment.', 404);
        // Only ever refund payments made by this ₹1 tool
        [, $ord] = rzp_api('GET', '/v1/orders/' . rawurlencode((string)($pay['order_id'] ?? '')));
        if ((int)($pay['amount'] ?? 0) !== TEST_PAISE || ($ord['notes']['purpose'] ?? '') !== TEST_PURPOSE) fail('This is not a ₹1 test payment, so it was not refunded.', 400);
        if (($pay['status'] ?? '') === 'refunded' || (int)($pay['amount_refunded'] ?? 0) >= TEST_PAISE) json_out(['ok' => true, 'refund_id' => '', 'already' => true]);
        if (($pay['status'] ?? '') !== 'captured') fail('The payment is "' . ($pay['status'] ?? '?') . '", not captured, so it cannot be refunded yet. Razorpay returns uncaptured payments automatically.', 400);
        [$st, $r] = rzp_api('POST', '/v1/payments/' . rawurlencode($p) . '/refund', ['amount' => TEST_PAISE, 'notes' => ['purpose' => TEST_PURPOSE]]);
        if ($st < 200 || $st >= 300 || empty($r['id'])) fail('Refund failed (HTTP ' . $st . '): ' . ($r['error']['description'] ?? 'unknown'), 502);
        log_activity((int)$me['id'], 'rzp_test_refunded', $p . ' ' . $r['id']);
        json_out(['ok' => true, 'refund_id' => $r['id'], 'refund_status' => (string)($r['status'] ?? '')]);

    default:
        fail('Unknown action', 400);
}
