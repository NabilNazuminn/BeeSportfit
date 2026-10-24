<?php
/**
 * LIVE CHAT API for visitors (returns JSON). Used by assets/js/chat.js.
 *   GET  ?action=state            -> welcome text, my conversation + messages (marks admin replies as read)
 *   GET  ?action=poll&after=ID    -> new messages after ID (called every few seconds while the chat is open)
 *   GET  ?action=unread           -> number of unread admin replies (for the badge when the chat is closed)
 *   POST action=send              -> send a message (creates the conversation on the first message)
 */
require_once __DIR__ . '/../app/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!chat_enabled()) out(['ok' => false, 'error' => 'Live chat sedang tidak aktif.'], 404);

$action = input_str($_GET, 'action') ?: input_str($_POST, 'action');
$user   = current_user();
$conv   = chat_visitor_conversation();

// ---------------- read-only actions ----------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    session_write_close();      // don't block other page loads while we poll

    if ($action === 'state') {
        $msgs = $conv ? chat_messages((int)$conv['id']) : [];
        if ($conv) chat_mark_read((int)$conv['id'], 'admin');     // visitor is looking at the replies now
        out(['ok' => true, 'welcome' => chat_welcome(), 'hasConversation' => (bool)$conv, 'status' => $conv['status'] ?? null,
             'user' => $user ? ['name' => $user['name']] : null,
             'messages' => array_map('chat_format', $msgs), 'lastId' => $msgs ? (int)end($msgs)['id'] : 0]);
    }
    if ($action === 'poll') {
        if (!$conv) out(['ok' => true, 'messages' => [], 'status' => null]);
        $msgs = chat_messages((int)$conv['id'], (int)($_GET['after'] ?? 0));
        chat_mark_read((int)$conv['id'], 'admin');
        out(['ok' => true, 'messages' => array_map('chat_format', $msgs), 'status' => $conv['status']]);
    }
    if ($action === 'unread') {
        out(['ok' => true, 'unread' => $conv ? chat_unread_for_visitor((int)$conv['id']) : 0]);
    }
    out(['ok' => false, 'error' => 'Aksi tidak dikenal.'], 400);
}

// ---------------- send a message ----------------
if ($action !== 'send') out(['ok' => false, 'error' => 'Aksi tidak dikenal.'], 400);
if (!csrf_valid())      out(['ok' => false, 'error' => 'Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.'], 419);
if (input_str($_POST, 'website') !== '') out(['ok' => true]);            // honeypot: real people leave this hidden field empty

$body = chat_clean_body(input_str($_POST, 'body'));
if ($body === '') out(['ok' => false, 'error' => 'Pesan tidak boleh kosong.'], 422);
if (!chat_rate_ok('chat_msg', 12, 60) || !rate_ok('chatmsg:ip:' . client_ip(), 40, 600)) out(['ok' => false, 'error' => 'Terlalu banyak pesan. Tunggu sebentar.'], 429);

if (!$conv) {
    $name    = $user ? $user['name'] : input_str($_POST, 'name');
    $contact = input_str($_POST, 'contact');
    if ($name === '' || text_len($name) > 100)  out(['ok' => false, 'error' => 'Nama wajib diisi (maks. 100 karakter).'], 422);
    if (text_len($contact) > 150)               out(['ok' => false, 'error' => 'Kontak terlalu panjang.'], 422);
    if (!chat_rate_ok('chat_new', 3, 3600) || !rate_ok('chatnew:ip:' . client_ip(), 6, 3600)) out(['ok' => false, 'error' => 'Terlalu banyak percakapan baru. Coba lagi nanti.'], 429);
    $conv = chat_create_conversation($name, $contact, $user ? (int)$user['id'] : null);
}

$msg = chat_add_message((int)$conv['id'], 'visitor', $body);
out(['ok' => true, 'message' => chat_format($msg)]);
