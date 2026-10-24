/* CHAT.JS - visitor live-chat widget. Talks to chat.php (JSON). No libraries needed.
 * Messages are always inserted with textContent (never innerHTML), so visitors can't inject HTML. */
(function () {
  var root = document.getElementById('chat-widget');
  if (!root) return;

  var endpoint = root.dataset.endpoint, csrf = root.dataset.csrf;
  var loggedIn = root.dataset.loggedIn === '1';
  var fab = document.getElementById('chat-fab'), panel = document.getElementById('chat-panel');
  var body = document.getElementById('chat-body'), form = document.getElementById('chat-form');
  var input = document.getElementById('chat-input'), intro = document.getElementById('chat-intro');
  var nameEl = document.getElementById('chat-name'), contactEl = document.getElementById('chat-contact');
  var hp = document.getElementById('chat-hp'), errEl = document.getElementById('chat-error');
  var sendBtn = document.getElementById('chat-send'), badge = document.getElementById('chat-badge');

  var isOpen = false, started = false, lastId = 0, seen = {}, closedNoticeShown = false;
  var pollTimer = null, unreadTimer = null;

  // ---------- helpers ----------
  function get(action, extra) {
    return fetch(endpoint + '?action=' + action + (extra || ''), { credentials: 'same-origin', cache: 'no-store' })
      .then(function (r) { return r.json(); });
  }
  function post(data) {
    var fd = new URLSearchParams(data);
    return fetch(endpoint, { method: 'POST', credentials: 'same-origin', body: fd,
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' } })
      .then(function (r) { return r.json(); });
  }
  function scrollDown() { body.scrollTop = body.scrollHeight; }
  function showError(msg) { errEl.textContent = msg || ''; }

  function bubble(cls, text, time) {
    var wrap = document.createElement('div'); wrap.className = 'chat-msg ' + cls;
    var p = document.createElement('p'); p.textContent = text; wrap.appendChild(p);      // textContent = safe
    if (time) { var t = document.createElement('span'); t.textContent = time; wrap.appendChild(t); }
    body.appendChild(wrap);
    return wrap;
  }
  function addMessage(m) {
    if (seen[m.id]) return;
    seen[m.id] = true; lastId = Math.max(lastId, m.id);
    bubble(m.sender === 'admin' ? 'from-admin' : 'from-me', m.body, m.time);
  }
  function setBadge(n) { badge.textContent = n > 99 ? '99+' : n; badge.hidden = !(n > 0); }
  function updateIntro() { intro.hidden = started || loggedIn; }

  // ---------- open / close ----------
  function open(prefill) {
    panel.hidden = false; isOpen = true; fab.setAttribute('aria-expanded', 'true'); setBadge(0);
    if (!body.childElementCount) loadState();
    if (prefill && !input.value) input.value = prefill;
    setTimeout(function () { (started || loggedIn ? input : nameEl).focus(); }, 50);
    startPolling();
  }
  function close() {
    panel.hidden = true; isOpen = false; fab.setAttribute('aria-expanded', 'false');
    stopPolling(); scheduleUnread();
  }

  function loadState() {
    get('state').then(function (d) {
      if (!d.ok) { bubble('system', d.error || 'Chat tidak tersedia.'); return; }
      started = d.hasConversation;
      bubble('from-admin', d.welcome);
      (d.messages || []).forEach(addMessage);
      if (d.status === 'closed') showClosedNotice();
      updateIntro(); scrollDown();
    }).catch(function () { bubble('system', 'Gagal memuat chat. Periksa koneksi Anda.'); });
  }
  function showClosedNotice() {
    if (closedNoticeShown) return; closedNoticeShown = true;
    bubble('system', 'Percakapan ditandai selesai. Kirim pesan baru untuk melanjutkan.'); scrollDown();
  }

  // ---------- polling ----------
  function poll() {
    if (!isOpen || document.hidden || !started) return;
    get('poll', '&after=' + lastId).then(function (d) {
      if (!d.ok) return;
      var before = lastId;
      (d.messages || []).forEach(addMessage);
      if (lastId !== before) scrollDown();
      if (d.status === 'closed') showClosedNotice(); else closedNoticeShown = false;
    }).catch(function () {});
  }
  function startPolling() { stopPolling(); clearTimeout(unreadTimer); pollTimer = setInterval(poll, 4000); }
  function stopPolling() { clearInterval(pollTimer); }

  // when closed: check now and then whether the admin replied (badge on the button)
  function checkUnread() {
    if (isOpen || document.hidden) return scheduleUnread();
    get('unread').then(function (d) { if (d.ok) setBadge(d.unread || 0); }).catch(function () {}).then(scheduleUnread);
  }
  function scheduleUnread() { clearTimeout(unreadTimer); if (started || root.dataset.hasChat === '1') unreadTimer = setTimeout(checkUnread, 20000); }

  // ---------- sending ----------
  form.addEventListener('submit', function (e) {
    e.preventDefault(); showError('');
    var text = input.value.trim();
    if (!text) return;
    if (!started && !loggedIn && !nameEl.value.trim()) { showError('Mohon isi nama Anda.'); nameEl.focus(); return; }
    sendBtn.disabled = true;
    post({ action: 'send', csrf: csrf, body: text, name: nameEl.value.trim(), contact: contactEl.value.trim(), website: hp.value })
      .then(function (d) {
        if (!d.ok) { showError(d.error || 'Pesan gagal dikirim.'); return; }
        if (d.message) { addMessage(d.message); scrollDown(); }
        input.value = ''; input.style.height = ''; started = true; closedNoticeShown = false; updateIntro(); startPolling();
      })
      .catch(function () { showError('Koneksi bermasalah. Coba lagi.'); })
      .then(function () { sendBtn.disabled = false; input.focus(); });
  });

  // Enter = send, Shift+Enter = new line; textarea grows a little
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); }
  });
  input.addEventListener('input', function () { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 110) + 'px'; });

  // ---------- wiring ----------
  fab.addEventListener('click', function () { isOpen ? close() : open(); });
  document.getElementById('chat-close').addEventListener('click', close);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && isOpen) close(); });
  document.addEventListener('visibilitychange', function () { if (!document.hidden && isOpen) poll(); });

  // any button with data-chat-open opens the chat (optionally prefilled)
  document.querySelectorAll('[data-chat-open]').forEach(function (el) {
    el.addEventListener('click', function (e) { e.preventDefault(); open(el.dataset.chatMessage || ''); });
  });

  updateIntro();
  if (root.dataset.hasChat === '1') { started = true; updateIntro(); checkUnread(); }
})();
