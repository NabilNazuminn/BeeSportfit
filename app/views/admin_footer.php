  </div><!-- /admin-main -->
</div><!-- /admin-layout -->
<script src="<?= asset('js/main.js') ?>"></script>
<?php foreach ($extra_js ?? [] as $js): ?><script src="<?= asset('js/' . $js) ?>"></script><?php endforeach; ?>
</body>
</html>
