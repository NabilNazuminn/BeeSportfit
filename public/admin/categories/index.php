<?php
/** ADMIN: category list. */
require_once __DIR__ . '/../../../app/config.php';
require_admin();

$cats = get_categories(false);
$page_title = 'Kategori'; $active = 'categories';
include APP_VIEWS . '/admin_header.php';
?>
<div class="admin-top"><h1>KATEGORI</h1><a class="btn btn-primary btn-sm" href="<?= url('admin/categories/create.php') ?>"><i class="fa-solid fa-plus"></i> TAMBAH</a></div>
<div class="table-wrap"><table>
  <thead><tr><th>Urutan</th><th>Nama</th><th>Deskripsi</th><th>Produk</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($cats as $c): ?>
    <tr><td><?= (int)$c['sort_order'] ?></td><td><strong><?= e($c['name']) ?></strong></td><td><?= e($c['description']) ?></td><td><?= (int)$c['product_count'] ?></td>
      <td><?= $c['is_active'] ? '<span class="status status-completed">Aktif</span>' : '<span class="status status-cancelled">Nonaktif</span>' ?></td>
      <td><a class="btn btn-ghost btn-sm" href="<?= url('admin/categories/edit.php?id=' . (int)$c['id']) ?>">EDIT</a></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<p class="form-hint mt-1">Untuk menyembunyikan kategori dari website, nonaktifkan lewat tombol Edit.</p>
<?php include APP_VIEWS . '/admin_footer.php'; ?>
