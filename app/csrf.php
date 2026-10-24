<?php
/**
 * CSRF protection. Put <?= csrf_field() ?> inside every POST form and
 * call csrf_verify() at the top of the code that handles that form.
 */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** true if the submitted token matches the session token (never true for empty tokens). */
function csrf_valid(): bool
{
    $sent  = $_POST['csrf'] ?? '';
    $known = $_SESSION['csrf'] ?? '';
    return is_string($sent) && $sent !== '' && $known !== '' && hash_equals($known, $sent);
}

function csrf_verify(): void
{
    if (!csrf_valid()) {
        http_response_code(419);
        exit('Sesi formulir kedaluwarsa. Silakan kembali dan coba lagi.');
    }
}

/** Only allow POST for actions that change data. */
function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed');
    }
    csrf_verify();
}
