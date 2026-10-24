<?php
/** ADMIN LOGIN (only users with role = admin). */
require_once __DIR__ . '/../../app/config.php';

if (is_logged_in() && is_admin()) redirect('admin/index.php');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = input_str($_POST, 'email');
    $pass  = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    if (login_blocked($email)) {
        $error = 'Terlalu banyak percobaan login. Coba lagi dalam 15 menit.';
    } else {
        $user = attempt_login($email, $pass);
        if ($user && $user['role'] === 'admin') {
            login_user($user);
            redirect('admin/index.php');
        }
        login_failed($email);
        $error = 'Email atau password admin salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id"><head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
  <?php include APP_VIEWS . '/theme_init.php'; ?>
  <title>Admin Login | BeeSportFit</title>
  <link rel="icon" type="image/png" href="<?= asset('img/favicon.png') ?>">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Oswald:wght@600;800&display=swap" rel="stylesheet">
  <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
</head><body>
<div class="login-wrap"><div class="panel" style="width:100%;max-width:400px">
  <img class="logo-emblem" src="<?= asset('img/logo-emblem.png') ?>" alt="<?= e(SITE_NAME) ?>" style="height:76px;width:auto;margin-bottom:.5rem">
  <div class="text-muted" style="font-family:var(--font-heading);letter-spacing:.2em;font-size:.8rem;margin-bottom:1.5rem">ADMIN PANEL</div>
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <div class="form-group"><label>Email</label><input type="email" name="email" required autofocus></div>
    <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
    <button class="btn btn-primary btn-block" type="submit">MASUK</button>
  </form>
</div></div>
</body></html>
