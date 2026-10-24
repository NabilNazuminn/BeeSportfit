<?php
/** ADMIN DASHBOARD. */
require_once __DIR__ . '/../../app/config.php';
require_admin();

$stats = dashboard_stats();
$page_title = 'Dashboard'; $active = 'dashboard';
include APP_VIEWS . '/admin_header.php';
?>
<div class="admin-top"><h1>DASHBOARD</h1></div>
<div class="stat-grid">
  <div class="stat"><b><?= $stats['products'] ?></b><span>Produk</span></div>
  <div class="stat"><b><?= $stats['orders_new'] ?></b><span>Pesanan Baru</span></div>
  <div class="stat"><b><?= $stats['leads_new'] ?></b><span>Konsultasi Baru</span></div>
  <div class="stat"><b><?= $stats['customers'] ?></b><span>Pelanggan</span></div>
</div>
<div class="stat" style="margin-bottom:2rem"><b><?= format_rupiah($stats['revenue']) ?></b><span>Total pesanan dibayar / selesai</span></div>

<h2 style="font-size:1.3rem">PESANAN TERBARU</h2>
<div class="table-wrap"><table>
  <thead><tr><th>Kode</th><th>Pelanggan</th><th>Total</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($stats['recent_orders'] as $o): ?>
    <tr><td><?= e($o['order_code']) ?></td><td><?= e($o['customer_name']) ?></td><td><?= format_rupiah($o['grand_total']) ?></td>
      <td><span class="status status-<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td>
      <td><a class="btn btn-ghost btn-sm" href="<?= url('admin/orders/detail.php?id=' . (int)$o['id']) ?>">BUKA</a></td></tr>
  <?php endforeach; if (!$stats['recent_orders']): ?><tr><td colspan="5" class="text-muted">Belum ada pesanan.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php include APP_VIEWS . '/admin_footer.php'; ?>
