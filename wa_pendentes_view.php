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
<link href="assets/css/style.css" rel="stylesheet">
<style>
body { background:#f0fdfa; }
.file-item {
    padding:12px 14px;
    border-radius:10px;
    cursor:pointer;
    transition:all .15s;
    margin-bottom:6px;
}
.file-item:hover { background:#ecfeff; }
.file-item.active {
    background:#dcfce7;
    border-left:4px solid #10b981;
}
.preview {
    white-space:pre-wrap;
    font-family:ui-monospace,Menlo,Consolas,monospace;
    font-size:0.9rem;
    background:#0f172a;
    color:#e2e8f0;
    padding:22px;
    border-radius:14px;
    max-height:420px;
    overflow:auto;
    word-break: break-all;
}
</style>
</head>
<body class="com-topbar">

    <!-- Topbar Mobile -->
    <div class="topbar-mobile">
        <button type="button" class="btn-hamburguer" id="btnHamburguer" aria-label="Abrir menu">
            <i class="bi bi-list"></i>
        </button>
        <a class="brand-mobile" href="index.php">
            <i class="bi bi-box-seam text-warning"></i>
            <span>Portaria<strong class="text-warning">Control</strong></span>
        </a>
        <div style="width:40px;"></div>
    </div>

    <!-- Overlay Mobile -->
    <div class="overlay-mobile" id="overlayMobile"></div>

    <!-- Menu Lateral (Padrão) -->
    <nav class="sidebar-wrapper shadow" id="sidebarPrincipal">
        <a class="sidebar-brand" href="index.php">
            <i class="bi bi-box-seam text-warning fs-3"></i>
            <span>Portaria<strong class="text-warning">Control</strong></span>
        </a>
        <ul class="nav-sidebar">
            <li><a href="index.php"><i class="bi bi-grid-1x2-fill"></i> Painel Principal</a></li>
            <li><a href="cadastrar_encomenda.php"><i class="bi bi-plus-circle-fill"></i> Nova Encomenda</a></li>
            <li><a href="moradores.php"><i class="bi bi-people-fill"></i> Gestão de Moradores</a></li>
            <li><a href="historico.php"><i class="bi bi-clock-history"></i> Histórico de Retiradas</a></li>
            <li><a href="retirada_morador.php" target="_blank"><i class="bi bi-box-arrow-up-right text-info"></i> <span class="d-none-mobile"><strong>Retirada Morador (Auto)</strong></span><span class="d-none-desktop"><strong>Retirada Auto</strong></span></a></li>
            <li><a href="wa_pendentes_view.php" class="active"><i class="bi bi-whatsapp text-success"></i> WhatsApp Pendentes</a></li>
            <li><a href="emails_pendentes_view.php"><i class="bi bi-envelope-paper text-warning"></i> E-mails Pendentes</a></li>
            <?php if ($usuario['perfil'] === 'administrador'): ?>
                <li><a href="cadastrar_usuario.php"><i class="bi bi-person-plus-fill"></i> Cadastro de Usuários</a></li>
                <li><a href="listar_usuarios.php"><i class="bi bi-list-ul"></i> Listar Usuários</a></li>
                <li><a href="teste_whatsapp.php" target="_blank"><i class="bi bi-whatsapp text-success"></i> Teste WhatsApp</a></li>
                <li><a href="verifica_env.php" target="_blank"><i class="bi bi-gear-wide-connected text-warning"></i> Verificar Variáveis</a></li>
                <li><a href="teste_smtp.php" target="_blank"><i class="bi bi-envelope-exclamation-fill text-info"></i> Diagnóstico SMTP</a></li>
            <?php endif; ?>
        </ul>
        <div class="sidebar-footer">
            <div class="user-name"><?= htmlspecialchars($usuario['nome']) ?></div>
            <div class="user-role"><?= htmlspecialchars(label_perfil($usuario['perfil'])) ?></div>
            <a href="logout.php" class="btn btn-outline-light btn-sm mt-3 btn-sair">
                <i class="bi bi-box-arrow-right me-1"></i> Sair
            </a>
        </div>
    </nav>

    <!-- Conteúdo Principal -->
    <main class="main-content">
        <div class="container-fluid px-0">

            <div class="page-header">
                <div>
                    <h2 class="mb-1"><i class="bi bi-whatsapp me-2 text-success"></i>WhatsApp Pendentes</h2>
                    <p class="subtitle">Mensagens gravadas localmente (WA_USE_MOCK_FALLBACK). Use o botão verde para abrir no WhatsApp Web e enviar manualmente.</p>
                </div>
                <div class="header-actions">
                    <a href="index.php" class="btn btn-secondary fw-semibold btn-full-mobile"><i class="bi bi-arrow-left me-1"></i> Voltar</a>
                    <?php if ($usuario['perfil'] === 'administrador'): ?>
                    <a href="?limpar_todos=1" onclick="return confirm('Tem certeza que deseja apagar TODAS as mensagens WhatsApp pendentes?')" class="btn btn-danger fw-semibold btn-full-mobile">
                    <i class="bi bi-trash3 me-1"></i> Limpar Todos (<?= count($arquivos) ?>)
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (count($arquivos) === 0): ?>
            <div class="card card-form p-5 text-center">
                <div class="mb-3"><i class="bi bi-whatsapp" style="font-size:60px; color:#10b981;"></i></div>
                <h3 class="fw-bold">Parabéns! Nenhum WhatsApp pendente.</h3>
                <p class="text-muted mb-0">Todas as mensagens foram enviadas (ou usaram o provider nativo com sucesso). Quando cadastrar uma encomenda e o provider falhar, ela aparece aqui.</p>
            </div>
            <?php else: ?>
            <div class="row g-3">
                <div class="col-lg-4">
                    <div class="card card-form p-3" style="max-height:520px;overflow:auto;">
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
                            <a href="?delete=<?= urlencode($bn) ?>" class="btn btn-sm btn-outline-danger rounded-circle ms-2 flex-shrink-0" onclick="event.stopPropagation();return confirm('Apagar este arquivo?')">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card card-form p-4">
                        <div class="page-header mb-3" style="margin-bottom: 1rem !important;">
                            <h5 class="fw-bold mb-0"><i class="bi bi-eye me-1"></i> Preview da Mensagem</h5>
                            <div class="header-actions" style="margin:0;">
                                <?php if ($waLink): ?>
                                <a href="<?= htmlspecialchars($waLink) ?>" target="_blank" class="btn btn-success fw-semibold btn-full-mobile">
                                    <i class="bi bi-whatsapp me-2"></i> ABRIR NO WHATSAPP WEB →
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="preview"><?= $conteudo !== null ? htmlspecialchars($conteudo) : 'Selecione um arquivo para visualizar.' ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </main>

    <footer class="global-footer">
        © 2026 Desenvolvido por Alexandre Anjos. Todos os direitos reservados.
    </footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
