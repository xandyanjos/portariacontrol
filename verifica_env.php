<?php
require_once 'conexao.php';
require_once 'auth.php';
use PHPMailer\PHPMailer\PHPMailer;

if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
    require_once __DIR__ . '/email_config.php';
}

$usuario = exigir_login(['administrador']);

$env_host     = getenv('SMTP_HOST');
$env_user     = getenv('SMTP_USERNAME');
$env_pass     = getenv('SMTP_PASSWORD');
$env_port     = getenv('SMTP_PORT');
$env_from     = getenv('EMAIL_FROM');

$mask = function($s) {
    if (!$s) return '(vazio / nao definido)';
    $len = strlen($s);
    if ($len <= 4) return str_repeat('*', $len);
    return $s[0] . substr($s, 1, (int)($len/3)) . str_repeat('*', max(1, (int)($len/3))) . substr($s, -(int)($len/3)) . " ($len caracteres)";
};

echo '<pre style="background:#0f172a;color:#e2e8f0;padding:25px;border-radius:12px;font-size:14px;line-height:1.7;">';
echo "==================================================\n";
echo "   VERIFICAO DE VARIAVEIS DE AMBIENTE APACHE\n";
echo "==================================================\n\n";
echo "Este script mostra os valores que o PHP realmente esta recebendo.\n";
echo "Se houver divergencia do valor esperado, reinicie o Apache.\n\n";

echo "🔹 Variaveis SetEnv do VirtualHost (httpd-vhosts.conf):\n";
echo "    SMTP_HOST     -> " . ($env_host ?: '❌ NAO DEFINIDO') . "\n";
echo "    SMTP_USERNAME -> " . ($env_user ?: '❌ NAO DEFINIDO') . "\n";
echo "    SMTP_PASSWORD -> " . $mask($env_pass) . "\n";
echo "    SMTP_PORT     -> " . ($env_port ?: '❌ NAO DEFINIDO') . "\n";
echo "    EMAIL_FROM    -> " . ($env_from ?: '❌ NAO DEFINIDO') . "\n\n";

echo "--------------------------------------------------\n";
echo "🔹 Valores usados pelo email_config.php (define()):\n";
echo "    SMTP_HOST     -> " . SMTP_HOST . "\n";
echo "    SMTP_USERNAME -> " . SMTP_USERNAME . "\n";
echo "    SMTP_PASSWORD -> " . $mask(SMTP_PASSWORD) . "\n";
echo "    SMTP_PORT     -> " . SMTP_PORT . "\n";
echo "    EMAIL_FROM    -> " . EMAIL_FROM . "\n";
echo "    SMTP_SECURE   -> " . (SMTP_SECURE === PHPMailer::ENCRYPTION_STARTTLS ? 'STARTTLS' : (SMTP_SECURE === PHPMailer::ENCRYPTION_SMTPS ? 'SSL/SMTPS' : 'OUTRO')) . "\n\n";

echo "--------------------------------------------------\n";
echo "🔹 HASH MD5 da senha atual (para comparar com valor esperado):\n";
echo "    getenv (Apache SetEnv) : " . md5($env_pass ?: '') . "\n";
echo "    define (email_config)  : " . md5(SMTP_PASSWORD) . "\n";
echo "    IGUAIS?                -> " . (md5($env_pass ?: '') === md5(SMTP_PASSWORD) ? '✅ SIM (OK)' : '❌ NAO (Apache ainda nao reiniciou!)') . "\n\n";

echo "--------------------------------------------------\n";
echo "📋 PASSOS PARA CORRIGIR (ordem de prioridade):\n";
echo "--------------------------------------------------\n\n";
echo "   1. 🔴 PRIMEIRO: Reinicie o Apache no XAMPP Control!\n";
echo "      • Stop → aguarde 2s → Start\n";
echo "      • O Apache so le o httpd-vhosts.conf no RESTART.\n";
echo "      • Sem reiniciar = senha antiga ainda em memoria.\n\n";

echo "   2. 🟡 SEGUNDO: Desbloqueio de Captcha do Gmail (30s)\n";
echo "      • Abra: https://accounts.google.com/b/0/DisplayUnlockCaptcha\n";
echo "      • (logado com orquideasvivendas@gmail.com)\n";
echo "      • Clique em Permitir / Continuar\n";
echo "      • Espere 2 minutos\n\n";

echo "   3. 🟢 TERCEIRO: Confirme que é SENHA DE APP mesmo.\n";
echo "      A Senha de App gerada pelo Google tem 16 CARACTERES.\n";
echo "      Nao pode ter espacos, nem ser a senha normal do email.\n\n";
echo "      ATENCAO com caracteres parecidos:\n";
echo "        • O (letra o maiuscula) vs 0 (zero)\n";
echo "        • l (L minusculo) vs 1 (um) vs I (i maiusculo)\n";
echo "        • Digite/cole novamente SEM ESPACOS.\n";
echo "        • Gere uma nova em: https://myaccount.google.com/security\n";
echo "          → Senhas de App → Outro → PHPMailer → Gerar\n\n";

echo "==================================================\n";
echo "</pre>";
echo '<a href="teste_smtp.php" style="background:#22c55e;color:#052e16;padding:12px 24px;border-radius:10px;text-decoration:none;font-weight:bold;display:inline-block;margin-right:10px;">🔁 Rodar Teste SMTP Novamente</a>';
echo '<a href="index.php" style="background:#94a3b8;color:#0f172a;padding:12px 24px;border-radius:10px;text-decoration:none;font-weight:bold;display:inline-block;">← Voltar ao Painel</a>';
?>