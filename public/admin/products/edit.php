<?php
/** ADMIN: edit a product (data + photos). */
require_once __DIR__ . '/../../../app/config.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$product = get_product_by_id($id);
if (!$product) { flash_set('error', 'Produk tidak ditemukan.'); redirect('admin/products/index.php'); }
$errors = []; $editing = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // Photo buttons: "primary:12" or "delete:12"
    if (!empty($_POST['image_action'])) {
        [$act, $imgId] = explode(':', $_POST['image_action']) + [1 => 0];
        $img = db_one('SELECT * FROM product_images WHERE id = ? AND product_id = ?', [(int)$imgId, $id]);  // must belong to THIS product
        if ($img && $act === 'primary') set_primary_image($id, (int)$imgId);
        if ($img && $act === 'delete')  delete_product_image((int)$imgId);
        redirect('admin/products/edit.php?id=' . $id);
    }

    $errors = validate_product($_POST, $id);
    if (!$errors) {
        save_product($_POST, $id);
        $errors = save_uploaded_images($id, $_FILES['images'] ?? []);
        if (!$errors) { flash_set('success', 'Perubahan disimpan.'); redirect('admin/products/edit.php?id=' . $id); }
    }
    $p = $_POST;
} else {
    $p = $product;
}
$images = get_product_images($id);

$page_title = 'Edit Produk'; $active = 'products';
include APP_VIEWS . '/admin_header.php';
?>
<div class="admin-top"><h1>EDIT PRODUK</h1>
  <a class="btn btn-ghost btn-sm" target="_blank" href="<?= url('product.php?slug=' . urlencode($product['slug'])) ?>">LIHAT DI WEBSITE</a></div>
<?php include __DIR__ . '/_form.php'; ?>
<?php include APP_VIEWS . '/admin_footer.php'; ?>
