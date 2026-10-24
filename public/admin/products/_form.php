<?php
/**
 * Product form (shared by create.php and edit.php). Not a page by itself.
 * Needs: $p (field values), $errors, $images (existing images, edit only), $editing (bool)
 */
$categories = get_categories(false);
$brands     = get_brands();
$val = fn($k, $d = '') => e($p[$k] ?? $d);
?>
<?php foreach (($errors ?? []) as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save">

  <div class="panel">
    <h3>INFORMASI UTAMA</h3>
    <div class="form-group"><label>Nama Produk *</label><input type="text" name="name" required value="<?= $val('name') ?>"></div>
    <div class="form-row">
      <div class="form-group"><label>Kategori *</label>
        <select name="category_id" required><option value="">— pilih —</option>
          <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)($p['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
        </select></div>
      <div class="form-group"><label>Brand</label>
        <select name="brand_id"><option value="">— tanpa brand —</option>
          <?php foreach ($brands as $b): ?><option value="<?= (int)$b['id'] ?>" <?= (int)($p['brand_id'] ?? 0) === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option><?php endforeach; ?>
        </select></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Kode / SKU</label><input type="text" name="sku" value="<?= $val('sku') ?>"></div>
      <div class="form-group"><label>Garansi (bulan)</label><input type="number" name="warranty_months" min="0" value="<?= $val('warranty_months') ?>"></div>
    </div>
    <div class="form-group"><label>Deskripsi Singkat</label><input type="text" name="short_description" maxlength="300" value="<?= $val('short_description') ?>"></div>
    <div class="form-group"><label>Deskripsi Lengkap</label><textarea name="description" rows="5"><?= $val('description') ?></textarea></div>
    <div class="form-group"><label>Spesifikasi</label><textarea name="specifications" rows="5" placeholder="Satu baris satu spesifikasi, format  Nama: Nilai&#10;Berat: 270 kg&#10;Ukuran: 1310x1200x1600 mm"><?= $val('specifications') ?></textarea>
      <div class="form-hint">Tulis dengan format “Nama: Nilai” agar tampil sebagai tabel di halaman produk.</div></div>
  </div>

  <div class="panel">
    <h3>HARGA &amp; STOK</h3>
    <div class="form-row">
      <div class="form-group"><label>Harga Normal (Rp)</label><input type="number" name="price" min="1" step="1" value="<?= $val('price') ?>">
        <div class="form-hint">Kosongkan jika harga perlu dikonfirmasi.</div></div>
      <div class="form-group"><label>Harga Diskon (Rp)</label><input type="number" name="discount_price" min="0" step="1" value="<?= $val('discount_price') ?>">
        <div class="form-hint">Kosongkan jika tidak ada diskon.</div></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Diskon Berakhir (opsional)</label>
        <input type="datetime-local" name="discount_ends_at" value="<?= e(!empty($p['discount_ends_at']) ? date('Y-m-d\TH:i', strtotime(str_replace('T', ' ', $p['discount_ends_at']))) : '') ?>"></div>
      <div class="form-group"><label>Jumlah Stok</label><input type="number" name="stock" min="0" value="<?= $val('stock', 0) ?>"></div>
    </div>
    <div class="form-group"><label>Status Ketersediaan</label>
      <select name="stock_status">
        <?php foreach (['in_stock' => 'Tersedia', 'pre_order' => 'Pre-Order', 'out_of_stock' => 'Habis'] as $v => $l): ?>
          <option value="<?= $v ?>" <?= ($p['stock_status'] ?? 'in_stock') === $v ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select></div>
    <label class="check-label"><input type="checkbox" name="is_active" value="1" <?= ($p['is_active'] ?? 1) ? 'checked' : '' ?>> Tampilkan di website (aktif)</label>
    <label class="check-label" style="margin-top:.5rem"><input type="checkbox" name="is_featured" value="1" <?= !empty($p['is_featured']) ? 'checked' : '' ?>> Produk unggulan (tampil di halaman depan)</label>
  </div>

  <div class="panel">
    <h3>FOTO PRODUK</h3>
    <div class="form-group"><label>Unggah Foto Baru</label><input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
      <div class="form-hint">JPG / PNG / WEBP, maksimal <?= MAX_UPLOAD_MB ?> MB per foto. Foto pertama otomatis menjadi foto utama.</div></div>
  </div>

  <button class="btn btn-primary" type="submit"><?= $editing ? 'SIMPAN PERUBAHAN' : 'TAMBAH PRODUK' ?></button>
  <a class="btn btn-ghost" href="<?= url('admin/products/index.php') ?>">BATAL</a>
</form>

<?php if (!empty($images)): ?>
<div class="panel mt-2">
  <h3>KELOLA FOTO SAAT INI</h3>
  <div class="img-grid">
    <?php foreach ($images as $img): ?>
      <figure><img src="<?= e(image_url($img['image_path'])) ?>" alt="">
        <?= $img['is_primary'] ? '<span class="badge badge-solid" style="margin-top:4px">UTAMA</span>' : '' ?>
        <div class="actions" style="margin-top:4px">
          <?php if (!$img['is_primary']): ?>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="image_action" value="primary:<?= (int)$img['id'] ?>">
              <button class="btn btn-ghost btn-sm" type="submit">Jadikan utama</button></form>
          <?php endif; ?>
          <form method="post" data-confirm="Hapus foto ini?"><?= csrf_field() ?><input type="hidden" name="image_action" value="delete:<?= (int)$img['id'] ?>">
            <button class="btn btn-danger btn-sm" type="submit">Hapus</button></form>
        </div></figure>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
