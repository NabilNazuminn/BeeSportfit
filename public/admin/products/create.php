<?php
/** ADMIN: add a product. */
require_once __DIR__ . '/../../../app/config.php';
require_admin();

$errors = []; $p = $_POST; $images = []; $editing = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $errors = validate_product($_POST);
    if (!$errors) {
        $id = save_product($_POST);
        $errors = save_uploaded_images($id, $_FILES['images'] ?? []);
        flash_set($errors ? 'info' : 'success', $errors ? 'Produk tersimpan, tetapi ada foto gagal: ' . implode('; ', $errors) : 'Produk berhasil ditambahkan.');
        redirect('admin/products/edit.php?id=' . $id);
    }
}

$page_title = 'Tambah Produk'; $active = 'products';
include APP_VIEWS . '/admin_header.php';
?>
<div class="admin-top"><h1>TAMBAH PRODUK</h1></div>
<?php include __DIR__ . '/_form.php'; ?>
<?php include APP_VIEWS . '/admin_footer.php'; ?>
