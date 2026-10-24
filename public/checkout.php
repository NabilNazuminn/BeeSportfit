<?php
/** CHECKOUT: place a normal order OR request a price quote. Login required. */
require_once __DIR__ . '/../app/config.php';
require_login();

$items = cart_items();
if (!$items) { flash_set('info', 'Keranjang masih kosong.'); redirect('products.php'); }
$subtotal = cart_subtotal($items);
$user = current_user();
$errors = [];
$noindex = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $type = ($_POST['order_type'] ?? '') === 'quote' ? 'quote' : 'order';
    $d = [
        'customer_name'    => input_str($_POST, 'customer_name'),
        'customer_phone'   => input_str($_POST, 'customer_phone'),
        'customer_email'   => input_str($_POST, 'customer_email'),
        'shipping_address' => input_str($_POST, 'shipping_address'),
        'province'         => input_str($_POST, 'province'),
        'city'             => input_str($_POST, 'city'),
        'notes'            => input_str($_POST, 'notes'),
    ];
    if ($d['customer_name'] === '')                                    $errors[] = 'Nama wajib diisi.';
    if (!preg_match('/^[0-9+\-\s()]{8,30}$/', $d['customer_phone']))    $errors[] = 'Nomor telepon tidak valid.';
    if ($d['customer_email'] !== '' && !filter_var($d['customer_email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if ($d['shipping_address'] === '')                                 $errors[] = 'Alamat pengiriman wajib diisi.';
    check_len($errors, 'Nama', $d['customer_name'], 100);
    check_len($errors, 'Email', $d['customer_email'], 150);
    check_len($errors, 'Alamat', $d['shipping_address'], 1000);
    check_len($errors, 'Provinsi', $d['province'], 80);
    check_len($errors, 'Kota / Kabupaten', $d['city'], 80);
    check_len($errors, 'Catatan', $d['notes'], 1000);

    if (!$errors) {
        try {
            $orderId = create_order($d, $items, $type, (int)$user['id']);
        } catch (RuntimeException $ex) {            // e.g. a product just sold out
            $errors[] = $ex->getMessage();
            $orderId = 0;
        }
        if ($orderId) {
            cart_clear();
            $code = db_one('SELECT order_code FROM orders WHERE id = ?', [$orderId])['order_code'];
            $lines = '';
            foreach ($items as $it) $lines .= '- ' . $it['name'] . ' x' . $it['qty'] . ' = ' . format_rupiah($it['line_total']) . "\n";
            notify_admin(($type === 'quote' ? 'Permintaan penawaran ' : 'Pesanan baru ') . $code,
                $d['customer_name'] . ' (' . $d['customer_phone'] . ")\n" . $d['shipping_address'] . ', ' . $d['city'] . ' ' . $d['province'] . "\n\n" . $lines
                . 'Subtotal: ' . format_rupiah($subtotal) . "\n\n" . abs_url('admin/orders/detail.php?id=' . $orderId));
            if ($d['customer_email'] !== '') {
                send_mail($d['customer_email'], ($type === 'quote' ? 'Permintaan penawaran diterima ' : 'Pesanan diterima ') . $code . ' - ' . SITE_NAME,
                    'Halo ' . $d['customer_name'] . ",\n\nTerima kasih. " . ($type === 'quote' ? 'Permintaan penawaran' : 'Pesanan') . " Anda $code sudah kami terima.\n\n" . $lines
                    . "Subtotal: " . format_rupiah($subtotal) . "\nOngkos kirim dan cara pembayaran disepakati lewat chat WhatsApp dengan admin kami.\n\nLihat status: " . abs_url('account/order-detail.php?id=' . $orderId));
            }
            if (has_wa()) {
                // Orders are finished in a WhatsApp chat with the admin (shipping + payment are agreed there).
                flash_set('success', 'Pesanan ' . $code . ' tersimpan. Lanjutkan chat di WhatsApp agar admin bisa memberi info ongkir dan pembayaran. Jika WhatsApp tidak terbuka, tekan tombol "Lanjutkan ke WhatsApp" di bawah.', true);
                redirect(wa_link(order_wa_message(get_order($orderId), get_order_items($orderId))));
            }
            flash_set('success', $type === 'quote' ? 'Permintaan penawaran terkirim. Tim kami akan menghubungi Anda.' : 'Pesanan berhasil dibuat. Tim kami akan menghubungi Anda untuk ongkir dan pembayaran.');
            redirect('account/order-detail.php?id=' . $orderId);
        }
        $items = cart_items();                       // refresh (a product may have been removed)
        $subtotal = cart_subtotal($items);
        if (!$items) { redirect('cart.php'); }
    }
}

$page_title = 'Checkout | ' . SITE_NAME;
include APP_VIEWS . '/header.php';
$v = fn($k, $default = '') => e(is_string($_POST[$k] ?? null) ? $_POST[$k] : $default);
?>
<div class="page-head"><div class="container">
  <div class="breadcrumb"><a href="<?= url('cart.php') ?>">Keranjang</a> / Checkout</div>
  <h1 class="section-title">CHECKOUT</h1>
</div></div>

<div class="container" style="padding-bottom:4rem">
  <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
  <form method="post" class="cart-layout">
    <?= csrf_field() ?>
    <div>
      <div class="panel">
        <h3>JENIS PERMINTAAN</h3>
        <label class="check-label"><input type="radio" name="order_type" value="order" <?= ($_POST['order_type'] ?? 'order') === 'order' ? 'checked' : '' ?>> Buat pesanan (ongkir & pembayaran disepakati lewat WhatsApp)</label>
        <label class="check-label" style="margin-top:.5rem"><input type="radio" name="order_type" value="quote" <?= ($_POST['order_type'] ?? '') === 'quote' ? 'checked' : '' ?>> Minta penawaran harga (untuk pembelian proyek / jumlah besar)</label>
      </div>
      <div class="panel">
        <h3>DATA PENGIRIMAN</h3>
        <div class="form-group"><label>Nama Lengkap</label><input type="text" name="customer_name" required maxlength="100" value="<?= $v('customer_name', $user['name']) ?>"></div>
        <div class="form-row">
          <div class="form-group"><label>Nomor Telepon / WhatsApp</label><input type="tel" name="customer_phone" required maxlength="30" value="<?= $v('customer_phone', $user['phone'] ?? '') ?>"></div>
          <div class="form-group"><label>Email (opsional)</label><input type="email" name="customer_email" maxlength="150" value="<?= $v('customer_email', $user['email']) ?>"></div>
        </div>
        <div class="form-group"><label>Alamat Lengkap</label><textarea name="shipping_address" rows="3" required maxlength="1000"><?= $v('shipping_address') ?></textarea></div>
        <div class="form-row">
          <div class="form-group"><label>Provinsi</label><input type="text" name="province" maxlength="80" value="<?= $v('province') ?>"></div>
          <div class="form-group"><label>Kota / Kabupaten</label><input type="text" name="city" maxlength="80" value="<?= $v('city') ?>"></div>
        </div>
        <div class="form-group"><label>Catatan (opsional)</label><textarea name="notes" rows="2" maxlength="1000"><?= $v('notes') ?></textarea></div>
      </div>
    </div>

    <aside class="summary">
      <h3>PESANAN ANDA</h3>
      <?php foreach ($items as $it): ?>
        <div class="summary-row"><span><?= e($it['name']) ?> × <?= (int)$it['qty'] ?></span><span><?= format_rupiah($it['line_total']) ?></span></div>
      <?php endforeach; ?>
      <div class="summary-row summary-total"><span>TOTAL</span><span><?= format_rupiah($subtotal) ?></span></div>
      <div class="form-hint">Ongkos kirim dan cara pembayaran disepakati dengan admin lewat WhatsApp.</div>
      <button class="btn btn-primary btn-block mt-2" type="submit"><?= has_wa() ? '<i class="fa-brands fa-whatsapp"></i> KIRIM &amp; LANJUT KE WHATSAPP' : 'KIRIM' ?></button>
    </aside>
  </form>
</div>
<?php include APP_VIEWS . '/footer.php'; ?>
