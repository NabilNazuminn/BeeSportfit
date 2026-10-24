<?php
/** Search results preview for categories, brands, and public pages. */
require_once __DIR__ . '/../app/config.php';

$query = input_str($_GET, 'q');
$results = search_content($query);
$page_title = 'Hasil Pencarian | ' . SITE_NAME;
include APP_VIEWS . '/header.php';
?>
<div class="page-head"><div class="container">
  <div class="breadcrumb"><a href="<?= url('index.php') ?>">Home</a> / Pencarian</div>
  <h1 class="section-title">HASIL PENCARIAN</h1>
  <p class="text-muted mb-0"><?= $query !== '' ? 'Kata kunci: ' . e($query) : 'Masukkan kata kunci melalui kolom pencarian.' ?></p>
</div></div>
<div class="container" style="padding-bottom:4rem">
  <?php if (text_len($query) < 2): ?>
    <div class="empty-state"><i class="fa-solid fa-magnifying-glass"></i><p>Masukkan minimal 2 karakter untuk mencari produk, kategori, brand, atau halaman.</p></div>
  <?php else: ?>
    <?php $hasResults = false; foreach (['products' => 'PRODUK', 'categories' => 'KATEGORI', 'brands' => 'BRAND', 'pages' => 'HALAMAN'] as $type => $heading): ?>
      <?php if ($results[$type]): $hasResults = true; ?>
        <section class="search-results-section">
          <h2 class="section-title"><?= $heading ?></h2>
          <div class="search-results-grid">
            <?php foreach ($results[$type] as $item): ?>
              <a class="search-result-card" href="<?= e($item['url']) ?>"><span><?= e($item['label']) ?></span><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>
    <?php endforeach; ?>
    <?php if (!$hasResults): ?><div class="empty-state"><i class="fa-solid fa-magnifying-glass"></i><p>Tidak ada produk, kategori, brand, atau halaman yang cocok. <a class="text-primary" href="<?= url('products.php') ?>">Lihat semua produk</a></p></div><?php endif; ?>
  <?php endif; ?>
</div>
<?php include APP_VIEWS . '/footer.php'; ?>
