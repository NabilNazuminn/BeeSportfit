<?php
/**
 * ADMIN LAYOUT (top). Every admin page does:
 *   require_once __DIR__ . '/../../app/config.php';   (path depends on depth)
 *   require_admin();
 *   $page_title = 'Produk'; $active = 'products';
 *   include APP_VIEWS . '/admin_header.php';
 *   ... page content ...
 *   include APP_VIEWS . '/admin_footer.php';
 */
$page_title = $page_title ?? 'Admin';
$active     = $active ?? '';
$adminNav = [
    'dashboard'     => ['Dashboard',     'admin/index.php',               'fa-gauge'],
    'products'      => ['Produk',        'admin/products/index.php',      'fa-dumbbell'],
    'categories'    => ['Kategori',      'admin/categories/index.php',    'fa-layer-group'],
    'brands'        => ['Brand',         'admin/brands/index.php',        'fa-tags'],
    'orders'        => ['Pesanan',       'admin/orders/index.php',        'fa-receipt'],
    'customers'     => ['Pelanggan',     'admin/customers/index.php',     'fa-users'],
    'chat'          => ['Live Chat',     'admin/chat/index.php',          'fa-message'],
    'consultations' => ['Konsultasi',    'admin/consultations/index.php', 'fa-comments'],
    'testimonials'  => ['Testimoni',     'admin/testimonials/index.php',  'fa-quote-left'],
    'pages'         => ['Halaman',       'admin/pages/edit.php',          'fa-file-lines'],
    'settings'      => ['Pengaturan',    'admin/settings.php',            'fa-gear'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <?php include __DIR__ . '/theme_init.php'; ?>
  <link rel="icon" type="image/png" href="<?= asset('img/favicon.png') ?>">
  <title><?= e($page_title) ?> | Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Oswald:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="admin-layout">
  <aside class="admin-side">
    <a class="admin-brand" href="<?= url('admin/index.php') ?>"><img class="logo-emblem" src="<?= asset('img/logo-emblem.png') ?>" alt="<?= e(SITE_NAME) ?>"><span>ADMIN PANEL</span></a>
    <nav>
      <?php foreach ($adminNav as $key => [$label, $href, $icon]): ?>
        <a href="<?= url($href) ?>" class="<?= $active === $key ? 'active' : '' ?>"><i class="fa-solid <?= $icon ?>"></i> <?= $label ?>
          <?php if ($key === 'chat'): $unreadChats = chat_unread_for_admin(); ?>
            <b class="nav-badge" id="chat-nav-badge" data-endpoint="<?= e(url('admin/chat/api.php')) ?>" <?= $unreadChats ? '' : 'hidden' ?>><?= (int)$unreadChats ?></b>
          <?php endif; ?></a>
      <?php endforeach; ?>
      <a href="<?= url('index.php') ?>" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> Lihat Website</a>
      <form method="post" action="<?= url('logout.php') ?>"><?= csrf_field() ?><button type="submit" class="linklike"><i class="fa-solid fa-right-from-bracket"></i> Logout</button></form>
    </nav>
    <button type="button" class="admin-theme" data-theme-toggle aria-label="Ganti mode terang/gelap"><i class="fa-solid fa-circle-half-stroke"></i> Mode Terang / Gelap</button>
  </aside>
  <div class="admin-main">
    <div class="admin-toolbar" style="display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:1rem">
      <button type="button" class="btn btn-ghost btn-sm" data-navigation-open><i class="fa-solid fa-compass"></i> Navigasi</button>
      <a class="btn btn-ghost btn-sm" href="<?= url('admin/index.php') ?>"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    </div>
    <?php flash_render(); ?>
    <dialog class="navigation-dialog" data-navigation-dialog aria-labelledby="navigation-title">
      <div class="navigation-dialog-head"><h2 id="navigation-title">NAVIGASI ADMIN</h2><button type="button" class="icon-btn" data-navigation-close aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button></div>
      <p class="text-muted">Pilih tujuan navigasi.</p>
      <button type="button" class="btn btn-ghost btn-block" data-dialog-back data-back-fallback="<?= url('admin/index.php') ?>"><i class="fa-solid fa-arrow-left"></i> KEMBALI KE HALAMAN SEBELUMNYA</button>
      <a class="btn btn-primary btn-block mt-1" href="<?= url('admin/index.php') ?>"><i class="fa-solid fa-gauge"></i> DASHBOARD</a>
    </dialog>
