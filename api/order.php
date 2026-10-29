<?php
/** Pre-order a printed card / steel tag for an ID the logged-in owner holds. JSON body. */
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';
require_installed_api();
require_ajax_post();
$u = current_user();
if (!$u) fail('Please log in first.', 401, ['code' => 'login_required']);
if (rate_limited('order', 20, 3600)) fail('Too many orders. Please try again later.', 429);

$d = body_json();
$st = db()->prepare('SELECT id, name FROM pets WHERE id_no = ? AND owner_id = ?');
$st->execute([str_field($d, 'id_no', 32), $u['id']]);
$pet = $st->fetch();
if (!$pet) fail('ID not found on your account.', 404);

// Current product: the safety kit (2 QR tags, reflective collar, PVC card, travel bottle), ₹1,000 incl. delivery, pay on delivery
const KIT_PRICE = 1000;
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
db()->prepare('INSERT INTO orders (order_no, pet_id, owner_id, product, ship_name, ship_mobile, ship_address, ship_city, ship_state, ship_pin, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')
  ->execute([$no, $pet['id'], $u['id'], $product, str_field($d, 'name'), $mobile, str_field($d, 'address', 255), str_field($d, 'city', 80), str_field($d, 'state', 80), $pin, 'new', now(), now()]);
$oid = (int)db()->lastInsertId();
notify('order_new', 'New kit order ' . $no . ' for ' . $pet['name'] . ' · ₹' . number_format(KIT_PRICE) . ' COD', 'Collar ' . $size . ' · ' . str_field($d, 'name') . ' · +91 ' . $mobile . ' · ' . str_field($d, 'address', 255) . ', ' . str_field($d, 'city', 80) . ' ' . $pin, 'orders', $oid);
log_activity((int)$u['id'], 'order_new', $no);
json_out(['ok' => true, 'order_no' => $no, 'amount' => KIT_PRICE]);
