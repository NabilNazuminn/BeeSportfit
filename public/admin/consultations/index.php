<?php
/** ADMIN: consultation requests (leads). */
require_once __DIR__ . '/../../../app/config.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (($_POST['action'] ?? '') === 'delete') {
        delete_consultation((int)($_POST['id'] ?? 0));
        flash_set('success', 'Permintaan dihapus.');
    } else {
        update_consultation_status((int)($_POST['id'] ?? 0), input_str($_POST, 'status'));
        flash_set('success', 'Status diperbarui.');
    }
    redirect('admin/consultations/index.php?page=' . request_int('page'));
}
$res = get_consultations(request_int('page'));
$rows = $res['items'];
$types = project_types();
$page_title = 'Konsultasi'; $active = 'consultations';
include APP_VIEWS . '/admin_header.php';
?>
<div class="admin-top"><h1>PERMINTAAN KONSULTASI</h1><span class="text-muted"><?= (int)$res['total'] ?> total</span></div>
<div class="table-wrap"><table>
  <thead><tr><th>Tanggal</th><th>Kontak</th><th>Proyek</th><th>Pesan</th><th>Status</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= e(format_date($r['created_at'])) ?></td>
      <td><strong><?= e($r['name']) ?></strong><br><a class="text-primary" target="_blank" rel="noopener" href="https://wa.me/<?= e(preg_replace('/^0/', '62', preg_replace('/\D/', '', $r['phone']))) ?>"><?= e($r['phone']) ?></a><?= $r['email'] ? '<br><span class="text-muted" style="font-size:.8rem">' . e($r['email']) . '</span>' : '' ?></td>
      <td><?= e($types[$r['gym_type']] ?? $r['gym_type']) ?><br><span class="text-muted" style="font-size:.8rem"><?= e($r['room_size']) ?> <?= e($r['budget']) ?></span></td>
      <td style="max-width:320px"><?= nl2br(e($r['message'])) ?></td>
      <td><form method="post" style="display:flex;gap:.4rem"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
        <select name="status" style="padding:6px"><?php foreach (['new' => 'Baru', 'contacted' => 'Dihubungi', 'closed' => 'Selesai'] as $k => $l): ?><option value="<?= $k ?>" <?= $r['status'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
        <button class="btn btn-ghost btn-sm" type="submit">OK</button></form></td>
      <td><form method="post" data-confirm="Hapus permintaan ini?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
        <button class="btn btn-danger btn-sm" type="submit"><i class="fa-solid fa-trash"></i></button></form></td>
    </tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="6" class="text-muted">Belum ada permintaan konsultasi.</td></tr><?php endif; ?>
  </tbody></table></div>
<?php if ($res['pages'] > 1): ?><nav class="pagination">
  <?php for ($i = 1; $i <= $res['pages']; $i++): ?>
    <?php if ($i === $res['page']): ?><span class="current"><?= $i ?></span><?php else: ?><a href="<?= e(query_with(['page' => $i])) ?>"><?= $i ?></a><?php endif; ?>
  <?php endfor; ?></nav><?php endif; ?>
<?php include APP_VIEWS . '/admin_footer.php'; ?>
