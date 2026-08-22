<?php
require_once 'conexao.php';
require_once 'auth.php';

$usuario = exigir_login(['administrador', 'portaria', 'morador', 'padrao']);

$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

if ($busca !== '') {
    $stmt = $pdo->prepare("
        SELECT e.*, m.nome_completo as morador_nome, m.numero_unidade, l.data_retirada, l.entregue_por_funcionario, l.retirado_por_morador, l.nome_retirante_avulso, l.protocolo_retirada, pa.nome_completo as autorizado_nome, pa.parentesco_funcao
        FROM encomendas e
        JOIN moradores m ON e.morador_id = m.id
        JOIN logs_retirada l ON e.id = l.encomenda_id
        LEFT JOIN pessoas_autorizadas pa ON l.retirado_por_id = pa.id
        WHERE e.status = 'Retirado' AND (e.codigo_etiqueta LIKE ? OR m.numero_unidade LIKE ? OR m.nome_completo LIKE ?)
        ORDER BY l.data_retirada DESC 
    ");
    $stmt->execute(["%$busca%", "%$busca%", "%$busca%"]);
} else {
    $stmt = $pdo->query("
        SELECT e.*, m.nome_completo as morador_nome, m.numero_unidade, l.data_retirada, l.entregue_por_funcionario, l.retirado_por_morador, l.nome_retirante_avulso, l.protocolo_retirada, pa.nome_completo as autorizado_nome, pa.parentesco_funcao
        FROM encomendas e
        JOIN moradores m ON e.morador_id = m.id
        JOIN logs_retirada l ON e.id = l.encomenda_id
        LEFT JOIN pessoas_autorizadas pa ON l.retirado_por_id = pa.id
        WHERE e.status = 'Retirado'
        ORDER BY l.data_retirada DESC 
    ");
}
$historico = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico - PortariaControl</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
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

    <nav class="sidebar-wrapper shadow" id="sidebarPrincipal">
        <a class="sidebar-brand" href="index.php">
            <i class="bi bi-box-seam text-warning fs-3"></i>
            <span>Portaria<strong class="text-warning">Control</strong></span>
        </a>
        <ul class="nav-sidebar">
            <li><a href="index.php"><i class="bi bi-grid-1x2-fill"></i> Painel Principal</a></li>
            <li><a href="cadastrar_encomenda.php"><i class="bi bi-plus-circle-fill"></i> Nova Encomenda</a></li>
            <li><a href="moradores.php"><i class="bi bi-people-fill"></i> Gestão de Moradores</a></li>
            <li><a href="historico.php" class="active"><i class="bi bi-clock-history"></i> Histórico de Retiradas</a></li>
            <?php if ($usuario['perfil'] === 'administrador'): ?>
                <li><a href="cadastrar_usuario.php"><i class="bi bi-person-plus-fill"></i> Cadastro de Usuários</a></li>
                <li><a href="listar_usuarios.php"><i class="bi bi-list-ul"></i> Listar Usuários</a></li>
            <?php endif; ?>
        </ul>
        <div class="sidebar-footer">
            <div class="user-name"><?= htmlspecialchars($usuario['nome']) ?></div>
            <div class="user-role"><?= htmlspecialchars(label_perfil($usuario['perfil'])) ?></div>
            <a href="logout.php" class="btn btn-outline-light btn-sm mt-3 btn-sair"><i class="bi bi-box-arrow-right me-1"></i> Sair</a>
        </div>
    </nav>

    <main class="main-content">
        <div class="container-fluid px-0">
            
            <div class="page-header">
                <div>
                    <h2 class="mb-1">Histórico de Retiradas</h2>
                    <p class="subtitle">Registro completo de auditoria e liberação de pacotes</p>
                </div>
            </div>

            <!-- Pesquisa -->
            <div class="card card-search">
                <form method="GET" class="input-group">
                    <span class="input-group-text"><i class="bi bi-search fs-5"></i></span>
                    <input type="text" name="busca" class="form-control" placeholder="Pesquisar por código, unidade ou morador..." value="<?= htmlspecialchars($busca) ?>">
                    <?php if ($busca !== ''): ?>
                        <a href="historico.php" class="btn btn-light border-0 text-danger px-3 d-flex align-items-center"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                    <button class="btn btn-dark btn-pesquisar fw-medium" type="submit">Pesquisar</button>
                </form>
            </div>

            <!-- Tabela -->
            <div class="card card-table">
                <div class="table-responsive">
                    <table class="table table-hover table-custom table-responsive-stack mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Etiqueta</th>
                                <th>Unidade / Morador</th>
                                <th>Retirado Por</th>
                                <th>Data da Retirada</th>
                                <th>Porteiro</th>
                                <th>Protocolo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($historico) > 0): ?>
                                <?php foreach ($historico as $h): ?>
                                    <tr>
                                        <td><span class="code-tag"><?= htmlspecialchars($h['codigo_etiqueta']) ?></span></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($h['numero_unidade']) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($h['morador_nome']) ?></div>
                                        </td>
                                        <td>
                                            <?php if ($h['retirado_por_morador'] == 1): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2 py-1">Próprio Morador</span>
                                            <?php elseif (!empty($h['autorizado_nome'])): ?>
                                                <span class="fw-semibold text-dark"><?= htmlspecialchars($h['autorizado_nome']) ?></span>
                                                <span class="text-muted small d-block">Autorizado (<?= htmlspecialchars($h['parentesco_funcao']) ?>)</span>
                                            <?php else: ?>
                                                <span class="fw-semibold text-dark"><?= htmlspecialchars($h['nome_retirante_avulso']) ?></span>
                                                <span class="text-muted small d-block">Retirante Avulso</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted small"><i class="bi bi-calendar-check me-1"></i><?= date('d/m/Y H:i', strtotime($h['data_retirada'])) ?></td>
                                        <td><span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($h['entregue_por_funcionario']) ?></span></td> 
                                        <td><span class="code-tag"><?= htmlspecialchars($h['protocolo_retirada'] ?: 'N/A') ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="text-muted py-3">
                                            <i class="bi bi-clock-history display-4 d-block mb-2 text-secondary opacity-50"></i>
                                            <p class="mb-0 fw-medium">Nenhum registro de retirada encontrado.</p>
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

    <footer class="global-footer">
        © 2026 Desenvolvido por Alexandre Anjos. Todos os direitos reservados.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
