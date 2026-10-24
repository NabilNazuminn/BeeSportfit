<?php
/** HOME / LANDING PAGE. Sections: hero, about, categories, featured products, testimonials, contact. */
require_once __DIR__ . '/../app/config.php';

$page_title = SITE_NAME . ' | Supplier Alat Gym Impor Indonesia';
$active     = 'home';

$categories   = get_categories();
$featured     = get_products(['featured' => true, 'per_page' => 3])['items'];
if (!$featured) $featured = get_products(['per_page' => 3])['items'];   // fallback: newest
$testimonials = get_testimonials();

// Icon for each category (key = category slug). Add your own here.
$catIcons = ['beban' => 'fa-dumbbell', 'cardio' => 'fa-heart-pulse', 'strength' => 'fa-person-walking',
             'recovery' => 'fa-spa', 'reformer-pilates' => 'fa-child-reaching'];

include APP_VIEWS . '/header.php';
?>

<!-- ============ HERO ============ -->
<section class="hero" id="home">
  <div class="hero-bg"></div>
  <div class="container">
    <div class="hero-inner">
      <h2 class="hero-kicker">IMPORTIR ALAT GYM BERKUALITAS</h2>
      <div class="hero-title-wrap"><h1 class="hero-title brand-word"><span class="bee">Bee</span>SportFit</h1></div>
      <p class="hero-text">Membangun gym baru, menyediakan sauna, ice bath, body composition, maupun Hyrox.
      Pembelian partai besar dan juga eceran.</p>
      <a class="btn btn-primary" href="<?= url('products.php') ?>" style="font-size:1.1rem;padding:16px 32px">JELAJAHI KATALOG <i class="fa-solid fa-arrow-right"></i></a>

      <div class="metrics">
        <?php /* Edit these 3 numbers + labels in Admin > Pengaturan */ ?>
        <div><div class="metric-value"><?= e(get_setting('stat_products', '100%')) ?></div><div class="metric-label"><?= e(get_setting('stat_products_label', 'PRODUK IMPOR')) ?></div></div>
        <div><div class="metric-value"><?= e(get_setting('stat_gyms', 'GRATIS')) ?></div><div class="metric-label"><?= e(get_setting('stat_gyms_label', 'KONSULTASI LAYOUT')) ?></div></div>
        <div><div class="metric-value"><?= e(get_setting('stat_provinces', '38')) ?></div><div class="metric-label"><?= e(get_setting('stat_provinces_label', 'PROVINSI JANGKAUAN KIRIM')) ?></div></div>
      </div>
    </div>
  </div>
</section>

<!-- ============ BRAND STRIP (logos come from Admin > Brand) ============ -->
<?php $brandLogos = get_brands_with_logos(); if ($brandLogos): ?>
<section class="brand-strip" id="brand" aria-label="Brand impor">
  <div class="container reveal">
    <div class="brand-strip-label">BRAND IMPOR KAMI</div>
    <div class="brand-strip-row">
      <?php foreach ($brandLogos as $b): ?>
        <a class="brand-strip-item" href="<?= url('products.php?brand=' . (int)$b['id']) ?>" title="Lihat produk <?= e($b['name']) ?>"><?= brand_logo_html($b) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ============ ABOUT ============ -->
<section class="section section-grid" id="tentang" style="border-top:1px solid var(--border-soft);border-bottom:1px solid var(--border-soft)">
  <div class="container about-grid reveal">
    <div>
      <div class="about-badge">
        <img class="logo-emblem" src="<?= asset('img/logo-emblem.png') ?>" alt="<?= e(SITE_NAME) ?>" style="width:200px;height:auto;margin-bottom:.75rem">
        <div class="text-primary" style="font-family:var(--font-heading);font-weight:700;letter-spacing:.2em;font-size:.8rem">IMPORT GYM EQUIPMENT</div>
      </div>
    </div>
    <div>
      <h2 class="section-title text-primary brand-word">BeeSportFit</h2>
      <p class="text-muted" style="font-family:var(--font-heading);letter-spacing:.15em;text-transform:uppercase;font-weight:600;margin-bottom:2rem">MITRA ALAT GYM IMPOR ANDA</p>
      <ul class="about-list">
        <li><span>Menyediakan <strong>alat gym impor</strong> untuk <strong>gym komersial</strong>, hotel &amp; apartemen, kantor, hingga <strong>home gym</strong>.</span></li>
        <li><span>Produk pilihan dari <strong>brand internasional</strong> dengan kualitas teruji dan harga bersaing.</span></li>
        <li><span>Dari <strong>konsultasi layout</strong>, pengiriman, instalasi, sampai layanan purna jual – kami bantu sampai gym Anda siap beroperasi.</span></li>
      </ul>
    </div>
  </div>
</section>

<!-- ============ CATEGORIES ============ -->
<section class="section" id="kategori">
  <div class="container">
    <div class="section-head reveal">
      <div class="eyebrow">BROWSE BY CATEGORY</div>
      <h2 class="section-title">JELAJAHI KATEGORI</h2>
    </div>
    <div class="grid-4 reveal">
      <?php foreach ($categories as $c): ?>
        <a class="cat-tile" href="<?= url('products.php?category=' . urlencode($c['slug'])) ?>">
          <div class="cat-icon"><i class="fa-solid <?= e($catIcons[$c['slug']] ?? 'fa-dumbbell') ?>"></i></div>
          <h3><?= e($c['name']) ?></h3>
          <p><?= e($c['description']) ?></p>
          <span class="badge"><?= (int)$c['product_count'] ?> ITEMS</span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ FEATURED PRODUCTS ============ -->
<section class="section section-alt" id="katalog">
  <div class="container">
    <div class="section-head reveal">
      <div class="eyebrow">IMPORT EQUIPMENT</div>
      <h2 class="section-title">ALAT GYM PILIHAN</h2>
    </div>
    <?php if ($featured): ?>
      <div class="product-grid reveal">
        <?php foreach ($featured as $product) include APP_VIEWS . '/product_card.php'; ?>
      </div>
      <div class="text-center mt-3"><a class="btn btn-ghost" href="<?= url('products.php') ?>">LIHAT SEMUA PRODUK</a></div>
    <?php else: ?>
      <div class="empty-state"><i class="fa-solid fa-box-open"></i><p>Belum ada produk. Tambahkan lewat panel admin.</p></div>
    <?php endif; ?>
  </div>
</section>

<!-- ============ TESTIMONIALS ============ -->
<?php if ($testimonials): ?>
<section class="section" id="testimoni">
  <div class="container">
    <div class="section-head reveal">
      <div class="eyebrow">CUSTOMER STORIES</div>
      <h2 class="section-title">KATA MEREKA</h2>
    </div>
    <div class="grid-4 reveal" style="grid-template-columns:repeat(auto-fit,minmax(280px,1fr))">
      <?php foreach ($testimonials as $t): ?>
        <blockquote class="quote" style="margin:0">
          <p>“<?= e($t['content']) ?>”</p>
          <cite><?= e($t['name']) ?></cite>
          <small><?= e($t['role_title']) ?></small>
        </blockquote>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ============ CONTACT ============ -->
<section class="section section-grid" id="kontak">
  <div class="container">
    <div class="section-head reveal">
      <div class="eyebrow">GET IN TOUCH</div>
      <h2 class="section-title">HUBUNGI KAMI</h2>
    </div>
    <div class="contact-grid reveal">
      <div class="panel">
        <h3 class="text-primary brand-word" style="font-size:1.8rem;font-weight:900">BeeSportFit</h3>
        <p class="text-muted" style="font-family:var(--font-heading);letter-spacing:.12em;text-transform:uppercase;font-size:.75rem;font-weight:600;margin-bottom:2rem">IMPORTIR &amp; SUPPLIER ALAT GYM</p>

        <div class="contact-item"><div class="contact-icon"><i class="fa-solid fa-location-dot"></i></div>
          <div><h4>ALAMAT</h4><p><?= e(get_setting('address', 'Alamat belum diisi')) ?></p></div></div>
        <div class="contact-item"><div class="contact-icon"><i class="fa-solid fa-envelope"></i></div>
          <div><h4>EMAIL</h4><a href="mailto:<?= e(get_setting('contact_email')) ?>"><?= e(get_setting('contact_email')) ?></a></div></div>
        <?php if ($ig = get_setting('instagram')): ?>
        <div class="contact-item"><div class="contact-icon"><i class="fa-brands fa-instagram"></i></div>
          <div><h4>INSTAGRAM</h4><a href="<?= e($ig) ?>" target="_blank" rel="noopener"><?= e($ig) ?></a></div></div>
        <?php endif; ?>
        <?php if (chat_enabled()): ?>
        <div class="contact-item"><div class="contact-icon"><i class="fa-solid fa-comments"></i></div>
          <div><h4>LIVE CHAT</h4><p>Tanya produk, harga, atau layout gym langsung di website. <a href="#" data-chat-open style="color:var(--primary)">Mulai chat &rarr;</a></p></div></div>
        <?php endif; ?>
        <?php if (has_wa()): ?>
        <div class="contact-item"><div class="contact-icon"><i class="fa-brands fa-whatsapp"></i></div>
          <div><h4>WHATSAPP PEMESANAN</h4><a href="<?= e(wa_link('Halo ' . SITE_NAME . ', saya ingin memesan alat gym.')) ?>" target="_blank" rel="noopener">+<?= e(preg_replace('/\D/', '', get_setting('whatsapp_number'))) ?> (khusus pemesanan)</a></div></div>
        <?php endif; ?>
      </div>

      <div class="panel">
        <h3 style="font-size:1.25rem;letter-spacing:.05em">KONSULTASI &amp; PENAWARAN HARGA</h3>
        <p class="text-muted" style="font-size:.8rem;margin-bottom:1.5rem">Isi formulir di bawah ini untuk konsultasi layout ruangan gym atau permintaan katalog lengkap.</p>
        <?php $return = 'home'; include APP_VIEWS . '/consultation_form.php'; ?>
      </div>
    </div>
  </div>
</section>

<?php include APP_VIEWS . '/footer.php'; ?>
