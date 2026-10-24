<?php
/** RESET PASSWORD: opened from the e-mailed link (reset-password.php?token=...). */
require_once __DIR__ . '/../app/config.php';

$noindex = true;
$token = input_str($_GET, 'token') ?: input_str($_POST, 'token');
$target = find_reset_user($token);
$error = '';

if ($target && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $pw  = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $pw2 = is_string($_POST['password2'] ?? null) ? $_POST['password2'] : '';
    $error = password_problem($pw, $pw2) ?? '';
    if ($error === '') {
        set_user_password((int)$target['id'], $pw);
        flash_set('success', 'Password berhasil diubah. Silakan login dengan password baru.');
        redirect('login.php');
    }
}

$page_title = 'Reset Password | ' . SITE_NAME;
include APP_VIEWS . '/header.php';
?>
<div class="container"><div class="auth-box">
  <div class="panel">
    <h1 style="font-size:1.8rem">PASSWORD BARU</h1>
    <?php if (!$target): ?>
      <div class="alert alert-error">Tautan reset tidak valid atau sudah kedaluwarsa.</div>
      <a class="btn btn-primary btn-block" href="<?= url('forgot-password.php') ?>">MINTA TAUTAN BARU</a>
    <?php else: ?>
      <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
      <p class="text-muted" style="font-size:.9rem">Akun: <?= e($target['email']) ?></p>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="form-row">
          <div class="form-group"><label>Password baru</label><input type="password" name="password" required minlength="8" maxlength="72" autofocus></div>
          <div class="form-group"><label>Ulangi password</label><input type="password" name="password2" required minlength="8" maxlength="72"></div>
        </div>
        <button class="btn btn-primary btn-block" type="submit">SIMPAN PASSWORD</button>
      </form>
    <?php endif; ?>
  </div>
</div></div>
<?php include APP_VIEWS . '/footer.php'; ?>
