</main>
<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <img class="footer-logo" src="<?= asset('img/logo.png') ?>" alt="<?= e(SITE_NAME) ?>" width="161" height="120">
        <p class="text-muted mt-1" style="font-size:.8rem;max-width:440px">
          BeeSportFit adalah importir dan supplier alat gym impor untuk gym komersial, hotel, kantor, dan home gym. Kami membantu dari konsultasi layout, pengiriman, hingga instalasi.
        </p>
        <div class="socials">
          <?php if ($ig = get_setting('instagram')): ?>
            <a href="<?= e($ig) ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
          <?php endif; ?>
        </div>
      </div>
      <div>
        <h4>NAVIGASI</h4>
        <ul>
          <li><a href="<?= url('index.php#home') ?>">Home</a></li>
          <li><a href="<?= url('index.php#kategori') ?>">Kategori Produk</a></li>
          <li><a href="<?= url('products.php') ?>">Katalog Alat</a></li>
          <li><a href="<?= url('index.php#tentang') ?>">Tentang Kami</a></li>
          <li><a href="<?= url('consultation.php') ?>">Konsultasi Gratis</a></li>
        </ul>
      </div>
      <div>
        <h4>LAYANAN KAMI</h4>
        <ul>
          <li><span class="tick">✓</span> Konsultasi Layout Gym 2D/3D</li>
          <li><span class="tick">✓</span> Garansi &amp; Layanan Purna Jual</li>
          <li><span class="tick">✓</span> Pengiriman Seluruh Indonesia</li>
          <li><span class="tick">✓</span> Teknisi Berpengalaman</li>
        </ul>
      </div>
    </div>
    <?php $footBrands = get_brands_with_logos(); if ($footBrands): ?>
    <div class="footer-brands"><span>BRAND RESMI</span>
      <?php foreach ($footBrands as $b): ?>
        <a href="<?= url('products.php?brand=' . (int)$b['id']) ?>" title="<?= e($b['name']) ?>"><?= brand_logo_html($b) ?></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="footer-bottom">
      <div>© <?= date('Y') ?> <?= e(SITE_NAME) ?>. All Rights Reserved.</div>
      <div>
        <a href="<?= url('page.php?slug=terms') ?>">Syarat &amp; Ketentuan</a> &nbsp;
        <a href="<?= url('page.php?slug=warranty') ?>">Kebijakan Garansi</a>
      </div>
    </div>
  </div>
</footer>

<?php /* Floating button bottom-right = LIVE CHAT (see app/views/chat_widget.php). WhatsApp is only used for ordering. */ ?>
<?php include APP_VIEWS . '/chat_widget.php'; ?>
<script src="<?= asset('js/main.js') ?>"></script>
<?php if (chat_enabled()): ?><script src="<?= asset('js/chat.js') ?>"></script><?php endif; ?>
</body>
</html>
