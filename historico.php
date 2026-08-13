<?php
require_once 'conexao.php';
require_once 'auth.php';

$usuario = exigir_login(['administrador', 'portaria', 'morador', 'padrao']);

$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

if ($busca !== '') {
    $stmt = $pdo->prepare("
        SELECT e.*, m.nome_completo as morador_nome, m.numero_unidade, l.data_retirada, l.entregue_por_funcionario, l.retirado_por_morador, l.nome_retirante_avulso, pa.nome_completo as autorizado_nome, pa.parentesco_funcao
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
        SELECT e.*, m.nome_completo as morador_nome, m.numero_unidade, l.data_retirada, l.entregue_por_funcionario, l.retirado_por_morador, l.nome_retirante_avulso, pa.nome_completo as autorizado_nome, pa.parentesco_funcao
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
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; color: #334155; }
        .sidebar { position: fixed; top: 0; bottom: 0; left: 0; width: 260px; z-index: 100; background-color: #1e293b; padding: 20px; display: flex; flex-direction: column; }
        .sidebar-brand { font-size: 1.25rem; font-weight: 700; color: #fff; text-decoration: none; display: flex; align-items: center; gap: 10px; margin-bottom: 30px; padding-left: 10px; }
        .nav-sidebar { list-style: none; padding: 0; margin: 0; flex-grow: 1; }
        .nav-sidebar li { margin-bottom: 8px; }
        .nav-sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 10px; font-weight: 500; transition: all 0.2s ease; }
        .nav-sidebar a:hover { background-color: #334155; color: #f8fafc; }
        .nav-sidebar a.active { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #0f172a; font-weight: 600; }
        .main-content { margin-left: 260px; padding: 30px; }
        @media (max-width: 992px) { .sidebar { width: 100%; height: auto; position: relative; } .main-content { margin-left: 0; padding: 15px; } }
        .card-custom { border: none; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .table-custom th { background-color: #1e293b; color: #fff; font-weight: 500; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; padding: 12px 16px; }
        .table-custom td { padding: 14px 16px; vertical-align: middle; font-size: 0.9rem; }
        .code-tag { background-color: #f1f5f9; color: #0f172a; padding: 4px 8px; border-radius: 6px; font-family: monospace; font-weight: 600; border: 1px solid #cbd5e1; }
    </style>
</head>
<body>

    <nav class="sidebar shadow">
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
        <div class="text-muted small text-center pt-3 border-top border-secondary opacity-75">Sistema v1.0</div>
    </nav>

    <main class="main-content">
        <div class="container-fluid px-0">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1">📜 Histórico de Retiradas</h3>
                    <p class="text-muted small mb-0">Registro completo de auditoria e liberação de pacotes</p>
                </div>
            </div>

            <!-- Pesquisa -->
            <div class="card border-0 shadow-sm mb-4 rounded-4 p-2 bg-white">
                <form method="GET" class="input-group">
                    <span class="input-group-text bg-transparent border-0 ps-3 text-muted"><i class="bi bi-search fs-5"></i></span>
                    <input type="text" name="busca" class="form-control border-0 shadow-none fs-6" placeholder="Pesquisar por código, unidade ou morador..." value="<?= htmlspecialchars($busca) ?>">
                    <?php if ($busca !== ''): ?>
                        <a href="historico.php" class="btn btn-light border-0 text-danger px-3 d-flex align-items-center"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                    <button class="btn btn-dark px-4 rounded-3 m-1 fw-medium" type="submit">Pesquisar</button>
                </form>
            </div>

            <!-- Tabela -->
            <div class="card card-custom overflow-hidden bg-white">
                <div class="table-responsive">
                    <table class="table table-hover table-custom mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Etiqueta</th>
                                <th>Unidade / Morador</th>
                                <th>Retirado Por</th>
                                <th>Data da Retirada</th>
                                <th>Porteiro</th>
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
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5">
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

    <footer class="text-center py-4 mt-4 text-muted small">
        © 2026 Desenvolvido por Alexandre Anjos. Todos os direitos reservados.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>