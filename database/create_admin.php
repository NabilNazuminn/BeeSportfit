<?php
/**
 * Create (or reset) an ADMIN account from the command line.
 *
 *   php database/create_admin.php admin@example.com "StrongPassword123" "Admin Name"
 *
 * Run it from the project root. It refuses to run in a browser.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only.'); }
require_once __DIR__ . '/../app/config.php';

[$script, $email, $password, $name] = array_pad($argv, 4, null);
$name = $name ?: 'Admin';
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$password || strlen($password) < 8) {
    exit("Usage: php database/create_admin.php email password [name]   (password min 8 chars)\n");
}
$hash = password_hash($password, PASSWORD_DEFAULT);
$existing = db_one('SELECT id FROM users WHERE email = ?', [strtolower($email)]);
if ($existing) {
    db_run("UPDATE users SET password_hash = ?, role = 'admin', is_active = 1 WHERE id = ?", [$hash, $existing['id']]);
    echo "Updated existing user to admin: $email\n";
} else {
    db_run("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'admin')", [$name, strtolower($email), $hash]);
    echo "Admin created: $email\n";
}
