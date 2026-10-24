<?php
/** CUSTOMER REGISTRATION. */
require_once __DIR__ . '/../app/config.php';

if (is_logged_in()) redirect('account/orders.php');
$error = '';
$noindex = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $pw  = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $pw2 = is_string($_POST['password2'] ?? null) ? $_POST['password2'] : '';
    if ($pw !== $pw2) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        [$id, $error] = register_user(input_str($_POST, 'name'), input_str($_POST, 'email'), input_str($_POST, 'phone'), $pw);
        if ($id) {
            login_user(db_one('SELECT * FROM users WHERE id = ?', [$id]));
            flash_set('success', 'Akun berhasil dibuat. Selamat datang!');
            redirect('index.php');
        }
    }
}

$page_title = 'Daftar | ' . SITE_NAME;
include APP_VIEWS . '/header.php';
?>
<div class="container"><div class="auth-box">
  <div class="panel">
    <h1 style="font-size:1.8rem">BUAT AKUN</h1>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <div class="form-group"><label>Nama Lengkap</label><input type="text" name="name" required value="<?= old('name') ?>"></div>
      <div class="form-group"><label>Email</label><input type="email" name="email" required value="<?= old('email') ?>"></div>
      <div class="form-group"><label>Nomor WhatsApp</label><input type="tel" name="phone" value="<?= old('phone') ?>"></div>
      <div class="form-row">
        <div class="form-group"><label>Password</label><input type="password" name="password" required minlength="8"></div>
        <div class="form-group"><label>Ulangi Password</label><input type="password" name="password2" required minlength="8"></div>
      </div>
      <button class="btn btn-primary btn-block" type="submit">DAFTAR</button>
    </form>
    <p class="text-muted mt-2 mb-0" style="font-size:.9rem">Sudah punya akun? <a class="text-primary" href="<?= url('login.php') ?>">Login</a></p>
  </div>
</div></div>
<?php include APP_VIEWS . '/footer.php'; ?>
