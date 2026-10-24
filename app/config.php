<?php
/**
 * CONFIG  -  reads local environment settings from the project-root .env file.
 * Every page starts with:  require_once __DIR__ . '/../app/config.php';
 * which loads everything else in the right order.
 */

// Load simple KEY=value settings from the project-root .env file. Server-level
// environment variables take precedence, so deployments can configure these safely.
$__envFile = dirname(__DIR__) . '/.env';
if (is_readable($__envFile)) {
    foreach (file($__envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $__line) {
        $__line = trim($__line);
        if ($__line === '' || $__line[0] === '#' || strpos($__line, '=') === false) {
            continue;
        }
        [$__key, $__value] = explode('=', $__line, 2);
        $__key = trim($__key);
        $__value = trim($__value);
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $__key) || getenv($__key) !== false) {
            continue;
        }
        if (strlen($__value) >= 2 && (($__value[0] === '"' && substr($__value, -1) === '"') || ($__value[0] === "'" && substr($__value, -1) === "'"))) {
            $__value = substr($__value, 1, -1);
        }
        putenv($__key . '=' . $__value);
        $_ENV[$__key] = $__value;
    }
}

$__setting = static function (string $name, string $default): string {
    $value = getenv($name);
    return $value === false ? $default : $value;
};

// ---------------------------------------------------------------------
// 1. DATABASE  (change these to match XAMPP / the client's hosting)
// ---------------------------------------------------------------------
define('DB_HOST', $__setting('DB_HOST', 'localhost'));
define('DB_NAME', $__setting('DB_NAME', 'fitness_store'));
define('DB_USER', $__setting('DB_USER', 'root'));
define('DB_PASS', $__setting('DB_PASS', ''));  // XAMPP/Laragon commonly use an empty local password

// ---------------------------------------------------------------------
// 2. SITE CONSTANTS
// ---------------------------------------------------------------------
define('SITE_NAME',     'BeeSportFit');
define('SITE_TAGLINE',  'Import Gym Equipment');   // small text under the logo
define('PER_PAGE',      9);       // products per page on the catalog
define('MAX_UPLOAD_MB', 3);       // max product image size
// DEBUG is detected automatically: ON only on localhost / *.test / *.local, OFF on a real domain.
// (Force it with the environment variable APP_DEBUG=0 or 1 if you ever need to.)
$__host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
$__env  = getenv('APP_DEBUG');
define('DEBUG', $__env !== false && $__env !== '' ? $__env === '1'
    : (PHP_SAPI === 'cli' || $__host === 'localhost' || $__host === '127.0.0.1' || preg_match('/\.(test|local|localhost)$/', $__host) === 1));
define('SESSION_IDLE_ADMIN', 7200);   // admin is logged out after 2 hours without any request

// BASE_URL = the web path that points to the "public" folder.
//   null  -> auto-detect (works for XAMPP subfolders and php -S)
//   ''    -> site is at the domain root (https://example.com/)
//   '/fitness-store/public' -> type it by hand if auto-detect is wrong
define('BASE_URL_OVERRIDE', null);

// PUBLIC_PATH = real folder on disk that contains index.php (for uploads)
define('PUBLIC_PATH', is_dir(__DIR__ . '/../public')
    ? realpath(__DIR__ . '/../public')
    : rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));

define('APP_VIEWS', __DIR__ . '/views');   // shared HTML pieces

// ---------------------------------------------------------------------
// 3. BOOTSTRAP (don't edit below unless you know why)
// ---------------------------------------------------------------------
date_default_timezone_set('Asia/Jakarta');
error_reporting(E_ALL);
ini_set('display_errors', DEBUG ? '1' : '0');

// Security headers (work on Apache AND Nginx because PHP sends them)
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    ob_start();      // lets the error handler below replace a half-drawn page with a clean error page
}

$__https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
define('IS_HTTPS', $__https);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => IS_HTTPS]);
    session_start();
}

// Friendly error page on the live site (details go to the server error log, never to the visitor).
if (!DEBUG) {
    set_exception_handler(function (Throwable $e) {
        error_log('[BeeSportFit] ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        while (ob_get_level() > 0) ob_end_clean();
        if (!headers_sent()) { http_response_code(500); }
        $json = false;
        foreach (headers_list() as $h) if (stripos($h, 'content-type: application/json') === 0) $json = true;
        if ($json) { header('Content-Type: application/json'); echo '{"ok":false,"error":"Terjadi kesalahan pada server."}'; exit; }
        echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Terjadi kesalahan</title></head>'
           . '<body style="font-family:sans-serif;background:#0E0D0B;color:#eee;text-align:center;padding:4rem 1rem"><h1>Maaf, terjadi kesalahan</h1>'
           . '<p>Permintaan Anda tidak dapat diproses. Silakan coba lagi, atau hubungi kami jika masalah berlanjut.</p>'
           . '<p><a style="color:#D4AF37" href="javascript:history.back()">&larr; Kembali</a></p></body></html>';
        exit;
    });
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions/settings.php';
require_once __DIR__ . '/functions/products.php';
require_once __DIR__ . '/functions/orders.php';
require_once __DIR__ . '/functions/chat.php';
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/migrate.php';
run_migrations();
