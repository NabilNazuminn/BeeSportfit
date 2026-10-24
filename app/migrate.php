<?php
/**
 * AUTOMATIC DATABASE UPDATES
 * When you upload a newer version of the website over an existing one, this file adds any new tables / columns
 * by itself (once). Fresh installs already have everything from database/schema.sql.
 * Safe to run many times. If your database user may not ALTER tables, run database/update_v2.sql by hand instead.
 */

function run_migrations(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        if ((int)get_setting('schema_version', '1') >= 2) return;
        $pdo = db();

        $hasCol = function (string $table, string $col) use ($pdo): bool {
            $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
            $st->execute([$table, $col]);
            return (int)$st->fetchColumn() > 0;
        };

        $pdo->exec('CREATE TABLE IF NOT EXISTS rate_limits (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, bucket VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL,
            INDEX idx_rate_bucket (bucket, created_at)) ENGINE=InnoDB');

        $pdo->exec('CREATE TABLE IF NOT EXISTS password_resets (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL, token_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL,
            CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_reset_token (token_hash)) ENGINE=InnoDB');

        if (!$hasCol('order_items', 'stock_reduced')) {
            $pdo->exec('ALTER TABLE order_items ADD COLUMN stock_reduced TINYINT(1) NOT NULL DEFAULT 0');
        }

        // the old default said 34 provinces; Indonesia has 38
        db_run("UPDATE settings SET setting_value = '38' WHERE setting_key = 'stat_provinces' AND setting_value = '34'");
        // replace the two placeholder pages with a first draft (only if nobody edited them yet)
        require_once __DIR__ . '/default_pages.php';
        db_run('UPDATE pages SET content = ? WHERE slug = ? AND content = ?', [default_page_html('warranty'), 'warranty', '<p>Isi kebijakan garansi.</p>']);
        db_run('UPDATE pages SET content = ? WHERE slug = ? AND content = ?', [default_page_html('terms'), 'terms', '<p>Isi syarat dan ketentuan.</p>']);

        set_setting('schema_version', '2');
    } catch (Throwable $e) {
        error_log('[BeeSportFit] migration skipped: ' . $e->getMessage());
    }
}
