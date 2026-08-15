<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function env_value($key, $fallback) {
    $hardcoded = [
        'SMTP_PASSWORD' => 'bzgcxvshxysjvuhc',
    ];
    if (isset($hardcoded[$key]) && is_string($hardcoded[$key]) && $hardcoded[$key] !== '') {
        return $hardcoded[$key];
    }
    $v = getenv($key);
    if ($v !== false && $v !== '') return $v;
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
    return $fallback;
}

define('SMTP_HOST',        env_value('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_USERNAME',    env_value('SMTP_USERNAME', 'orquideasvivendas@gmail.com'));
define('SMTP_PASSWORD',    env_value('SMTP_PASSWORD', 'bzgcxvshxysjvuhc'));
define('SMTP_SECURE',      defined('PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS') ? PHPMailer::ENCRYPTION_STARTTLS : 'tls');
define('SMTP_SECURE_SSL',  defined('PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS')     ? PHPMailer::ENCRYPTION_SMTPS     : 'ssl');
define('SMTP_PORT',        (int)env_value('SMTP_PORT', '587'));
define('EMAIL_FROM',       env_value('EMAIL_FROM', 'orquideasvivendas@gmail.com'));
define('EMAIL_FROM_NAME',  'PortariaControl');

define('EMAIL_MOCK_DIR',   __DIR__ . '/emails_pendentes');
define('EMAIL_USE_MOCK_FALLBACK', true);
define('EMAIL_SMTP_FALLBACK_TRY_SSL465', true);

if (!is_dir(EMAIL_MOCK_DIR)) {
    @mkdir(EMAIL_MOCK_DIR, 0777, true);
    if (!file_exists(EMAIL_MOCK_DIR . '/index.html')) {
        @file_put_contents(EMAIL_MOCK_DIR . '/index.html', '<html><head><meta charset="utf-8"><title>Acesso negado</title></head><body>Acesso restrito.</body></html>');
        @file_put_contents(EMAIL_MOCK_DIR . '/.htaccess', "Options -Indexes\nDeny from all\n");
    }
}

function email_save_mock($to, $toName, $subject, $body, $altBody = '') {
    if (!is_dir(EMAIL_MOCK_DIR)) @mkdir(EMAIL_MOCK_DIR, 0777, true);
    $file = EMAIL_MOCK_DIR . '/' . date('Ymd_His') . '_' . substr(preg_replace('/[^a-zA-Z0-9]/', '_', $to), 0, 30) . '_' . uniqid() . '.html';
    $html = '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Email Mock</title>';
    $html .= '<style>body{font-family:Segoe UI,Arial,sans-serif;max-width:800px;margin:30px auto;padding:20px;background:#f8fafc;color:#0f172a;}';
    $html .= 'h1{color:#0369a1;border-bottom:2px solid #0ea5e9;padding-bottom:10px;}';
    $html .= '.meta{background:#e0f2fe;padding:15px;border-radius:8px;margin-bottom:20px;border-left:4px solid #0ea5e9;}';
    $html .= '.meta p{margin:5px 0;font-size:14px;color:#075985;}';
    $html .= '.content{background:white;padding:30px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.1);}';
    $html .= '.mock-flag{background:#fef3c7;color:#92400e;padding:10px;border-radius:6px;margin-bottom:20px;font-weight:bold;border-left:4px solid #f59e0b;}';
    $html .= '</style></head><body>';
    $html .= '<div class="mock-flag">📨 Este e-mail foi GRAVADO LOCALMENTE (SMTP indisponivel). O SMTP do Gmail rejeitou a autenticacao. Abra o cliente de e-mail e envie manualmente usando o botao abaixo ou o link "mailto:".</div>';
    $html .= '<h1>' . htmlspecialchars($subject) . '</h1>';
    $html .= '<div class="meta">';
    $html .= '<p><strong>De:</strong> ' . htmlspecialchars(EMAIL_FROM_NAME . ' <' . EMAIL_FROM . '>') . '</p>';
    $html .= '<p><strong>Para:</strong> ' . htmlspecialchars(($toName ? $toName . ' ' : '') . '<' . $to . '>') . '</p>';
    $html .= '<p><strong>Data/Hora:</strong> ' . date('d/m/Y H:i:s') . '</p>';
    $html .= '<p><strong>Assunto:</strong> ' . htmlspecialchars($subject) . '</p>';
    $mailto = 'mailto:' . rawurlencode($to) . '?subject=' . rawurlencode($subject) . '&body=' . rawurlencode(strip_tags($body));
    $html .= '<p style="margin-top:15px;"><a href="' . $mailto . '" style="background:#0ea5e9;color:white;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:bold;">📧 ABRIR NO OUTLOOK/GMAIL (para enviar manualmente)</a></p>';
    $html .= '</div>';
    $html .= '<div class="content">' . $body . '</div>';
    $html .= '</body></html>';
    @file_put_contents($file, $html);
    return $file;
}
?>