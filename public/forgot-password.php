<?php
/** FORGOT PASSWORD: e-mails a one-hour reset link. Always shows the same message (does not reveal which e-mails exist). */
require_once __DIR__ . '/../app/config.php';

if (is_logged_in()) redirect('account/profile.php');
$noindex = true;
$done = false; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = input_str($_POST, 'email');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Masukkan alamat email yang valid.';
    } elseif (!rate_ok('reset:ip:' . client_ip(), 5, 3600) || !rate_ok('reset:em:' . strtolower($email), 3, 3600)) {
        $error = 'Terlalu banyak permintaan. Coba lagi dalam satu jam.';
    } else {
        $r = create_password_reset($email);
        if ($r) {
            send_mail($r['user']['email'], 'Reset password ' . SITE_NAME,
                "Halo " . $r['user']['name'] . ",\n\nKami menerima permintaan reset password. Buka tautan berikut (berlaku 1 jam):\n\n" . $r['link']
                . "\n\nJika bukan Anda yang meminta, abaikan email ini - password Anda tidak berubah.");
        }
        $done = true;
    }
}

$page_title = 'Lupa Password | ' . SITE_NAME;
include APP_VIEWS . '/header.php';
?>
<div class="container"><div class="auth-box">
  <div class="panel">
    <h1 style="font-size:1.8rem">LUPA PASSWORD</h1>
    <?php if ($done): ?>
      <div class="alert alert-success">Jika email tersebut terdaftar, tautan reset password sudah dikirim. Periksa kotak masuk (dan folder spam). Tautan berlaku 1 jam.</div>
      <p class="text-muted" style="font-size:.9rem">Tidak menerima email? Hubungi kami lewat live chat atau WhatsApp agar admin dapat membantu mengatur ulang password Anda.</p>
    <?php else: ?>
      <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
      <form method="post"><?= csrf_field() ?>
        <div class="form-group"><label for="email">Email akun</label><input type="email" id="email" name="email" required maxlength="150" value="<?= old('email') ?>" autofocus></div>
        <button class="btn btn-primary btn-block" type="submit">KIRIM TAUTAN RESET</button>
      </form>
    <?php endif; ?>
    <p class="text-muted mt-2 mb-0" style="font-size:.9rem"><a class="text-primary" href="<?= url('login.php') ?>">&larr; Kembali ke login</a></p>
  </div>
</div></div>
<?php include APP_VIEWS . '/footer.php'; ?>
