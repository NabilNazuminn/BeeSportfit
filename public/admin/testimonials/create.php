<?php
/** ADMIN: add testimonial. */
require_once __DIR__ . '/../../../app/config.php';
require_admin();
$errors = []; $t = $_POST;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (!is_string($_POST['name'] ?? null) || !is_string($_POST['content'] ?? null) || trim($_POST['name']) === '' || trim($_POST['content']) === '') $errors[] = 'Nama dan isi testimoni wajib diisi.';
    elseif (text_len($_POST['name']) > 100 || text_len((string)($_POST['role_title'] ?? '')) > 150 || text_len($_POST['content']) > 2000) $errors[] = 'Teks terlalu panjang (nama maks. 100, jabatan maks. 150, isi maks. 2000 karakter).';
    else { save_testimonial($_POST); flash_set('success', 'Testimoni ditambahkan.'); redirect('admin/testimonials/index.php'); }
}
$page_title = 'Tambah Testimoni'; $active = 'testimonials';
include APP_VIEWS . '/admin_header.php';
?>
<div class="admin-top"><h1>TAMBAH TESTIMONI</h1></div>
<?php include __DIR__ . '/_form.php'; ?>
<?php include APP_VIEWS . '/admin_footer.php'; ?>
