<?php
/** ADMIN: product list. */
require_once __DIR__ . '/../../../app/config.php';
require_admin();

$q = input_str($_GET, 'q');
$res = get_products(['q' => $q, 'include_inactive' => true, 'per_page' => 20, 'page' => request_int('page')]);

$page_title = 'Produk'; $active = 'products';
include APP_VIEWS . '/admin_header.php';
?>
<div class="admin-top">
  <h1>PRODUK</h1>
  <a class="btn btn-primary btn-sm" href="<?= url('admin/products/create.php') ?>"><i class="fa-solid fa-plus"></i> TAMBAH PRODUK</a>
</div>
<form method="get" class="mb-2" style="display:flex;gap:.5rem;max-width:480px">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nama / kode..."><button class="btn btn-ghost btn-sm" type="submit">CARI</button>
</form>
<div class="table-wrap"><table>
  <thead><tr><th></th><th>Produk</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($res['items'] as $p): ?>
    <tr>
      <td><img class="thumb" src="<?= e(image_url($p['image'])) ?>" alt=""></td>
      <td><strong><?= e($p['name']) ?></strong><br><span class="text-muted" style="font-size:.8rem"><?= e($p['sku']) ?></span></td>
      <td><?= e($p['category_name']) ?></td>
      <td><?= (float)$p['price'] > 0 ? format_rupiah(final_price($p)) : '' ?><?= has_discount($p) ? '<br><span class="text-danger" style="font-size:.75rem">diskon ' . discount_percent($p) . '%</span>' : '' ?></td>
      <td><?= (int)$p['stock'] ?></td>
      <td><?= $p['is_active'] ? '<span class="status status-completed">Aktif</span>' : '<span class="status status-cancelled">Nonaktif</span>' ?><?= $p['is_featured'] ? ' <span class="status status-new">Unggulan</span>' : '' ?></td>
      <td><div class="actions">
        <a class="btn btn-ghost btn-sm" href="<?= url('admin/products/edit.php?id=' . (int)$p['id']) ?>">EDIT</a>
        <form method="post" action="<?= url('admin/products/delete.php') ?>" data-confirm="Hapus produk ini? Tindakan tidak bisa dibatalkan.">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn btn-danger btn-sm" type="submit"><i class="fa-solid fa-trash"></i></button>
        </form>
      </div></td>
    </tr>
  <?php endforeach; if (!$res['items']): ?><tr><td colspan="7" class="text-muted">Tidak ada produk.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php if ($res['pages'] > 1): ?><nav class="pagination">
  <?php for ($i = 1; $i <= $res['pages']; $i++): ?>
    <?php if ($i === $res['page']): ?><span class="current"><?= $i ?></span><?php else: ?><a href="<?= e(query_with(['page' => $i])) ?>"><?= $i ?></a><?php endif; ?>
  <?php endfor; ?></nav><?php endif; ?>
<?php include APP_VIEWS . '/admin_footer.php'; ?>
