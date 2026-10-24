<?php
/** ADMIN: edit testimonial. */
require_once __DIR__ . '/../../../app/config.php';
require_admin();
$id = (int)($_GET['id'] ?? 0);
$t = get_testimonial($id);
if (!$t) { flash_set('error', 'Testimoni tidak ditemukan.'); redirect('admin/testimonials/index.php'); }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (!is_string($_POST['name'] ?? null) || !is_string($_POST['content'] ?? null) || trim($_POST['name']) === '' || trim($_POST['content']) === '') { $errors[] = 'Nama dan isi testimoni wajib diisi.'; $t = array_merge($t, $_POST); }
    elseif (text_len($_POST['name']) > 100 || text_len((string)($_POST['role_title'] ?? '')) > 150 || text_len($_POST['content']) > 2000) { $errors[] = 'Teks terlalu panjang (nama maks. 100, jabatan maks. 150, isi maks. 2000 karakter).'; $t = array_merge($t, $_POST); }
    else { save_testimonial($_POST, $id); flash_set('success', 'Testimoni diperbarui.'); redirect('admin/testimonials/index.php'); }
}
$page_title = 'Edit Testimoni'; $active = 'testimonials';
include APP_VIEWS . '/admin_header.php';
?>
<div class="admin-top"><h1>EDIT TESTIMONI</h1></div>
<?php include __DIR__ . '/_form.php'; ?>
<?php include APP_VIEWS . '/admin_footer.php'; ?>
