<?php
/**
 * Create a dog/cat ID. multipart/form-data:
 *   data      JSON with all wizard fields
 *   password  owner password (only when not logged in)
 *   photo     front photo (optional)
 *   docs[]    vaccination cards / reports (optional)
 *   kyc[]     owner ID proof (optional)
 */
declare(strict_types=1);
require __DIR__ . '/lib/bootstrap.php';
require_installed_api();
require_ajax_post();
start_session();

if (rate_limited('pet_create', 15, 3600)) fail('Too many IDs created from this connection. Please try again in an hour.', 429);

$d = json_decode((string)($_POST['data'] ?? ''), true);
if (!is_array($d)) fail('Missing form data.');

$errors = [];
$species = in_array($d['species'] ?? '', ['dog', 'cat'], true) ? $d['species'] : '';
$name = str_field($d, 'name', 40);
$breed = str_field($d, 'breed', 80);
$gender = yn($d, 'gender', ['m', 'f']);
$oName = str_field($d, 'oName', 120);
$oMobile = clean_mobile($d['oMobile'] ?? '');
$oEmail = strtolower(str_field($d, 'oEmail', 190));
$oPin = preg_replace('/\D/', '', (string)($d['oPin'] ?? ''));
$state = str_field($d, 'oState', 80);
if (!$species) $errors['species'] = 'Choose dog or cat.';
if ($name === '') $errors['name'] = 'Add a name.';
if ($breed === '') $errors['breed'] = 'Choose a breed.';
if (!$gender) $errors['gender'] = 'Choose male or female.';
if ($oName === '') $errors['oName'] = 'Add your name.';
if (!valid_mobile($oMobile)) $errors['oMobile'] = 'Enter a valid 10-digit mobile.';
if ($oEmail !== '' && !filter_var($oEmail, FILTER_VALIDATE_EMAIL)) $errors['oEmail'] = 'Check the email.';
if (!preg_match('/^\d{6}$/', $oPin)) $errors['oPin'] = 'PIN codes are 6 digits.';
if (empty($d['cTerms'])) $errors['cTerms'] = 'Please accept the terms.';
foreach (['doctorPh', 'cOwnerPh', 'clinicPh', 'eMobile'] as $k) { $v = clean_mobile($d[$k] ?? ''); if ($v !== '' && !valid_mobile($v)) $errors[$k] = 'Enter a valid 10-digit mobile.'; }
if ($errors) fail('Please fix the highlighted fields.', 422, ['fields' => $errors]);

$pdo = db();
$user = current_user();
$newAccount = false;
if (!$user) {
    $existing = find_user_by_login($oMobile);
    if (!$existing && $oEmail) $existing = find_user_by_login($oEmail);
    if ($existing) fail('This mobile or email already has an account. Log in to add this ID to it.', 409, ['code' => 'account_exists']);
    $pw = (string)($_POST['password'] ?? '');
    if (strlen($pw) < 8) fail('Create a password of at least 8 characters.', 422, ['fields' => ['password' => 'At least 8 characters.']]);
    $pdo->prepare('INSERT INTO users (role, name, email, mobile, password_hash, status, mobile_verified, created_at) VALUES (?,?,?,?,?,?,?,?)')
        ->execute(['owner', $oName, $oEmail ?: null, $oMobile, password_hash($pw, PASSWORD_DEFAULT), 'active', 0, now()]);
    $user = ['id' => (int)$pdo->lastInsertId(), 'name' => $oName, 'role' => 'owner'];
    $newAccount = true;
}

// Keep a clean copy of every wizard field. The full ID-proof number is never stored, only its last 4 digits.
$allowed = ['species','name','breed','gender','neutered','dobMode','dob','ageY','ageM','weight','colour','marks','lic','licNo','licAuth','kci','kciNo','chip','chipNo',
  'records','rabies','core','deworm','conditions','meds','clinicNone','clinic','clinicArea','doctor','doctorPh','cOwner','cOwnerPh','clinicPh',
  'oName','oMobile','oEmail','oHouse','oLocality','oCity','oState','oPin','eName','eMobile','kycType','pubPhone','pubAddr','pubVet','pubAllergy','cTerms','cWa','cMkt','src','entry'];
$clean = [];
foreach ($allowed as $k) { if (array_key_exists($k, $d)) $clean[$k] = is_bool($d[$k]) ? $d[$k] : mb_substr(trim((string)$d[$k]), 0, 200); }
$clean['oMobile'] = $oMobile;
$clean['oEmail'] = $oEmail;
$last4 = substr(preg_replace('/[^0-9A-Za-z]/', '', (string)($d['kycNo'] ?? '')), -4);
$clean['kycLast4'] = $last4 ?: '';
$docCount = 0;

try {
    $pdo->beginTransaction();
    $idNo = new_pet_id($state);
    $pdo->prepare('INSERT INTO pets (id_no, owner_id, species, name, breed, gender, city, state, clinic, doctor_mobile, pub_phone, pub_addr, pub_vet, pub_allergy, status, data, created_at, updated_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([
        $idNo, $user['id'], $species, $name, $breed, $gender, str_field($d, 'oCity', 80), $state, str_field($d, 'clinic', 160), clean_mobile($d['doctorPh'] ?? '') ?: null,
        !empty($d['pubPhone']) ? 1 : 0, ($d['pubAddr'] ?? '') === 'exact' ? 'exact' : 'locality', !empty($d['pubVet']) ? 1 : 0, !empty($d['pubAllergy']) ? 1 : 0,
        'active', json_encode($clean, JSON_UNESCAPED_UNICODE), now(), now()]);
    $petId = (int)$pdo->lastInsertId();
    foreach (files_list('photo') as $f) { if (save_upload($f, $petId, 'photo', 'Profile photo')) $docCount++; break; }
    foreach (array_slice(files_list('docs'), 0, 15) as $f) { if (save_upload($f, $petId, 'record', 'Health record')) $docCount++; }
    foreach (array_slice(files_list('kyc'), 0, 4) as $f) { if (save_upload($f, $petId, 'kyc', (string)($d['kycType'] ?? 'ID proof'))) $docCount++; }
    $pdo->commit();
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($newAccount) { $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$user['id']]); }
    error_log('pet create: ' . $ex->getMessage());
    fail($ex instanceof RuntimeException ? $ex->getMessage() : 'Something went wrong while saving. Please try again.', 500);
}

if (!empty($d['cMkt'])) { $pdo->prepare('UPDATE users SET marketing = 1 WHERE id = ?')->execute([$user['id']]); }
if ($newAccount) { login_user(['id' => $user['id']]); notify('owner_signup', 'New owner: ' . $oName, '+91 ' . $oMobile . ($oEmail ? ' · ' . $oEmail : ''), 'users', $user['id']); }
notify('pet_created', (($d['entry'] ?? '') === 'insurance' ? 'New ID via insurance quote: ' : 'New ID: ') . $name . ' (' . $breed . ')', $idNo . ' · ' . $oName . ' · +91 ' . $oMobile . ' · ' . str_field($d, 'oCity', 80) . ($docCount ? " · $docCount file(s)" : '') . (!empty($d['src']) ? ' · ' . str_field($d, 'src', 160) : ''), 'pets', $petId);
log_activity($user['id'], 'pet_created', $idNo);

json_out(['ok' => true, 'id_no' => $idNo, 'profile_url' => site_url('p/' . $idNo), 'files' => $docCount, 'new_account' => $newAccount]);
