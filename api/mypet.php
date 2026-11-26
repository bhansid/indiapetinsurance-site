<?php
/** Full details of an ID the visitor holds (logged-in owner, or X-Pet-Key). GET ?id=IPI-.. */
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';
require_installed_api();
$p = pet_for_request(str_field($_GET, 'id', 32));
if (!$p) fail('ID not found.', 404);
$d = json_decode($p['data'], true) ?: [];
$d['pubPhone'] = (bool)$p['pub_phone']; $d['pubAddr'] = $p['pub_addr']; $d['pubVet'] = (bool)$p['pub_vet']; $d['pubAllergy'] = (bool)$p['pub_allergy'];
unset($d['kycNo']);
$ph = db()->prepare("SELECT id FROM documents WHERE pet_id = ? AND kind = 'photo' ORDER BY id DESC LIMIT 1");
$ph->execute([$p['id']]);
$photoId = $ph->fetchColumn();
json_out(['ok' => true, 'id_no' => $p['id_no'], 'created_at' => $p['created_at'], 'data' => $d, 'photo' => $photoId ? '/account/file.php?id=' . (int)$photoId : '']);
