<?php
/** Site settings + small content tables (pages, testimonials, consultations). */

// ---------- Settings (key/value) ----------

function all_settings(): array
{
    static $cache = null;
    if ($cache === null) {
        try {
            $cache = array_column(db_all('SELECT setting_key, setting_value FROM settings'), 'setting_value', 'setting_key');
        } catch (PDOException $e) {
            $cache = [];
        }
    }
    return $cache;
}

function get_setting(string $key, string $default = ''): string
{
    $all = all_settings();
    return isset($all[$key]) && $all[$key] !== '' ? $all[$key] : $default;
}

function set_setting(string $key, string $value): void
{
    db_run('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)', [$key, $value]);
}

// ---------- Static pages (warranty, terms ...) ----------

function get_page($slug): ?array             { return is_string($slug) ? db_one('SELECT * FROM pages WHERE slug = ?', [$slug]) : null; }
function get_all_pages(): array              { return db_all('SELECT * FROM pages ORDER BY title'); }

function save_page(string $slug, string $title, string $content): void
{
    db_run('UPDATE pages SET title = ?, content = ? WHERE slug = ?', [$title, $content, $slug]);
}

// ---------- Testimonials ----------

function get_testimonials(bool $onlyActive = true): array
{
    return db_all('SELECT * FROM testimonials ' . ($onlyActive ? 'WHERE is_active = 1 ' : '') . 'ORDER BY sort_order, id');
}

function get_testimonial(int $id): ?array { return db_one('SELECT * FROM testimonials WHERE id = ?', [$id]); }

function save_testimonial(array $d, ?int $id = null): void
{
    $vals = [trim($d['name']), trim($d['role_title'] ?? '') ?: null, trim($d['content']),
             !empty($d['is_active']) ? 1 : 0, (int)($d['sort_order'] ?? 0)];
    if ($id) {
        db_run('UPDATE testimonials SET name=?, role_title=?, content=?, is_active=?, sort_order=? WHERE id=?', [...$vals, $id]);
    } else {
        db_run('INSERT INTO testimonials (name, role_title, content, is_active, sort_order) VALUES (?,?,?,?,?)', $vals);
    }
}

function delete_testimonial(int $id): void { db_run('DELETE FROM testimonials WHERE id = ?', [$id]); }

// ---------- Consultation requests (leads) ----------

function project_types(): array
{
    return ['commercial' => 'Commercial Gym Center', 'home' => 'Private Home Gym', 'other' => 'Hotel / Kantor / Lainnya'];
}

/** Validate the consultation form. Returns a list of error messages. */
function validate_consultation(array $d): array
{
    $errors = [];
    $name  = is_string($d['name'] ?? null) ? trim($d['name']) : '';
    $phone = is_string($d['phone'] ?? null) ? trim($d['phone']) : '';
    if ($name === '') $errors[] = 'Nama wajib diisi.';
    check_len($errors, 'Nama', $name, 100);
    if (!preg_match('/^[0-9+\-\s()]{8,30}$/', $phone)) $errors[] = 'Nomor WhatsApp tidak valid.';
    $email = is_string($d['email'] ?? null) ? trim($d['email']) : '';
    if ($email !== '' && (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL))) $errors[] = 'Format email tidak valid.';
    check_len($errors, 'Ukuran ruangan', is_string($d['room_size'] ?? null) ? $d['room_size'] : '', 50);
    check_len($errors, 'Budget', is_string($d['budget'] ?? null) ? $d['budget'] : '', 50);
    check_len($errors, 'Pesan', is_string($d['message'] ?? null) ? $d['message'] : '', 3000);
    return $errors;
}

function create_consultation(array $d): void
{
    $s = fn($k) => is_string($d[$k] ?? null) ? trim($d[$k]) : '';
    $type = array_key_exists($s('gym_type'), project_types()) ? $s('gym_type') : 'commercial';
    db_run('INSERT INTO consultations (name, phone, email, gym_type, room_size, budget, message) VALUES (?,?,?,?,?,?,?)', [
        $s('name'), $s('phone'), $s('email') ?: null, $type, $s('room_size') ?: null, $s('budget') ?: null, $s('message') ?: null,
    ]);
    notify_admin('Konsultasi baru dari ' . $s('name'), "Nama: " . $s('name') . "\nWhatsApp: " . $s('phone') . "\nProyek: " . (project_types()[$type] ?? $type)
        . "\nUkuran: " . $s('room_size') . "\nBudget: " . $s('budget') . "\n\n" . $s('message') . "\n\n" . abs_url('admin/consultations/index.php'));
}

/** Leads for the admin, newest first, 25 per page. */
function get_consultations(int $page = 1, int $perPage = 25): array
{
    $total = (int)db_one('SELECT COUNT(*) n FROM consultations')['n'];
    $pages = max(1, (int)ceil($total / $perPage));
    $page  = min(max(1, $page), $pages);
    return ['items' => db_all("SELECT * FROM consultations ORDER BY created_at DESC, id DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage)),
            'total' => $total, 'pages' => $pages, 'page' => $page];
}

function update_consultation_status(int $id, string $status): void
{
    if (in_array($status, ['new', 'contacted', 'closed'], true)) {
        db_run('UPDATE consultations SET status = ? WHERE id = ?', [$status, $id]);
    }
}

function delete_consultation(int $id): void { db_run('DELETE FROM consultations WHERE id = ?', [$id]); }
