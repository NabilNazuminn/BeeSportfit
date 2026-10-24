<?php
/** Login, logout, registration and page protection. */

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = isset($_SESSION['user_id'])
            ? db_one('SELECT id, name, email, phone, role FROM users WHERE id = ? AND is_active = 1', [$_SESSION['user_id']])
            : null;
        if ($user && $user['role'] === 'admin') {                       // admins are signed out after a long idle period
            $seen = (int)($_SESSION['admin_seen'] ?? time());
            if (time() - $seen > SESSION_IDLE_ADMIN) {
                unset($_SESSION['user_id'], $_SESSION['admin_seen']);
                $user = null;
            } else {
                $_SESSION['admin_seen'] = time();
            }
        }
    }
    return $user;
}

function is_logged_in(): bool { return current_user() !== null; }
function is_admin(): bool     { return (current_user()['role'] ?? '') === 'admin'; }

/** Put this at the top of pages only logged-in users may see. */
function require_login(): void
{
    if (!is_logged_in()) {
        flash_set('info', 'Silakan login terlebih dahulu.');
        redirect('login.php?next=' . urlencode(ltrim($_SERVER['REQUEST_URI'] ?? '', '/')));
    }
}

/** Put this at the top of every admin page. */
function require_admin(): void
{
    if (!is_logged_in() || !is_admin()) {
        redirect('admin/login.php');
    }
}

/** Too many failed logins from this IP or for this e-mail in the last 15 minutes? */
function login_blocked(string $email): bool
{
    return rate_count('login:ip:' . client_ip(), 900) >= 10 || rate_count('login:em:' . strtolower(trim($email)), 900) >= 5;
}

function login_failed(string $email): void
{
    rate_add('login:ip:' . client_ip());
    rate_add('login:em:' . strtolower(trim($email)));
}

/** Check email + password. Returns the user row or null. */
function attempt_login(string $email, string $password): ?array
{
    $user = db_one('SELECT * FROM users WHERE email = ? AND is_active = 1', [strtolower(trim($email))]);
    if ($user && password_verify($password, $user['password_hash'])) {
        return $user;
    }
    return null;
}

function login_user(array $user): void
{
    session_regenerate_id(true);          // stops session fixation
    $_SESSION['user_id'] = (int)$user['id'];
    cart_merge_on_login((int)$user['id']);
}

function logout_user(): void
{
    $_SESSION = [];
    session_destroy();
}

/** Create a customer account. Returns [id|null, error|null]. */
function register_user(string $name, string $email, string $phone, string $password): array
{
    $email = strtolower(trim($email));
    $name  = trim($name);
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) return [null, 'Nama dan email yang valid wajib diisi.'];
    if (text_len($name) > 100)  return [null, 'Nama maksimal 100 karakter.'];
    if (strlen($email) > 150)   return [null, 'Email maksimal 150 karakter.'];
    if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{8,30}$/', $phone)) return [null, 'Nomor telepon tidak valid.'];
    if (strlen($password) < 8)  return [null, 'Password minimal 8 karakter.'];
    if (strlen($password) > 72) return [null, 'Password maksimal 72 karakter.'];
    if (!rate_ok('register:ip:' . client_ip(), 8, 3600)) return [null, 'Terlalu banyak pendaftaran dari jaringan ini. Coba lagi nanti.'];
    if (db_one('SELECT id FROM users WHERE email = ?', [$email])) return [null, 'Email sudah terdaftar.'];

    db_run('INSERT INTO users (name, email, phone, password_hash) VALUES (?, ?, ?, ?)',
        [$name, $email, $phone ?: null, password_hash($password, PASSWORD_DEFAULT)]);
    return [(int)db()->lastInsertId(), null];
}

/** Only allow redirecting to pages inside this site (blocks //host, \host, tabs/newlines and absolute URLs). */
function safe_next(string $next, string $fallback = 'index.php'): string
{
    $next = ltrim($next, '/');
    if ($next === '' || strpos($next, chr(92)) !== false || preg_match('/[[:cntrl:]\s]/', $next)) return $fallback;
    if (preg_match('#^([a-z][a-z0-9+.\-]*:|//)#i', $next) || strpos($next, '..') !== false) return $fallback;
    if (!preg_match('#^[A-Za-z0-9_]#', $next)) return $fallback;
    $base = ltrim(base_url(), '/');
    if ($base !== '' && strpos($next, $base . '/') === 0) $next = substr($next, strlen($base) + 1);
    return $next;
}

// ---------- Password reset / change ----------

/** Create a one-hour reset link for the account with this e-mail. Returns the link or null (unknown e-mail). */
function create_password_reset(string $email): ?array
{
    $user = db_one('SELECT id, name, email FROM users WHERE email = ? AND is_active = 1', [strtolower(trim($email))]);
    if (!$user) return null;
    $token = bin2hex(random_bytes(32));
    db_run('DELETE FROM password_resets WHERE user_id = ?', [$user['id']]);
    db_run('INSERT INTO password_resets (user_id, token_hash, expires_at, created_at) VALUES (?,?,?,?)',
        [$user['id'], hash('sha256', $token), date('Y-m-d H:i:s', time() + 3600), date('Y-m-d H:i:s')]);
    return ['user' => $user, 'link' => abs_url('reset-password.php?token=' . $token)];
}

/** The user a (still valid) reset token belongs to, or null. */
function find_reset_user(string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) return null;
    return db_one('SELECT u.id, u.name, u.email FROM password_resets r JOIN users u ON u.id = r.user_id
                   WHERE r.token_hash = ? AND r.expires_at > ? AND u.is_active = 1', [hash('sha256', $token), date('Y-m-d H:i:s')]);
}

/** Validate a new password. Returns an error message or null. */
function password_problem(string $pw, string $pw2): ?string
{
    if ($pw !== $pw2)      return 'Konfirmasi password tidak sama.';
    if (strlen($pw) < 8)   return 'Password minimal 8 karakter.';
    if (strlen($pw) > 72)  return 'Password maksimal 72 karakter.';
    return null;
}

function set_user_password(int $userId, string $pw): void
{
    db_run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $userId]);
    db_run('DELETE FROM password_resets WHERE user_id = ?', [$userId]);
}
