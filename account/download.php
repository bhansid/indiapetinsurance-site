<?php
/** Owner portal: download everything we hold about you as a ZIP (data.json + all your uploaded files). */
declare(strict_types=1);
require dirname(__DIR__) . '/api/lib/bootstrap.php';
header('X-Robots-Tag: noindex');
if (!installed()) { http_response_code(404); exit; }
$u = current_user();
if (!$u) { header('Location: /account/'); exit; }
$q = function (string $sql, array $a) { $st = db()->prepare($sql); $st->execute($a); return $st->fetchAll(); };
$pets = $q('SELECT * FROM pets WHERE owner_id = ? ORDER BY id', [$u['id']]);
$out = [
    'exported_at' => date('c'),
    'account' => ['name' => $u['name'], 'mobile' => $u['mobile'], 'email' => $u['email'], 'created_at' => $u['created_at'], 'marketing_opt_in' => (bool)($u['marketing'] ?? 0)],
    'ids' => [], 'orders' => [], 'insurance_quotes' => [],
];
$files = [];
foreach ($pets as $p) {
    $d = json_decode($p['data'], true) ?: [];
    unset($d['kycNo']);
    $docs = $q('SELECT * FROM documents WHERE pet_id = ? ORDER BY id', [$p['id']]);
    $docList = [];
    foreach ($docs as $doc) {
        $name = $p['id_no'] . '/' . $doc['kind'] . '-' . $doc['id'] . '-' . preg_replace('/[^\w.\-]+/', '_', (string)$doc['original_name']);
        $docList[] = ['type' => $doc['kind'], 'label' => $doc['label'], 'file' => $name, 'uploaded_at' => $doc['created_at']];
        $files[$name] = upload_dir() . '/' . $doc['path'];
    }
    $out['ids'][] = ['id_number' => $p['id_no'], 'name' => $p['name'], 'species' => $p['species'], 'breed' => $p['breed'], 'qr_profile' => site_url('p/' . $p['id_no']), 'qr_scans' => (int)$p['scan_count'], 'created_at' => $p['created_at'], 'details' => $d, 'files' => $docList];
}
foreach ($q('SELECT o.*, p.id_no FROM orders o JOIN pets p ON p.id = o.pet_id WHERE o.owner_id = ? ORDER BY o.id', [$u['id']]) as $o) {
    $out['orders'][] = ['order_number' => $o['order_no'], 'id_number' => $o['id_no'], 'product' => $o['product'], 'status' => $o['status'], 'deliver_to' => [$o['ship_name'], $o['ship_mobile'], $o['ship_address'], $o['ship_city'], $o['ship_state'], $o['ship_pin']], 'created_at' => $o['created_at']];
}
foreach ($q('SELECT x.*, p.id_no FROM quotes x JOIN pets p ON p.id = x.pet_id WHERE x.owner_id = ? ORDER BY x.id', [$u['id']]) as $x) {
    $out['insurance_quotes'][] = ['request_number' => $x['quote_no'], 'id_number' => $x['id_no'], 'plan' => $x['plan'], 'cover' => (int)$x['sum_insured'], 'status' => $x['status'], 'created_at' => $x['created_at']];
}
log_activity((int)$u['id'], 'data_export');
$json = json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$base = 'indiapetinsurance-my-data-' . date('Y-m-d');
if (class_exists('ZipArchive')) {
    $tmp = tempnam(sys_get_temp_dir(), 'ipi');
    $zip = new ZipArchive();
    $zip->open($tmp, ZipArchive::OVERWRITE);
    $zip->addFromString('my-data.json', $json);
    $zip->addFromString('README.txt', "Your data from IndiaPetInsurance.com, exported " . date('j M Y, g:i a') . ".\nmy-data.json has every detail of your IDs, orders and insurance quote requests.\nEach ID's photos and documents are in the folder named after its ID number.\n");
    foreach ($files as $name => $path) { if (is_file($path)) $zip->addFile($path, $name); }
    $zip->close();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $base . '.zip"');
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp);
    @unlink($tmp);
    exit;
}
header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $base . '.json"');
echo $json;
