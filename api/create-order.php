<?php
/**
 * POST /api/create-order
 * Creates a Razorpay order for one of the logged-in owner's unpaid online orders.
 * Body: {order_no}. The amount always comes from our database, never from the browser.
 * Returns: {ok, order_id, amount, currency, key_id, ...checkout options}
 */
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/razorpay.php';
require_installed_api();
require_ajax_post();
$u = current_user();
if (!$u) fail('Please log in first.', 401, ['code' => 'login_required']);
if (rate_limited('rzp_create', 30, 3600)) fail('Too many payment attempts. Please try again later.', 429);
if (!rzp_configured()) { error_log('razorpay: keys missing'); fail('Online payment is not available right now. Please choose cash on delivery.', 500, ['code' => 'payments_not_configured']); }

$d = body_json();
$orderNo = str_field($d, 'order_no', 20);
if ($orderNo === '') fail('Missing order number.', 400);
$st = db()->prepare('SELECT o.*, p.name AS pet, p.id_no FROM orders o JOIN pets p ON p.id = o.pet_id WHERE o.order_no = ? AND o.owner_id = ?');
$st->execute([$orderNo, $u['id']]);
$o = $st->fetch();
if (!$o) fail('Order not found.', 404);
if ($o['payment_status'] === 'paid') fail('This order is already paid.', 409, ['code' => 'already_paid', 'order_no' => $o['order_no']]);
if ($o['payment_method'] !== 'online') fail('This order is set to cash on delivery.', 400);

$amount = (int)$o['amount_paise'];
if ($amount < RZP_MIN_PAISE) fail('Amount must be at least ₹1.', 400);
$currency = 'INR';

// Reuse the Razorpay order if one already exists for this order (retry after a cancel or failure)
$mode = rzp_mode();
$rzpOrderId = (string)($o['rzp_order_id'] ?? '');
if ($rzpOrderId !== '' && ($o['rzp_mode'] ?? '') !== $mode) $rzpOrderId = '';   // keys switched test↔live since the last attempt
if ($rzpOrderId === '') {
    [$status, $res] = rzp_api('POST', '/v1/orders', [
        'amount' => $amount,
        'currency' => $currency,
        'receipt' => $o['order_no'],
        'notes' => ['order_no' => $o['order_no'], 'id_no' => $o['id_no'], 'pet' => $o['pet'], 'item' => 'Safety kit ' . substr((string)$o['product'], 4)],
    ]);
    if ($status === 401) { error_log('razorpay create order: authentication failed'); fail('Payment gateway authentication failed. Please choose cash on delivery or try later.', 401, ['code' => 'gateway_auth']); }
    if ($status < 200 || $status >= 300 || empty($res['id'])) {
        error_log('razorpay create order failed: HTTP ' . $status . ' ' . json_encode($res['error'] ?? $res));
        fail('Could not start the payment. Please try again, or choose cash on delivery.', 500, ['code' => 'gateway_error']);
    }
    $rzpOrderId = (string)$res['id'];
    db()->prepare('UPDATE orders SET rzp_order_id = ?, rzp_mode = ?, updated_at = ? WHERE id = ?')->execute([$rzpOrderId, $mode, now(), $o['id']]);
}

json_out([
    'ok' => true,
    'order_id' => $rzpOrderId,
    'amount' => $amount,
    'currency' => $currency,
    'key_id' => rzp_key_id(),          // public key only; the secret never leaves the server
    'test_mode' => rzp_test_mode(),
    'order_no' => $o['order_no'],
    'name' => 'IndiaPetInsurance.com',
    'description' => 'Safety kit for ' . $o['pet'] . ' (collar ' . substr((string)$o['product'], 4) . ')',
    'prefill' => ['name' => $o['ship_name'], 'contact' => '+91' . $o['ship_mobile'], 'email' => (string)($u['email'] ?? '')],
]);
