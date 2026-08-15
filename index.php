<?php
require_once 'conexao.php';
require_once 'auth.php';
require_once 'alertas_abandono.php';

$usuario = exigir_login(['administrador', 'portaria', 'morador', 'padrao']);

// Filtro de busca
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

if ($busca !== '') {
    $stmt = $pdo->prepare("
        SELECT e.*, m.nome_completo, m.numero_unidade 
        FROM encomendas e 
        JOIN moradores m ON e.morador_id = m.id 
        WHERE e.codigo_etiqueta LIKE ? OR m.numero_unidade LIKE ? OR m.nome_completo LIKE ?
        ORDER BY e.data_recebimento DESC
    ");
    $stmt->execute(["%$busca%", "%$busca%", "%$busca%"]);
} else {
    $stmt = $pdo->query("
        SELECT e.*, m.nome_completo, m.numero_unidade 
        FROM encomendas e 
        JOIN moradores m ON e.morador_id = m.id 
        WHERE e.status = 'Pendente'
        ORDER BY e.data_recebimento DESC
    ");
}
$encomendas = $stmt->fetchAll();

// Contagem para os cards de resumo
$totalPendentes = $pdo->query("SELECT COUNT(*) FROM encomendas WHERE status = 'Pendente'")->fetchColumn();
$totalRetiradasHoje = $pdo->query("SELECT COUNT(*) FROM encomendas e JOIN logs_retirada l ON e.id = l.encomenda_id WHERE DATE(l.data_retirada) = CURDATE()")->fetchColumn();

// Alertas de abandono
$alertas = gerar_alertas_abandono($pdo);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel - PortariaControl</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; color: #334155; overflow-x: hidden; }
        
        /* Layout Sidebar */
        .sidebar { position: fixed; top: 0; bottom: 0; left: 0; width: 260px; z-index: 100; background-color: #1e293b; padding: 20px; display: flex; flex-direction: column; }
        .sidebar-brand { font-size: 1.25rem; font-weight: 700; color: #fff; text-decoration: none; display: flex; align-items: center; gap: 10px; margin-bottom: 30px; padding-left: 10px; }
        .nav-sidebar { list-style: none; padding: 0; margin: 0; flex-grow: 1; }
        .nav-sidebar li { margin-bottom: 8px; }
        .nav-sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; font-weight: 500; transition: all 0.2s ease; }
        .nav-sidebar a:hover, .nav-sidebar a.active { background-color: #334155; color: #f8fafc; }
        .nav-sidebar a.active { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #0f172a; font-weight: 600; }
        
        /* Main Content */
        .main-content { margin-left: 260px; padding: 30px; }
        @media (max-width: 992px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; padding: 15px; }
        }

        .card-stats { border: none; border-radius: 12px; }
        .table-custom th { background-color: #1e293b; color: #fff; font-weight: 500; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; padding: 12px 16px; }
        .table-custom td { padding: 14px 16px; vertical-align: middle; font-size: 0.9rem; }
        .badge-status { padding: 6px 12px; border-radius: 50rem; font-weight: 500; font-size: 0.75rem; }
        .code-tag { background-color: #f1f5f9; color: #0f172a; padding: 4px 8px; border-radius: 6px; font-family: monospace; font-weight: 600; border: 1px solid #cbd5e1; }
    </style>
</head>
<body>

    <!-- Menu Lateral -->
    <nav class="sidebar shadow">
        <a class="sidebar-brand" href="index.php">
            <i class="bi bi-box-seam text-warning fs-3"></i>
            <span>Portaria<strong class="text-warning">Control</strong></span>
        </a>
        <ul class="nav-sidebar">
            <li><a href="index.php" class="active"><i class="bi bi-grid-1x2-fill"></i> Painel Principal</a></li>
            <li><a href="cadastrar_encomenda.php"><i class="bi bi-plus-circle-fill"></i> Nova Encomenda</a></li>
            <li><a href="moradores.php"><i class="bi bi-people-fill"></i> Gestão de Moradores</a></li>
            <li><a href="historico.php"><i class="bi bi-clock-history"></i> Histórico de Retiradas</a></li>
            <li><a href="retirada_morador.php" target="_blank"><i class="bi bi-box-arrow-up-right text-info"></i> <strong>Retirada Morador (Auto)</strong></a></li>
            <li><a href="wa_pendentes_view.php"><i class="bi bi-whatsapp text-success"></i> WhatsApp Pendentes</a></li>
            <li><a href="emails_pendentes_view.php"><i class="bi bi-envelope-paper text-warning"></i> E-mails Pendentes</a></li>
            <?php if ($usuario['perfil'] === 'administrador'): ?>
                <li><a href="cadastrar_usuario.php"><i class="bi bi-person-plus-fill"></i> Cadastro de Usuários</a></li>
                <li><a href="listar_usuarios.php"><i class="bi bi-list-ul"></i> Listar Usuários</a></li>
                <li><a href="teste_whatsapp.php" target="_blank"><i class="bi bi-whatsapp me-1 text-success"></i> Teste WhatsApp</a></li>
                <li><a href="verifica_env.php" target="_blank"><i class="bi bi-gear-wide-connected text-warning"></i> Verificar Variáveis</a></li>
                <li><a href="teste_smtp.php" target="_blank"><i class="bi bi-envelope-exclamation-fill text-info"></i> Diagnóstico SMTP</a></li>
            <?php endif; ?>
        </ul>
        <div class="text-muted small text-center pt-3 border-top border-secondary opacity-75">
            <div class="fw-semibold text-light mb-2"><?= htmlspecialchars($usuario['nome']) ?></div>
            <div class="small text-warning"><?= htmlspecialchars(label_perfil($usuario['perfil'])) ?></div>
            <a href="logout.php" class="btn btn-outline-light btn-sm mt-3 w-100">Sair</a>
        </div>
    </nav>

    <!-- Conteúdo Principal -->
    <main class="main-content">
        <div class="container-fluid px-0">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-1">Painel de Encomendas</h2>
                    <p class="text-muted small mb-0">Gerencie a entrada e saída de pacotes da guarda do condomínio</p>
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-end">
                    <a href="retirada_morador.php" target="_blank" class="btn btn-info text-white fw-semibold px-4 py-2 shadow-sm d-flex align-items-center gap-2">
                        <i class="bi bi-box-arrow-up-right"></i> Retirada Autoatendimento
                    </a>
                    <a href="cadastrar_encomenda.php" class="btn btn-warning text-dark fw-semibold px-4 py-2 shadow-sm d-flex align-items-center gap-2">
                        <i class="bi bi-plus-lg"></i> Registrar Encomenda
                    </a>
                </div>
            </div>

            <?php if (isset($_GET['email_sucesso'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-envelope-check me-2"></i><?= htmlspecialchars($_GET['email_sucesso']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['email_erro'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-envelope-x me-2"></i><?= htmlspecialchars($_GET['email_erro']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['email_aviso'])): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($_GET['email_aviso']) ?>
                    <?php if (strpos($_GET['email_aviso'], 'gravado') !== false || strpos($_GET['email_aviso'], 'SMTP indisponivel') !== false): ?>
                        <a href="emails_pendentes_view.php" class="alert-link fw-bold ms-2">Abrir E-mails Pendentes →</a>
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Alertas WhatsApp -->
            <?php if (!empty($_GET['sucesso']) && !empty($_GET['wa_ok'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-whatsapp me-2 fs-5" style="color:#10b981"></i>
                    <strong>WhatsApp enviado!</strong> Notificação do WhatsApp enviada com sucesso para o morador.
                    <?php if (!empty($_GET['email_ok'])): ?>
                        <span class="text-success fw-semibold">E-mail também enviado.</span>
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php elseif (!empty($_GET['sucesso']) && empty($_GET['wa_ok'])): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="bi bi-whatsapp me-2"></i>
                    Encomenda cadastrada! Não foi possível enviar o WhatsApp automático (ou morador sem telefone).
                    A mensagem ficou gravada em
                    <a href="wa_pendentes_view.php" class="alert-link fw-bold">📱 WhatsApp Pendentes — clique para abrir e enviar manualmente</a>.
                    <?php if (!empty($_GET['email_ok'])): ?>
                        <span class="text-success fw-semibold"> (E-mail enviado normalmente).</span>
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <!-- Cards de Resumo -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card card-stats bg-white shadow-sm border-start border-warning border-4 p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted text-uppercase fs-7 fw-bold">Pendentes na Guarda</span>
                                <h2 class="fw-bold mb-0 text-dark mt-1"><?= $totalPendentes ?></h2>
                            </div>
                            <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                                <i class="bi bi-hourglass-split fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-stats bg-white shadow-sm border-start border-success border-4 p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted text-uppercase fs-7 fw-bold">Retiradas Hoje</span>
                                <h2 class="fw-bold mb-0 text-dark mt-1"><?= $totalRetiradasHoje ?></h2>
                            </div>
                            <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                                <i class="bi bi-check2-all fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alertas de Abandono -->
            <div class="card border-0 shadow-sm mb-4 rounded-4 p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold text-dark mb-1"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Alertas de Abandono</h5>
                        <p class="text-muted small mb-0">Avisos gerados automaticamente para encomendas que ficaram muito tempo na portaria.</p>
                    </div>
                    <span class="badge bg-warning text-dark px-3 py-2"><?= count($alertas) ?> ativo(s)</span>
                </div>

                <?php if (count($alertas) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($alertas as $alerta): ?>
                            <div class="list-group-item px-0 py-3 border-0 border-bottom">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <div class="fw-semibold text-dark">
                                            <?= htmlspecialchars($alerta['codigo_etiqueta']) ?> · <?= htmlspecialchars($alerta['dias_na_portaria']) ?> dias na portaria
                                        </div>
                                        <div class="text-muted small mt-1">
                                            <?= htmlspecialchars($alerta['mensagem']) ?>
                                        </div>
                                    </div>
                                    <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle">Atenção</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-muted small py-2">
                        <i class="bi bi-check-circle-fill text-success me-2"></i>Nenhum alerta ativo no momento.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Barra de Pesquisa -->
            <div class="card border-0 shadow-sm mb-4 rounded-4 p-2 bg-white">
                <form method="GET" class="input-group">
                    <span class="input-group-text bg-transparent border-0 ps-3 text-muted">
                        <i class="bi bi-search fs-5"></i>
                    </span>
                    <input type="text" name="busca" class="form-control border-0 shadow-none fs-6" placeholder="Busque por código de etiqueta, número da unidade ou nome do morador..." value="<?= htmlspecialchars($busca) ?>">
                    <?php if ($busca !== ''): ?>
                        <a href="index.php" class="btn btn-light border-0 text-danger px-3 d-flex align-items-center">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                    <button class="btn btn-dark px-4 rounded-3 m-1 fw-medium" type="submit">Pesquisar</button>
                </form>
            </div>

            <!-- Tabela -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="table-responsive">
                    <table class="table table-hover table-custom mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Etiqueta / Rastreio</th>
                                <th>Unidade</th>
                                <th>Morador</th>
                                <th>Origem</th>
                                <th>Recebimento</th>
                                <th>Status</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($encomendas) > 0): ?>
                                <?php foreach ($encomendas as $enc): ?>
                                    <tr>
                                        <td><span class="code-tag"><?= htmlspecialchars($enc['codigo_etiqueta']) ?></span></td>
                                        <td><span class="fw-bold text-dark"><?= htmlspecialchars($enc['numero_unidade']) ?></span></td>
                                        <td><?= htmlspecialchars($enc['nome_completo']) ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border px-2 py-1">
                                                <?= htmlspecialchars($enc['transportadora_marketplace']) ?>
                                            </span>
                                        </td>
                                        <td class="text-muted small">
                                            <i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i', strtotime($enc['data_recebimento'])) ?>
                                        </td>
                                        <td>
                                            <span class="badge-status bg-warning bg-opacity-15 text-warning-emphasis border border-warning-subtle">
                                                <i class="bi bi-circle-fill small me-1"></i> <?= $enc['status'] ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($enc['status'] == 'Pendente'): ?>
                                                <a href="dar_baixa.php?id=<?= $enc['id'] ?>" class="btn btn-sm btn-success px-3 shadow-sm d-inline-flex align-items-center gap-1">
                                                    <i class="bi bi-check-lg"></i> Dar Baixa
                                                </a>
                                                <a href="resend_email.php?id=<?= $enc['id'] ?>" class="btn btn-sm btn-outline-info px-3 shadow-sm d-inline-flex align-items-center gap-1 ms-2" title="Reenviar e-mail para o morador">
                                                    <i class="bi bi-envelope"></i> Reenviar E-mail
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small fst-italic">Retirado</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="text-muted py-3">
                                            <i class="bi bi-inbox display-4 d-block mb-2 text-secondary opacity-50"></i>
                                            <p class="mb-0 fw-medium">Nenhuma encomenda pendente encontrada.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <footer class="text-center py-4 mt-4 text-muted small">
        © 2026 Desenvolvido por Alexandre Anjos. Todos os direitos reservados.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>