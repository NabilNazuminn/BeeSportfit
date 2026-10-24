<?php
/**
 * ONE product card. Use inside a loop:
 *   foreach ($products as $product) include __DIR__ . '/../app/views/product_card.php';
 * Needs $product (a row from get_products()).
 */
$pDiscount = has_discount($product);
?>
<article class="product-card">
  <a class="product-photo" href="<?= url('product.php?slug=' . urlencode($product['slug'])) ?>">
    <?php if ($pDiscount): ?><span class="product-flag"><?= discount_percent($product) ?>% OFF</span><?php endif; ?>
    <?php if ($product['stock_status'] === 'pre_order'): ?><span class="product-flag pre">PRE-ORDER</span><?php endif; ?>
    <img src="<?= e(image_url($product['image'] ?? null)) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
    <div class="product-spec">
      <strong><?= e(($product['sku'] ? $product['sku'] . ' | ' : '') . $product['name']) ?></strong>
      <div><span><?= e($product['brand_name'] ?? '-') ?></span><span><?= e($product['category_name'] ?? '') ?></span></div>
    </div>
  </a>
  <div class="product-body">
    <div>
      <h3 class="product-name"><a href="<?= url('product.php?slug=' . urlencode($product['slug'])) ?>"><?= e($product['name']) ?></a></h3>
      <?php if ((float)$product['price'] > 0): ?>
        <?php if ($pDiscount): ?><span class="price-old"><?= format_rupiah($product['price']) ?></span><?php endif; ?>
        <span class="price-now <?= $pDiscount ? '' : 'normal' ?>"><?= format_rupiah(final_price($product)) ?></span>
      <?php endif; ?>
    </div>
    <div class="product-actions">
      <a class="btn btn-dark btn-sm" href="<?= url('product.php?slug=' . urlencode($product['slug'])) ?>">Lihat Detail</a>
      <?php if ($product['stock_status'] !== 'out_of_stock' && (float)$product['price'] > 0): ?>
        <form method="post" action="<?= url('cart.php') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
          <input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
          <button class="btn btn-primary btn-sm" type="submit" aria-label="Tambah ke keranjang"><i class="fa-solid fa-cart-plus"></i></button>
        </form>
      <?php elseif ($product['stock_status'] === 'out_of_stock'): ?>
        <span class="badge badge-red" style="margin:0">HABIS</span>
      <?php endif; ?>
    </div>
  </div>
</article>
