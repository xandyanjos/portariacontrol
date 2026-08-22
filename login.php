<?php
require_once 'conexao.php';
require_once 'auth.php';

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $usuario = fazer_login($pdo, $username, $password);
    if ($usuario) {
        header('Location: index.php');
        exit;
    }

    $erro = 'Usuário ou senha inválidos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PortariaControl</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card card-custom p-4 p-md-5" style="width: min(100%, 430px);">
            <div class="text-center mb-4">
                <h3 class="fw-bold text-dark">Acesso ao Sistema</h3>
                <p class="text-muted small mb-0">Entre com suas credenciais para continuar</p>
            </div>

            <?php if ($erro): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Usuário</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Senha</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-warning w-100 fw-semibold btn-full-mobile">Entrar</button>
            </form>

            <!-- <div class="mt-3 small text-muted">
              <strong>Usuários prontos:</strong> admin / admin123, portaria / portaria123, morador / morador123, padrao / padrao123
            </div> -->
        </div>
    </div>

    <footer class="global-footer">
        © 2026 Desenvolvido por Alexandre Anjos. Todos os direitos reservados.
    </footer>
    <script src="assets/js/app.js"></script>
</body>
</html>
