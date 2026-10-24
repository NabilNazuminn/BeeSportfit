<?php
/** CUSTOMER: list of my orders. */
require_once __DIR__ . '/../../app/config.php';
require_login();

$user   = current_user();
$orders = get_user_orders((int)$user['id']);

$page_title = 'Pesanan Saya | ' . SITE_NAME;
$noindex = true;
include APP_VIEWS . '/header.php';
?>
<div class="page-head"><div class="container">
  <div class="breadcrumb"><a href="<?= url('index.php') ?>">Home</a> / Akun</div>
  <h1 class="section-title">PESANAN SAYA</h1>
  <p class="text-muted mb-0">Halo, <?= e($user['name']) ?> · <a class="text-primary" href="<?= url('account/profile.php') ?>">Profil &amp; Password</a> ·
    <form method="post" action="<?= url('logout.php') ?>" style="display:inline"><?= csrf_field() ?><button type="submit" class="linklike text-primary">Logout</button></form></p>
</div></div>
<div class="container" style="padding-bottom:4rem">
  <?php if (!$orders): ?>
    <div class="empty-state"><i class="fa-solid fa-receipt"></i><p>Anda belum memiliki pesanan.</p><a class="btn btn-primary" href="<?= url('products.php') ?>">LIHAT KATALOG</a></div>
  <?php else: ?>
    <div class="table-wrap"><table>
      <thead><tr><th>Kode</th><th>Tanggal</th><th>Jenis</th><th>Total</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td><strong><?= e($o['order_code']) ?></strong></td>
          <td><?= e(format_date($o['created_at'])) ?></td>
          <td><?= $o['order_type'] === 'quote' ? 'Penawaran' : 'Pesanan' ?></td>
          <td><?= format_rupiah($o['grand_total']) ?></td>
          <td><span class="status status-<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td>
          <td><a class="btn btn-ghost btn-sm" href="<?= url('account/order-detail.php?id=' . (int)$o['id']) ?>">DETAIL</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>
<?php include APP_VIEWS . '/footer.php'; ?>
