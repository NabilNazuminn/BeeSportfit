<?php
/**
 * LIVE CHAT API for the admin inbox (JSON). Used by assets/js/chat-admin.js and the sidebar badge.
 *   GET  ?action=unread                 -> total unread visitor messages
 *   GET  ?action=list                   -> conversations (for the left column)
 *   GET  ?action=messages&id=N&after=M  -> new messages of conversation N (marks them read)
 *   POST action=send  id=N body=...     -> reply as admin
 */
require_once __DIR__ . '/../../../app/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!is_logged_in() || !is_admin()) out(['ok' => false, 'error' => 'Tidak diizinkan.'], 403);
$admin  = current_user();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    session_write_close();
    if ($action === 'unread') out(['ok' => true, 'unread' => chat_unread_for_admin()]);
    if ($action === 'list')   out(['ok' => true, 'conversations' => array_map('chat_format_conversation', chat_list()), 'unread' => chat_unread_for_admin()]);
    if ($action === 'messages') {
        $conv = chat_get((int)($_GET['id'] ?? 0));
        if (!$conv) out(['ok' => false, 'error' => 'Percakapan tidak ditemukan.'], 404);
        $msgs = chat_messages((int)$conv['id'], (int)($_GET['after'] ?? 0));
        chat_mark_read((int)$conv['id'], 'visitor');
        out(['ok' => true, 'messages' => array_map('chat_format', $msgs), 'status' => $conv['status']]);
    }
    out(['ok' => false, 'error' => 'Aksi tidak dikenal.'], 400);
}

if (!csrf_valid()) out(['ok' => false, 'error' => 'Sesi kedaluwarsa. Muat ulang halaman.'], 419);
if ($action === 'send') {
    $conv = chat_get((int)($_POST['id'] ?? 0));
    $body = chat_clean_body((string)($_POST['body'] ?? ''));
    if (!$conv)       out(['ok' => false, 'error' => 'Percakapan tidak ditemukan.'], 404);
    if ($body === '') out(['ok' => false, 'error' => 'Pesan kosong.'], 422);
    $msg = chat_add_message((int)$conv['id'], 'admin', $body, (int)$admin['id']);
    out(['ok' => true, 'message' => chat_format($msg)]);
}
out(['ok' => false, 'error' => 'Aksi tidak dikenal.'], 400);
