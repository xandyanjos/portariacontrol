<?php
// email_utils.php
// Este arquivo contém funções utilitárias para envio de e-mails,
// para evitar duplicação de código.

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Certifica-se de que email_config.php foi incluído para as constantes SMTP
if (!defined('SMTP_HOST')) {
    require_once __DIR__ . '/email_config.php';
}

/**
 * Envia um e-mail usando PHPMailer, com fallback para mock local se o SMTP falhar.
 *
 * @param string $to Endereço de e-mail do destinatário.
 * @param string $toName Nome do destinatário.
 * @param string $subject Assunto do e-mail.
 * @param string $body Corpo HTML do e-mail.
 * @param string $altBody Corpo de texto simples do e-mail.
 * @return array Um array com 'ok' (bool), 'via' (string), 'file' (string|null), 'error' (string|null).
 */
function smtp_enviar_com_fallback($to, $toName, $subject, $body, $altBody) {
    $tentativas = [
        ['host' => SMTP_HOST, 'port' => SMTP_PORT, 'secure' => SMTP_SECURE, 'label' => 'STARTTLS :' . SMTP_PORT],
    ];
    if (defined('EMAIL_SMTP_FALLBACK_TRY_SSL465') && EMAIL_SMTP_FALLBACK_TRY_SSL465 && SMTP_PORT !== 465) {
        $tentativas[] = ['host' => 'smtp.gmail.com', 'port' => 465, 'secure' => SMTP_SECURE_SSL, 'label' => 'SSL :465'];
    }
    $lastErr = '';
    foreach ($tentativas as $cfg) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $cfg['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = $cfg['secure'];
            $mail->Port       = $cfg['port'];
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 12;
            $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
            $mail->addAddress($to, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $altBody;
            $mail->send();
            return ['ok' => true, 'via' => $cfg['label'], 'file' => null];
        } catch (Exception $e) {
            $lastErr = $mail->ErrorInfo;
            error_log("SMTP [{$cfg['label']}] falhou: " . $lastErr);
        }
    }
    if (defined('EMAIL_USE_MOCK_FALLBACK') && EMAIL_USE_MOCK_FALLBACK) {
        $file = email_save_mock($to, $toName, $subject, $body, $altBody);
        return ['ok' => true, 'via' => 'MOCK (gravado localmente)', 'file' => $file, 'warn' => 'SMTP indisponivel — email gravado localmente.'];
    }
    return ['ok' => false, 'via' => 'nenhuma', 'file' => null, 'error' => $lastErr];
}