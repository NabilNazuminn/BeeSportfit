<?php
/** ADMIN: edit static pages (warranty, terms). With no ?slug shows the list. */
require_once __DIR__ . '/../../../app/config.php';
require_admin();

$slug = input_str($_GET, 'slug');
$page = $slug ? get_page($slug) : null;

if ($page && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    save_page($slug, text_cut(input_str($_POST, 'title', $page['title']), 150), is_string($_POST['content'] ?? null) ? $_POST['content'] : '');
    flash_set('success', 'Halaman disimpan.');
    redirect('admin/pages/edit.php?slug=' . urlencode($slug));
}
$page_title = 'Halaman'; $active = 'pages';
include APP_VIEWS . '/admin_header.php';
?>
<div class="admin-top"><h1>HALAMAN</h1></div>
<div class="pills">
  <?php foreach (get_all_pages() as $pg): ?>
    <a class="pill <?= $slug === $pg['slug'] ? 'active' : '' ?>" href="?slug=<?= e($pg['slug']) ?>"><?= e($pg['title']) ?></a>
  <?php endforeach; ?>
</div>
<?php if ($page): ?>
<form method="post" class="panel"><?= csrf_field() ?>
  <div class="form-group"><label>Judul</label><input type="text" name="title" required value="<?= e($page['title']) ?>"></div>
  <div class="form-group"><label>Isi (boleh HTML: &lt;p&gt;, &lt;h2&gt;, &lt;ul&gt;)</label><textarea name="content" rows="16" style="font-family:monospace"><?= e($page['content']) ?></textarea></div>
  <button class="btn btn-primary" type="submit">SIMPAN</button>
  <a class="btn btn-ghost" target="_blank" href="<?= url('page.php?slug=' . urlencode($slug)) ?>">LIHAT HALAMAN</a>
</form>
<?php else: ?><p class="text-muted">Pilih halaman di atas untuk diedit.</p><?php endif; ?>
<?php include APP_VIEWS . '/admin_footer.php'; ?>
