<?php
/**
 * POST /api/verify-payment
 * Body: {razorpay_order_id, razorpay_payment_id, razorpay_signature} from Razorpay Checkout.
 * Marks the order paid ONLY if HMAC-SHA256(order_id + "|" + payment_id, key_secret) matches the signature.
 */
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/razorpay.php';
require_installed_api();
require_ajax_post();
if (rate_limited('rzp_verify', 60, 3600)) fail('Too many requests. Please try again later.', 429);

$d = body_json();
$orderId = str_field($d, 'razorpay_order_id', 60);
$paymentId = str_field($d, 'razorpay_payment_id', 60);
$signature = str_field($d, 'razorpay_signature', 200);
if ($orderId === '' || $paymentId === '' || $signature === '') fail('Missing payment details.', 400, ['code' => 'missing_fields']);
// Use the keys of the mode (test/live) the Razorpay order was created with
$st = db()->prepare('SELECT rzp_mode FROM orders WHERE rzp_order_id = ?');
$st->execute([$orderId]);
$mode = $st->fetchColumn();
if ($mode === false) fail('Order not found for this payment.', 404);
$mode = $mode ?: null;
if (!rzp_configured($mode)) fail('Online payment is not available right now.', 500, ['code' => 'payments_not_configured']);

if (!rzp_payment_signature_ok($orderId, $paymentId, $signature, $mode)) {
    error_log('razorpay verify: signature mismatch for ' . $orderId . ' / ' . $paymentId);
    log_activity(current_user()['id'] ?? null, 'payment_signature_mismatch', $orderId . ' ' . $paymentId);
    fail('We could not verify this payment. If money was deducted, it will be refunded automatically or contact support.', 400, ['code' => 'signature_mismatch']);
}

$o = mark_order_paid($orderId, $paymentId, 'checkout');
if (!$o) fail('Order not found for this payment.', 404);
json_out(['ok' => true, 'order_no' => $o['order_no'], 'payment_id' => $paymentId, 'amount' => (int)$o['amount_paise'] / 100]);
