<?php
/**
 * LIVE CHAT between website visitors and the admin (inside the website, no WhatsApp).
 *
 * How it works (plain PHP, no websockets):
 *  - A visitor gets a random token in a cookie. The token IS their "login" for that conversation.
 *  - The browser asks the server for new messages every few seconds (polling) - see public/chat.php
 *    and public/assets/js/chat.js. The admin inbox does the same - see public/admin/chat/.
 *  - messages.read_at = NULL means the other side has not seen the message yet (unread badge).
 */

const CHAT_COOKIE  = 'bsf_chat';
const CHAT_MAX_LEN = 1000;

function chat_enabled(): bool { return get_setting('chat_enabled', '1') !== '0'; }

function chat_welcome(): string
{
    return get_setting('chat_welcome', 'Halo! Ada yang bisa kami bantu? Tanyakan produk, harga, atau kebutuhan layout gym Anda.');
}

// ---------- Visitor identity ----------

/** The conversation that belongs to this browser (from its cookie), or null. */
function chat_visitor_conversation(): ?array
{
    $token = $_COOKIE[CHAT_COOKIE] ?? '';
    if (!is_string($token) || !preg_match('/^[a-f0-9]{32}$/', $token)) return null;
    return db_one('SELECT * FROM chat_conversations WHERE token = ?', [$token]);
}

function chat_create_conversation(string $name, string $contact, ?int $userId): array
{
    $token = bin2hex(random_bytes(16));
    db_run('INSERT INTO chat_conversations (token, user_id, visitor_name, visitor_contact, last_message_at) VALUES (?,?,?,?,NOW())',
        [$token, $userId, $name, $contact !== '' ? $contact : null]);
    $id = (int)db()->lastInsertId();
    setcookie(CHAT_COOKIE, $token, [
        'expires'  => time() + 86400 * 30,
        'path'     => base_url() . '/',
        'httponly' => true,                       // JavaScript cannot read it
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    $_COOKIE[CHAT_COOKIE] = $token;
    return db_one('SELECT * FROM chat_conversations WHERE id = ?', [$id]);
}

// ---------- Messages ----------

/** Trim, normalise line breaks, limit length. Returns '' if empty. */
function chat_clean_body(string $body): string
{
    $body = trim(str_replace("\r\n", "\n", $body));
    $body = preg_replace("/\n{3,}/", "\n\n", $body);
    return text_cut($body, CHAT_MAX_LEN);
}

function chat_add_message(int $convId, string $sender, string $body, ?int $adminId = null): array
{
    $first = $sender !== 'admin' && !db_one('SELECT id FROM chat_messages WHERE conversation_id = ? LIMIT 1', [$convId]);
    db_run('INSERT INTO chat_messages (conversation_id, sender, admin_id, body) VALUES (?,?,?,?)',
        [$convId, $sender === 'admin' ? 'admin' : 'visitor', $adminId, $body]);
    $id = (int)db()->lastInsertId();
    // any new message re-opens a closed conversation
    db_run("UPDATE chat_conversations SET last_message_at = NOW(), status = 'open' WHERE id = ?", [$convId]);
    if ($first) {
        $c = db_one('SELECT visitor_name, visitor_contact FROM chat_conversations WHERE id = ?', [$convId]);
        notify_admin('Live chat baru dari ' . ($c['visitor_name'] ?? '-'), 'Kontak: ' . ($c['visitor_contact'] ?? '-') . "\n\n" . $body . "\n\n" . abs_url('admin/chat/index.php?id=' . $convId));
    }
    return db_one('SELECT * FROM chat_messages WHERE id = ?', [$id]);
}

function chat_messages(int $convId, int $afterId = 0): array
{
    return db_all('SELECT id, sender, body, created_at FROM chat_messages WHERE conversation_id = ? AND id > ? ORDER BY id LIMIT 200',
        [$convId, $afterId]);
}

/** Shape sent to the browser as JSON. */
function chat_format(array $m): array
{
    $ts = strtotime($m['created_at']);
    return ['id' => (int)$m['id'], 'sender' => $m['sender'], 'body' => $m['body'],
            'time' => date('Y-m-d', $ts) === date('Y-m-d') ? date('H:i', $ts) : date('d M H:i', $ts)];
}

/** Mark messages written by $fromSender as seen (called by the OTHER side when it views them). */
function chat_mark_read(int $convId, string $fromSender): void
{
    db_run('UPDATE chat_messages SET read_at = NOW() WHERE conversation_id = ? AND sender = ? AND read_at IS NULL', [$convId, $fromSender]);
}

function chat_unread_for_visitor(int $convId): int
{
    return (int)db_one("SELECT COUNT(*) n FROM chat_messages WHERE conversation_id = ? AND sender = 'admin' AND read_at IS NULL", [$convId])['n'];
}

function chat_unread_for_admin(): int
{
    return (int)db_one("SELECT COUNT(*) n FROM chat_messages WHERE sender = 'visitor' AND read_at IS NULL")['n'];
}

// ---------- Admin inbox ----------

function chat_list(): array
{
    return db_all("SELECT c.*,
        (SELECT body FROM chat_messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_body,
        (SELECT COUNT(*) FROM chat_messages m WHERE m.conversation_id = c.id AND m.sender = 'visitor' AND m.read_at IS NULL) AS unread
        FROM chat_conversations c ORDER BY c.last_message_at DESC LIMIT 100");
}

function chat_get(int $id): ?array { return db_one('SELECT * FROM chat_conversations WHERE id = ?', [$id]); }

function chat_set_status(int $id, string $status): void
{
    if (in_array($status, ['open', 'closed'], true)) db_run('UPDATE chat_conversations SET status = ? WHERE id = ?', [$status, $id]);
}

function chat_delete(int $id): void { db_run('DELETE FROM chat_conversations WHERE id = ?', [$id]); }   // messages cascade

/** JSON-ready conversation row for the inbox list. */
function chat_format_conversation(array $c): array
{
    $ts = strtotime($c['last_message_at']);
    return ['id' => (int)$c['id'], 'name' => $c['visitor_name'], 'contact' => $c['visitor_contact'], 'status' => $c['status'],
            'preview' => text_cut((string)($c['last_body'] ?? ''), 70, '…'), 'unread' => (int)($c['unread'] ?? 0),
            'time' => date('Y-m-d', $ts) === date('Y-m-d') ? date('H:i', $ts) : date('d M', $ts)];
}

// ---------- Abuse protection (per browser session) ----------

/** Allow at most $max actions of kind $key within $seconds. Records the action when allowed. */
function chat_rate_ok(string $key, int $max, int $seconds): bool
{
    $now = time();
    $log = array_filter($_SESSION['rate'][$key] ?? [], fn($t) => $t > $now - $seconds);
    if (count($log) >= $max) { $_SESSION['rate'][$key] = $log; return false; }
    $log[] = $now;
    $_SESSION['rate'][$key] = array_values($log);
    return true;
}
