<?php
/** CUSTOMER: one order's details + status history. */
require_once __DIR__ . '/../../app/config.php';
require_login();

$user  = current_user();
$order = get_order((int)($_GET['id'] ?? 0));
// A customer may only see THEIR OWN orders.
if (!$order || (int)$order['user_id'] !== (int)$user['id']) {
    http_response_code(404);
    flash_set('error', 'Pesanan tidak ditemukan.');
    redirect('account/orders.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (($_POST['action'] ?? '') === 'cancel') {
        $ok = customer_cancel_order((int)$order['id'], (int)$user['id']);
        flash_set($ok ? 'success' : 'error', $ok ? 'Pesanan dibatalkan.' : 'Pesanan ini sudah diproses dan tidak bisa dibatalkan sendiri. Silakan hubungi kami.');
    }
    redirect('account/order-detail.php?id=' . (int)$order['id']);
}
$items   = get_order_items((int)$order['id']);
$history = get_order_history((int)$order['id']);

$page_title = $order['order_code'] . ' | ' . SITE_NAME;
$noindex = true;
include APP_VIEWS . '/header.php';
?>
<div class="page-head"><div class="container">
  <div class="breadcrumb"><a href="<?= url('account/orders.php') ?>">Pesanan Saya</a> / <?= e($order['order_code']) ?></div>
  <h1 class="section-title"><?= e($order['order_code']) ?></h1>
  <span class="status status-<?= e($order['status']) ?>"><?= e(status_label($order['status'])) ?></span>
  <span class="text-muted" style="margin-left:.75rem"><?= $order['order_type'] === 'quote' ? 'Permintaan Penawaran' : 'Pesanan' ?> · <?= e(format_date($order['created_at'])) ?></span>
</div></div>
<div class="container" style="padding-bottom:4rem">
  <div class="cart-layout">
    <div>
      <div class="table-wrap"><table>
        <thead><tr><th>Produk</th><th>Harga</th><th>Qty</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
          <tr><td><?= e($it['product_name']) ?></td><td><?= format_rupiah($it['unit_price']) ?></td><td><?= (int)$it['quantity'] ?></td><td><?= format_rupiah($it['line_total']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <div class="panel mt-2">
        <h3>RIWAYAT STATUS</h3>
        <ul class="timeline">
          <?php foreach ($history as $h): ?>
            <li><strong><?= e(status_label($h['status'])) ?></strong> · <?= e(format_date($h['created_at'])) ?><?= $h['note'] ? ' — ' . e($h['note']) : '' ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <aside class="summary">
      <h3>RINGKASAN</h3>
      <div class="summary-row"><span>Subtotal</span><span><?= format_rupiah($order['subtotal']) ?></span></div>
      <div class="summary-row"><span>Ongkir</span><span><?= $order['shipping_cost'] > 0 ? format_rupiah($order['shipping_cost']) : 'Disepakati via WhatsApp' ?></span></div>
      <div class="summary-row summary-total"><span>TOTAL</span><span><?= format_rupiah($order['grand_total']) ?></span></div>
      <hr style="border:0;border-top:1px solid var(--border);margin:1rem 0">
      <p class="mb-0" style="font-size:.85rem"><strong><?= e($order['customer_name']) ?></strong><br><?= e($order['customer_phone']) ?><br><?= nl2br(e($order['shipping_address'])) ?><br><?= e(trim($order['city'] . ', ' . $order['province'], ', ')) ?></p>
      <?php if ($order['status'] === 'new'): ?>
        <form method="post" data-confirm="Batalkan <?= e($order['order_type'] === 'quote' ? 'permintaan' : 'pesanan') ?> ini?" class="mt-2"><?= csrf_field() ?><input type="hidden" name="action" value="cancel">
          <button class="btn btn-danger btn-block" type="submit">BATALKAN</button></form>
      <?php endif; ?>
      <?php if (has_wa() && !in_array($order['status'], ['completed', 'cancelled'], true)): ?><a class="btn btn-primary btn-block mt-2" target="_blank" rel="noopener" href="<?= e(wa_link(order_wa_message($order, $items))) ?>"><i class="fa-brands fa-whatsapp"></i> LANJUTKAN KE WHATSAPP</a><?php endif; ?>
    </aside>
  </div>
</div>
<?php include APP_VIEWS . '/footer.php'; ?>
