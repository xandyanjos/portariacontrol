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
            <li><a href="historico.php"><i class="bi bi-clock-history"></i> Histórico de Retiradas</a></li>
            <li><a href="cadastrar_usuario.php" class="active"><i class="bi bi-person-plus-fill"></i> Cadastro de Usuários</a></li>
            <li><a href="listar_usuarios.php"><i class="bi bi-list-ul"></i> Listar Usuários</a></li>
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
                    <h2 class="mb-1">Cadastro de Usuários</h2>
                    <p class="subtitle">Crie contas para os perfis do sistema.</p>
                </div>
            </div>

            <?php if ($sucesso): ?>
                <div class="alert alert-success shadow-sm"><?= htmlspecialchars($sucesso) ?></div>
            <?php endif; ?>
            <?php if ($mensagem): ?>
                <div class="alert alert-danger shadow-sm"><?= htmlspecialchars($mensagem) ?></div>
            <?php endif; ?>

            <div class="card card-table bg-white p-4">
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

                    <button type="submit" class="btn btn-warning fw-semibold mt-4 btn-full-mobile">
                        <i class="bi bi-person-plus-fill me-2"></i>Cadastrar Usuário
                    </button>
                </form>
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
