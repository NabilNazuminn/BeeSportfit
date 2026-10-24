<?php
/** ADMIN: one order - change its status, set final prices + shipping cost, see history. */
require_once __DIR__ . '/../../../app/config.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$order = get_order($id);
if (!$order) { flash_set('error', 'Pesanan tidak ditemukan.'); redirect('admin/orders/index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $adminId = (int)current_user()['id'];
    if (($_POST['action'] ?? '') === 'pricing') {
        $err = update_order_pricing($id, is_array($_POST['price'] ?? null) ? $_POST['price'] : [], input_str($_POST, 'shipping_cost'), $adminId);
        flash_set($err ? 'error' : 'success', $err ?? 'Harga dan ongkos kirim disimpan. Pelanggan diberi tahu lewat email (jika ada).');
    } else {
        $ok = update_order_status($id, input_str($_POST, 'status'), $adminId, text_cut(input_str($_POST, 'note'), 255));
        flash_set($ok ? 'success' : 'error', $ok ? 'Status pesanan diperbarui.' : 'Perubahan status itu tidak diizinkan dari status saat ini.');
    }
    redirect('admin/orders/detail.php?id=' . $id);
}
$items = get_order_items($id);
$history = get_order_history($id);
$labels = order_statuses();
$choices = array_merge([$order['status']], order_next_statuses($order['status']));
$locked = in_array($order['status'], ['completed', 'cancelled'], true);

$page_title = $order['order_code']; $active = 'orders';
include APP_VIEWS . '/admin_header.php';
?>
<div class="admin-top"><h1><?= e($order['order_code']) ?></h1>
  <span class="status status-<?= e($order['status']) ?>"><?= e(status_label($order['status'])) ?></span></div>
<div class="cart-layout">
  <div>
    <form method="post" class="panel"><?= csrf_field() ?><input type="hidden" name="action" value="pricing">
      <h3><?= $order['order_type'] === 'quote' ? 'PENAWARAN HARGA' : 'HARGA &amp; ONGKOS KIRIM' ?></h3>
      <div class="table-wrap"><table>
        <thead><tr><th>Produk</th><th>Harga satuan (Rp)</th><th>Qty</th><th>Total</th></tr></thead><tbody>
        <?php foreach ($items as $it): ?><tr><td><?= e($it['product_name']) ?></td>
          <td><input type="number" step="1" min="0" name="price[<?= (int)$it['id'] ?>]" value="<?= e(round((float)$it['unit_price'])) ?>" style="max-width:170px" <?= $locked ? 'disabled' : '' ?>></td>
          <td><?= (int)$it['quantity'] ?></td><td><?= format_rupiah($it['line_total']) ?></td></tr><?php endforeach; ?>
        <tr><td colspan="3" style="text-align:right">Subtotal</td><td><?= format_rupiah($order['subtotal']) ?></td></tr>
        <tr><td colspan="2" style="text-align:right">Ongkos kirim (Rp) - isi setelah disepakati di WhatsApp</td>
            <td colspan="2"><input type="number" step="1" min="0" name="shipping_cost" value="<?= e(round((float)$order['shipping_cost'])) ?>" style="max-width:170px" <?= $locked ? 'disabled' : '' ?>></td></tr>
        <tr><td colspan="3" style="text-align:right"><strong>TOTAL</strong></td><td><strong class="text-primary"><?= format_rupiah($order['grand_total']) ?></strong></td></tr>
        </tbody></table></div>
      <?php if (!$locked): ?>
        <button class="btn btn-primary mt-1" type="submit">SIMPAN HARGA &amp; ONGKIR</button>
        <div class="form-hint">Setelah harga dan ongkir disepakati di WhatsApp, catat di sini agar tercatat rapi. Total dihitung ulang dan pelanggan diberi tahu lewat email (jika ada).</div>
      <?php else: ?><div class="form-hint">Pesanan <?= e(strtolower(status_label($order['status']))) ?> - harga tidak bisa diubah.</div><?php endif; ?>
    </form>
    <div class="panel mt-2"><h3>RIWAYAT</h3><ul class="timeline">
      <?php foreach ($history as $h): ?><li><strong><?= e(status_label($h['status'])) ?></strong> · <?= e(format_date($h['created_at'])) ?><?= $h['admin_name'] ? ' · ' . e($h['admin_name']) : '' ?><?= $h['note'] ? ' — ' . e($h['note']) : '' ?></li><?php endforeach; ?>
    </ul></div>
  </div>
  <aside>
    <div class="panel">
      <h3>UBAH STATUS</h3>
      <form method="post"><?= csrf_field() ?>
        <div class="form-group"><select name="status"><?php foreach ($choices as $k): ?><option value="<?= e($k) ?>" <?= $order['status'] === $k ? 'selected' : '' ?>><?= e($labels[$k]) ?><?= $k === $order['status'] ? ' (saat ini)' : '' ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Catatan (opsional)</label><input type="text" name="note" maxlength="255"></div>
        <button class="btn btn-primary btn-block" type="submit">SIMPAN STATUS</button>
        <div class="form-hint">Status hanya bisa maju satu arah (atau dibatalkan). Pembatalan mengembalikan stok.</div>
      </form>
    </div>
    <div class="panel">
      <h3>PELANGGAN</h3>
      <p class="mb-0" style="font-size:.9rem"><strong><?= e($order['customer_name']) ?></strong><br><?= e($order['customer_phone']) ?><br><?= e($order['customer_email']) ?><br><br>
        <?= nl2br(e($order['shipping_address'])) ?><br><?= e(trim($order['city'] . ', ' . $order['province'], ', ')) ?>
        <?php if ($order['notes']): ?><br><br><em>Catatan: <?= e($order['notes']) ?></em><?php endif; ?></p>
      <a class="btn btn-ghost btn-block mt-2" target="_blank" rel="noopener" href="https://wa.me/<?= e(preg_replace('/^0/', '62', preg_replace('/\D/', '', $order['customer_phone']))) ?>"><i class="fa-brands fa-whatsapp"></i> CHAT PELANGGAN</a>
    </div>
  </aside>
</div>
<?php include APP_VIEWS . '/admin_footer.php'; ?>
