<?php
/** CUSTOMER LOGIN. */
require_once __DIR__ . '/../app/config.php';

if (is_logged_in()) redirect(is_admin() ? 'admin/index.php' : 'account/orders.php');
$next = input_str($_GET, 'next') ?: input_str($_POST, 'next');
$noindex = true;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = input_str($_POST, 'email');
    $pass  = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    if (login_blocked($email)) {
        $error = 'Terlalu banyak percobaan login. Coba lagi dalam 15 menit, atau gunakan "Lupa password".';
    } elseif ($user = attempt_login($email, $pass)) {
        login_user($user);
        redirect($user['role'] === 'admin' ? 'admin/index.php' : safe_next($next, 'account/orders.php'));
    } else {
        login_failed($email);
        $error = 'Email atau password salah.';
    }
}

$page_title = 'Login | ' . SITE_NAME;
include APP_VIEWS . '/header.php';
?>
<div class="container"><div class="auth-box">
  <div class="panel">
    <h1 style="font-size:1.8rem">LOGIN</h1>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="next" value="<?= e($next) ?>">
      <div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" required value="<?= old('email') ?>" autofocus></div>
      <div class="form-group"><label for="password">Password</label><input type="password" id="password" name="password" required></div>
      <button class="btn btn-primary btn-block" type="submit">MASUK</button>
    </form>
    <p class="text-muted mt-2 mb-0" style="font-size:.9rem"><a class="text-primary" href="<?= url('forgot-password.php') ?>">Lupa password?</a></p>
    <p class="text-muted mt-1 mb-0" style="font-size:.9rem">Belum punya akun? <a class="text-primary" href="<?= url('register.php') ?>">Daftar di sini</a></p>
  </div>
</div></div>
<?php include APP_VIEWS . '/footer.php'; ?>
