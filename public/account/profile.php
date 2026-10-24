<?php
/** CUSTOMER: edit name / phone and change password. */
require_once __DIR__ . '/../../app/config.php';
require_login();

$user = current_user();
$noindex = true;
$errors = []; $pwErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (($_POST['action'] ?? '') === 'profile') {
        $name = input_str($_POST, 'name'); $phone = input_str($_POST, 'phone');
        if ($name === '') $errors[] = 'Nama wajib diisi.';
        check_len($errors, 'Nama', $name, 100);
        if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{8,30}$/', $phone)) $errors[] = 'Nomor telepon tidak valid.';
        if (!$errors) {
            db_run('UPDATE users SET name = ?, phone = ? WHERE id = ?', [$name, $phone ?: null, $user['id']]);
            flash_set('success', 'Profil diperbarui.');
            redirect('account/profile.php');
        }
    }
    if (($_POST['action'] ?? '') === 'password') {
        $cur = is_string($_POST['current'] ?? null) ? $_POST['current'] : '';
        $pw  = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $pw2 = is_string($_POST['password2'] ?? null) ? $_POST['password2'] : '';
        $hash = db_one('SELECT password_hash FROM users WHERE id = ?', [$user['id']])['password_hash'];
        if (!password_verify($cur, $hash))              $pwErrors[] = 'Password saat ini salah.';
        elseif ($p = password_problem($pw, $pw2))       $pwErrors[] = $p;
        if (!$pwErrors) {
            set_user_password((int)$user['id'], $pw);
            session_regenerate_id(true);
            flash_set('success', 'Password berhasil diubah.');
            redirect('account/profile.php');
        }
    }
}

$page_title = 'Profil | ' . SITE_NAME;
include APP_VIEWS . '/header.php';
?>
<div class="page-head"><div class="container">
  <div class="breadcrumb"><a href="<?= url('account/orders.php') ?>">Pesanan Saya</a> / Profil</div>
  <h1 class="section-title">PROFIL &amp; PASSWORD</h1>
</div></div>
<div class="container" style="max-width:720px;padding-bottom:4rem">
  <div class="panel">
    <h3>DATA AKUN</h3>
    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="profile">
      <div class="form-group"><label>Email (tidak bisa diubah)</label><input type="email" value="<?= e($user['email']) ?>" disabled></div>
      <div class="form-group"><label>Nama Lengkap</label><input type="text" name="name" required maxlength="100" value="<?= e($_POST['name'] ?? $user['name']) ?>"></div>
      <div class="form-group"><label>Nomor WhatsApp</label><input type="tel" name="phone" maxlength="30" value="<?= e($_POST['phone'] ?? ($user['phone'] ?? '')) ?>"></div>
      <button class="btn btn-primary" type="submit">SIMPAN</button>
    </form>
  </div>
  <div class="panel mt-2">
    <h3>GANTI PASSWORD</h3>
    <?php foreach ($pwErrors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="password">
      <div class="form-group"><label>Password saat ini</label><input type="password" name="current" required></div>
      <div class="form-row">
        <div class="form-group"><label>Password baru</label><input type="password" name="password" required minlength="8" maxlength="72"></div>
        <div class="form-group"><label>Ulangi password baru</label><input type="password" name="password2" required minlength="8" maxlength="72"></div>
      </div>
      <button class="btn btn-primary" type="submit">GANTI PASSWORD</button>
    </form>
  </div>
</div>
<?php include APP_VIEWS . '/footer.php'; ?>
