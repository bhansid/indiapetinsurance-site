<?php
/**
 * POST /api/razorpay-webhook  (optional, recommended for live)
 * Backup confirmation from Razorpay's servers, for buyers who close the page right after paying.
 * Set it up in Razorpay Dashboard → Webhooks with events "payment.captured" and "order.paid",
 * and put the webhook secret in RAZORPAY_WEBHOOK_SECRET. Does nothing until that secret is set.
 */
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/razorpay.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('Method not allowed', 405);
if (!installed()) fail('Not ready', 503);

$raw = (string)file_get_contents('php://input');
if (!rzp_webhook_signature_ok($raw, (string)($_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? ''))) fail('Invalid signature', 400);

$evt = json_decode($raw, true) ?: [];
$pay = $evt['payload']['payment']['entity'] ?? [];
if (in_array($evt['event'] ?? '', ['payment.captured', 'order.paid'], true) && !empty($pay['order_id']) && !empty($pay['id'])) {
    mark_order_paid((string)$pay['order_id'], (string)$pay['id'], 'webhook');
}
json_out(['ok' => true]);
