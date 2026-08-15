<?php
require_once 'conexao.php';
require_once 'auth.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
    require_once __DIR__ . '/email_config.php';
} else {
    die("vendor/autoload.php nao encontrado. Execute 'composer install'.");
}

$usuario = exigir_login(['administrador']);

echo '<pre style="background:#1e293b;color:#e2e8f0;padding:20px;border-radius:10px;font-size:14px;line-height:1.6;">';
echo "==========================================\n";
echo "  TESTE DE CONEXAO SMTP - PortariaControl\n";
echo "==========================================\n\n";

echo "[1] CONFIGURACAO ATUAL:\n";
echo "    Host:     " . SMTP_HOST . "\n";
echo "    Porta:    " . SMTP_PORT . "\n";
echo "    Usuario:  " . SMTP_USERNAME . "\n";
echo "    Senha:    " . str_repeat('*', strlen(SMTP_PASSWORD)) . " (" . strlen(SMTP_PASSWORD) . " caracteres)\n";
echo "    Seguro:   " . SMTP_SECURE . "\n";
echo "    Remetente:" . EMAIL_FROM_NAME . " <" . EMAIL_FROM . ">\n\n";

echo "[2] INICIANDO CONEXAO (DEBUG ATIVADO)...\n\n";

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->SMTPDebug   = SMTP::DEBUG_CONNECTION;
    $mail->Debugoutput = function($str, $level) {
        $prefix = match($level) {
            0 => '[CLIENT]  ',
            1 => '[SERVER]  ',
            2 => '[CONN]    ',
            3 => '[ERROR]   ',
            4 => '[LOWLVL]  ',
            default => '[UNKNOWN] '
        };
        echo "    " . $prefix . trim($str) . "\n";
    };
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';
    $mail->Timeout    = 15;

    $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
    $mail->addAddress(SMTP_USERNAME, 'Teste');
    $mail->isHTML(true);
    $mail->Subject = 'Teste SMTP - PortariaControl (' . date('d/m/Y H:i:s') . ')';
    $mail->Body    = '<h1>Teste OK!</h1><p>Se voce recebeu este e-mail, a configuracao SMTP esta funcionando perfeitamente.</p>';
    $mail->AltBody = 'Teste OK! Se voce recebeu este e-mail, a configuracao SMTP esta funcionando.';

    echo "\n[3] ENVIANDO E-MAIL DE TESTE...\n\n";

    $mail->send();
    echo "\n✅ SUCESSO! E-mail enviado com sucesso para: " . SMTP_USERNAME . "\n";
    echo "Verifique a caixa de entrada (ou SPAM) do e-mail remetente.\n";
} catch (Exception $e) {
    echo "\n==========================================\n";
    echo "❌ ERRO NO ENVIO:\n";
    echo "    " . $mail->ErrorInfo . "\n";
    echo "==========================================\n\n";
    echo "💡 DICAS DE SOLUCAO:\n\n";
    echo "   1. Verifique se a SENHA esta correta:\n";
    echo "      - Senha comum do Gmail NAO funciona se a conta tiver 2FA ativado.\n";
    echo "      - Se a conta tem Autenticacao de 2 Fatores, use SENHA DE APP.\n\n";
    echo "   2. Gerar Senha de App:\n";
    echo "      - Acesse: https://myaccount.google.com/security\n";
    echo "      - Clique em: Senhas de App\n";
    echo "      - Escolha App: Outro -> Digite: PHPMailer\n";
    echo "      - Copie a senha de 16 caracteres sem espacos\n";
    echo "      - Cole no arquivo email_config.php na linha SMTP_PASSWORD\n\n";
    echo "   3. Sem 2FA? Ative o acesso a apps menos seguros:\n";
    echo "      - Acesse: https://myaccount.google.com/lesssecureapps\n";
    echo "      - ATIVE a opcao (obs: o Gmail pode ter removido essa opcao em contas novas)\n\n";
    echo "   4. Verifique Captcha/Desbloqueio:\n";
    echo "      - Acesse: https://accounts.google.com/b/0/DisplayUnlockCaptcha\n";
    echo "      - Clique em Permitir\n\n";
    echo "   5. Tentar outra combinacao:\n";
    echo "      - STARTTLS + Porta 587 (atual)\n";
    echo "      - SSL + Porta 465 (testar alternativa)\n";
}
echo "\n</pre>";
echo '<a href="index.php" style="background:#f59e0b;color:#0f172a;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-block;margin-top:10px;">← Voltar ao Painel</a>';
?>