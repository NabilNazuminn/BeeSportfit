<?php
/**
 * SHARED HEADER (top of every public page).
 * Before including, a page can set:
 *   $page_title  - text for the browser tab
 *   $active      - which menu item is highlighted: home|kategori|katalog|tentang|kontak
 */
$page_title = $page_title ?? SITE_NAME;
$active     = $active ?? '';
$user       = current_user();
$cartCount  = cart_count();
$nav = [
    'home'     => ['HOME',          url('index.php#home')],
    'kategori' => ['KATEGORI',      url('index.php#kategori')],
    'katalog'  => ['KATALOG ALAT',  url('products.php')],
    'tentang'  => ['TENTANG KAMI',  url('index.php#tentang')],
    'kontak'   => ['KONTAK',        url('index.php#kontak')],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($page_title) ?></title>
  <meta name="theme-color" content="#0E0D0B">
  <?php include __DIR__ . '/theme_init.php'; ?>
  <link rel="icon" type="image/png" href="<?= asset('img/favicon.png') ?>">
  <link rel="apple-touch-icon" href="<?= asset('img/apple-touch-icon.png') ?>">
  <?php $__desc = $meta_description ?? 'BeeSportFit - supplier alat gym impor untuk gym komersial, hotel, dan home gym di Indonesia.'; ?>
  <meta name="description" content="<?= e($__desc) ?>">
  <meta property="og:site_name" content="<?= e(SITE_NAME) ?>">
  <meta property="og:type" content="<?= e($meta_type ?? 'website') ?>">
  <meta property="og:title" content="<?= e($page_title) ?>">
  <meta property="og:description" content="<?= e($__desc) ?>">
  <meta property="og:image" content="<?= e($meta_image ?? abs_url('assets/img/logo.png')) ?>">
  <meta property="og:url" content="<?= e(abs_url(ltrim(substr((string)strtok($_SERVER['REQUEST_URI'] ?? '/', '?'), strlen(base_url())), '/'))) ?>">
  <?php if (!empty($noindex)): ?><meta name="robots" content="noindex,follow"><?php endif; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Oswald:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
</head>
<body>
<header class="site-header">
  <div class="container header-inner">
    <a class="brand" href="<?= url('index.php') ?>">
      <img class="brand-logo logo-emblem" src="<?= asset('img/logo-emblem.png') ?>" alt="" width="80" height="46">
      <span class="brand-text">
        <span class="brand-name brand-word"><span class="bee">Bee</span>SportFit</span>
        <span class="brand-tag"><?= e(SITE_TAGLINE) ?></span>
      </span>
    </a>

    <nav class="main-nav">
      <?php foreach ($nav as $key => [$label, $href]): ?>
        <a href="<?= $href ?>" class="<?= $active === $key ? 'active' : '' ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="nav-actions">
      <button type="button" class="icon-btn" data-search-toggle aria-label="Cari" title="Cari produk, kategori, brand, atau halaman" aria-expanded="false">
        <i class="fa-solid fa-magnifying-glass"></i>
      </button>
      <button type="button" class="icon-btn" data-navigation-open aria-label="Navigasi" title="Buka navigasi">
        <i class="fa-solid fa-compass"></i>
      </button>
      <button type="button" class="icon-btn theme-toggle" data-theme-toggle aria-label="Ganti ke mode terang" title="Mode terang / gelap">
        <i class="fa-solid fa-sun"></i><i class="fa-solid fa-moon"></i>
      </button>
      <?php if ($user): ?>
        <a class="icon-btn" href="<?= url($user['role'] === 'admin' ? 'admin/index.php' : 'account/orders.php') ?>" title="<?= e($user['name']) ?>"><i class="fa-solid fa-user"></i></a>
      <?php else: ?>
        <a class="icon-btn" href="<?= url('login.php') ?>" aria-label="Login"><i class="fa-regular fa-user"></i></a>
      <?php endif; ?>
      <a class="icon-btn" href="<?= url('cart.php') ?>" aria-label="Keranjang">
        <i class="fa-solid fa-cart-shopping"></i>
        <span class="cart-badge"><?= (int)$cartCount ?></span>
      </a>
      <button class="icon-btn nav-toggle" id="nav-toggle" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
    </div>
  </div>
  <section class="header-search-panel" data-search-panel hidden>
    <div class="container">
      <form class="header-search-form" action="<?= url('search.php') ?>" method="get" role="search">
        <label class="sr-only" for="header-search-input">Cari produk, kategori, brand, atau halaman</label>
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        <input id="header-search-input" type="search" name="q" placeholder="Cari produk, kategori, brand, atau halaman..." autocomplete="off" data-search-input data-preview-endpoint="<?= e(url('search-preview.php')) ?>">
        <button class="btn btn-primary btn-sm" type="submit">CARI</button>
        <button class="icon-btn" type="button" data-search-close aria-label="Tutup pencarian"><i class="fa-solid fa-xmark"></i></button>
      </form>
      <div class="search-preview" data-search-preview hidden aria-live="polite"></div>
    </div>
  </section>
  <div class="mobile-menu" id="mobile-menu">
    <?php foreach ($nav as $key => [$label, $href]): ?>
      <a href="<?= $href ?>"><?= $label ?></a>
    <?php endforeach; ?>
    <?php if ($user): ?>
      <form method="post" action="<?= url('logout.php') ?>"><?= csrf_field() ?><button type="submit" class="linklike">LOGOUT</button></form>
    <?php endif; ?>
  </div>
</header>
<dialog class="navigation-dialog" data-navigation-dialog aria-labelledby="navigation-title">
  <div class="navigation-dialog-head"><h2 id="navigation-title">NAVIGASI</h2><button type="button" class="icon-btn" data-navigation-close aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button></div>
  <p class="text-muted">Pilih tujuan navigasi.</p>
  <button type="button" class="btn btn-ghost btn-block" data-dialog-back data-back-fallback="<?= url('index.php') ?>"><i class="fa-solid fa-arrow-left"></i> KEMBALI KE HALAMAN SEBELUMNYA</button>
  <a class="btn btn-primary btn-block mt-1" href="<?= url('index.php') ?>"><i class="fa-solid fa-house"></i> BERANDA</a>
</dialog>
<?php if (!empty($_SESSION['flash'])): ?>
  <div class="container flash-wrap"><?php flash_render(); ?></div>
<?php endif; ?>
<main>
