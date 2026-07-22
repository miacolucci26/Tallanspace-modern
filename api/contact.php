<?php
header('Content-Type: application/json; charset=UTF-8');

$recipient = 'tallanspace@gmail.com';

function respond(bool $ok, string $error = ''): void
{
    http_response_code($ok ? 200 : 400);
    echo json_encode($ok ? ['ok' => true] : ['ok' => false, 'error' => $error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'method_not_allowed');
}

// Honeypot: real visitors never fill this hidden field.
if (!empty($_POST['company'])) {
    respond(true);
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'invalid_fields');
}

// Strip line breaks so submitted values can't inject extra mail headers.
$safeName = str_replace(["\r", "\n"], '', $name);
$safeEmail = str_replace(["\r", "\n"], '', $email);

$subject = '=?UTF-8?B?' . base64_encode("Nuevo mensaje web — {$safeName}") . '?=';
$body = "Nombre: {$safeName}\nCorreo: {$safeEmail}\n\nMensaje:\n{$message}\n";

$headers = [
    'From: Tallanspace Web <web@tallanspace.com>',
    "Reply-To: {$safeEmail}",
    'Content-Type: text/plain; charset=UTF-8',
];

$sent = mail($recipient, $subject, $body, implode("\r\n", $headers));

respond($sent, $sent ? '' : 'send_failed');
