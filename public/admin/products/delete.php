<?php
/** ADMIN: delete a product (POST only). */
require_once __DIR__ . '/../../../app/config.php';
require_admin();
require_post();

delete_product((int)($_POST['id'] ?? 0));
flash_set('success', 'Produk dihapus.');
redirect('admin/products/index.php');
