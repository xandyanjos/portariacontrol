<?php
require_once 'conexao.php';
require_once 'auth.php';

if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
}

$usuario = exigir_login(['administrador', 'portaria']);

require_once __DIR__ . '/email_config.php';

$dir = EMAIL_MOCK_DIR;
$arquivos = [];
if (is_dir($dir)) {
    $all = glob($dir . '/*.html');
    rsort($all);
    foreach ($all as $f) {
        $arquivos[] = [
            'full' => $f,
            'name' => basename($f),
            'size' => filesize($f),
            'time' => filemtime($f),
        ];
    }
}

$view = isset($_GET['view']) ? basename($_GET['view']) : '';
$viewPath = '';
$viewContent = '';
if ($view && preg_match('/^[0-9A-Za-z_\-]+\.html$/', $view)) {
    $candidate = $dir . '/' . $view;
    if (file_exists($candidate)) {
        $viewPath = $candidate;
        $viewContent = file_get_contents($candidate);
    }
}

$delete = isset($_GET['delete']) ? basename($_GET['delete']) : '';
if ($delete && preg_match('/^[0-9A-Za-z_\-]+\.html$/', $delete)) {
    $candidate = $dir . '/' . $delete;
    if (file_exists($candidate)) {
        @unlink($candidate);
        header("Location: " . strtok($_SERVER['REQUEST_URI'], '?') . "?del=1");
        exit;
    }
}

$limpar = isset($_GET['limpar_todos']) && $_GET['limpar_todos'] === '1';
if ($limpar && $usuario['perfil'] === 'administrador') {
    $all = glob($dir . '/*.html');
    foreach ($all as $f) @unlink($f);
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?') . "?limpo=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-mails Pendentes - PortariaControl</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f6f9; color: #334155; }
        .card-custom { border: none; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,.05), 0 2px 4px -1px rgba(0,0,0,.03); }
        .email-mock-row { transition: all .15s ease; }
        .email-mock-row:hover { background-color: #fffbeb; transform: translateX(2px); }
        iframe.email-preview { border: 1px solid #e2e8f0; border-radius: 10px; width: 100%; min-height: 550px; background: white; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark bg-dark shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <i class="bi bi-box-seam text-warning fs-4"></i>
                <span>Portaria<strong class="text-warning">Control</strong></span>
            </a>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-warning text-dark"><?= htmlspecialchars(label_perfil($usuario['perfil'])) ?></span>
                <a href="index.php" class="btn btn-outline-light btn-sm px-3 d-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i> Voltar ao Painel
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-5" style="max-width: 1200px;">

        <?php if (isset($_GET['del'])): ?>
            <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                <div>E-mail apagado com sucesso.</div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['limpo'])): ?>
            <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                <div>Todos os e-mails pendentes foram apagados.</div>
            </div>
        <?php endif; ?>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1"><i class="bi bi-envelope-paper text-warning"></i> E-mails Pendentes</h2>
                <p class="text-muted small mb-0">
                    Estes e-mails foram gravados localmente porque o SMTP do Gmail rejeitou a autenticacao. 
                    Abra cada um e clique em <strong>"Abrir no Outlook/Gmail"</strong> para enviar manualmente.
                </p>
            </div>
            <div class="d-flex gap-2">
                <span class="badge rounded-pill bg-warning text-dark fs-6 px-3 py-2">
                    <i class="bi bi-files"></i> <?= count($arquivos) ?> arquivos
                </span>
                <?php if ($usuario['perfil'] === 'administrador' && count($arquivos)): ?>
                    <a href="?limpar_todos=1" onclick="return confirm('Tem certeza que quer APAGAR TODOS os <?= count($arquivos) ?> e-mails? Esta acao e irreversivel.')"
                       class="btn btn-outline-danger btn-sm px-3">
                        <i class="bi bi-trash"></i> Limpar Todos
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card card-custom bg-white p-3">
                    <?php if (count($arquivos) === 0): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-success"></i>
                            <h5 class="mt-3 text-success fw-bold">Nenhum e-mail pendente!</h5>
                            <p class="text-muted small mt-2 mb-0">Todos os e-mails foram enviados ou apagados.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($arquivos as $i => $arq):
                                $ativo = ($view === $arq['name']) ? 'active' : '';
                            ?>
                                <a href="?view=<?= urlencode($arq['name']) ?>"
                                   class="list-group-item list-group-item-action email-mock-row border-0 rounded-9 px-3 py-3 mb-1 <?= $ativo ?>">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="fw-semibold text-dark text-truncate">
                                                <?= htmlspecialchars($arq['name']) ?>
                                            </div>
                                            <div class="small text-muted mt-1">
                                                <i class="bi bi-calendar3"></i> <?= date('d/m/Y H:i:s', $arq['time']) ?>
                                                &nbsp;&nbsp;<i class="bi bi-database"></i> <?= number_format($arq['size']/1024, 1) ?> KB
                                            </div>
                                        </div>
                                        <a href="?delete=<?= urlencode($arq['name']) ?>"
                                           onclick="event.stopPropagation(); return confirm('Apagar este e-mail?')"
                                           class="btn btn-sm btn-outline-danger flex-shrink-0">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card card-custom bg-white p-3">
                    <h5 class="fw-semibold text-dark mb-3 ps-2">
                        <i class="bi bi-eye-fill text-warning"></i> Visualizacao
                        <?php if ($viewPath): ?>
                            <div class="float-end">
                                <a href="<?= 'emails_pendentes/' . $view ?>" target="_blank" class="btn btn-sm btn-outline-info me-2">
                                    <i class="bi bi-box-arrow-up-right"></i> Abrir nova aba
                                </a>
                                <a href="baixar_email.php?file=<?= urlencode($view) ?>" class="btn btn-sm btn-outline-dark">
                                    <i class="bi bi-download"></i> Baixar HTML
                                </a>
                            </div>
                        <?php endif; ?>
                    </h5>
                    <div style="padding: 0 10px 14px 10px;">
                        <?php if (!$viewPath): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-arrow-left fs-1"></i>
                                <h6 class="mt-3">Selecione um e-mail ao lado para visualizar</h6>
                                <p class="small mt-2 mb-0">Cada arquivo tem um botao "Abrir no Outlook/Gmail" que abre o cliente de e-mail do seu computador com todas as informacoes prontas para enviar.</p>
                            </div>
                        <?php else: ?>
                            <iframe class="email-preview" srcdoc="<?= htmlspecialchars($viewContent) ?>"></iframe>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-custom bg-light mt-4 border border-warning border-opacity-25">
            <div class="card-body">
                <h5 class="fw-bold text-warning mb-2"><i class="bi bi-lightbulb"></i> Como enviar o e-mail manualmente</h5>
                <ol class="text-muted mb-0 small" style="line-height: 1.8;">
                    <li>Clique no e-mail ao lado.</li>
                    <li>No preview, clique no botao <strong>azul</strong> <em>"📧 ABRIR NO OUTLOOK/GMAIL (para enviar manualmente)"</em>.</li>
                    <li>Seu cliente de e-mail (Outlook, Gmail no navegador, Thunderbird, etc.) vai abrir com: destinatario, assunto e mensagem TUDO preenchido.</li>
                    <li>Basta clicar em <strong>Enviar</strong>. O morador recebe o aviso normalmente.</li>
                    <li>Depois de enviado, apague o e-mail desta lista clicando na lixeira.</li>
                </ol>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>