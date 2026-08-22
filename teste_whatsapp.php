<?php
require_once 'conexao.php';
require_once 'auth.php';
if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
    require_once __DIR__ . '/whatsapp_config.php';
}

$usuario = exigir_login(['administrador']);

$resultado = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero   = trim($_POST['numero'] ?? '');
    $nome     = trim($_POST['nome'] ?? 'Destinatário Teste');
    $mensagem = trim($_POST['mensagem'] ?? '');
    if ($mensagem === '') {
        $mensagem = wa_template_notificacao(
            $nome,
            $_POST['unidade'] ?? '10',
            $_POST['etiqueta'] ?? 'TESTE123BR',
            $_POST['origem'] ?? 'Amazon',
            $usuario['nome'],
            date('d/m/Y H:i'),
            false
        );
    }
    $resultado = whatsapp_enviar($numero, $nome, $mensagem);
}
$moradores_sel = $pdo->query("SELECT id, nome_completo, numero_unidade, telefone FROM moradores WHERE telefone IS NOT NULL AND telefone <> '' ORDER BY numero_unidade")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Teste e Configuração WhatsApp - PortariaControl</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
<style>
body { font-family:'Inter',sans-serif; background:#f0fdfa; color:#0f172a; }
.card { border:none; border-radius:18px; box-shadow:0 10px 30px rgba(2,132,199,.08); }
.section { background:#fff; border-radius:16px; padding:22px; margin-bottom:20px; border:1px solid #e2e8f0; }
.title { font-weight:800; letter-spacing:-.5px; }
code { background:#f8fafc; padding:2px 6px; border-radius:6px; color:#0f766e; }
.mono { font-family:ui-monospace,Menlo,monospace; }
</style>
</head>
<body>

<nav class="navbar navbar-dark shadow-sm py-3 navbar-simple" style="background:linear-gradient(135deg,#0f766e,#0ea5e9);">
<div class="container">
<a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="index.php">
<i class="bi bi-whatsapp text-warning fs-3"></i>
<span>Portaria<strong class="text-warning">Control</strong> · WhatsApp Teste</span>
</a>
<a href="index.php" class="btn btn-outline-light btn-sm px-3"><i class="bi bi-arrow-left me-1"></i> Voltar</a>
</div>
</nav>

<main class="container py-5">

<h1 class="title mb-4 text-dark"><i class="bi bi-whatsapp me-2 text-success"></i>Configuração e Teste de WhatsApp</h1>

<!-- Resultado do teste -->
<?php if ($resultado): ?>
<div class="section" style="background:<?= $resultado['ok'] ? '#ecfdf5' : '#fef2f2' ?>; border-left:6px solid <?= $resultado['ok'] ? '#10b981' : '#ef4444' ?>;">
<div class="row align-items-start">
<div class="col">
<div class="fw-bold mb-1" style="color:<?= $resultado['ok'] ? '#065f46' : '#991b1b' ?>; font-size:1.1rem;">
<?= $resultado['ok'] ? '<i class="bi bi-check-circle-fill me-2"></i>Envio realizado (ou mock gravado)' : '<i class="bi bi-x-circle-fill me-2"></i>Falha no envio direto (será usado mock fallback)' ?>
</div>
<div class="text-muted small">Provider: <strong class="mono"><?= htmlspecialchars($resultado['via']) ?></strong></div>
<div class="mt-2"><?= htmlspecialchars($resultado['msg']) ?></div>
<?php if (!empty($resultado['wa_me'])): ?>
<div class="mt-3">
<a class="btn btn-success fw-semibold rounded-pill px-4" href="<?= htmlspecialchars($resultado['wa_me']) ?>" target="_blank">
<i class="bi bi-whatsapp me-2"></i>ABRIR NO WHATSAPP WEB e enviar manual →
</a>
</div>
<?php endif; ?>
</div>
</div>
</div>
<?php endif; ?>

<div class="row g-4">
<!-- Coluna esquerda: formulário -->
<div class="col-lg-5">
<div class="section">
<h4 class="fw-bold text-dark mb-3"><i class="bi bi-send-fill me-2 text-teal"></i>Enviar Mensagem de Teste</h4>

<form method="POST">
<div class="mb-3">
<label class="form-label fw-semibold small text-uppercase text-secondary">Número de telefone (com DDD)</label>
<input type="text" name="numero" id="numero" class="form-control form-control-lg" placeholder="Ex: 77991643525 ou (77) 99164-3525" required>
<small class="form-text text-muted">Formato brasileiro, com ou sem máscara. É automaticamente completado com 55.</small>
</div>
<div class="mb-3">
<label class="form-label fw-semibold small text-uppercase text-secondary">Escolher morador (preenche automático)</label>
<select id="sel_morador" class="form-select">
<option value="">-- Selecione um morador com telefone cadastrado --</option>
<?php foreach ($moradores_sel as $m): ?>
<option data-nome="<?= htmlspecialchars($m['nome_completo']) ?>" data-unidade="<?= htmlspecialchars($m['numero_unidade']) ?>" value="<?= htmlspecialchars($m['telefone']) ?>">
Unid. <?= htmlspecialchars($m['numero_unidade']) ?> - <?= htmlspecialchars($m['nome_completo']) ?> · <?= htmlspecialchars($m['telefone']) ?>
</option>
<?php endforeach; ?>
</select>
</div>
<div class="mb-3">
<label class="form-label fw-semibold small text-uppercase text-secondary">Nome destinatário (opcional)</label>
<input type="text" name="nome" id="nome" class="form-control" value="Morador Teste">
</div>
<div class="mb-3">
<label class="form-label fw-semibold small text-uppercase text-secondary">Unidade</label>
<input type="text" name="unidade" id="unidade" class="form-control" value="22">
</div>
<div class="mb-3">
<label class="form-label fw-semibold small text-uppercase text-secondary">Mensagem (deixe em branco para usar o modelo oficial)</label>
<textarea name="mensagem" id="mensagem" class="form-control" rows="6" placeholder="Mensagem customizada, ou deixe em branco..."></textarea>
<small class="form-text text-muted">O modelo oficial usa a etiqueta, origem, porteiro e data formatados.</small>
</div>
<button type="submit" class="btn btn-success btn-lg w-100 fw-bold rounded-pill" style="background:linear-gradient(135deg,#10b981,#0ea5e9);border:none;">
<i class="bi bi-send-fill me-2"></i>ENVIAR TESTE AGORA
</button>
</form>
</div>
</div>

<!-- Coluna direita: documentação -->
<div class="col-lg-7">
<div class="section">
<h4 class="fw-bold text-dark mb-3"><i class="bi bi-gear-wide-connected me-2 text-warning"></i>4 Maneiras de Configurar (todas suportadas)</h4>
<div class="accordion" id="acc1">

<div class="accordion-item rounded-3 border mb-2">
<h2 class="accordion-header" id="hMeta"><button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#cMeta">🥇 1. META WhatsApp Cloud API (OFICIAL · Melhor)</button></h2>
<div id="cMeta" class="accordion-collapse collapse" data-bs-parent="#acc1"><div class="accordion-body small">
<p><strong>Custo:</strong> Gratuito até <strong>1.000 conversas/mês</strong>. Depois R$ 0,15 ~ 0,70/conversa.</p>
<p><strong>O que precisa:</strong> 1) Conta Meta for Developers → 2) App Business → 3) Número de telefone válido.</p>
<p class="mb-1"><strong>Documentação:</strong> <a class="fw-bold" href="https://business.facebook.com/developers" target="_blank">business.facebook.com/developers</a></p>
<p><strong>Arquivo para configurar:</strong> <code>whatsapp_config.php</code> (topo) → Constantes <code>WA_META_PHONE_NUMBER_ID</code>, <code>WA_META_ACCESS_TOKEN</code> e mudar <code>WA_PROVIDER = 'meta'</code>.</p>
</div></div>
</div>

<div class="accordion-item rounded-3 border mb-2">
<h2 class="accordion-header" id="hEvo"><button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#cEvo">🥈 2. Evolution API (Número pessoal · Mais Fácil)</button></h2>
<div id="cEvo" class="accordion-collapse collapse" data-bs-parent="#acc1"><div class="accordion-body small">
<p><strong>Custo:</strong> $0 ~ $10 self-hosted, ou R$ 30 ~ R$ 80 por SaaS (Evolution Brasil, WZAPI Evolution).</p>
<p><strong>Como funciona:</strong> Conecta seu NÚMERO PESSOAL via QR code (igual WhatsApp Web), não precisa de Business, não muda plano.</p>
<p><strong>Documentação:</strong> <a class="fw-bold" href="https://doc.evolution-api.com/" target="_blank">doc.evolution-api.com</a></p>
<p><strong>Arquivo para configurar:</strong> <code>whatsapp_config.php</code> → <code>WA_EVOLUTION_BASE_URL</code>, <code>WA_EVOLUTION_API_KEY</code>, <code>WA_EVOLUTION_INSTANCE</code> e mudar <code>WA_PROVIDER = 'evolution'</code>.</p>
</div></div>
</div>

<div class="accordion-item rounded-3 border mb-2">
<h2 class="accordion-header" id="hTw"><button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#cTw">🥉 3. Twilio (Número alugado · Rápido)</button></h2>
<div id="cTw" class="accordion-collapse collapse" data-bs-parent="#acc1"><div class="accordion-body small">
<p><strong>Custo:</strong> ~R$ 0,50 ~ R$ 2/mensagem (caro, mas integra em 5 minutos).</p>
<p><strong>Documentação:</strong> <a class="fw-bold" href="https://console.twilio.com/" target="_blank">console.twilio.com</a></p>
<p><strong>Arquivo:</strong> <code>whatsapp_config.php</code> → <code>WA_TWILIO_ACCOUNT_SID</code>, <code>WA_TWILIO_AUTH_TOKEN</code>, <code>WA_TWILIO_FROM</code> e mudar <code>WA_PROVIDER = 'twilio'</code>.</p>
</div></div>
</div>

<div class="accordion-item rounded-3 border">
<h2 class="accordion-header" id="hZ"><button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#cZ">4. Z-API (Brasileiro · Popular)</button></h2>
<div id="cZ" class="accordion-collapse collapse" data-bs-parent="#acc1"><div class="accordion-body small">
<p><strong>Custo:</strong> R$ 40 ~ R$ 120 / mês dependendo plano. Suporte brasileiro.</p>
<p><strong>Arquivo:</strong> <code>whatsapp_config.php</code> → <code>WA_ZAPI_BASE_URL</code>, <code>WA_ZAPI_CLIENT_KEY</code>, mudar <code>WA_PROVIDER = 'zapi'</code>.</p>
</div></div>
</div>

</div>
</div>

<div class="section">
<h4 class="fw-bold text-dark mb-3"><i class="bi bi-cpu me-2 text-info"></i>Configuração Atual</h4>
<table class="table table-sm table-borderless mb-0 small">
<tr><td class="fw-semibold text-secondary w-40">Provider selecionado</td><td class="mono fw-bold text-success"><?= WA_PROVIDER ?></td></tr>
<tr><td class="fw-semibold text-secondary">WA_META_PHONE_NUMBER_ID</td><td class="mono"><?= WA_META_PHONE_NUMBER_ID !== '' ? '******** (definido)' : '<span class="text-danger">vazio</span>' ?></td></tr>
<tr><td class="fw-semibold text-secondary">WA_META_ACCESS_TOKEN</td><td class="mono"><?= WA_META_ACCESS_TOKEN !== '' ? '******** (definido)' : '<span class="text-danger">vazio</span>' ?></td></tr>
<tr><td class="fw-semibold text-secondary">WA_EVOLUTION_BASE_URL</td><td class="mono"><?= WA_EVOLUTION_BASE_URL !== '' ? htmlspecialchars(WA_EVOLUTION_BASE_URL) : '<span class="text-danger">vazio</span>' ?></td></tr>
<tr><td class="fw-semibold text-secondary">WA_USE_MOCK_FALLBACK</td><td class="mono fw-bold"><?= WA_USE_MOCK_FALLBACK ? '<span class="text-success">SIM</span> (grava arquivo local se provider falhar)' : '<span class="text-warning">NAO</span>' ?></td></tr>
</table>
</div>
</div>
</div>

</main>

<footer class="global-footer">
    © 2026 Desenvolvido por Alexandre Anjos. Todos os direitos reservados.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
<script>
document.getElementById('sel_morador').addEventListener('change', function(){
    const opt = this.options[this.selectedIndex];
    if (opt.value){
        document.getElementById('numero').value = opt.value;
        document.getElementById('nome').value = opt.dataset.nome;
        document.getElementById('unidade').value = opt.dataset.unidade;
    }
});
</script>
</body>
</html>