<?php
require_once 'conexao.php';
require_once 'auth.php';

$usuario = exigir_login(['administrador']);

$mensagem = '';
$sucesso = '';

// Deletar usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'deletar') {
    $id = (int)$_POST['id'];
    
    if ($id === $usuario['id']) {
        $mensagem = 'Você não pode deletar sua própria conta.';
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            $sucesso = 'Usuário deletado com sucesso!';
        } catch (PDOException $e) {
            $mensagem = 'Erro ao deletar usuário: ' . $e->getMessage();
        }
    }
}

// Atualizar status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'atualizar_status') {
    $id = (int)$_POST['id'];
    $status = trim($_POST['status'] ?? '');
    
    if (!in_array($status, ['Ativo', 'Inativo'], true)) {
        $mensagem = 'Status inválido.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE usuarios SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            $sucesso = 'Status atualizado com sucesso!';
        } catch (PDOException $e) {
            $mensagem = 'Erro ao atualizar status: ' . $e->getMessage();
        }
    }
}

// Buscar todos os usuários
$usuarios = $pdo->query("SELECT id, nome, username, perfil, status, data_criacao FROM usuarios ORDER BY data_criacao DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - PortariaControl</title>
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
        .nav-sidebar a:hover, .nav-sidebar a.active { background-color: #334155; color: #f8fafc; }
        .nav-sidebar a.active { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #0f172a; font-weight: 600; }
        .main-content { margin-left: 260px; padding: 30px; }
        @media (max-width: 992px) { .sidebar { width: 100%; height: auto; position: relative; } .main-content { margin-left: 0; padding: 15px; } }
        .card-custom { border: none; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .table-custom th { background-color: #1e293b; color: #fff; font-weight: 500; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; padding: 12px 16px; }
        .table-custom td { padding: 14px 16px; vertical-align: middle; font-size: 0.9rem; }
        .badge-perfil { padding: 6px 12px; border-radius: 6px; font-weight: 500; font-size: 0.8rem; }
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
            <li><a href="historico.php"><i class="bi bi-clock-history"></i> Histórico de Retiradas</a></li>
            <li><a href="cadastrar_usuario.php"><i class="bi bi-person-plus-fill"></i> Cadastro de Usuários</a></li>
            <li><a href="listar_usuarios.php" class="active"><i class="bi bi-list-ul"></i> Listar Usuários</a></li>
        </ul>
        <div class="text-muted small text-center pt-3 border-top border-secondary opacity-75">Sistema v1.0</div>
    </nav>

    <main class="main-content">
        <div class="container-fluid px-0">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1">Usuários Cadastrados</h3>
                    <p class="text-muted small mb-0">Visualize e gerencie todas as contas do sistema.</p>
                </div>
                <a href="cadastrar_usuario.php" class="btn btn-warning fw-semibold px-4 py-2 shadow-sm d-flex align-items-center gap-2">
                    <i class="bi bi-person-plus-fill"></i> Novo Usuário
                </a>
            </div>

            <?php if ($sucesso): ?>
                <div class="alert alert-success shadow-sm"><?= htmlspecialchars($sucesso) ?></div>
            <?php endif; ?>
            <?php if ($mensagem): ?>
                <div class="alert alert-danger shadow-sm"><?= htmlspecialchars($mensagem) ?></div>
            <?php endif; ?>

            <div class="card card-custom overflow-hidden bg-white">
                <div class="table-responsive">
                    <table class="table table-hover table-custom mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Usuário</th>
                                <th>Perfil</th>
                                <th>Status</th>
                                <th>Data Criação</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($usuarios) > 0): ?>
                                <?php foreach ($usuarios as $u): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="bg-warning bg-opacity-10 p-2 rounded-circle text-warning">
                                                    <i class="bi bi-person-fill small"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($u['nome']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <code class="bg-light text-dark px-2 py-1 rounded" style="font-size: 0.85rem;">
                                                <?= htmlspecialchars($u['username']) ?>
                                            </code>
                                        </td>
                                        <td>
                                            <?php 
                                                $cores = [
                                                    'administrador' => 'bg-danger bg-opacity-10 text-danger',
                                                    'portaria' => 'bg-info bg-opacity-10 text-info',
                                                    'morador' => 'bg-success bg-opacity-10 text-success',
                                                    'padrao' => 'bg-secondary bg-opacity-10 text-secondary'
                                                ];
                                                $classe = $cores[$u['perfil']] ?? 'bg-light text-dark';
                                            ?>
                                            <span class="badge-perfil <?= $classe ?>"><?= label_perfil($u['perfil']) ?></span>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="acao" value="atualizar_status">
                                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto; display: inline-block;">
                                                    <option value="Ativo" <?= $u['status'] === 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                                                    <option value="Inativo" <?= $u['status'] === 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td class="text-muted small">
                                            <i class="bi bi-calendar me-1"></i><?= date('d/m/Y H:i', strtotime($u['data_criacao'])) ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($u['id'] !== $usuario['id']): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Deseja realmente deletar este usuário?');">
                                                    <input type="hidden" name="acao" value="deletar">
                                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger px-2 shadow-sm">
                                                        <i class="bi bi-trash"></i> Deletar
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted small fst-italic">Sua conta</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="text-muted py-3">
                                            <i class="bi bi-people display-4 d-block mb-2 text-secondary opacity-50"></i>
                                            <p class="mb-0 fw-medium">Nenhum usuário cadastrado.</p>
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
</body>
</html>
