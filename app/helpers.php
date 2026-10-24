<?php
/** Small helper functions used everywhere. */

// ---------- Output & URLs ----------

/** Escape text for HTML. ALWAYS use this when printing user/database data. */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function base_url(): string
{
    if (BASE_URL_OVERRIDE !== null) {
        return rtrim((string) BASE_URL_OVERRIDE, '/');
    }
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $public  = PUBLIC_PATH;
    if ($docRoot && $public && strpos($public, $docRoot) === 0) {
        return rtrim(str_replace('\\', '/', substr($public, strlen($docRoot))), '/');
    }
    return '';
}

/** Build a link:  url('products.php?x=1')  ->  /fitness-store/public/products.php?x=1 */
function url (string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

/**
 * URL of a file in public/assets/. A "?v=<last-modified time>" is added automatically, so when you replace
 * a logo / CSS / JS file the browser downloads the new one instead of showing a cached old copy.
 */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $url  = url('assets/' . $path);
    $file = PUBLIC_PATH . '/assets/' . $path;
    return is_file($file) ? $url . '?v=' . filemtime($file) : $url;
}

function redirect(string $path): void
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
    exit;
}

// ---------- Text & numbers ----------

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'item';
}

/** 29400000 -> "IDR 29.400.000" */
function format_rupiah($amount): string
{
    return 'IDR ' . number_format((float)$amount, 0, ',', '.');
}

function format_date($datetime): string
{
    return date('d M Y, H:i', strtotime($datetime));
}

// ---------- Flash messages (one-time notices) ----------

function flash_set(string $type, string $message, bool $sticky = false): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message, 'sticky' => $sticky];
}

/** Print and clear all flash messages. Types: success | error | info */
function flash_render(): void
{
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $auto = empty($f['sticky']) ? ' flash-auto-dismiss" data-auto-dismiss-ms="5000' : '';   // sticky = stays until the page is left
        echo '<div class="alert alert-' . e($f['type']) . $auto . '">' . e($f['message']) . '</div>';
    }
    unset($_SESSION['flash']);
}

/** Re-fill a form field after a validation error. */
function old(string $key, $default = ''): string
{
    return e(is_scalar($_POST[$key] ?? null) ? $_POST[$key] : $default);
}

// ---------- Prices ----------

/** Is a discount active right now? */
function has_discount(array $p): bool
{
    if (empty($p['discount_price']) || (float)$p['discount_price'] <= 0) return false;
    if ((float)$p['discount_price'] >= (float)$p['price']) return false;
    if (!empty($p['discount_ends_at']) && strtotime($p['discount_ends_at']) < time()) return false;
    return true;
}

/** The price the customer actually pays. */
function final_price(array $p): float
{
    return has_discount($p) ? (float)$p['discount_price'] : (float)$p['price'];
}

function discount_percent(array $p): int
{
    return has_discount($p) ? (int)round((1 - $p['discount_price'] / $p['price']) * 100) : 0;
}

// ---------- Images ----------

/** URL of a product's main picture (or the placeholder). */
function image_url(?string $path): string
{
    return $path ? url('uploads/' . ltrim($path, '/')) : asset('img/placeholder.svg');
}

/**
 * Save an uploaded image safely. Returns the saved relative path
 * (e.g. "products/ab12cd.jpg") or null if nothing uploaded. Throws on error.
 * Big photos are shrunk (max 1600 px, 800 px for logos) so pages stay fast on mobile data.
 */
function upload_image(array $file, string $folder = 'products'): ?string
{
    if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload gagal (kode ' . $file['error'] . ').');
    if ($file['size'] > MAX_UPLOAD_MB * 1024 * 1024) throw new RuntimeException('Ukuran gambar maksimal ' . MAX_UPLOAD_MB . ' MB.');

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$mime])) throw new RuntimeException('Format gambar harus JPG, PNG, atau WEBP.');
    if (@getimagesize($file['tmp_name']) === false) throw new RuntimeException('File bukan gambar yang valid.');

    $dir = PUBLIC_PATH . '/uploads/' . $folder;
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $name = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];   // renamed: never trust user filenames
    $dest = $dir . '/' . $name;
    $max  = $folder === 'brands' ? 800 : 1600;

    if (!resize_image_if_needed($file['tmp_name'], $mime, $dest, $max)) {
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new RuntimeException('Gagal menyimpan gambar. Cek izin folder uploads/.');
        }
    }
    return $folder . '/' . $name;
}

/** Shrink an image with GD when it is wider/taller than $max px. Returns true when $dest was written. */
function resize_image_if_needed(string $src, string $mime, string $dest, int $max): bool
{
    if (!extension_loaded('gd')) return false;
    [$w, $h] = getimagesize($src);
    if ($w <= $max && $h <= $max) return false;
    if ($w * $h > 40000000) throw new RuntimeException('Dimensi gambar terlalu besar. Perkecil dulu (maks. sekitar 6000 x 6000 px).');

    $img = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($src),
        'image/png'  => @imagecreatefrompng($src),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : false,
        default      => false,
    };
    if (!$img) return false;

    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {          // keep phone photos upright
        $exif = @exif_read_data($src);
        $deg  = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 1] ?? 0;
        if ($deg) { $rot = imagerotate($img, $deg, 0); if ($rot) { imagedestroy($img); $img = $rot; } }
    }
    $w = imagesx($img); $h = imagesy($img);
    $scale = min($max / $w, $max / $h, 1);
    $nw = max(1, (int)round($w * $scale)); $nh = max(1, (int)round($h * $scale));
    $out = imagecreatetruecolor($nw, $nh);
    if ($mime !== 'image/jpeg') {                                                // keep transparency
        imagealphablending($out, false); imagesavealpha($out, true);
        imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
    }
    imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $ok = match ($mime) {
        'image/jpeg' => imagejpeg($out, $dest, 85),
        'image/png'  => imagepng($out, $dest, 7),
        'image/webp' => imagewebp($out, $dest, 85),
    };
    imagedestroy($img); imagedestroy($out);
    return (bool)$ok;
}

function delete_image(?string $path): void
{
    if (!$path) return;
    $full = PUBLIC_PATH . '/uploads/' . $path;
    if (is_file($full) && strpos(realpath($full), realpath(PUBLIC_PATH . '/uploads')) === 0) @unlink($full);
}

// ---------- WhatsApp ----------

/** Is a WhatsApp number configured? (used to hide WhatsApp buttons instead of showing dead links) */
function has_wa(): bool
{
    return strlen(preg_replace('/\D/', '', get_setting('whatsapp_number', ''))) >= 8;
}

/** Link to chat with the store. $message is pre-filled text. */
function wa_link(string $message = ''): string
{
    $num = preg_replace('/\D/', '', get_setting('whatsapp_number', ''));
    return 'https://wa.me/' . $num . ($message !== '' ? '?text=' . rawurlencode($message) : '');
}

// ---------- Pagination ----------

/** Build ?query string keeping current filters but changing some keys. */
function query_with(array $changes): string
{
    $q = array_merge($_GET, $changes);
    foreach ($q as $k => $v) if ($v === '' || $v === null || is_array($v)) unset($q[$k]);
    return '?' . http_build_query($q);
}

function request_int(string $key, int $default = 1): int
{
    return max(1, (int)($_GET[$key] ?? $default));
}

// ---------- UTF-8 text length / cut (work even if PHP's mbstring extension is missing) ----------

function text_len(string $s): int
{
    return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : (int)preg_match_all('/./us', $s);
}

/** Cut to at most $max characters (not bytes); adds $suffix when something was cut. */
function text_cut(string $s, int $max, string $suffix = ''): string
{
    if (text_len($s) <= $max) return $s;
    if (function_exists('mb_substr')) return mb_substr($s, 0, $max, 'UTF-8') . $suffix;
    preg_match('/^.{0,' . $max . '}/us', $s, $m);
    return ($m[0] ?? '') . $suffix;
}


// ---------- Safe input (never crashes on ?q[]=x style requests) ----------

/** Read a TEXT value from $_GET / $_POST. Returns $default when it is missing or is not plain text (e.g. an array). */
function input_str(array $src, string $key, string $default = ''): string
{
    return is_string($src[$key] ?? null) ? trim($src[$key]) : $default;
}

/** Escape % and _ so that user text is matched literally inside LIKE. */
function like_escape(string $s): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $s);
}

/** Add an error message when $value is longer than $max characters. */
function check_len(array &$errors, string $label, string $value, int $max): void
{
    if (text_len($value) > $max) $errors[] = $label . ' maksimal ' . $max . ' karakter.';
}

// ---------- Absolute URLs (SEO, e-mails) ----------

function abs_url(string $path = ''): string
{
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return (IS_HTTPS ? 'https://' : 'http://') . $host . url($path);
}

// ---------- Rate limiting by IP (stored in the database, so it cannot be dodged by dropping cookies) ----------

function client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

function rate_table_ready(): bool
{
    static $ok = null;
    if ($ok !== null) return $ok;
    try {
        db()->exec('CREATE TABLE IF NOT EXISTS rate_limits (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, bucket VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL,
            INDEX idx_rate_bucket (bucket, created_at)) ENGINE=InnoDB');
        $ok = true;
    } catch (Throwable $e) { $ok = false; }
    return $ok;
}

/** How many hits are recorded for $bucket in the last $seconds seconds. */
function rate_count(string $bucket, int $seconds): int
{
    if (!rate_table_ready()) return 0;
    try {
        return (int)db_one('SELECT COUNT(*) n FROM rate_limits WHERE bucket = ? AND created_at > ?',
            [$bucket, date('Y-m-d H:i:s', time() - $seconds)])['n'];
    } catch (Throwable $e) { return 0; }
}

function rate_add(string $bucket): void
{
    if (!rate_table_ready()) return;
    try {
        db_run('INSERT INTO rate_limits (bucket, created_at) VALUES (?, ?)', [$bucket, date('Y-m-d H:i:s')]);
        if (random_int(1, 200) === 1) db_run('DELETE FROM rate_limits WHERE created_at < ?', [date('Y-m-d H:i:s', time() - 86400)]);
    } catch (Throwable $e) {}
}

/** Allow at most $max hits of $bucket per $seconds. Records the hit when allowed. */
function rate_ok(string $bucket, int $max, int $seconds): bool
{
    if (rate_count($bucket, $seconds) >= $max) return false;
    rate_add($bucket);
    return true;
}

// ---------- E-mail (uses the hosting's mail(); on localhost the e-mail is written to storage/mail.log instead) ----------

function send_mail(string $to, string $subject, string $body): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || str_ends_with(strtolower($to), '.example')) return false;
    $host = preg_replace('/^www\./', '', preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
    $from = 'noreply@' . (str_contains($host, '.') ? $host : 'localhost.localdomain');
    if (DEBUG) {
        $dir = __DIR__ . '/../storage';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        @file_put_contents($dir . '/mail.log', '[' . date('Y-m-d H:i:s') . "] TO: $to\nSUBJECT: $subject\n$body\n" . str_repeat('-', 60) . "\n", FILE_APPEND);
        return true;
    }
    $headers = "From: " . SITE_NAME . " <$from>\r\nContent-Type: text/plain; charset=UTF-8\r\nMIME-Version: 1.0";
    return (bool)@mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
}

/** Tell the shop owner (e-mail from Admin > Pengaturan) that something new arrived. Never throws. */
function notify_admin(string $subject, string $body): void
{
    try { send_mail(get_setting('contact_email'), '[' . SITE_NAME . '] ' . $subject, $body); } catch (Throwable $e) {}
}
