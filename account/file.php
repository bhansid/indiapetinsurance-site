<?php
/** Owner portal: open or download one of your own uploaded files. */
declare(strict_types=1);
require dirname(__DIR__) . '/api/lib/bootstrap.php';
header('X-Robots-Tag: noindex');
if (!installed()) { http_response_code(404); exit; }
$u = current_user();
if (!$u) { header('Location: /account/'); exit; }
$st = db()->prepare('SELECT d.* FROM documents d JOIN pets p ON p.id = d.pet_id WHERE d.id = ? AND p.owner_id = ?');
$st->execute([(int)($_GET['id'] ?? 0), $u['id']]);
$doc = $st->fetch();
if (!$doc) { http_response_code(404); exit('Not found'); }
send_file($doc['path'], $doc['mime'], $doc['original_name'] ?: 'file', !isset($_GET['dl']));
