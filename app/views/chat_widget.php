<?php
/**
 * LIVE CHAT WIDGET (floating button bottom-right + chat panel).
 * Included by footer.php on every public page. Logic: assets/js/chat.js, styles: style.css section 20.
 * To open the chat from any button, give it:  data-chat-open  (optional: data-chat-message="text to prefill")
 */
if (!chat_enabled()) return;
$chatUser = current_user();
?>
<div id="chat-widget" class="chat"
     data-endpoint="<?= e(url('chat.php')) ?>" data-csrf="<?= e(csrf_token()) ?>"
     data-has-chat="<?= chat_visitor_conversation() ? '1' : '0' ?>" data-logged-in="<?= $chatUser ? '1' : '0' ?>">
  <button type="button" class="chat-fab" id="chat-fab" aria-label="Buka live chat" aria-expanded="false" aria-controls="chat-panel">
    <i class="fa-solid fa-comments"></i><span class="chat-badge" id="chat-badge" hidden>0</span>
  </button>

  <section class="chat-panel" id="chat-panel" role="dialog" aria-label="Live chat <?= e(SITE_NAME) ?>" hidden>
    <header class="chat-head">
      <div><strong><?= e(SITE_NAME) ?></strong><span>Live Chat · tanya produk, harga &amp; layout gym</span></div>
      <button type="button" class="chat-close" id="chat-close" aria-label="Tutup chat"><i class="fa-solid fa-xmark"></i></button>
    </header>

    <div class="chat-body" id="chat-body" aria-live="polite"></div>

    <form class="chat-form" id="chat-form" autocomplete="off">
      <div class="chat-intro" id="chat-intro" hidden>
        <input type="text" id="chat-name" placeholder="Nama Anda *" maxlength="100">
        <input type="text" id="chat-contact" placeholder="No. WhatsApp / email (opsional)" maxlength="150">
      </div>
      <input type="text" id="chat-hp" class="chat-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
      <div class="chat-row">
        <textarea id="chat-input" rows="1" maxlength="1000" placeholder="Tulis pesan..." aria-label="Pesan"></textarea>
        <button type="submit" class="chat-send" id="chat-send" aria-label="Kirim pesan"><i class="fa-solid fa-paper-plane"></i></button>
      </div>
      <div class="chat-error" id="chat-error" role="alert"></div>
    </form>
    <p class="chat-note">Ingin memesan? Gunakan tombol <b>Pesan via WhatsApp</b> di halaman produk atau keranjang.</p>
  </section>
</div>
