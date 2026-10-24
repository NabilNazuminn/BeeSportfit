<?php
/**
 * CART
 * The cart lives in $_SESSION['cart'] as  [product_id => quantity].
 * If the visitor is logged in, it is also saved to the database so it
 * survives logout / another device. Guests' carts are merged on login.
 *
 * Every read goes through cart_items(), which drops products that were deleted, hidden, sold out or
 * have no price - so the cart page, the header badge and checkout always agree.
 */

function cart_get(): array
{
    return $_SESSION['cart'] ?? [];
}

/** Can this product be bought online right now? */
function cart_orderable(?array $p): bool
{
    return $p && $p['is_active'] && $p['stock_status'] !== 'out_of_stock' && (float)$p['price'] > 0;
}

/** Highest quantity allowed for a product (its stock when stock is tracked, otherwise 999). */
function cart_max_qty(array $p): int
{
    return ($p['stock_status'] === 'in_stock' && (int)$p['stock'] > 0) ? min(999, (int)$p['stock']) : 999;
}

function cart_count(): int
{
    return (int)array_sum(array_column(cart_items(), 'qty'));
}

function cart_add(int $productId, int $qty = 1): bool
{
    $p = get_product_by_id($productId);
    if (!cart_orderable($p)) return false;
    $cart = cart_get();
    $cart[$productId] = min(cart_max_qty($p), ($cart[$productId] ?? 0) + max(1, $qty));
    $_SESSION['cart'] = $cart;
    cart_save_to_db();
    return true;
}

function cart_set(int $productId, int $qty): void
{
    $cart = cart_get();
    if ($qty <= 0) {
        unset($cart[$productId]);
    } else {
        $p = get_product_by_id($productId);
        if (!cart_orderable($p)) return;                   // cannot add unavailable / unknown products through "update"
        $cart[$productId] = min(cart_max_qty($p), $qty);
    }
    $_SESSION['cart'] = $cart;
    cart_save_to_db();
}

function cart_remove(int $productId): void { cart_set($productId, 0); }

function cart_clear(): void
{
    $_SESSION['cart'] = [];
    cart_save_to_db();
}

/**
 * Cart rows with full product info and line totals.
 * Each row = product fields + qty + unit_price + line_total.
 * Products that can no longer be bought are removed from the cart (and the customer is told once).
 */
function cart_items(): array
{
    static $cache = [];
    $cart = cart_get();
    if (!$cart) return [];
    $key = json_encode($cart);
    if (isset($cache[$key])) return $cache[$key];

    $ids = array_map('intval', array_keys($cart));
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $rows = db_all("SELECT p.*, (SELECT image_path FROM product_images i WHERE i.product_id = p.id
                    ORDER BY i.is_primary DESC, i.sort_order, i.id LIMIT 1) AS image
                    FROM products p JOIN categories c ON c.id = p.category_id
                    WHERE p.id IN ($in) AND p.is_active = 1 AND c.is_active = 1
                      AND p.stock_status <> 'out_of_stock' AND p.price > 0", $ids);
    $items = []; $clean = [];
    foreach ($rows as $r) {
        $qty = min((int)$cart[$r['id']], cart_max_qty($r));
        if ($qty < 1) continue;
        $r['qty']        = $qty;
        $r['unit_price'] = final_price($r);
        $r['line_total'] = $r['unit_price'] * $qty;
        $items[] = $r;
        $clean[(int)$r['id']] = $qty;
    }
    if ($clean != array_map('intval', $cart)) {            // something disappeared or was capped: fix the saved cart
        $removed = count($cart) - count($clean);
        $_SESSION['cart'] = $clean;
        cart_save_to_db();
        if ($removed > 0) flash_set('info', 'Beberapa produk di keranjang sudah tidak tersedia dan dihapus.');
    }
    return $cache[json_encode($clean)] = $cache[$key] = $items;
}

function cart_subtotal(array $items): float
{
    return array_sum(array_column($items, 'line_total'));
}

// ---------- Database sync (logged-in users) ----------

function cart_save_to_db(): void
{
    $user = current_user();
    if (!$user) return;
    $pdo = db();
    $pdo->prepare('INSERT IGNORE INTO carts (user_id) VALUES (?)')->execute([$user['id']]);
    $cartId = db_one('SELECT id FROM carts WHERE user_id = ?', [$user['id']])['id'];
    $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute([$cartId]);
    // INSERT ... SELECT: a product that no longer exists is simply skipped instead of breaking the page
    $ins = $pdo->prepare('INSERT INTO cart_items (cart_id, product_id, quantity) SELECT ?, id, ? FROM products WHERE id = ?');
    foreach (cart_get() as $pid => $qty) $ins->execute([$cartId, max(1, (int)$qty), (int)$pid]);
}

/** On login: combine the guest cart with the saved cart. */
function cart_merge_on_login(int $userId): void
{
    $guest = cart_get();
    $saved = db_all('SELECT ci.product_id, ci.quantity FROM cart_items ci
                     JOIN carts c ON c.id = ci.cart_id WHERE c.user_id = ?', [$userId]);
    $merged = [];
    foreach ($saved as $row) $merged[$row['product_id']] = (int)$row['quantity'];
    foreach ($guest as $pid => $qty) $merged[$pid] = min(999, ($merged[$pid] ?? 0) + $qty);
    $_SESSION['cart'] = $merged;
    $_SESSION['user_id'] = $userId;          // so cart_save_to_db() knows who we are
    cart_save_to_db();
}
