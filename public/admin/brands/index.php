<?php
/** ADMIN: brands - add, rename, upload logo, delete. The logos feed the home strip, catalog chips, product page and footer. */
require_once __DIR__ . '/../../../app/config.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);
    $name   = trim($_POST['name'] ?? '');

    if ($action === 'delete' && $id) {
        delete_brand($id);
        flash_set('success', 'Brand dihapus. Produk terkait menjadi "tanpa brand".');
    } elseif ($name === '' || text_len($name) > 100) {
        flash_set('error', 'Nama brand wajib diisi (maks. 100 karakter).');
    } elseif (($action === 'update' && !get_brand($id)) || !in_array($action, ['add', 'update'], true)) {
        flash_set('error', 'Brand tidak ditemukan.');
    } else {
        try {
            $newLogo = upload_image($_FILES['logo'] ?? ['name' => '', 'error' => UPLOAD_ERR_NO_FILE], 'brands');   // null if no file chosen
            save_brand($name, $action === 'update' ? $id : null, $newLogo, !empty($_POST['logo_white']), !empty($_POST['remove_logo']));
            flash_set('success', $action === 'update' ? 'Brand diperbarui.' : 'Brand ditambahkan.');
        } catch (RuntimeException $e) {
            flash_set('error', 'Logo gagal diunggah: ' . $e->getMessage());
        }
    }
    redirect('admin/brands/index.php');
}

$brands = get_brands_with_counts();
$page_title = 'Brand'; $active = 'brands';
include APP_VIEWS . '/admin_header.php';
?>
<div class="admin-top"><h1>BRAND</h1></div>
<p class="text-muted" style="max-width:720px">Logo brand tampil di <strong>halaman depan</strong>, <strong>filter katalog</strong>, <strong>halaman produk</strong>, dan <strong>footer</strong>
  (hanya brand yang punya logo). Gunakan PNG transparan. Logo berwarna <strong>hitam</strong>? Centang &ldquo;tampilkan putih&rdquo; agar terlihat di latar gelap.</p>

<form method="post" enctype="multipart/form-data" class="panel" style="max-width:520px">
  <?= csrf_field() ?><input type="hidden" name="action" value="add">
  <h3>TAMBAH BRAND BARU</h3>
  <div class="form-group"><label>Nama Brand *</label><input type="text" name="name" required maxlength="100" placeholder="Contoh: Insight"></div>
  <div class="form-group"><label>Logo (opsional)</label><input type="file" name="logo" accept="image/png,image/jpeg,image/webp"></div>
  <label class="check-label"><input type="checkbox" name="logo_white" value="1"> Logo berwarna hitam &rarr; tampilkan putih</label>
  <button class="btn btn-primary mt-2" type="submit">TAMBAH</button>
</form>

<div class="brand-admin-grid">
<?php foreach ($brands as $b): ?>
  <div class="panel brand-admin">
    <div class="brand-preview"><?= $b['logo'] ? brand_logo_html($b) : 'Belum ada logo' ?></div>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
      <div class="form-group"><label>Nama</label><input type="text" name="name" value="<?= e($b['name']) ?>" required maxlength="100"></div>
      <div class="form-group"><label><?= $b['logo'] ? 'Ganti Logo' : 'Unggah Logo' ?></label><input type="file" name="logo" accept="image/png,image/jpeg,image/webp"></div>
      <label class="check-label"><input type="checkbox" name="logo_white" value="1" <?= $b['logo_white'] ? 'checked' : '' ?>> Logo hitam &rarr; tampilkan putih</label>
      <?php if ($b['logo']): ?><label class="check-label" style="margin-top:.4rem"><input type="checkbox" name="remove_logo" value="1"> Hapus logo</label><?php endif; ?>
      <div class="actions mt-2"><button class="btn btn-primary btn-sm" type="submit">SIMPAN</button>
        <span class="text-muted" style="font-size:.8rem;align-self:center"><?= (int)$b['product_count'] ?> produk</span></div>
    </form>
    <form method="post" data-confirm="Hapus brand ini? Produknya akan menjadi tanpa brand." style="margin-top:.75rem">
      <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
      <button class="btn btn-danger btn-sm" type="submit"><i class="fa-solid fa-trash"></i> HAPUS BRAND</button>
    </form>
  </div>
<?php endforeach; if (!$brands): ?><p class="text-muted">Belum ada brand.</p><?php endif; ?>
</div>
<?php include APP_VIEWS . '/admin_footer.php'; ?>
