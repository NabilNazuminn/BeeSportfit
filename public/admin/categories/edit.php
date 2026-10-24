<?php
/** ADMIN: edit category. */
require_once __DIR__ . '/../../../app/config.php';
require_admin();
$id = (int)($_GET['id'] ?? 0);
$c = get_category($id);
if (!$c) { flash_set('error', 'Kategori tidak ditemukan.'); redirect('admin/categories/index.php'); }
$errors = []; $editing = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if ($errors = validate_category($_POST)) { $c = array_merge($c, $_POST); }
    else { save_category($_POST, $id); flash_set('success', 'Kategori diperbarui.'); redirect('admin/categories/index.php'); }
}
$page_title = 'Edit Kategori'; $active = 'categories';
include APP_VIEWS . '/admin_header.php';
?>
<div class="admin-top"><h1>EDIT KATEGORI</h1></div>
<?php include __DIR__ . '/_form.php'; ?>
<?php include APP_VIEWS . '/admin_footer.php'; ?>
