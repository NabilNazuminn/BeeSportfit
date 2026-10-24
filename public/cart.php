<?php
/** SHOPPING CART. POST actions: add | update | remove. GET shows the cart. */
require_once __DIR__ . '/../app/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $ok = cart_add((int)($_POST['product_id'] ?? 0), (int)($_POST['qty'] ?? 1));
        flash_set($ok ? 'success' : 'error', $ok ? 'Produk ditambahkan ke keranjang.' : 'Produk tidak tersedia.');
        redirect(safe_next($_POST['back'] ?? '', 'cart.php'));
    }
    if ($action === 'update') {
        foreach (is_array($_POST['qty'] ?? null) ? $_POST['qty'] : [] as $pid => $qty) cart_set((int)$pid, is_scalar($qty) ? (int)$qty : 0);
        flash_set('success', 'Keranjang diperbarui.');
    }
    if ($action === 'remove') {
        cart_remove((int)($_POST['product_id'] ?? 0));
        flash_set('info', 'Produk dihapus dari keranjang.');
    }
    redirect('cart.php');
}

$items    = cart_items();
$subtotal = cart_subtotal($items);

// WhatsApp = ORDERING: pre-written message listing the cart
$waText = SITE_NAME . ' - saya ingin memesan:' . "\n";
foreach ($items as $it) $waText .= '- ' . $it['name'] . ' x' . $it['qty'] . ' = ' . format_rupiah($it['line_total']) . "\n";
$waText .= 'Total: ' . format_rupiah($subtotal) . '. Mohon info ongkir dan cara pembayaran.';

$page_title = 'Keranjang | ' . SITE_NAME;
$noindex = true;
include APP_VIEWS . '/header.php';
?>
<div class="page-head"><div class="container">
  <div class="breadcrumb"><a href="<?= url('index.php') ?>">Home</a> / Keranjang</div>
  <h1 class="section-title">KERANJANG BELANJA</h1>
</div></div>

<div class="container" style="padding-bottom:4rem">
<?php if (!$items): ?>
  <div class="empty-state"><i class="fa-solid fa-cart-shopping"></i><p>Keranjang Anda masih kosong.</p>
    <a class="btn btn-primary" href="<?= url('products.php') ?>">LIHAT KATALOG</a></div>
<?php else: ?>
  <div class="cart-layout">
    <form method="post" id="cart-form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update">
      <?php foreach ($items as $it): ?>
        <div class="cart-row">
          <img src="<?= e(image_url($it['image'])) ?>" alt="">
          <div>
            <a href="<?= url('product.php?slug=' . urlencode($it['slug'])) ?>"><h3 style="font-size:1.1rem;margin-bottom:.2rem"><?= e($it['name']) ?></h3></a>
            <div class="text-muted" style="font-size:.85rem"><?= format_rupiah($it['unit_price']) ?> / unit</div>
            <div class="cart-row-controls">
              <div class="qty"><button type="button" data-qty="-1">−</button>
                <input type="number" name="qty[<?= (int)$it['id'] ?>]" value="<?= (int)$it['qty'] ?>" min="0" max="999">
                <button type="button" data-qty="1">+</button></div>
              <strong class="text-primary" style="font-family:var(--font-heading)"><?= format_rupiah($it['line_total']) ?></strong>
              <button class="btn btn-danger btn-sm" type="submit" form="rm-<?= (int)$it['id'] ?>"><i class="fa-solid fa-trash"></i></button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <button class="btn btn-ghost btn-sm" type="submit"><i class="fa-solid fa-rotate"></i> PERBARUI KERANJANG</button>
    </form>

    <?php /* separate tiny forms for "remove" (forms can't be nested) */ foreach ($items as $it): ?>
      <form id="rm-<?= (int)$it['id'] ?>" method="post" style="display:none">
        <?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="product_id" value="<?= (int)$it['id'] ?>">
      </form>
    <?php endforeach; ?>

    <aside class="summary">
      <h3>RINGKASAN</h3>
      <div class="summary-row"><span>Subtotal</span><span><?= format_rupiah($subtotal) ?></span></div>
      <div class="summary-row"><span>Ongkos kirim</span><span class="text-muted">Disepakati via WhatsApp</span></div>
      <div class="summary-row summary-total"><span>TOTAL</span><span><?= format_rupiah($subtotal) ?></span></div>
      <a class="btn btn-primary btn-block mt-2" href="<?= url('checkout.php') ?>">LANJUT KE CHECKOUT</a>
      <?php if (has_wa()): ?><a class="btn btn-ghost btn-block mt-1" target="_blank" rel="noopener" href="<?= e(wa_link($waText)) ?>"><i class="fa-brands fa-whatsapp"></i> PESAN VIA WHATSAPP</a><?php endif; ?>
    </aside>
  </div>
<?php endif; ?>
</div>
<?php include APP_VIEWS . '/footer.php'; ?>
