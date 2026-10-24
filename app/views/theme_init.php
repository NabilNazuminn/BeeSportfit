<script>
/* THEME LOADER - applies the saved light/dark choice BEFORE the page is drawn (prevents a flash).
   Default = dark (the brand look). To follow the visitor's device setting by default instead, use:
   t = localStorage.getItem('bsf_theme') || (matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark'); */
(function () {
  var d = document.documentElement, t = 'dark';
  try { t = localStorage.getItem('bsf_theme') || 'dark'; } catch (e) {}
  if (t !== 'light') t = 'dark';
  d.dataset.theme = t;
  d.classList.add('js');                                   // lets CSS know JavaScript is running (used by scroll reveal)
  var m = document.querySelector('meta[name="theme-color"]');
  if (m) m.setAttribute('content', t === 'light' ? '#FBF8F2' : '#0E0D0B');
})();
</script>
