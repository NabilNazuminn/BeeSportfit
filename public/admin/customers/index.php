<?php
/** ADMIN: customer accounts - search, activate/deactivate, and set a temporary password (for customers who cannot receive e-mail). */
require_once __DIR__ . '/../../../app/config.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $uid = (int)($_POST['id'] ?? 0);
    $u = db_one("SELECT id, name, email FROM users WHERE id = ? AND role = 'customer'", [$uid]);
    if (!$u) {
        flash_set('error', 'Pelanggan tidak ditemukan.');
    } elseif (($_POST['action'] ?? '') === 'reset') {
        $tmp = substr(strtr(base64_encode(random_bytes(9)), '+/=', 'xyz'), 0, 10);
        set_user_password($uid, $tmp);
        flash_set('success', 'Password sementara untuk ' . $u['email'] . ': ' . $tmp . '  (kirim ke pelanggan, minta diganti lewat Profil.)', true);
    } elseif (($_POST['action'] ?? '') === 'toggle') {
        db_run('UPDATE users SET is_active = 1 - is_active WHERE id = ?', [$uid]);
        flash_set('success', 'Status akun diperbarui.');
    }
    redirect('admin/customers/index.php?' . http_build_query(['q' => input_str($_GET, 'q'), 'page' => request_int('page')]));
}

$q = input_str($_GET, 'q');
$where = "WHERE role = 'customer'"; $params = [];
if ($q !== '') { $where .= ' AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)'; $like = '%' . like_escape($q) . '%'; $params = [$like, $like, $like]; }
$total = (int)db_one("SELECT COUNT(*) n FROM users $where", $params)['n'];
$pages = max(1, (int)ceil($total / 25)); $page = min(request_int('page'), $pages);
$rows = db_all("SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count FROM users u $where ORDER BY u.created_at DESC, u.id DESC LIMIT 25 OFFSET " . (($page - 1) * 25), $params);

$page_title = 'Pelanggan'; $active = 'customers';
include APP_VIEWS . '/admin_header.php';
?>
<div class="admin-top"><h1>PELANGGAN</h1><span class="text-muted"><?= $total ?> akun</span></div>
<form method="get" class="mb-2" style="display:flex;gap:.5rem;max-width:480px">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nama / email / telepon..."><button class="btn btn-ghost btn-sm" type="submit">CARI</button>
</form>
<div class="table-wrap"><table>
  <thead><tr><th>Nama</th><th>Kontak</th><th>Pesanan</th><th>Daftar</th><th>Status</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $u): ?>
    <tr><td><strong><?= e($u['name']) ?></strong></td><td><?= e($u['email']) ?><br><span class="text-muted" style="font-size:.8rem"><?= e($u['phone']) ?></span></td>
      <td><?= (int)$u['order_count'] ?></td><td><?= e(format_date($u['created_at'])) ?></td>
      <td><?= $u['is_active'] ? '<span class="status status-completed">Aktif</span>' : '<span class="status status-cancelled">Nonaktif</span>' ?></td>
      <td><div class="actions">
        <form method="post" data-confirm="Buat password sementara baru untuk <?= e($u['email']) ?>? Password lama tidak berlaku lagi."><?= csrf_field() ?><input type="hidden" name="action" value="reset"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button class="btn btn-ghost btn-sm" type="submit">RESET PASSWORD</button></form>
        <form method="post" data-confirm="<?= $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?> akun ini?"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button class="btn btn-ghost btn-sm" type="submit"><?= $u['is_active'] ? 'NONAKTIFKAN' : 'AKTIFKAN' ?></button></form>
      </div></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="6" class="text-muted">Tidak ada pelanggan.</td></tr><?php endif; ?>
  </tbody></table></div>
<?php if ($pages > 1): ?><nav class="pagination">
  <?php for ($i = 1; $i <= $pages; $i++): ?>
    <?php if ($i === $page): ?><span class="current"><?= $i ?></span><?php else: ?><a href="<?= e(query_with(['page' => $i])) ?>"><?= $i ?></a><?php endif; ?>
  <?php endfor; ?></nav><?php endif; ?>
<?php include APP_VIEWS . '/admin_footer.php'; ?>
