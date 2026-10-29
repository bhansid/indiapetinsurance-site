<?php
/**
 * One-time setup. Open https://yourdomain/install.php, enter the MySQL details from
 * hPanel → Databases and create the superadmin. Once setup is done this page locks itself.
 */
declare(strict_types=1);
require __DIR__ . '/api/lib/bootstrap.php';
require __DIR__ . '/api/lib/schema.php';
header('X-Robots-Tag: noindex');

$done = installed();
$err = '';
$ok = '';
$manualConfig = '';

if (!$done && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $in = fn($k) => trim((string)($_POST[$k] ?? ''));
    $host = $in('db_host') ?: 'localhost';
    $name = $in('db_name'); $user = $in('db_user'); $pass = (string)($_POST['db_pass'] ?? '');
    $appUrl = rtrim($in('app_url'), '/');
    $aName = $in('a_name'); $aEmail = strtolower($in('a_email')); $aMobile = clean_mobile($in('a_mobile')); $aPass = (string)($_POST['a_pass'] ?? '');
    try {
        if (!$name || !$user) throw new RuntimeException('Enter the database name and user.');
        if (!$aName || !filter_var($aEmail, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter your name and a valid email.');
        if ($aMobile && !valid_mobile($aMobile)) throw new RuntimeException('Mobile must be a 10-digit Indian number.');
        if (strlen($aPass) < 10) throw new RuntimeException('Use a superadmin password of at least 10 characters.');
        $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        run_schema($pdo, 'mysql');
        $cnt = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'superadmin'")->fetchColumn();
        if ($cnt === 0) {
            $pdo->prepare('INSERT INTO users (role, name, email, mobile, password_hash, status, mobile_verified, created_at) VALUES (?,?,?,?,?,?,?,?)')
                ->execute(['superadmin', $aName, $aEmail, $aMobile ?: null, password_hash($aPass, PASSWORD_DEFAULT), 'active', 0, now()]);
        }
        $uploads = IPI_HOME . '/ipi-uploads';
        if (!is_dir($uploads) && !@mkdir($uploads, 0750, true)) { $uploads = IPI_PUBLIC . '/uploads'; }
        $config = "<?php\n// IndiaPetInsurance.com config. Keep this file private.\nreturn " . var_export([
            'installed' => true, 'db_driver' => 'mysql', 'db_host' => $host, 'db_name' => $name, 'db_user' => $user, 'db_pass' => $pass,
            'app_url' => $appUrl, 'upload_dir' => $uploads, 'installed_at' => date('c'),
        ], true) . ";\n";
        $target = IPI_HOME . '/ipi-config.php';
        if (@file_put_contents($target, $config, LOCK_EX) === false) {
            $target = IPI_PUBLIC . '/api/config.php';
            if (@file_put_contents($target, $config, LOCK_EX) === false) { $manualConfig = $config; }
        }
        if ($manualConfig === '') { @chmod($target, 0640); $ok = 'done'; $done = true; }
        else { $err = 'Database is ready, but the config file could not be written. Create the file shown below.'; }
    } catch (Throwable $ex) {
        $err = $ex instanceof PDOException ? 'Could not connect to the database. Check the name, user and password from hPanel → Databases. (' . $ex->getMessage() . ')' : $ex->getMessage();
    }
}
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">
<title>Set up · IndiaPetInsurance.com</title>
<link rel="stylesheet" href="/admin/admin.css">
</head><body class="auth">
<main class="auth-card" style="max-width:620px">
  <div class="brand"><span class="mk"><svg width="20" height="20" viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><ellipse cx="12" cy="16" rx="5" ry="4.2"/><circle cx="5.5" cy="10" r="2.2"/><circle cx="9.5" cy="6" r="2.2"/><circle cx="14.5" cy="6" r="2.2"/><circle cx="18.5" cy="10" r="2.2"/></svg></span> IndiaPetInsurance<small>.com</small></div>
  <?php if ($done): ?>
    <h1>Setup complete</h1>
    <p class="muted"><?= $ok ? 'Your database and superadmin account are ready.' : 'This site is already set up. This page is locked.' ?></p>
    <a class="btn" href="/admin/">Go to the admin panel</a>
  <?php else: ?>
    <h1>Set up the backend</h1>
    <p class="muted">First, create a MySQL database in Hostinger hPanel → <b>Databases → Management</b>. Then enter its details here.</p>
    <?php if ($err): ?><div class="alert err"><?= e($err) ?></div><?php endif; ?>
    <?php if ($manualConfig): ?><p>Create <code>ipi-config.php</code> one folder above public_html with this content:</p><pre class="code"><?= e($manualConfig) ?></pre><?php endif; ?>
    <form method="post" class="form" autocomplete="off">
      <h3>Database</h3>
      <div class="g2"><label>Host<input name="db_host" value="<?= e($_POST['db_host'] ?? 'localhost') ?>"></label><label>Database name<input name="db_name" required value="<?= e($_POST['db_name'] ?? '') ?>" placeholder="u123456789_ipi"></label></div>
      <div class="g2"><label>Database user<input name="db_user" required value="<?= e($_POST['db_user'] ?? '') ?>"></label><label>Database password<input type="password" name="db_pass"></label></div>
      <label>Website address<input name="app_url" value="<?= e($_POST['app_url'] ?? site_url()) ?>"></label>
      <h3>Superadmin (you)</h3>
      <div class="g2"><label>Your name<input name="a_name" required value="<?= e($_POST['a_name'] ?? '') ?>"></label><label>Email (login)<input type="email" name="a_email" required value="<?= e($_POST['a_email'] ?? '') ?>"></label></div>
      <div class="g2"><label>Mobile (optional)<input name="a_mobile" inputmode="numeric" maxlength="10" value="<?= e($_POST['a_mobile'] ?? '') ?>"></label><label>Password (10+ characters)<input type="password" name="a_pass" required minlength="10"></label></div>
      <button class="btn" type="submit">Create database tables and superadmin</button>
    </form>
  <?php endif; ?>
</main></body></html>
