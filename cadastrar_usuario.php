<?php
require_once 'conexao.php';
require_once 'auth.php';

$usuario = exigir_login(['administrador']);

$mensagem = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $perfil = trim($_POST['perfil'] ?? '');
    $status = trim($_POST['status'] ?? 'Ativo');

    if ($nome === '' || $username === '' || $password === '' || !in_array($perfil, ['administrador', 'portaria', 'morador', 'padrao'], true)) {
        $mensagem = 'Preencha todos os campos corretamente.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $mensagem = 'Já existe um usuário com este nome de acesso.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $insert = $pdo->prepare("INSERT INTO usuarios (nome, username, password_hash, perfil, status) VALUES (?, ?, ?, ?, ?)");
                $insert->execute([$nome, $username, $hash, $perfil, $status]);
                $sucesso = 'Usuário cadastrado com sucesso!';
            }
        } catch (PDOException $e) {
            $mensagem = 'Erro ao cadastrar usuário: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuários - PortariaControl</title>
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
            <li><a href="cadastrar_usuario.php" class="active"><i class="bi bi-person-plus-fill"></i> Cadastro de Usuários</a></li>
        </ul>
        <div class="text-muted small text-center pt-3 border-top border-secondary opacity-75">Sistema v1.0</div>
    </nav>

    <main class="main-content">
        <div class="container-fluid px-0">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1">Cadastro de Usuários</h3>
                    <p class="text-muted small mb-0">Crie contas para os perfis do sistema.</p>
                </div>
            </div>

            <?php if ($sucesso): ?>
                <div class="alert alert-success shadow-sm"><?= htmlspecialchars($sucesso) ?></div>
            <?php endif; ?>
            <?php if ($mensagem): ?>
                <div class="alert alert-danger shadow-sm"><?= htmlspecialchars($mensagem) ?></div>
            <?php endif; ?>

            <div class="card card-custom bg-white p-4">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nome completo</label>
                            <input type="text" name="nome" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nome de acesso</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Senha</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Perfil</label>
                            <select name="perfil" class="form-select" required>
                                <option value="administrador">Administrador</option>
                                <option value="portaria">Portaria</option>
                                <option value="morador">Morador</option>
                                <option value="padrao">Padrão</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="Ativo">Ativo</option>
                                <option value="Inativo">Inativo</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-warning fw-semibold mt-4">
                        <i class="bi bi-person-plus-fill me-2"></i>Cadastrar Usuário
                    </button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
