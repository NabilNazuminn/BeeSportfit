/* MAIN.JS - tiny helpers. No framework needed. */
document.addEventListener('DOMContentLoaded', function () {

  // Mobile menu toggle
  var toggle = document.getElementById('nav-toggle');
  var menu = document.getElementById('mobile-menu');
  if (toggle && menu) toggle.addEventListener('click', function () { menu.classList.toggle('open'); });

  // Quantity +/- buttons:  <div class="qty"><button data-qty="-1">..<input>..<button data-qty="1">
  document.querySelectorAll('.qty').forEach(function (box) {
    var input = box.querySelector('input');
    box.querySelectorAll('button[data-qty]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var v = (parseInt(input.value, 10) || 1) + parseInt(btn.dataset.qty, 10);
        input.value = Math.max(parseInt(input.min || '0', 10), v);
      });
    });
  });

  // Product gallery: click a thumbnail to change the big image
  var main = document.getElementById('gallery-main-img');
  document.querySelectorAll('[data-gallery-src]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (main) main.src = btn.dataset.gallerySrc;
      document.querySelectorAll('[data-gallery-src]').forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');
    });
  });

  // Ask before dangerous actions:  <form data-confirm="Hapus produk ini?">
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!confirm(form.dataset.confirm)) e.preventDefault();
    });
  });

  // Auto-dismiss flash alerts after 5 seconds.
  document.querySelectorAll('.flash-auto-dismiss').forEach(function (alertEl) {
    var delay = parseInt(alertEl.dataset.autoDismissMs || '5000', 10);
    setTimeout(function () {
      alertEl.style.opacity = '0';
      alertEl.style.transition = 'opacity .35s ease';
      setTimeout(function () { alertEl.remove(); }, 360);
    }, delay);
  });

  // Centralized navigation popup: back navigation is only available inside this dialog.
  var navigationDialog = document.querySelector('[data-navigation-dialog]');
  document.querySelectorAll('[data-navigation-open]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (navigationDialog && !navigationDialog.open) navigationDialog.showModal();
    });
  });
  document.querySelectorAll('[data-navigation-close]').forEach(function (btn) {
    btn.addEventListener('click', function () { if (navigationDialog) navigationDialog.close(); });
  });
  document.querySelectorAll('[data-dialog-back]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var fallback = btn.dataset.backFallback || '/';
      if (document.referrer && document.referrer.indexOf(window.location.origin) === 0) history.back();
      else window.location.href = fallback;
    });
  });

  // Header search preview with category, brand, and page tabs.
  var searchToggle = document.querySelector('[data-search-toggle]');
  var searchPanel = document.querySelector('[data-search-panel]');
  var searchInput = document.querySelector('[data-search-input]');
  var searchPreview = document.querySelector('[data-search-preview]');
  if (searchToggle && searchPanel && searchInput && searchPreview) {
    var searchTimer;
    var currentResults = {};
    var activeSearchTab = 'products';
    var searchLabels = { products: 'Produk', categories: 'Kategori', brands: 'Brand', pages: 'Halaman' };
    searchToggle.addEventListener('click', function () {
      var opening = searchPanel.hidden;
      searchPanel.hidden = !opening;
      searchToggle.setAttribute('aria-expanded', opening ? 'true' : 'false');
      if (opening) searchInput.focus();
    });
    document.querySelectorAll('[data-search-close]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        searchPanel.hidden = true;
        searchToggle.setAttribute('aria-expanded', 'false');
      });
    });
    function renderSearchPreview() {
      searchPreview.replaceChildren();
      var available = Object.keys(searchLabels).filter(function (key) { return (currentResults[key] || []).length; });
      if (!available.length) {
        var empty = document.createElement('div');
        empty.className = 'search-preview-empty';
        empty.textContent = 'Tidak ada produk, kategori, brand, atau halaman yang cocok.';
        searchPreview.appendChild(empty);
        return;
      }
      if (available.indexOf(activeSearchTab) === -1) activeSearchTab = available[0];
      var tabs = document.createElement('div');
      tabs.className = 'search-preview-tabs';
      tabs.setAttribute('role', 'tablist');
      available.forEach(function (key) {
        var tab = document.createElement('button');
        tab.type = 'button';
        tab.className = 'search-preview-tab' + (key === activeSearchTab ? ' active' : '');
        tab.setAttribute('role', 'tab');
        tab.setAttribute('aria-selected', key === activeSearchTab ? 'true' : 'false');
        tab.textContent = searchLabels[key] + ' (' + currentResults[key].length + ')';
        tab.addEventListener('click', function () { activeSearchTab = key; renderSearchPreview(); });
        tabs.appendChild(tab);
      });
      var list = document.createElement('div');
      list.className = 'search-preview-list';
      list.setAttribute('role', 'tabpanel');
      currentResults[activeSearchTab].forEach(function (item) {
        var link = document.createElement('a');
        link.className = 'search-preview-item';
        link.href = item.url;
        link.textContent = item.label;
        list.appendChild(link);
      });
      searchPreview.append(tabs, list);
    }
    searchInput.addEventListener('input', function () {
      clearTimeout(searchTimer);
      var term = searchInput.value.trim();
      if (term.length < 2) {
        searchPreview.hidden = true;
        searchPreview.replaceChildren();
        return;
      }
      searchTimer = setTimeout(function () {
        fetch(searchInput.dataset.previewEndpoint + '?q=' + encodeURIComponent(term), { credentials: 'same-origin' })
          .then(function (response) { if (!response.ok) throw new Error('Search preview unavailable'); return response.json(); })
          .then(function (data) {
            if (searchInput.value.trim() !== term) return;
            currentResults = data;
            searchPreview.hidden = false;
            renderSearchPreview();
          })
          .catch(function () { searchPreview.hidden = true; });
      }, 180);
    });
  }

  // Admin sidebar: red number on "Live Chat" = unread visitor messages (refreshes every 10 s)
  var navBadge = document.getElementById('chat-nav-badge');
  if (navBadge) {
    var refreshBadge = function () {
      if (document.hidden) return;
      fetch(navBadge.dataset.endpoint + '?action=unread', { credentials: 'same-origin', cache: 'no-store' })
        .then(function (r) { return r.json(); })
        .then(function (d) { if (d.ok) { navBadge.textContent = d.unread; navBadge.hidden = !(d.unread > 0); } })
        .catch(function () {});
    };
    setInterval(refreshBadge, 10000);
  }

  // ---------- Light / dark mode toggle (choice is remembered in the browser) ----------
  var root = document.documentElement;
  function applyTheme(t) {
    root.dataset.theme = t;
    var meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', t === 'light' ? '#FBF8F2' : '#0E0D0B');
    document.querySelectorAll('[data-theme-toggle]').forEach(function (b) {
      b.setAttribute('aria-label', t === 'light' ? 'Ganti ke mode gelap' : 'Ganti ke mode terang');
    });
  }
  applyTheme(root.dataset.theme === 'light' ? 'light' : 'dark');
  document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var next = root.dataset.theme === 'light' ? 'dark' : 'light';
      root.classList.add('theme-anim');                         // short colour fade
      applyTheme(next);
      try { localStorage.setItem('bsf_theme', next); } catch (e) {}
      setTimeout(function () { root.classList.remove('theme-anim'); }, 450);
    });
  });
  window.addEventListener('storage', function (e) {            // another tab changed the theme
    if (e.key === 'bsf_theme' && (e.newValue === 'light' || e.newValue === 'dark')) applyTheme(e.newValue);
  });

  // ---------- Scroll reveal: elements with class="reveal" fade up when they enter the screen ----------
  var revealEls = document.querySelectorAll('.reveal');
  if (revealEls.length) {
    if (!('IntersectionObserver' in window)) {
      revealEls.forEach(function (el) { el.classList.add('in'); });
    } else {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
      }, { threshold: 0.12 });
      revealEls.forEach(function (el) { io.observe(el); });
    }
  }
});
