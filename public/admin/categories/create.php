<?php
/** ADMIN: add category. */
require_once __DIR__ . '/../../../app/config.php';
require_admin();
$errors = []; $c = $_POST; $editing = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if ($errors = validate_category($_POST)) { }
    else { save_category($_POST); flash_set('success', 'Kategori ditambahkan.'); redirect('admin/categories/index.php'); }
}
$page_title = 'Tambah Kategori'; $active = 'categories';
include APP_VIEWS . '/admin_header.php';
?>
<div class="admin-top"><h1>TAMBAH KATEGORI</h1></div>
<?php include __DIR__ . '/_form.php'; ?>
<?php include APP_VIEWS . '/admin_footer.php'; ?>
