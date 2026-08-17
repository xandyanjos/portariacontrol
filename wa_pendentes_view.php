<?php
require_once 'conexao.php';
require_once 'auth.php';
if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
    require_once __DIR__ . '/whatsapp_config.php';
}
$usuario = exigir_login(['administrador', 'portaria']);

$dir = WA_MOCK_DIR;
$view = $_GET['view'] ?? '';

if (!empty($_GET['delete']) && $usuario['perfil'] === 'administrador') {
    $file = realpath($dir . '/' . basename($_GET['delete']));
    if ($file && strpos($file, realpath($dir)) === 0 && is_file($file)) {
        unlink($file);
    }
    header('Location: ' . basename(__FILE__));
    exit;
}

if (!empty($_GET['limpar_todos']) && $usuario['perfil'] === 'administrador') {
    foreach (glob($dir . '/*.txt') as $f) @unlink($f);
    header('Location: ' . basename(__FILE__));
    exit;
}

$arquivos = glob($dir . '/*.txt');
if ($arquivos === false) $arquivos = [];
usort($arquivos, fn($a,$b) => filemtime($b) - filemtime($a));

$conteudo = null;
$waLink = null;
if ($view) {
    $file = realpath($dir . '/' . basename($view));
    if ($file && strpos($file, realpath($dir)) === 0 && is_file($file)) {
        $conteudo = file_get_contents($file);
        if (preg_match('/(https:\/\/wa\.me\/\S+)/', $conteudo, $m)) {
            $waLink = $m[1];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WhatsApp Pendentes - PortariaControl</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
body { font-family:'Inter',sans-serif; background:#f0fdf4; color:#0f172a; }
.sidebar a { color:#cbd5e1; padding:10px 16px; display:block; text-decoration:none; border-radius:8px; margin-bottom:4px; transition:all .15s; }
.sidebar a:hover, .sidebar a.active { background:rgba(16,185,129,.12); color:#fff; }
.sidebar-brand { font-weight:800; color:#fff; padding:18px 16px; border-bottom:1px solid rgba(255,255,255,.08); }
.wrapper { display:flex; min-height:100vh; }
.sidebar { width:260px; background:#0f172a; padding:0; flex-shrink:0; }
.main { flex-grow:1; padding:30px; max-width: calc(100vw - 260px); overflow-x: hidden; } /* Ajuste para evitar overflow horizontal */
.card { border:none; border-radius:16px; box-shadow:0 8px 20px rgba(2,132,199,.06); }
.file-item { padding:10px 12px; border-radius:10px; cursor:pointer; transition:all .15s; margin-bottom:6px; }
.file-item:hover { background:#ecfeff; }
.file-item.active { background:#dcfce7; border-left:4px solid #10b981; }
.preview { white-space:pre-wrap; font-family:ui-monospace,Menlo,Consolas,monospace; font-size:0.9rem; background:#0f172a; color:#e2e8f0; padding:22px; border-radius:14px; max-height:420px; overflow:auto; word-break: break-all; } /* Adicionado word-break para textos longos */
</style>
</head>
<body>
<div class="wrapper">
<aside class="sidebar">
<div class="sidebar-brand d-flex align-items-center gap-2">
<i class="bi bi-box-seam text-warning fs-3"></i>
Portaria<strong class="text-warning">Control</strong>
</div>
<div style="padding:10px 12px;">
<?php
$menu = [
    'index.php' => ['bi-grid-1x2-fill', 'Painel Principal'],
    'cadastrar_encomenda.php' => ['bi-plus-circle-fill', 'Nova Encomenda'],
    'moradores.php' => ['bi-people-fill', 'Gestão de Moradores'],
    'historico.php' => ['bi-clock-history', 'Histórico de Retiradas'],
    'retirada_morador.php' => ['bi-box-arrow-up-right text-info', 'Retirada Morador (Auto)'],
    'emails_pendentes_view.php' => ['bi-envelope-paper text-warning', 'E-mails Pendentes'],
    basename(__FILE__) => ['bi-whatsapp text-success', '<strong>WhatsApp Pendentes</strong>'],
];
foreach ($menu as $url => [$icon, $label]): ?>
<a href="<?= htmlspecialchars($url) ?>" class="<?= basename($_SERVER['PHP_SELF']) === basename($url) ? 'active' : '' ?>"><i class="bi <?= $icon ?> me-2"></i> <?= $label ?></a>
<?php endforeach; ?>
<?php if ($usuario['perfil'] === 'administrador'): ?>
<a href="teste_whatsapp.php" target="_blank"><i class="bi bi-whatsapp me-2" style="color:#10b981"></i> Teste WhatsApp (Admin)</a>
<a href="teste_smtp.php" target="_blank"><i class="bi bi-envelope-exclamation-fill text-info me-2"></i> Diagnóstico SMTP</a>
<?php endif; ?>
</div>
<div class="text-center mt-4 small text-muted-50 px-3">
<div class="fw-semibold text-light mb-1"><?= htmlspecialchars($usuario['nome']) ?></div>
<div class="small text-warning"><?= htmlspecialchars(label_perfil($usuario['perfil'])) ?></div>
<a href="logout.php" class="btn btn-outline-light btn-sm mt-3 w-75">Sair</a>
</div>
</aside>

<section class="main">
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
<div>
<h1 class="fw-bold text-dark mb-1"><i class="bi bi-whatsapp me-2 text-success"></i>WhatsApp Pendentes</h1>
<p class="text-muted small mb-0">Mensagens gravadas localmente (WA_USE_MOCK_FALLBACK). Use o botão verde para abrir no WhatsApp Web e enviar manualmente.</p>
</div>
<div class="d-flex gap-2">
<a href="index.php" class="btn btn-secondary rounded-pill px-4"><i class="bi bi-arrow-left me-1"></i> Voltar</a>
<?php if ($usuario['perfil'] === 'administrador'): ?>
<a href="?limpar_todos=1" onclick="return confirm('Tem certeza que deseja apagar TODAS as mensagens WhatsApp pendentes?')" class="btn btn-danger rounded-pill px-4">
<i class="bi bi-trash3 me-1"></i> Limpar Todos (<?= count($arquivos) ?>)
</a>
<?php endif; ?>
</div>
</div>

<?php if (count($arquivos) === 0): ?>
<div class="card p-5 text-center">
<div class="mb-3"><i class="bi bi-whatsapp" style="font-size:60px; color:#10b981;"></i></div>
<h3 class="fw-bold">Parabéns! Nenhum WhatsApp pendente.</h3>
<p class="text-muted mb-0">Todas as mensagens foram enviadas (ou usaram o provider nativo com sucesso). Quando cadastrar uma encomenda e o provider falhar, ela aparece aqui.</p>
</div>
<?php else: ?>
<div class="row g-3">
<div class="col-lg-4">
<div class="card p-3" style="max-height:520px;overflow:auto;">
<h5 class="fw-bold text-dark px-2 mb-2"><i class="bi bi-folder2-open me-1"></i> <?= count($arquivos) ?> arquivo(s)</h5>
<?php foreach ($arquivos as $i => $f):
    $bn = basename($f);
    $sz = round(filesize($f)/1024, 1);
    $dt = date('d/m/Y H:i', filemtime($f));
    $ativo = ($view === $bn || ($view === '' && $i === 0));
    if ($view === '' && $i === 0) { $view = $bn; $conteudo = file_get_contents($f); if (preg_match('/(https:\/\/wa\.me\/\S+)/',$conteudo,$m)) $waLink=$m[1]; }
?>
<div class="file-item <?= $ativo ? 'active' : '' ?> d-flex justify-content-between align-items-center" onclick="location='?view=<?= urlencode($bn) ?>'">
<div style="min-width:0;flex-grow:1;">
<div class="fw-semibold small text-truncate"><?= htmlspecialchars($bn) ?></div>
<div class="text-muted small"><?= $dt ?> · <?= $sz ?> KB</div>
</div>
<?php if ($usuario['perfil'] === 'administrador'): ?>
<a href="?delete=<?= urlencode($bn) ?>" class="btn btn-sm btn-outline-danger rounded-circle ms-2" onclick="event.stopPropagation();return confirm('Apagar este arquivo?')">
<i class="bi bi-trash"></i>
</a>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
</div>

<div class="col-lg-8">
<div class="card p-4">
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
<h5 class="fw-bold mb-0"><i class="bi bi-eye me-1"></i> Preview da Mensagem</h5>
<?php if ($waLink): ?>
<a href="<?= htmlspecialchars($waLink) ?>" target="_blank" class="btn btn-success fw-semibold rounded-pill px-4">
<i class="bi bi-whatsapp me-2"></i> ABRIR NO WHATSAPP WEB E ENVIAR →
</a>
<?php endif; ?>
</div>
<div class="preview"><?= $conteudo !== null ? htmlspecialchars($conteudo) : 'Selecione um arquivo para visualizar.' ?></div>
</div>
</div>
</div>
<?php endif; ?>
</section>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>