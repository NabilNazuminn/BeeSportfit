<?php
/** Everything about categories, brands and products. */

// ---------- Categories & brands ----------

/** Categories with how many active products each one has. */
function get_categories(bool $onlyActive = true): array
{
    return db_all('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.is_active = 1) AS product_count
                   FROM categories c ' . ($onlyActive ? 'WHERE c.is_active = 1 ' : '') . 'ORDER BY c.sort_order, c.name');
}

function get_category(int $id): ?array            { return db_one('SELECT * FROM categories WHERE id = ?', [$id]); }
function get_category_by_slug(string $s): ?array  { return db_one('SELECT * FROM categories WHERE slug = ?', [$s]); }
function get_brands(): array                      { return db_all('SELECT * FROM brands ORDER BY name'); }

/** Search active products, categories, brands and static pages (header search + preview). */
function search_content(string $query): array
{
    $results = ['products' => [], 'categories' => [], 'brands' => [], 'pages' => []];
    $query = trim($query);
    if (text_len($query) < 2) return $results;

    $like = '%' . like_escape($query) . '%';
    foreach (db_all('SELECT p.name, p.slug, p.sku FROM products p JOIN categories c ON c.id = p.category_id
                     WHERE p.is_active = 1 AND c.is_active = 1 AND (p.name LIKE ? OR p.sku LIKE ? OR p.short_description LIKE ?)
                     ORDER BY p.name LIMIT 20', [$like, $like, $like]) as $row) {
        $results['products'][] = ['label' => $row['name'] . ($row['sku'] ? ' (' . $row['sku'] . ')' : ''), 'url' => url('product.php?slug=' . rawurlencode($row['slug']))];
    }
    foreach (db_all('SELECT name, slug FROM categories WHERE is_active = 1 AND (name LIKE ? OR description LIKE ?) ORDER BY sort_order, name LIMIT 20', [$like, $like]) as $row) {
        $results['categories'][] = ['label' => $row['name'], 'url' => url('products.php?category=' . rawurlencode($row['slug']))];
    }
    foreach (db_all('SELECT b.id, b.name FROM brands b WHERE EXISTS (SELECT 1 FROM products p WHERE p.brand_id = b.id AND p.is_active = 1) AND b.name LIKE ? ORDER BY b.name LIMIT 20', [$like]) as $row) {
        $results['brands'][] = ['label' => $row['name'], 'url' => url('products.php?brand=' . (int)$row['id'])];
    }
    foreach (db_all('SELECT title, slug FROM pages WHERE title LIKE ? OR slug LIKE ? ORDER BY title LIMIT 20', [$like, $like]) as $row) {
        $results['pages'][] = ['label' => $row['title'], 'url' => url('page.php?slug=' . rawurlencode($row['slug']))];
    }
    return $results;
}

/** Make a slug that is not used yet:  "treadmill" -> "treadmill-2" if taken. */
function unique_slug(string $table, string $text, ?int $ignoreId = null): string
{
    $base = $slug = slugify($text);
    $i = 2;
    while (db_one("SELECT id FROM $table WHERE slug = ? AND id <> ?", [$slug, $ignoreId ?? 0])) {
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

/** Validation for the category form. Returns error messages. */
function validate_category(array $d): array
{
    $errors = [];
    $name = is_string($d['name'] ?? null) ? trim($d['name']) : '';
    if ($name === '') $errors[] = 'Nama kategori wajib diisi.';
    check_len($errors, 'Nama kategori', $name, 100);
    check_len($errors, 'Deskripsi', is_string($d['description'] ?? null) ? $d['description'] : '', 255);
    if (isset($d['sort_order']) && $d['sort_order'] !== '' && !preg_match('/^-?\d{1,6}$/', (string)$d['sort_order'])) $errors[] = 'Urutan harus berupa angka.';
    return $errors;
}

function save_category(array $d, ?int $id = null): void
{
    $slug = unique_slug('categories', $d['name'], $id);
    $vals = [trim($d['name']), $slug, trim($d['description'] ?? '') ?: null, (int)($d['sort_order'] ?? 0), !empty($d['is_active']) ? 1 : 0];
    if ($id) db_run('UPDATE categories SET name=?, slug=?, description=?, sort_order=?, is_active=? WHERE id=?', [...$vals, $id]);
    else     db_run('INSERT INTO categories (name, slug, description, sort_order, is_active) VALUES (?,?,?,?,?)', $vals);
}

// ---------- Product lists ----------

/**
 * Main catalog query.
 * $f keys (all optional): category (slug), brand (id), q (search text),
 *   sort (newest|price_asc|price_desc|name), featured (bool), page, per_page,
 *   include_inactive (bool, for admin)
 * Returns ['items' => [...], 'total' => n, 'pages' => n, 'page' => n]
 */
function get_products(array $f = []): array
{
    $where = [];
    $params = [];
    if (empty($f['include_inactive'])) { $where[] = 'p.is_active = 1'; $where[] = 'c.is_active = 1'; }   // hidden category = hidden products
    if (!empty($f['category']) && is_string($f['category'])) { $where[] = 'c.slug = ?';       $params[] = $f['category']; }
    if (!empty($f['brand']) && !is_array($f['brand']))       { $where[] = 'p.brand_id = ?';   $params[] = (int)$f['brand']; }
    if (!empty($f['featured'])) { $where[] = 'p.is_featured = 1'; }
    if (!empty($f['q']) && is_string($f['q'])) {
        $where[] = '(p.name LIKE ? OR p.sku LIKE ? OR p.short_description LIKE ?)';
        $like = '%' . like_escape($f['q']) . '%';
        array_push($params, $like, $like, $like);
    }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // the price a customer really pays: the discount only counts while it is valid (not expired)
    $realPrice = 'CASE WHEN p.discount_price > 0 AND p.discount_price < p.price AND (p.discount_ends_at IS NULL OR p.discount_ends_at > NOW())
                       THEN p.discount_price ELSE p.price END';
    $order = match (is_string($f['sort'] ?? null) ? $f['sort'] : 'newest') {
        'price_asc'  => "$realPrice ASC, p.id DESC",
        'price_desc' => "$realPrice DESC, p.id DESC",
        'name'       => 'p.name ASC',
        default      => 'p.id DESC',
    };

    $join = 'FROM products p JOIN categories c ON c.id = p.category_id LEFT JOIN brands b ON b.id = p.brand_id';
    $total = (int)db_one("SELECT COUNT(*) AS n $join $whereSql", $params)['n'];

    $perPage = (int)($f['per_page'] ?? PER_PAGE);
    $pages   = max(1, (int)ceil($total / $perPage));
    $page    = min(max(1, (int)($f['page'] ?? 1)), $pages);
    $offset  = ($page - 1) * $perPage;

    $items = db_all("SELECT p.*, c.name AS category_name, c.slug AS category_slug, b.name AS brand_name,
                     (SELECT image_path FROM product_images i WHERE i.product_id = p.id
                      ORDER BY i.is_primary DESC, i.sort_order, i.id LIMIT 1) AS image
                     $join $whereSql ORDER BY $order LIMIT $perPage OFFSET $offset", $params);

    return ['items' => $items, 'total' => $total, 'pages' => $pages, 'page' => $page];
}

function get_product_by_slug($slug): ?array
{
    if (!is_string($slug)) return null;
    return db_one('SELECT p.*, c.name AS category_name, c.slug AS category_slug, b.name AS brand_name, b.logo AS brand_logo, b.logo_white AS brand_logo_white
                   FROM products p JOIN categories c ON c.id = p.category_id LEFT JOIN brands b ON b.id = p.brand_id
                   WHERE p.slug = ? AND p.is_active = 1 AND c.is_active = 1', [$slug]);
}

function get_product_by_id(int $id): ?array
{
    return db_one('SELECT * FROM products WHERE id = ?', [$id]);
}

function get_product_images(int $productId): array
{
    return db_all('SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order, id', [$productId]);
}

function get_related_products(array $product, int $limit = 3): array
{
    return db_all('SELECT p.*, c.name AS category_name, b.name AS brand_name,
                   (SELECT image_path FROM product_images i WHERE i.product_id = p.id ORDER BY i.is_primary DESC, i.id LIMIT 1) AS image
                   FROM products p JOIN categories c ON c.id = p.category_id LEFT JOIN brands b ON b.id = p.brand_id
                   WHERE p.category_id = ? AND p.id <> ? AND p.is_active = 1 AND c.is_active = 1 ORDER BY RAND() LIMIT ' . (int)$limit,
                   [$product['category_id'], $product['id']]);
}

// ---------- Admin: create / update / delete ----------

/** Validate form data. Returns a list of error messages (empty = OK). $id = the product being edited (null when creating). */
function validate_product(array $d, ?int $id = null): array
{
    $errors = [];
    $name = is_string($d['name'] ?? null) ? trim($d['name']) : '';
    $sku  = is_string($d['sku'] ?? null) ? trim($d['sku']) : '';
    if ($name === '')                                  $errors[] = 'Nama produk wajib diisi.';
    check_len($errors, 'Nama produk', $name, 200);
    check_len($errors, 'Kode / SKU', $sku, 60);
    check_len($errors, 'Deskripsi singkat', is_string($d['short_description'] ?? null) ? $d['short_description'] : '', 300);
    check_len($errors, 'Deskripsi lengkap', is_string($d['description'] ?? null) ? $d['description'] : '', 20000);
    check_len($errors, 'Spesifikasi', is_string($d['specifications'] ?? null) ? $d['specifications'] : '', 20000);
    if ($sku !== '' && db_one('SELECT id FROM products WHERE sku = ? AND id <> ?', [$sku, $id ?? 0])) {
        $errors[] = 'Kode / SKU "' . $sku . '" sudah dipakai produk lain.';
    }
    if (empty($d['category_id']) || !get_category((int)$d['category_id'])) $errors[] = 'Pilih kategori.';
    if (!empty($d['brand_id']) && !get_brand((int)$d['brand_id']))         $errors[] = 'Brand tidak valid.';

    $price = is_string($d['price'] ?? null) ? trim($d['price']) : '';
    $disc  = is_string($d['discount_price'] ?? null) ? trim($d['discount_price']) : '';
    $hasPrice = $price !== '';
    if ($hasPrice && (!is_numeric($price) || $price <= 0)) $errors[] = 'Harga normal harus berupa angka lebih dari 0.';
    elseif ($hasPrice && $price > 9999999999999)           $errors[] = 'Harga terlalu besar.';
    if ($disc !== '' && (!$hasPrice || !is_numeric($disc) || $disc < 0 || $disc >= (float)$price)) {
        $errors[] = 'Harga diskon harus lebih kecil dari harga normal.';
    }
    $stock = is_string($d['stock'] ?? null) ? trim($d['stock']) : '0';
    if ($stock !== '' && (!ctype_digit($stock) || strlen($stock) > 9)) $errors[] = 'Jumlah stok harus angka 0 atau lebih.';
    $war = is_string($d['warranty_months'] ?? null) ? trim($d['warranty_months']) : '';
    if ($war !== '' && (!ctype_digit($war) || strlen($war) > 4)) $errors[] = 'Garansi (bulan) harus berupa angka.';
    $ends = is_string($d['discount_ends_at'] ?? null) ? trim($d['discount_ends_at']) : '';
    if ($ends !== '' && strtotime(str_replace('T', ' ', $ends)) === false) $errors[] = 'Tanggal berakhir diskon tidak valid.';
    return $errors;
}

/** Insert or update a product. Returns the product id. */
function save_product(array $d, ?int $id = null): int
{
    $vals = [
        (int)$d['category_id'],
        !empty($d['brand_id']) ? (int)$d['brand_id'] : null,
        trim($d['name']),
        unique_slug('products', $d['name'], $id),
        trim($d['sku'] ?? '') ?: null,
        trim($d['short_description'] ?? '') ?: null,
        trim($d['description'] ?? '') ?: null,
        trim($d['specifications'] ?? '') ?: null,
        ($d['price'] ?? '') !== '' ? (float)$d['price'] : 0,
        ($d['discount_price'] ?? '') !== '' ? (float)$d['discount_price'] : null,
        !empty($d['discount_ends_at']) ? str_replace('T', ' ', $d['discount_ends_at']) : null,
        max(0, (int)($d['stock'] ?? 0)),
        in_array($d['stock_status'] ?? '', ['in_stock', 'pre_order', 'out_of_stock'], true) ? $d['stock_status'] : 'in_stock',
        ($d['warranty_months'] ?? '') !== '' ? (int)$d['warranty_months'] : null,
        !empty($d['is_featured']) ? 1 : 0,
        !empty($d['is_active']) ? 1 : 0,
    ];
    $cols = 'category_id=?, brand_id=?, name=?, slug=?, sku=?, short_description=?, description=?, specifications=?,
             price=?, discount_price=?, discount_ends_at=?, stock=?, stock_status=?, warranty_months=?, is_featured=?, is_active=?';
    if ($id) {
        db_run("UPDATE products SET $cols WHERE id = ?", [...$vals, $id]);
        return $id;
    }
    db_run("INSERT INTO products SET $cols", $vals);
    return (int)db()->lastInsertId();
}

function add_product_image(int $productId, string $path): void
{
    $hasPrimary = db_one('SELECT id FROM product_images WHERE product_id = ? AND is_primary = 1', [$productId]);
    db_run('INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?,?,?)', [$productId, $path, $hasPrimary ? 0 : 1]);
}

function delete_product_image(int $imageId): void
{
    $img = db_one('SELECT * FROM product_images WHERE id = ?', [$imageId]);
    if (!$img) return;
    delete_image($img['image_path']);
    db_run('DELETE FROM product_images WHERE id = ?', [$imageId]);
    // if we removed the primary, promote another one
    if ($img['is_primary']) {
        db_run('UPDATE product_images SET is_primary = 1 WHERE product_id = ? ORDER BY id LIMIT 1', [$img['product_id']]);
    }
}

function set_primary_image(int $productId, int $imageId): void
{
    db_run('UPDATE product_images SET is_primary = (id = ?) WHERE product_id = ?', [$imageId, $productId]);
}

function delete_product(int $id): void
{
    foreach (get_product_images($id) as $img) delete_image($img['image_path']);
    db_run('DELETE FROM products WHERE id = ?', [$id]);   // images/cart rows cascade
}

/** Save every file from a multi-file <input name="images[]"> for one product. Returns error messages. */
function save_uploaded_images(int $productId, array $files): array
{
    $errors = [];
    if (empty($files['name'][0])) return $errors;
    foreach ($files['name'] as $i => $name) {
        $one = ['name' => $name, 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i], 'size' => $files['size'][$i]];
        try {
            if ($path = upload_image($one, 'products')) add_product_image($productId, $path);
        } catch (RuntimeException $e) {
            $errors[] = $name . ': ' . $e->getMessage();
        }
    }
    return $errors;
}

// ---------- Brands & brand logos ----------

function get_brand(int $id): ?array { return db_one('SELECT * FROM brands WHERE id = ?', [$id]); }

/** Brands that have a logo (home strip, catalog chips, footer). Order = the order they were added. */
function get_brands_with_logos(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = db_all("SELECT * FROM brands WHERE logo IS NOT NULL AND logo <> '' ORDER BY id");
    }
    return $cache;
}

/**
 * <img> tag for a brand logo. $b needs: name, logo, logo_white.
 * Logos flagged logo_white (black logos, e.g. Force USA) are turned white by CSS so they show on dark backgrounds.
 */
function brand_logo_html(array $b, string $class = ''): string
{
    $cls = trim('brand-logo-img ' . (!empty($b['logo_white']) ? 'logo-white ' : '') . $class);
    return '<img class="' . e($cls) . '" src="' . e(image_url($b['logo'])) . '" alt="' . e($b['name']) . '" loading="lazy">';
}

function get_brands_with_counts(): array
{
    return db_all('SELECT b.*, (SELECT COUNT(*) FROM products p WHERE p.brand_id = b.id) AS product_count FROM brands b ORDER BY b.id');
}

/**
 * Create or update a brand. $logo = path of a NEW uploaded logo (null = keep the current one),
 * $removeLogo = delete the current logo. Returns the brand id.
 */
function save_brand(string $name, ?int $id = null, ?string $logo = null, bool $logoWhite = false, bool $removeLogo = false): int
{
    $slug = unique_slug('brands', $name, $id);
    if ($id) {
        $old = get_brand($id);
        $newLogo = $old['logo'] ?? null;
        if ($logo !== null)      { delete_image($newLogo); $newLogo = $logo; }
        elseif ($removeLogo)     { delete_image($newLogo); $newLogo = null; }
        db_run('UPDATE brands SET name = ?, slug = ?, logo = ?, logo_white = ? WHERE id = ?', [trim($name), $slug, $newLogo, $logoWhite ? 1 : 0, $id]);
        return $id;
    }
    db_run('INSERT INTO brands (name, slug, logo, logo_white) VALUES (?, ?, ?, ?)', [trim($name), $slug, $logo, $logoWhite ? 1 : 0]);
    return (int)db()->lastInsertId();
}

/** Products of a deleted brand simply become "no brand" (ON DELETE SET NULL). The logo file is removed too. */
function delete_brand(int $id): void
{
    if ($b = get_brand($id)) delete_image($b['logo']);
    db_run('DELETE FROM brands WHERE id = ?', [$id]);
}
