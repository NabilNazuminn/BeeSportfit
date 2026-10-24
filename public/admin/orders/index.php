<?php
/** ADMIN: all orders & quote requests, filter by status. */
require_once __DIR__ . '/../../../app/config.php';
require_admin();

$status = input_str($_GET, 'status');
$res = get_all_orders($status, request_int('page'));
$orders = $res['items'];
$page_title = 'Pesanan'; $active = 'orders';
include APP_VIEWS . '/admin_header.php';
?>
<div class="admin-top"><h1>PESANAN</h1></div>
<div class="pills">
  <a class="pill <?= $status === '' ? 'active' : '' ?>" href="?">Semua</a>
  <?php foreach (order_statuses() as $k => $l): ?><a class="pill <?= $status === $k ? 'active' : '' ?>" href="?status=<?= $k ?>"><?= e($l) ?></a><?php endforeach; ?>
</div>
<div class="table-wrap"><table>
  <thead><tr><th>Kode</th><th>Tanggal</th><th>Pelanggan</th><th>Jenis</th><th>Total</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($orders as $o): ?>
    <tr><td><strong><?= e($o['order_code']) ?></strong></td><td><?= e(format_date($o['created_at'])) ?></td>
      <td><?= e($o['customer_name']) ?><br><span class="text-muted" style="font-size:.8rem"><?= e($o['customer_phone']) ?></span></td>
      <td><?= $o['order_type'] === 'quote' ? 'Penawaran' : 'Pesanan' ?></td><td><?= format_rupiah($o['grand_total']) ?></td>
      <td><span class="status status-<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td>
      <td><a class="btn btn-ghost btn-sm" href="<?= url('admin/orders/detail.php?id=' . (int)$o['id']) ?>">DETAIL</a></td></tr>
  <?php endforeach; if (!$orders): ?><tr><td colspan="7" class="text-muted">Tidak ada pesanan.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php if ($res['pages'] > 1): ?><nav class="pagination">
  <?php for ($i = 1; $i <= $res['pages']; $i++): ?>
    <?php if ($i === $res['page']): ?><span class="current"><?= $i ?></span><?php else: ?><a href="<?= e(query_with(['page' => $i])) ?>"><?= $i ?></a><?php endif; ?>
  <?php endfor; ?></nav><?php endif; ?>
<?php include APP_VIEWS . '/admin_footer.php'; ?>
