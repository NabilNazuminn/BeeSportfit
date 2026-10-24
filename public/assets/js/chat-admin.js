/* CHAT-ADMIN.JS - live updates for the admin chat inbox. Messages are inserted with textContent (safe). */
(function () {
  var root = document.getElementById('chat-admin');
  if (!root) return;
  var api = root.dataset.api, csrf = root.dataset.csrf;
  var convId = parseInt(root.dataset.conv, 10) || 0;
  var lastId = parseInt(root.dataset.last, 10) || 0;
  var seen = {};
  var bodyEl = document.getElementById('chat-thread-body');
  var listEl = document.getElementById('chat-list');

  function getJson(q) { return fetch(api + '?' + q, { credentials: 'same-origin', cache: 'no-store' }).then(function (r) { return r.json(); }); }
  function atBottom() { return !bodyEl || bodyEl.scrollHeight - bodyEl.scrollTop - bodyEl.clientHeight < 80; }
  function scrollDown() { if (bodyEl) bodyEl.scrollTop = bodyEl.scrollHeight; }

  // server-rendered messages are already on the page: remember their ids
  if (bodyEl) bodyEl.querySelectorAll('[data-id]').forEach(function (n) { seen[n.dataset.id] = true; });
  scrollDown();

  function addMessage(m) {
    if (seen[m.id]) return;
    seen[m.id] = true; lastId = Math.max(lastId, m.id);
    var wrap = document.createElement('div');
    wrap.className = 'chat-msg ' + (m.sender === 'admin' ? 'from-me' : 'from-admin'); wrap.dataset.id = m.id;
    var p = document.createElement('p'); p.textContent = m.body;            // textContent = safe
    var t = document.createElement('span'); t.textContent = m.time;
    wrap.appendChild(p); wrap.appendChild(t); bodyEl.appendChild(wrap);
  }

  // ---- poll the open conversation ----
  function pollMessages() {
    if (!convId || document.hidden) return;
    getJson('action=messages&id=' + convId + '&after=' + lastId).then(function (d) {
      if (!d.ok) return;
      var stick = atBottom();
      (d.messages || []).forEach(addMessage);
      if (stick && d.messages && d.messages.length) scrollDown();
    }).catch(function () {});
  }

  // ---- refresh the conversation list ----
  function renderList(convs) {
    listEl.textContent = '';
    if (!convs.length) { var p = document.createElement('p'); p.className = 'text-muted'; p.style.padding = '1rem'; p.textContent = 'Belum ada percakapan.'; listEl.appendChild(p); return; }
    convs.forEach(function (c) {
      var a = document.createElement('a'); a.className = 'chat-item' + (c.id === convId ? ' active' : ''); a.href = '?id=' + c.id;
      var top = document.createElement('div'); top.className = 'chat-item-top';
      var n = document.createElement('strong'); n.textContent = c.name; var tm = document.createElement('span'); tm.textContent = c.time;
      top.appendChild(n); top.appendChild(tm);
      var bot = document.createElement('div'); bot.className = 'chat-item-bottom';
      var pv = document.createElement('span'); pv.textContent = c.preview; bot.appendChild(pv);
      if (c.unread > 0 && c.id !== convId) { var b = document.createElement('b'); b.className = 'nav-badge'; b.textContent = c.unread; bot.appendChild(b); }
      a.appendChild(top); a.appendChild(bot);
      if (c.status === 'closed') { var s = document.createElement('small'); s.className = 'text-muted'; s.textContent = 'selesai'; a.appendChild(s); }
      listEl.appendChild(a);
    });
  }
  function pollList() {
    if (document.hidden) return;
    getJson('action=list').then(function (d) { if (d.ok) renderList(d.conversations); }).catch(function () {});
  }

  setInterval(pollMessages, 3000);
  setInterval(pollList, 6000);

  // ---- send a reply without reloading the page ----
  var form = document.getElementById('chat-reply');
  if (form) {
    var input = document.getElementById('chat-reply-input'), err = document.getElementById('chat-reply-error');
    form.addEventListener('submit', function (e) {
      e.preventDefault(); err.textContent = '';
      var text = input.value.trim(); if (!text) return;
      var data = new URLSearchParams({ action: 'send', csrf: csrf, id: convId, body: text });
      fetch(api, { method: 'POST', credentials: 'same-origin', body: data, headers: { 'Content-Type': 'application/x-www-form-urlencoded' } })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (!d.ok) { err.textContent = d.error || 'Gagal mengirim.'; return; }
          addMessage(d.message); scrollDown(); input.value = '';
        })
        .catch(function () { err.textContent = 'Koneksi bermasalah.'; });
    });
    input.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); } });
  }
})();
