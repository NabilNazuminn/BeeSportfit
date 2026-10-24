<?php
/** PRODUCT DETAIL  (product.php?slug=...) */
require_once __DIR__ . '/../app/config.php';

$product = get_product_by_slug($_GET['slug'] ?? '');   // returns null for anything that is not plain text
if (!$product) {
    http_response_code(404);
    $page_title = 'Produk tidak ditemukan';
    include APP_VIEWS . '/header.php';
    echo '<div class="container"><div class="empty-state mt-3"><i class="fa-solid fa-triangle-exclamation"></i><p>Produk tidak ditemukan.</p></div></div>';
    include APP_VIEWS . '/footer.php';
    exit;
}

$images  = get_product_images((int)$product['id']);
$related = get_related_products($product);
$disc    = has_discount($product);
$mainImg = image_url($images[0]['image_path'] ?? null);

$page_title       = $product['name'] . ' | ' . SITE_NAME;
$meta_description = $product['short_description'] ?: 'Beli ' . $product['name'] . ' di ' . SITE_NAME;
$meta_image       = $images ? abs_url('uploads/' . ltrim($images[0]['image_path'], '/')) : null;
$meta_type        = 'product';
$jsonld = [
    '@context' => 'https://schema.org', '@type' => 'Product', 'name' => $product['name'],
    'description' => $product['short_description'] ?: $product['name'], 'sku' => $product['sku'] ?: null,
    'brand' => $product['brand_name'] ? ['@type' => 'Brand', 'name' => $product['brand_name']] : null,
    'image' => $meta_image,
];
if ((float)$product['price'] > 0) {
    $jsonld['offers'] = ['@type' => 'Offer', 'priceCurrency' => 'IDR', 'price' => (string)round(final_price($product)),
        'url' => abs_url('product.php?slug=' . urlencode($product['slug'])),
        'availability' => 'https://schema.org/' . ['in_stock' => 'InStock', 'pre_order' => 'PreOrder', 'out_of_stock' => 'OutOfStock'][$product['stock_status']]];
}
$jsonld = array_filter($jsonld, fn($v) => $v !== null);
$active           = 'katalog';
include APP_VIEWS . '/header.php';
?>
<div class="page-head"><div class="container">
  <div class="breadcrumb">
    <a href="<?= url('index.php') ?>">Home</a> /
    <a href="<?= url('products.php') ?>">Katalog</a> /
    <a href="<?= url('products.php?category=' . urlencode($product['category_slug'])) ?>"><?= e($product['category_name']) ?></a>
  </div>
  <h1 class="section-title" style="font-size:2rem"><?= e($product['name']) ?></h1>
</div></div>

<div class="container" style="padding-bottom:4rem">
  <div class="detail-grid">
    <!-- Gallery -->
    <div>
      <div class="gallery-main"><img id="gallery-main-img" src="<?= e($mainImg) ?>" alt="<?= e($product['name']) ?>"></div>
      <?php if (count($images) > 1): ?>
        <div class="gallery-thumbs">
          <?php foreach ($images as $i => $img): ?>
            <button type="button" class="<?= $i === 0 ? 'active' : '' ?>" data-gallery-src="<?= e(image_url($img['image_path'])) ?>"><img src="<?= e(image_url($img['image_path'])) ?>" alt=""></button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Info -->
    <div>
      <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem">
        <?php if ($disc): ?><span class="badge badge-red" style="margin:0"><?= discount_percent($product) ?>% OFF</span><?php endif; ?>
        <?php if ($product['stock_status'] === 'pre_order'): ?><span class="badge" style="margin:0">PRE-ORDER</span><?php endif; ?>
        <?php if ($product['stock_status'] === 'out_of_stock'): ?><span class="badge badge-gray" style="margin:0">STOK HABIS</span><?php endif; ?>
      </div>

      <div class="detail-price">
        <?php if ((float)$product['price'] > 0): ?>
          <?php if ($disc): ?><span class="price-old"><?= format_rupiah($product['price']) ?></span><?php endif; ?>
          <span class="price-now <?= $disc ? '' : 'normal' ?>"><?= format_rupiah(final_price($product)) ?></span>
        <?php endif; ?>
        <?php if ($disc && $product['discount_ends_at']): ?>
          <div class="form-hint">Promo berlaku sampai <?= e(date('d M Y', strtotime($product['discount_ends_at']))) ?></div>
        <?php endif; ?>
      </div>

      <?php if ($product['short_description']): ?><p class="text-muted mt-2"><?= e($product['short_description']) ?></p><?php endif; ?>

      <ul class="meta-list">
        <?php if ($product['sku']): ?><li><span>Kode</span><span><?= e($product['sku']) ?></span></li><?php endif; ?>
        <?php if ($product['brand_name']): ?><li><span>Brand</span><span>
          <?php if (!empty($product['brand_logo'])): ?>
            <a class="brand-badge" href="<?= url('products.php?brand=' . (int)$product['brand_id']) ?>" title="Lihat semua produk <?= e($product['brand_name']) ?>">
              <?= brand_logo_html(['name' => $product['brand_name'], 'logo' => $product['brand_logo'], 'logo_white' => $product['brand_logo_white']]) ?></a>
          <?php else: ?><?= e($product['brand_name']) ?><?php endif; ?></span></li><?php endif; ?>
        <li><span>Kategori</span><span><?= e($product['category_name']) ?></span></li>
        <?php if ($product['warranty_months']): ?><li><span>Garansi</span><span><?= (int)$product['warranty_months'] ?> bulan</span></li><?php endif; ?>
        <li><span>Ketersediaan</span><span><?= ['in_stock' => 'Tersedia', 'pre_order' => 'Pre-Order', 'out_of_stock' => 'Habis'][$product['stock_status']] ?></span></li>
      </ul>

      <?php if ($product['stock_status'] !== 'out_of_stock' && (float)$product['price'] > 0): ?>
        <form method="post" action="<?= url('cart.php') ?>" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
          <input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI']) ?>">
          <div class="qty"><button type="button" data-qty="-1">−</button><input type="number" name="qty" value="1" min="1" max="<?= cart_max_qty($product) ?>"><button type="button" data-qty="1">+</button></div>
          <button class="btn btn-primary" type="submit"><i class="fa-solid fa-cart-plus"></i> TAMBAH KE KERANJANG</button>
        </form>
      <?php endif; ?>
      <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-top:1rem">
        <?php if (chat_enabled()): ?>
          <button type="button" class="btn btn-ghost" data-chat-open data-chat-message="<?= e('Halo, saya ingin bertanya tentang ' . $product['name'] . '.') ?>">
            <i class="fa-solid fa-comments"></i> TANYA VIA CHAT</button>
        <?php endif; ?>
        <?php if (has_wa() && $product['stock_status'] !== 'out_of_stock' && (float)$product['price'] > 0): ?>
          <a class="btn btn-ghost" target="_blank" rel="noopener"
             href="<?= e(wa_link('Halo ' . SITE_NAME . ', saya ingin memesan: ' . $product['name'] . ' (' . format_rupiah(final_price($product)) . '). Mohon info total, ongkir, dan cara pembayaran.')) ?>">
            <i class="fa-brands fa-whatsapp"></i> PESAN VIA WHATSAPP</a>
        <?php endif; ?>
        <?php if (has_wa() && (float)$product['price'] <= 0): ?>
          <a class="btn btn-primary" target="_blank" rel="noopener" href="<?= e(wa_link('Halo ' . SITE_NAME . ', saya ingin bertanya harga untuk: ' . $product['name'] . '.')) ?>">
            <i class="fa-brands fa-whatsapp"></i> TANYA HARGA VIA WHATSAPP</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Description & specs -->
  <?php if ($product['description'] || $product['specifications']): ?>
  <div class="tabs-box">
    <?php if ($product['description']): ?>
      <h2 style="font-size:1.4rem">DESKRIPSI</h2>
      <div class="prose"><?= nl2br(e($product['description'])) ?></div>
    <?php endif; ?>
    <?php if ($product['specifications']): ?>
      <h2 style="font-size:1.4rem;margin-top:2rem">SPESIFIKASI</h2>
      <ul class="meta-list">
        <?php foreach (preg_split('/\r?\n/', trim($product['specifications'])) as $line):
              if (trim($line) === '') continue;
              if (strpos($line, ':') !== false): [$k, $v] = array_map('trim', explode(':', $line, 2)); ?>
          <li><span><?= e($k) ?></span><span><?= e($v) ?></span></li>
        <?php else: ?>
          <li><span><?= e($line) ?></span><span></span></li>
        <?php endif; endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if ($related): ?>
    <div class="section-head mt-3" style="margin-top:4rem"><div class="eyebrow">YOU MAY ALSO LIKE</div><h2 class="section-title" style="font-size:1.8rem">PRODUK TERKAIT</h2></div>
    <div class="product-grid">
      <?php foreach ($related as $product) include APP_VIEWS . '/product_card.php'; ?>
    </div>
  <?php endif; ?>
</div>
<script type="application/ld+json"><?= json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?php include APP_VIEWS . '/footer.php'; ?>
