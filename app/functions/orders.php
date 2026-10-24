<?php
/** Orders and quote requests. */

function order_statuses(): array
{
    return [
        'new'       => 'Baru',
        'confirmed' => 'Dikonfirmasi',
        'paid'      => 'Dibayar',
        'shipped'   => 'Dikirim',
        'installed' => 'Terpasang',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];
}

function status_label(string $status): string { return order_statuses()[$status] ?? $status; }

/** Unique code like INV-20261016-4821 (QT- for quotes). */
function generate_order_code(string $type): string
{
    do {
        $code = ($type === 'quote' ? 'QT-' : 'INV-') . date('Ymd') . '-' . random_int(1000, 9999);
    } while (db_one('SELECT id FROM orders WHERE order_code = ?', [$code]));
    return $code;
}

/**
 * Create an order from the current cart items.
 * $d = customer_name, customer_phone, customer_email, shipping_address, province, city, notes
 * $type = 'order' | 'quote'.  Returns the new order id. Throws RuntimeException (with a customer-friendly message)
 * when a product just became unavailable.
 * Orders (not quotes) reduce the stock of products whose stock is tracked (stock > 0); at 0 they turn "Habis".
 */
function create_order(array $d, array $items, string $type, ?int $userId): int
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $subtotal = 0.0;
        $lines = [];
        foreach ($items as $it) {
            // lock the product row and re-check it, so two customers cannot buy the last unit together
            $p = db_one('SELECT * FROM products WHERE id = ? FOR UPDATE', [$it['id']]);
            if (!cart_orderable($p)) throw new RuntimeException('Produk "' . $it['name'] . '" sudah tidak tersedia. Silakan periksa keranjang Anda.');
            $qty = (int)$it['qty'];
            $tracked = $p['stock_status'] === 'in_stock' && (int)$p['stock'] > 0;
            if ($type === 'order' && $tracked && $qty > (int)$p['stock']) {
                throw new RuntimeException('Stok "' . $p['name'] . '" tinggal ' . (int)$p['stock'] . ' unit. Kurangi jumlahnya atau minta penawaran.');
            }
            $unit = final_price($p);
            $lines[] = ['p' => $p, 'qty' => $qty, 'unit' => $unit, 'total' => $unit * $qty, 'tracked' => $tracked];
            $subtotal += $unit * $qty;
        }
        $shipping = 0.0;                       // shipping is confirmed by the admin later (Admin > Pesanan)
        $code = generate_order_code($type);

        db_run('INSERT INTO orders (order_code, user_id, order_type, status, customer_name, customer_phone, customer_email,
                shipping_address, province, city, notes, subtotal, shipping_cost, grand_total)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
            $code, $userId, $type, 'new', trim($d['customer_name']), trim($d['customer_phone']),
            trim($d['customer_email'] ?? '') ?: null, trim($d['shipping_address']),
            trim($d['province'] ?? '') ?: null, trim($d['city'] ?? '') ?: null, trim($d['notes'] ?? '') ?: null,
            $subtotal, $shipping, $subtotal + $shipping,
        ]);
        $orderId = (int)$pdo->lastInsertId();

        foreach ($lines as $ln) {
            $reduce = $type === 'order' && $ln['tracked'];
            // price & name are COPIED so future edits to the product don't change old orders
            db_run('INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, line_total, stock_reduced) VALUES (?,?,?,?,?,?,?)',
                [$orderId, $ln['p']['id'], $ln['p']['name'], $ln['unit'], $ln['qty'], $ln['total'], $reduce ? 1 : 0]);
            if ($reduce) {
                // NOTE: MySQL applies SET items left to right, so the status is decided BEFORE stock is reduced
                db_run("UPDATE products SET stock_status = IF(stock - ? <= 0, 'out_of_stock', stock_status), stock = stock - ? WHERE id = ?",
                    [$ln['qty'], $ln['qty'], $ln['p']['id']]);
            }
        }
        db_run('INSERT INTO order_status_history (order_id, status, note) VALUES (?,?,?)',
            [$orderId, 'new', $type === 'quote' ? 'Permintaan penawaran dibuat' : 'Pesanan dibuat']);

        $pdo->commit();
        return $orderId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function get_order(int $id): ?array            { return db_one('SELECT * FROM orders WHERE id = ?', [$id]); }
function get_order_items(int $orderId): array  { return db_all('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$orderId]); }
function get_user_orders(int $userId): array   { return db_all('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC', [$userId]); }

function get_order_history(int $orderId): array
{
    return db_all('SELECT h.*, u.name AS admin_name FROM order_status_history h
                   LEFT JOIN users u ON u.id = h.changed_by WHERE h.order_id = ? ORDER BY h.created_at DESC, h.id DESC', [$orderId]);
}

/** Orders for the admin list, newest first. Returns ['items'=>[], 'total'=>n, 'pages'=>n, 'page'=>n]. */
function get_all_orders($status = '', int $page = 1, int $perPage = 25): array
{
    $where = ''; $params = [];
    if (is_string($status) && $status !== '' && isset(order_statuses()[$status])) { $where = 'WHERE status = ?'; $params[] = $status; }
    $total = (int)db_one("SELECT COUNT(*) n FROM orders $where", $params)['n'];
    $pages = max(1, (int)ceil($total / $perPage));
    $page  = min(max(1, $page), $pages);
    $items = db_all("SELECT * FROM orders $where ORDER BY created_at DESC, id DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage), $params);
    return ['items' => $items, 'total' => $total, 'pages' => $pages, 'page' => $page];
}

/** Which statuses an order may move to from its current status (forward steps, cancel, or re-open a cancelled order). */
function order_next_statuses(string $current): array
{
    $map = [
        'new'       => ['confirmed', 'paid', 'cancelled'],
        'confirmed' => ['paid', 'cancelled'],
        'paid'      => ['shipped', 'cancelled'],
        'shipped'   => ['installed', 'completed'],
        'installed' => ['completed'],
        'completed' => [],
        'cancelled' => ['new'],
    ];
    return $map[$current] ?? [];
}

function update_order_status(int $id, string $status, ?int $adminId, string $note = ''): bool
{
    $order = get_order($id);
    if (!$order || !isset(order_statuses()[$status])) return false;
    $from = $order['status'];
    if ($from !== $status && !in_array($status, order_next_statuses($from), true)) return false;

    $pdo = db();
    $pdo->beginTransaction();
    try {
        db_run('UPDATE orders SET status = ? WHERE id = ?', [$status, $id]);
        db_run('INSERT INTO order_status_history (order_id, status, note, changed_by) VALUES (?,?,?,?)',
            [$id, $status, $note ?: null, $adminId]);
        if ($status === 'cancelled' && $from !== 'cancelled') order_restore_stock($id);
        if ($from === 'cancelled' && $status === 'new')       order_take_stock_again($id);
        $pdo->commit();
    } catch (Throwable $e) { $pdo->rollBack(); throw $e; }

    if ($from !== $status) order_mail_customer($order, 'Status pesanan ' . $order['order_code'] . ': ' . status_label($status),
        "Halo " . $order['customer_name'] . ",\n\nStatus " . ($order['order_type'] === 'quote' ? 'permintaan penawaran' : 'pesanan') . ' ' . $order['order_code']
        . " sekarang: " . status_label($status) . ($note ? "\nCatatan: $note" : '') . "\n\nLihat detail: " . abs_url('account/order-detail.php?id=' . $id));
    return true;
}

/** Give stock back when an order is cancelled (only units that this order actually took from stock). */
function order_restore_stock(int $orderId): void
{
    foreach (db_all('SELECT * FROM order_items WHERE order_id = ? AND stock_reduced = 1 AND product_id IS NOT NULL', [$orderId]) as $it) {
        db_run("UPDATE products SET stock = stock + ?, stock_status = IF(stock_status = 'out_of_stock', 'in_stock', stock_status) WHERE id = ?", [$it['quantity'], $it['product_id']]);
        db_run('UPDATE order_items SET stock_reduced = 2 WHERE id = ?', [$it['id']]);          // 2 = given back
    }
}

/** A cancelled order was re-opened: take the stock again where possible. */
function order_take_stock_again(int $orderId): void
{
    foreach (db_all('SELECT * FROM order_items WHERE order_id = ? AND stock_reduced = 2 AND product_id IS NOT NULL', [$orderId]) as $it) {
        db_run("UPDATE products SET stock_status = IF(stock - ? <= 0, 'out_of_stock', stock_status), stock = GREATEST(stock - ?, 0) WHERE id = ?",
            [$it['quantity'], $it['quantity'], $it['product_id']]);
        db_run('UPDATE order_items SET stock_reduced = 1 WHERE id = ?', [$it['id']]);
    }
}

/**
 * Admin sets the final prices: new unit price per item ($prices = [order_item_id => price]) and the shipping cost.
 * Totals are recalculated. Returns an error message or null on success.
 */
function update_order_pricing(int $orderId, array $prices, string $shipping, ?int $adminId): ?string
{
    $order = get_order($orderId);
    if (!$order) return 'Pesanan tidak ditemukan.';
    if (in_array($order['status'], ['completed', 'cancelled'], true)) return 'Pesanan yang selesai/dibatalkan tidak bisa diubah harganya.';
    $shipping = trim(str_replace(['.', ' '], '', $shipping));
    if ($shipping === '') $shipping = '0';
    if (!is_numeric($shipping) || $shipping < 0 || $shipping > 9999999999999) return 'Ongkos kirim harus berupa angka 0 atau lebih.';

    $items = get_order_items($orderId);
    $subtotal = 0.0; $changes = [];
    foreach ($items as $it) {
        $raw = isset($prices[$it['id']]) && is_string($prices[$it['id']]) ? trim(str_replace(['.', ' '], '', $prices[$it['id']])) : (string)$it['unit_price'];
        if (!is_numeric($raw) || $raw < 0 || $raw > 9999999999999) return 'Harga untuk "' . $it['product_name'] . '" tidak valid.';
        $changes[$it['id']] = [(float)$raw, (float)$raw * (int)$it['quantity']];
        $subtotal += (float)$raw * (int)$it['quantity'];
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        foreach ($changes as $itemId => [$unit, $line]) db_run('UPDATE order_items SET unit_price = ?, line_total = ? WHERE id = ?', [$unit, $line, $itemId]);
        db_run('UPDATE orders SET subtotal = ?, shipping_cost = ?, grand_total = ? WHERE id = ?', [$subtotal, (float)$shipping, $subtotal + (float)$shipping, $orderId]);
        db_run('INSERT INTO order_status_history (order_id, status, note, changed_by) VALUES (?,?,?,?)',
            [$orderId, $order['status'], 'Harga / ongkos kirim diperbarui. Total: ' . format_rupiah($subtotal + (float)$shipping), $adminId]);
        $pdo->commit();
    } catch (Throwable $e) { $pdo->rollBack(); throw $e; }

    order_mail_customer($order, 'Total ' . $order['order_code'] . ' sudah dikonfirmasi',
        "Halo " . $order['customer_name'] . ",\n\nHarga dan ongkos kirim untuk " . $order['order_code'] . " sudah kami konfirmasi.\nSubtotal: " . format_rupiah($subtotal)
        . "\nOngkos kirim: " . format_rupiah((float)$shipping) . "\nTOTAL: " . format_rupiah($subtotal + (float)$shipping)
        . "\n\nLihat detail: " . abs_url('account/order-detail.php?id=' . $orderId));
    return null;
}

/** A customer cancels their own order while it is still "Baru". */
function customer_cancel_order(int $orderId, int $userId): bool
{
    $order = get_order($orderId);
    if (!$order || (int)$order['user_id'] !== $userId || $order['status'] !== 'new') return false;
    $ok = update_order_status($orderId, 'cancelled', null, 'Dibatalkan oleh pelanggan');
    if ($ok) notify_admin('Pesanan dibatalkan ' . $order['order_code'], 'Pelanggan ' . $order['customer_name'] . ' membatalkan ' . $order['order_code'] . ".\n" . abs_url('admin/orders/detail.php?id=' . $orderId));
    return $ok;
}

/** The ready-written WhatsApp message for an order / quote (code, items, subtotal, customer details). */
function order_wa_message(array $order, array $items): string
{
    $isQuote = $order['order_type'] === 'quote';
    $m = 'Halo ' . SITE_NAME . ', saya ingin ' . ($isQuote ? 'meminta penawaran' : 'memesan') . ' (kode ' . $order['order_code'] . "):\n";
    foreach ($items as $it) $m .= '- ' . $it['product_name'] . ' x' . (int)$it['quantity'] . ' = ' . format_rupiah($it['line_total']) . "\n";
    $m .= 'Subtotal: ' . format_rupiah($order['subtotal']) . "\n\n";
    $m .= 'Nama: ' . $order['customer_name'] . "\nTelepon: " . $order['customer_phone'] . "\nAlamat: " . $order['shipping_address'];
    $place = trim(($order['city'] ?? '') . ', ' . ($order['province'] ?? ''), ', ');
    if ($place !== '') $m .= ', ' . $place;
    return $m . "\n\nMohon info ongkir dan cara pembayaran. Terima kasih.";
}

function order_mail_customer(array $order, string $subject, string $body): void
{
    try { if (!empty($order['customer_email'])) send_mail($order['customer_email'], $subject . ' - ' . SITE_NAME, $body); } catch (Throwable $e) {}
}

/** Numbers for the admin dashboard. */
function dashboard_stats(): array
{
    return [
        'products'      => (int)db_one('SELECT COUNT(*) n FROM products')['n'],
        'orders_new'    => (int)db_one("SELECT COUNT(*) n FROM orders WHERE status = 'new'")['n'],
        'orders_total'  => (int)db_one('SELECT COUNT(*) n FROM orders')['n'],
        'leads_new'     => (int)db_one("SELECT COUNT(*) n FROM consultations WHERE status = 'new'")['n'],
        'customers'     => (int)db_one("SELECT COUNT(*) n FROM users WHERE role = 'customer'")['n'],
        'revenue'       => (float)(db_one("SELECT COALESCE(SUM(grand_total),0) s FROM orders
                                            WHERE order_type = 'order' AND status IN ('paid','shipped','installed','completed')")['s']),
        'recent_orders' => db_all('SELECT * FROM orders ORDER BY created_at DESC LIMIT 5'),
    ];
}
