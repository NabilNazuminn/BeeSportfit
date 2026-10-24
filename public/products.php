<?php
/** CATALOG: search + category/brand filter + sorting + pagination. */
require_once __DIR__ . '/../app/config.php';

$filters = [
    'q'        => input_str($_GET, 'q'),
    'category' => input_str($_GET, 'category'),
    'brand'    => input_str($_GET, 'brand'),
    'sort'     => input_str($_GET, 'sort', 'newest'),
    'page'     => request_int('page'),
];
$result     = get_products($filters);
$categories = get_categories();
$brands     = get_brands();

$page_title = 'Katalog Alat Gym Impor | ' . SITE_NAME;
$active     = 'katalog';
include APP_VIEWS . '/header.php';
?>
<div class="page-head"><div class="container">
  <div class="breadcrumb"><a href="<?= url('index.php') ?>">Home</a> / Katalog</div>
  <h1 class="section-title">KATALOG ALAT GYM</h1>
  <p class="text-muted mb-0"><?= (int)$result['total'] ?> produk ditemukan</p>
</div></div>

<div class="container" style="padding-bottom:4rem">
  <div class="pills">
    <a class="pill <?= $filters['category'] === '' ? 'active' : '' ?>" href="<?= e(query_with(['category' => '', 'page' => ''])) ?>">Semua</a>
    <?php foreach ($categories as $c): ?>
      <a class="pill <?= $filters['category'] === $c['slug'] ? 'active' : '' ?>" href="<?= e(query_with(['category' => $c['slug'], 'page' => ''])) ?>"><?= e($c['name']) ?></a>
    <?php endforeach; ?>
  </div>

  <?php $brandChips = get_brands_with_logos(); if ($brandChips): ?>
  <div class="brand-chips" aria-label="Filter brand">
    <a class="brand-chip brand-chip-all <?= (string)$filters['brand'] === '' ? 'active' : '' ?>" href="<?= e(query_with(['brand' => '', 'page' => ''])) ?>">Semua Brand</a>
    <?php foreach ($brandChips as $b): ?>
      <a class="brand-chip <?= (string)$filters['brand'] === (string)$b['id'] ? 'active' : '' ?>" href="<?= e(query_with(['brand' => $b['id'], 'page' => ''])) ?>" title="<?= e($b['name']) ?>"><?= brand_logo_html($b) ?></a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form class="filter-bar" method="get">
    <input type="hidden" name="category" value="<?= e($filters['category']) ?>">
    <div><label for="q">Cari Produk</label><input type="search" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="Nama atau kode produk..."></div>
    <div><label for="brand">Brand</label>
      <select id="brand" name="brand">
        <option value="">Semua Brand</option>
        <?php foreach ($brands as $b): ?>
          <option value="<?= (int)$b['id'] ?>" <?= (string)$filters['brand'] === (string)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
        <?php endforeach; ?>
      </select></div>
    <div><label for="sort">Urutkan</label>
      <select id="sort" name="sort">
        <?php foreach (['newest' => 'Terbaru', 'price_asc' => 'Harga Terendah', 'price_desc' => 'Harga Tertinggi', 'name' => 'Nama A-Z'] as $v => $l): ?>
          <option value="<?= $v ?>" <?= $filters['sort'] === $v ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select></div>
    <div></div>
    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> CARI</button>
  </form>

  <?php if ($result['items']): ?>
    <div class="product-grid">
      <?php foreach ($result['items'] as $product) include APP_VIEWS . '/product_card.php'; ?>
    </div>

    <?php if ($result['pages'] > 1): ?>
      <nav class="pagination" aria-label="Halaman">
        <?php if ($result['page'] > 1): ?><a href="<?= e(query_with(['page' => $result['page'] - 1])) ?>">&laquo;</a><?php endif; ?>
        <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
          <?php if ($i === $result['page']): ?><span class="current"><?= $i ?></span>
          <?php else: ?><a href="<?= e(query_with(['page' => $i])) ?>"><?= $i ?></a><?php endif; ?>
        <?php endfor; ?>
        <?php if ($result['page'] < $result['pages']): ?><a href="<?= e(query_with(['page' => $result['page'] + 1])) ?>">&raquo;</a><?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php else: ?>
    <div class="empty-state"><i class="fa-solid fa-magnifying-glass"></i><p>Produk tidak ditemukan. Coba kata kunci atau filter lain.</p></div>
  <?php endif; ?>
</div>
<?php include APP_VIEWS . '/footer.php'; ?>
