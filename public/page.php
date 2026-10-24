<?php
/** STATIC PAGES (warranty, terms ...). Edited by admin in Admin > Halaman. */
require_once __DIR__ . '/../app/config.php';

$page = get_page($_GET['slug'] ?? '');   // null when missing or not plain text
if (!$page) { http_response_code(404); }
$page_title = ($page['title'] ?? 'Halaman tidak ditemukan') . ' | ' . SITE_NAME;
include APP_VIEWS . '/header.php';
?>
<div class="page-head"><div class="container">
  <div class="breadcrumb"><a href="<?= url('index.php') ?>">Home</a> / <?= e($page['title'] ?? '404') ?></div>
  <h1 class="section-title"><?= e($page['title'] ?? 'Halaman tidak ditemukan') ?></h1>
</div></div>
<div class="container" style="max-width:860px;padding-bottom:4rem">
  <?php if ($page): ?>
    <div class="prose"><?= $page['content'] /* HTML written by the admin - trusted */ ?></div>
  <?php else: ?>
    <div class="empty-state"><p>Halaman ini belum tersedia.</p></div>
  <?php endif; ?>
</div>
<?php include APP_VIEWS . '/footer.php'; ?>
