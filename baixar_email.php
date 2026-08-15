<?php
require_once 'conexao.php';
require_once 'auth.php';
$usuario = exigir_login(['administrador', 'portaria']);
require_once __DIR__ . '/email_config.php';

$file = isset($_GET['file']) ? basename($_GET['file']) : '';
if (!$file || !preg_match('/^[0-9A-Za-z_\-]+\.html$/', $file)) {
    http_response_code(400);
    die("Arquivo invalido.");
}
$full = EMAIL_MOCK_DIR . '/' . $file;
if (!file_exists($full)) {
    http_response_code(404);
    die("Arquivo nao encontrado.");
}
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . filesize($full));
readfile($full);
exit;
?>