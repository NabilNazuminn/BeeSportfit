<?php
/** ADMIN: testimonials list + delete. */
require_once __DIR__ . '/../../../app/config.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    delete_testimonial((int)($_POST['id'] ?? 0));
    flash_set('success', 'Testimoni dihapus.');
    redirect('admin/testimonials/index.php');
}
$rows = get_testimonials(false);
$page_title = 'Testimoni'; $active = 'testimonials';
include APP_VIEWS . '/admin_header.php';
?>
<div class="admin-top"><h1>TESTIMONI</h1><a class="btn btn-primary btn-sm" href="<?= url('admin/testimonials/create.php') ?>"><i class="fa-solid fa-plus"></i> TAMBAH</a></div>
<div class="table-wrap"><table>
  <thead><tr><th>Nama</th><th>Isi</th><th>Status</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $t): ?>
    <tr><td><strong><?= e($t['name']) ?></strong><br><span class="text-muted" style="font-size:.8rem"><?= e($t['role_title']) ?></span></td>
      <td style="max-width:420px"><?= e($t['content']) ?></td>
      <td><?= $t['is_active'] ? '<span class="status status-completed">Tampil</span>' : '<span class="status status-cancelled">Sembunyi</span>' ?></td>
      <td><div class="actions"><a class="btn btn-ghost btn-sm" href="<?= url('admin/testimonials/edit.php?id=' . (int)$t['id']) ?>">EDIT</a>
        <form method="post" data-confirm="Hapus testimoni ini?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button class="btn btn-danger btn-sm" type="submit"><i class="fa-solid fa-trash"></i></button></form></div></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="4" class="text-muted">Belum ada testimoni.</td></tr><?php endif; ?>
  </tbody></table></div>
<?php include APP_VIEWS . '/admin_footer.php'; ?>
