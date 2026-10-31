<?php
/**
 * Safety kit order for an ID the logged-in owner holds. JSON body.
 *   New order:      {id_no, product:'kit', size, name, mobile, address, city, state, pin, payment:'online'|'cod'}
 *   Switch to COD:  {order_no, payment:'cod'}   (for an online order that was never paid)
 * Online orders start as 'pending_payment' and become 'new' when Razorpay confirms payment.
 */
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';
require_installed_api();
require_ajax_post();
$u = current_user();
if (!$u) fail('Please log in first.', 401, ['code' => 'login_required']);
if (rate_limited('order', 20, 3600)) fail('Too many orders. Please try again later.', 429);

// Current product: the safety kit (2 QR tags, reflective collar, PVC card, one-press water bottle), ₹1,000 incl. delivery
const KIT_PRICE = 1000;
$d = body_json();
$payment = ($d['payment'] ?? 'cod') === 'online' ? 'online' : 'cod';

/* ---- Switch an unpaid online order to cash on delivery ---- */
if (!empty($d['order_no'])) {
    $st = db()->prepare("SELECT o.*, p.name AS pet FROM orders o JOIN pets p ON p.id = o.pet_id WHERE o.order_no = ? AND o.owner_id = ?");
    $st->execute([str_field($d, 'order_no', 20), $u['id']]);
    $o = $st->fetch();
    if (!$o) fail('Order not found.', 404);
    if ($o['payment_status'] === 'paid') json_out(['ok' => true, 'order_no' => $o['order_no'], 'payment' => 'paid']);
    if ($payment !== 'cod') fail('Unsupported change.', 400);
    db()->prepare("UPDATE orders SET payment_method = 'cod', payment_status = 'cod', status = CASE WHEN status = 'pending_payment' THEN 'new' ELSE status END, updated_at = ? WHERE id = ?")->execute([now(), $o['id']]);
    notify('order_new', 'New kit order ' . $o['order_no'] . ' for ' . $o['pet'] . ' · ₹' . number_format(KIT_PRICE) . ' COD', 'Switched from online payment · Collar ' . substr((string)$o['product'], 4) . ' · ' . $o['ship_name'] . ' · +91 ' . $o['ship_mobile'] . ' · ' . $o['ship_address'] . ', ' . $o['ship_city'] . ' ' . $o['ship_pin'], 'orders', (int)$o['id']);
    log_activity((int)$u['id'], 'order_cod', $o['order_no']);
    json_out(['ok' => true, 'order_no' => $o['order_no'], 'payment' => 'cod', 'amount' => KIT_PRICE]);
}

$st = db()->prepare('SELECT id, name FROM pets WHERE id_no = ? AND owner_id = ?');
$st->execute([str_field($d, 'id_no', 32), $u['id']]);
$pet = $st->fetch();
if (!$pet) fail('ID not found on your account.', 404);

$size = in_array($d['size'] ?? '', ['S', 'M', 'L', 'XL'], true) ? $d['size'] : '';
$product = ($d['product'] ?? '') === 'kit' && $size ? 'kit:' . $size : '';
$mobile = clean_mobile($d['mobile'] ?? '');
$pin = preg_replace('/\D/', '', (string)($d['pin'] ?? ''));
$fields = [];
if (!$product) $fields['size'] = 'Choose a collar size.';
if (str_field($d, 'name') === '') $fields['name'] = 'Add a name.';
if (!valid_mobile($mobile)) $fields['mobile'] = 'Enter a valid 10-digit mobile.';
if (str_field($d, 'address', 255) === '') $fields['address'] = 'Add a delivery address.';
if (!preg_match('/^\d{6}$/', $pin)) $fields['pin'] = 'PIN codes are 6 digits.';
if ($fields) fail('Please fix the highlighted fields.', 422, ['fields' => $fields]);

$no = unique_code('orders', 'order_no', fn() => 'ORD-' . date('ymd') . '-' . random_int(1000, 9999));
$status = $payment === 'online' ? 'pending_payment' : 'new';
$payStatus = $payment === 'online' ? 'pending' : 'cod';
db()->prepare('INSERT INTO orders (order_no, pet_id, owner_id, product, ship_name, ship_mobile, ship_address, ship_city, ship_state, ship_pin, status, payment_method, payment_status, amount_paise, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
  ->execute([$no, $pet['id'], $u['id'], $product, str_field($d, 'name'), $mobile, str_field($d, 'address', 255), str_field($d, 'city', 80), str_field($d, 'state', 80), $pin, $status, $payment, $payStatus, KIT_PRICE * 100, now(), now()]);
$oid = (int)db()->lastInsertId();
// Online orders are announced to the admin panel once they are paid (see lib/razorpay.php)
if ($payment === 'cod') {
    notify('order_new', 'New kit order ' . $no . ' for ' . $pet['name'] . ' · ₹' . number_format(KIT_PRICE) . ' COD', 'Collar ' . $size . ' · ' . str_field($d, 'name') . ' · +91 ' . $mobile . ' · ' . str_field($d, 'address', 255) . ', ' . str_field($d, 'city', 80) . ' ' . $pin, 'orders', $oid);
}
log_activity((int)$u['id'], 'order_new', $no . ' ' . $payment);
json_out(['ok' => true, 'order_no' => $no, 'amount' => KIT_PRICE, 'payment' => $payment]);
