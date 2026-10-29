<?php
/** Owner portal: full details of one of the logged-in owner's IDs. GET ?id=IPI-.. */
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';
require_installed_api();
$u = current_user();
if (!$u) fail('Please log in first.', 401, ['code' => 'login_required']);
$st = db()->prepare('SELECT * FROM pets WHERE id_no = ? AND owner_id = ?');
$st->execute([str_field($_GET, 'id', 32), $u['id']]);
$p = $st->fetch();
if (!$p) fail('ID not found on your account.', 404);
$d = json_decode($p['data'], true) ?: [];
$d['pubPhone'] = (bool)$p['pub_phone']; $d['pubAddr'] = $p['pub_addr']; $d['pubVet'] = (bool)$p['pub_vet']; $d['pubAllergy'] = (bool)$p['pub_allergy'];
unset($d['kycNo']);
$ph = db()->prepare("SELECT id FROM documents WHERE pet_id = ? AND kind = 'photo' ORDER BY id DESC LIMIT 1");
$ph->execute([$p['id']]);
$photoId = $ph->fetchColumn();
json_out(['ok' => true, 'id_no' => $p['id_no'], 'created_at' => $p['created_at'], 'data' => $d, 'photo' => $photoId ? '/account/file.php?id=' . (int)$photoId : '']);
