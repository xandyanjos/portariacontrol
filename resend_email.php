<?php
require_once 'conexao.php';
require_once 'auth.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
    require_once __DIR__ . '/email_config.php';
    require_once __DIR__ . '/whatsapp_config.php';
} else {
    error_log("CRITICAL ERROR: O arquivo 'vendor/autoload.php' não foi encontrado. Execute 'composer install'.");
    die("Ocorreu um erro critico no sistema. Por favor, contate o administrador.");
}

$usuario = exigir_login(['administrador', 'portaria']);

$mensagem = '';
$sucesso  = '';

$encomenda_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

function smtp_enviar_com_fallback($to, $toName, $subject, $body, $altBody) {
    $tentativas = [
        ['host' => SMTP_HOST, 'port' => SMTP_PORT, 'secure' => SMTP_SECURE, 'label' => 'STARTTLS :' . SMTP_PORT],
    ];
    if (EMAIL_SMTP_FALLBACK_TRY_SSL465 && SMTP_PORT !== 465) {
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
    if (EMAIL_USE_MOCK_FALLBACK) {
        $file = email_save_mock($to, $toName, $subject, $body, $altBody);
        return ['ok' => true, 'via' => 'MOCK (gravado localmente)', 'file' => $file, 'warn' => 'SMTP indisponivel — email gravado localmente.'];
    }
    return ['ok' => false, 'via' => 'nenhuma', 'file' => null, 'error' => $lastErr];
}

if ($encomenda_id > 0) {
    $stmtEncomenda = $pdo->prepare("SELECT codigo_etiqueta, morador_id, recebido_por_funcionario, transportadora_marketplace, data_recebimento FROM encomendas WHERE id = ?");
    $stmtEncomenda->execute([$encomenda_id]);
    $encomenda = $stmtEncomenda->fetch();

    if ($encomenda) {
        $stmtMorador = $pdo->prepare("SELECT nome_completo, email, telefone, numero_unidade FROM moradores WHERE id = ?");
        $stmtMorador->execute([$encomenda['morador_id']]);
        $morador = $stmtMorador->fetch();

        $msg_email_ok = [];
        $msg_wa_ok    = [];
        $avisos = [];

        if ($morador && !empty($morador['email'])) {
            $subject = 'Lembrete: Nova encomenda para voce!';
            $body    = "Ola, " . htmlspecialchars($morador['nome_completo']) . "!<br><br>Este e um lembrete de que uma encomenda foi recebida em seu nome e ja esta disponivel para retirada na portaria.<br><br><strong>Codigo da Etiqueta:</strong> " . htmlspecialchars($encomenda['codigo_etiqueta']) . "<br><strong>Recebida por:</strong> " . htmlspecialchars($encomenda['recebido_por_funcionario']) . "<br><br>Atenciosamente,<br>Equipe da Portaria";
            $altBody = "Ola, " . htmlspecialchars($morador['nome_completo']) . "! Lembrete: Uma nova encomenda foi recebida em seu nome. Codigo: " . htmlspecialchars($encomenda['codigo_etiqueta']);

            $res = smtp_enviar_com_fallback($morador['email'], $morador['nome_completo'], $subject, $body, $altBody);
            if ($res['ok']) {
                $msg_email_ok[] = "E-mail OK (via {$res['via']})";
                if (!empty($res['warn'])) $avisos[] = $res['warn'];
            } else {
                $avisos[] = "E-mail falhou: " . $res['error'];
            }
        }

        if ($morador && !empty($morador['telefone'])) {
            $msgWa = wa_template_notificacao(
                $morador['nome_completo'],
                $morador['numero_unidade'],
                $encomenda['codigo_etiqueta'],
                $encomenda['transportadora_marketplace'],
                $encomenda['recebido_por_funcionario'],
                date('d/m/Y H:i', strtotime($encomenda['data_recebimento'])),
                true
            );
            $resWa = whatsapp_enviar($morador['telefone'], $morador['nome_completo'], $msgWa);
            if (!empty($resWa['ok'])) {
                $msg_wa_ok[] = "WhatsApp OK (via {$resWa['via']})";
            } else {
                $avisos[] = "WhatsApp falhou: " . ($resWa['msg'] ?? 'erro desconhecido');
            }
        }

        if (empty($msg_email_ok) && empty($msg_wa_ok)) {
            $mensagem = "Nao foi possivel contatar o morador. Verifique se o morador tem e-mail ou telefone cadastrado.";
        } else {
            $sucesso = "Notificacao reenviada com sucesso para {$morador['nome_completo']} (Unid. {$morador['numero_unidade']}). — " . implode('; ', array_merge($msg_email_ok, $msg_wa_ok));
            if (!empty($avisos)) {
                $mensagem = implode(' | ', $avisos);
            }
        }
    } else {
        $mensagem = "Encomenda com ID {$encomenda_id} nao encontrada.";
    }
} else {
    $mensagem = "ID da encomenda invalido.";
}

$redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
$separator = (strpos($redirect_url, '?') !== false) ? '&' : '?';
if ($sucesso) {
    header("Location: " . $redirect_url . $separator . "email_sucesso=" . urlencode($sucesso));
    if ($mensagem) {
        $separator2 = '&';
        header("Location: " . $redirect_url . $separator . "email_sucesso=" . urlencode($sucesso) . $separator2 . "email_aviso=" . urlencode($mensagem));
    }
} else {
    header("Location: " . $redirect_url . $separator . "email_erro=" . urlencode($mensagem));
}
exit;
?>