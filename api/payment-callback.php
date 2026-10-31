<?php
/**
 * POST /api/payment-callback
 * Redirect-mode Razorpay Checkout (used inside Instagram/Facebook in-app browsers, where the pop-up
 * does not work). Razorpay posts the result here as a normal form, then we send the buyer back to their account.
 * Success: razorpay_payment_id, razorpay_order_id, razorpay_signature
 * Failure: error[code], error[description], error[metadata] (JSON with order_id, payment_id)
 */
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/razorpay.php';
header('X-Robots-Tag: noindex');
if (!installed()) { http_response_code(503); exit('Not ready'); }

function back(array $q): void { header('Location: /account/?' . http_build_query($q), true, 303); exit; }

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') back(['pay' => 'failed', 'msg' => 'Payment was not completed.']);
if (rate_limited('rzp_callback', 60, 3600)) back(['pay' => 'failed', 'msg' => 'Too many attempts. Please try again later.']);

$orderId = substr(trim((string)($_POST['razorpay_order_id'] ?? '')), 0, 60);
$paymentId = substr(trim((string)($_POST['razorpay_payment_id'] ?? '')), 0, 60);
$signature = substr(trim((string)($_POST['razorpay_signature'] ?? '')), 0, 200);

// Failure posted by Razorpay
if ($signature === '' || $paymentId === '' || $orderId === '') {
    $err = $_POST['error'] ?? [];
    $desc = is_array($err) ? (string)($err['description'] ?? '') : '';
    $meta = is_array($err) ? json_decode((string)($err['metadata'] ?? ''), true) : null;
    $no = '';
    if (is_array($meta) && !empty($meta['order_id'])) {
        $st = db()->prepare('SELECT order_no FROM orders WHERE rzp_order_id = ?');
        $st->execute([(string)$meta['order_id']]);
        $no = (string)($st->fetchColumn() ?: '');
    }
    back(['pay' => 'failed', 'order' => $no, 'msg' => mb_substr($desc ?: 'Payment was not completed.', 0, 160)]);
}

$st = db()->prepare('SELECT order_no, rzp_mode FROM orders WHERE rzp_order_id = ?');
$st->execute([$orderId]);
$row = $st->fetch();
if (!$row) back(['pay' => 'failed', 'msg' => 'Order not found for this payment. Contact support if money was deducted.']);

if (!rzp_payment_signature_ok($orderId, $paymentId, $signature, $row['rzp_mode'] ?: null)) {
    error_log('razorpay callback: signature mismatch for ' . $orderId . ' / ' . $paymentId);
    log_activity(null, 'payment_signature_mismatch', $orderId . ' ' . $paymentId . ' (callback)');
    back(['pay' => 'failed', 'order' => $row['order_no'], 'msg' => 'We could not verify this payment. Contact support if money was deducted.']);
}

$o = mark_order_paid($orderId, $paymentId, 'redirect');
back(['pay' => 'success', 'order' => $o['order_no'] ?? $row['order_no'], 'pid' => $paymentId]);
