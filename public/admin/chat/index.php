<?php
/** ADMIN: live chat inbox. Left = conversations, right = the selected conversation. */
require_once __DIR__ . '/../../../app/config.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);

// Buttons (work without JavaScript too): reply / close / reopen / delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $cid = (int)($_POST['id'] ?? 0);
    $act = $_POST['action'] ?? '';
    if ($cid && chat_get($cid)) {
        if ($act === 'send') {
            $body = chat_clean_body((string)($_POST['body'] ?? ''));
            if ($body !== '') chat_add_message($cid, 'admin', $body, (int)current_user()['id']);
        } elseif ($act === 'close' || $act === 'reopen') {
            chat_set_status($cid, $act === 'close' ? 'closed' : 'open');
            flash_set('success', $act === 'close' ? 'Percakapan ditandai selesai.' : 'Percakapan dibuka kembali.');
        } elseif ($act === 'delete') {
            chat_delete($cid);
            flash_set('success', 'Percakapan dihapus.');
            redirect('admin/chat/index.php');
        }
    }
    redirect('admin/chat/index.php?id=' . $cid);
}

$convs = chat_list();
$conv  = $id ? chat_get($id) : null;
$msgs  = [];
if ($conv) {
    $msgs = chat_messages($id);
    chat_mark_read($id, 'visitor');          // opening it = reading it
}
$lastId = $msgs ? (int)end($msgs)['id'] : 0;

$page_title = 'Live Chat'; $active = 'chat';
$extra_js = ['chat-admin.js'];
include APP_VIEWS . '/admin_header.php';
?>
<div class="admin-top"><h1>LIVE CHAT</h1></div>

<div class="chat-admin" id="chat-admin"
     data-api="<?= e(url('admin/chat/api.php')) ?>" data-csrf="<?= e(csrf_token()) ?>"
     data-conv="<?= (int)($conv['id'] ?? 0) ?>" data-last="<?= $lastId ?>">

  <aside class="chat-list" id="chat-list">
    <?php foreach ($convs as $c): $f = chat_format_conversation($c); ?>
      <a class="chat-item <?= $f['id'] === $id ? 'active' : '' ?>" href="<?= url('admin/chat/index.php?id=' . $f['id']) ?>" data-id="<?= $f['id'] ?>">
        <div class="chat-item-top"><strong><?= e($f['name']) ?></strong><span><?= e($f['time']) ?></span></div>
        <div class="chat-item-bottom"><span><?= e($f['preview']) ?></span>
          <?php if ($f['unread'] > 0 && $f['id'] !== $id): ?><b class="nav-badge"><?= $f['unread'] ?></b><?php endif; ?></div>
        <?php if ($f['status'] === 'closed'): ?><small class="text-muted">selesai</small><?php endif; ?>
      </a>
    <?php endforeach; ?>
    <?php if (!$convs): ?><p class="text-muted" style="padding:1rem">Belum ada percakapan. Pesan dari pengunjung akan muncul di sini.</p><?php endif; ?>
  </aside>

  <section class="chat-thread">
  <?php if (!$conv): ?>
    <div class="empty-state" style="margin:2rem"><i class="fa-solid fa-message"></i><p>Pilih percakapan di sebelah kiri.</p></div>
  <?php else: ?>
    <header class="chat-thread-head">
      <div><strong><?= e($conv['visitor_name']) ?></strong>
        <span class="text-muted"><?= $conv['visitor_contact'] ? ' · ' . e($conv['visitor_contact']) : '' ?><?= $conv['user_id'] ? ' · akun pelanggan' : ' · tamu' ?></span></div>
      <div class="actions">
        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$conv['id'] ?>">
          <button class="btn btn-ghost btn-sm" name="action" value="<?= $conv['status'] === 'open' ? 'close' : 'reopen' ?>" type="submit">
            <?= $conv['status'] === 'open' ? 'TANDAI SELESAI' : 'BUKA KEMBALI' ?></button></form>
        <form method="post" data-confirm="Hapus seluruh percakapan ini? Tidak bisa dibatalkan.">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$conv['id'] ?>">
          <button class="btn btn-danger btn-sm" name="action" value="delete" type="submit"><i class="fa-solid fa-trash"></i></button></form>
      </div>
    </header>

    <div class="chat-body chat-body-admin" id="chat-thread-body">
      <?php foreach ($msgs as $m): $f = chat_format($m); ?>
        <div class="chat-msg <?= $m['sender'] === 'admin' ? 'from-me' : 'from-admin' ?>" data-id="<?= $f['id'] ?>">
          <p><?= nl2br(e($f['body'])) ?></p><span><?= e($f['time']) ?></span></div>
      <?php endforeach; ?>
    </div>

    <form method="post" class="chat-form chat-reply" id="chat-reply">
      <?= csrf_field() ?><input type="hidden" name="action" value="send"><input type="hidden" name="id" value="<?= (int)$conv['id'] ?>">
      <div class="chat-row">
        <textarea name="body" id="chat-reply-input" rows="2" maxlength="1000" placeholder="Tulis balasan… (Enter = kirim, Shift+Enter = baris baru)" required></textarea>
        <button class="chat-send" type="submit" aria-label="Kirim"><i class="fa-solid fa-paper-plane"></i></button>
      </div>
      <div class="chat-error" id="chat-reply-error"></div>
    </form>
  <?php endif; ?>
  </section>
</div>
<?php include APP_VIEWS . '/admin_footer.php'; ?>
