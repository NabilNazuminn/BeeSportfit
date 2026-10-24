<?php
/** Free layout consultation: shows the form and saves requests (leads) to the database. */
require_once __DIR__ . '/../app/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $back = ($_POST['return'] ?? '') === 'home' ? 'index.php#kontak' : 'consultation.php';

    if (input_str($_POST, 'website') !== '') redirect($back);                       // honeypot: only bots fill this hidden field
    $errors = validate_consultation($_POST);
    if ($errors) {
        flash_set('error', implode(' ', $errors));
        redirect($back);
    }
    if (!rate_ok('consult:ip:' . client_ip(), 5, 3600)) {
        flash_set('error', 'Terlalu banyak permintaan dari jaringan ini. Silakan coba lagi nanti atau hubungi kami lewat live chat.');
        redirect($back);
    }
    create_consultation($_POST);
    flash_set('success', 'Terima kasih! Tim kami akan segera menghubungi Anda.');
    redirect($back);
}

$page_title = 'Konsultasi Gratis | ' . SITE_NAME;
$active = 'kontak';
$return = 'page';
include APP_VIEWS . '/header.php';
?>
<div class="page-head"><div class="container">
  <div class="breadcrumb"><a href="<?= url('index.php') ?>">Home</a> / Konsultasi</div>
  <h1 class="section-title">KONSULTASI LAYOUT GYM GRATIS</h1>
</div></div>
<div class="container" style="max-width:720px;padding-bottom:4rem">
  <div class="panel">
    <p class="text-muted">Ceritakan kebutuhan Anda. Tim kami akan membantu merencanakan layout ruangan dan memilih alat yang tepat.</p>
    <?php include APP_VIEWS . '/consultation_form.php'; ?>
  </div>
</div>
<?php include APP_VIEWS . '/footer.php'; ?>
