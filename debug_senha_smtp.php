<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (file_exists('vendor/autoload.php')) require 'vendor/autoload.php';
require_once __DIR__ . '/email_config.php';

function mask_pass($p) {
    if (!is_string($p) || $p === '') return 'VAZIO';
    return str_repeat('*', strlen($p)) . ' (' . strlen($p) . ' caracteres)';
}

$hardcoded_fallback = 'bzgcxvshxysjvuhc'; // mesma coisa que a linha 15

$v_getenv   = getenv('SMTP_PASSWORD');
$v_server   = isset($_SERVER['SMTP_PASSWORD']) ? $_SERVER['SMTP_PASSWORD'] : '(nao definido)';
$v_env      = isset($_ENV['SMTP_PASSWORD']) ? $_ENV['SMTP_PASSWORD'] : '(nao definido)';
$v_final    = SMTP_PASSWORD;

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Debug Senha SMTP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{background:#f4f6f9;font-family:Segoe UI,Arial;padding:30px;}
.card{border-radius:16px;border:none;box-shadow:0 4px 12px rgba(0,0,0,.06);}
.match-ok{background:#ecfdf5;color:#065f46;padding:12px;border-radius:10px;font-weight:bold;border-left:4px solid #10b981;}
.match-bad{background:#fef2f2;color:#991b1b;padding:12px;border-radius:10px;font-weight:bold;border-left:4px solid #ef4444;}
.row-p{padding:8px 12px;border-bottom:1px solid #f1f5f9;}
</style></head><body>
<div class="container" style="max-width:820px;">
<h1 class="mb-3 fw-bold text-dark">🔐 Debug: Qual senha o PHPMailer realmente vai usar?</h1>
<p class="text-muted mb-4">Compara todas as fontes da senha e confere se batem com o hardcoded esperado.</p>

<div class="card p-4 mb-4">
  <h5 class="fw-bold text-dark mb-3">Fontes de dados verificadas (ordem de prioridade):</h5>
  <div class="row-p d-flex justify-content-between align-items-center">
    <span class="text-secondary">1º Prioridade → <strong>getenv(SMTP_PASSWORD)</strong><br><small>(vem do SetEnv no httpd-vhosts.conf. Precisa REINICIAR APACHE para atualizar!)</small></span>
    <span class="fw-bold font-monospace">' . mask_pass($v_getenv) . '</span>
  </div>
  <div class="row-p d-flex justify-content-between align-items-center">
    <span class="text-secondary">2º Prioridade → <strong>$_SERVER[SMTP_PASSWORD]</strong></span>
    <span class="fw-bold font-monospace">' . mask_pass($v_server) . '</span>
  </div>
  <div class="row-p d-flex justify-content-between align-items-center">
    <span class="text-secondary">3º Prioridade → <strong>$_ENV[SMTP_PASSWORD]</strong></span>
    <span class="fw-bold font-monospace">' . mask_pass($v_env) . '</span>
  </div>
  <div class="row-p d-flex justify-content-between align-items-center bg-warning bg-opacity-10 rounded">
    <span class="text-secondary">4º (fallback) → <strong>Hardcoded no email_config.php L15</strong><br><small>(Valor que você colou manualmente)</small></span>
    <span class="fw-bold font-monospace">' . mask_pass($hardcoded_fallback) . '</span>
  </div>
  <div class="row-p d-flex justify-content-between align-items-center mt-2 bg-primary bg-opacity-10 rounded border-0">
    <span class="text-dark fw-semibold">✅ SENHA FINAL EFETIVA (SMTP_PASSWORD constant) →<br><small>A que o PHPMailer vai enviar para o Gmail:</small></span>
    <span class="fw-bold font-monospace fs-5 text-primary">' . mask_pass($v_final) . '</span>
  </div>
</div>';

$match = ($v_final === $hardcoded_fallback);
if ($match) {
    echo '<div class="match-ok mb-4">✅ COINCIDE! A senha efetiva é a HARDCODED. Se ainda deu 5.7.9 = problema é DESBLOQUEIO Google ou precisa tentar SSL:465.</div>';
} else {
    echo '<div class="match-bad mb-4">❌ DIFERENTE! A memória do Apache tem OUTRA senha (velha). Precisa REINICIAR Apache para ler a nova do httpd-vhosts.conf!</div>';
}

echo '<div class="card p-4 mb-4">
<h5 class="fw-bold text-dark mb-3">🧪 Ação recomendada agora:</h5>
<ul class="mb-0" style="line-height:2;">
<li><strong>1.</strong> Reinicie Apache no XAMPP Control (Stop → Start). Isto recarrega o SetEnv do VirtualHost.</li>
<li><strong>2.</strong> Abra (logado como orquideasvivendas@gmail.com): <a class="fw-bold" href="https://accounts.google.com/b/0/DisplayUnlockCaptcha" target="_blank">https://accounts.google.com/b/0/DisplayUnlockCaptcha</a> → clique em <strong>PERMITIR</strong> → espere 2 MINUTOS.</li>
<li><strong>3.</strong> Depois volte e abra: <a href="teste_smtp.php" class="fw-bold">Diagnóstico SMTP</a> novamente. Se ainda falhar, crio uma versão alternativa com SSL porta 465.</li>
</ul>
</div>';

echo '<div class="text-center"><a href="index.php" class="btn btn-secondary px-4 me-2">← Voltar Painel</a>';
echo '<a href="teste_smtp.php" class="btn btn-warning text-dark px-4 fw-semibold">Rodar Teste SMTP →</a></div>';
echo '</div></body></html>';
?>