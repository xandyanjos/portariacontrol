<?php
session_start();
require_once 'conexao.php';

function inicializar_usuarios(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            username VARCHAR(50) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            perfil VARCHAR(20) NOT NULL,
            morador_id INT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'Ativo',
            data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    $total = (int) $stmt->fetchColumn();

    if ($total > 0) {
        return;
    }

    $moradorId = $pdo->query("SELECT id FROM moradores ORDER BY id LIMIT 1")->fetchColumn();
    $moradorId = $moradorId ?: null;

    $usuarios = [
        ['Administrador', 'admin', 'admin123', 'administrador', null],
        ['Portaria', 'portaria', 'portaria123', 'portaria', null],
        ['Morador', 'morador', 'morador123', 'morador', $moradorId],
        ['Padrão', 'padrao', 'padrao123', 'padrao', null],
    ];

    $insert = $pdo->prepare("INSERT INTO usuarios (nome, username, password_hash, perfil, morador_id) VALUES (?, ?, ?, ?, ?)");
    foreach ($usuarios as $usuario) {
        $insert->execute([$usuario[0], $usuario[1], password_hash($usuario[2], PASSWORD_DEFAULT), $usuario[3], $usuario[4]]);
    }
}

function usuario_logado(): ?array
{
    if (empty($_SESSION['usuario_id'])) {
        return null;
    }

    return [
        'id' => (int) $_SESSION['usuario_id'],
        'nome' => $_SESSION['usuario_nome'] ?? 'Usuário',
        'username' => $_SESSION['usuario_username'] ?? '',
        'perfil' => $_SESSION['usuario_perfil'] ?? 'padrao',
        'morador_id' => $_SESSION['usuario_morador_id'] ?? null,
    ];
}

function exigir_login(array $perfisPermitidos = []): array
{
    inicializar_usuarios($GLOBALS['pdo']);

    $usuario = usuario_logado();
    if (!$usuario) {
        header('Location: login.php');
        exit;
    }

    if (!empty($perfisPermitidos) && !in_array($usuario['perfil'], $perfisPermitidos, true)) {
        $_SESSION['erro_acesso'] = 'Você não tem permissão para acessar esta área.';
        header('Location: index.php');
        exit;
    }

    return $usuario;
}

function fazer_login(PDO $pdo, string $username, string $password): ?array
{
    inicializar_usuarios($pdo);

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ? AND status = 'Ativo' LIMIT 1");
    $stmt->execute([$username]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
        return null;
    }

    $_SESSION['usuario_id'] = (int) $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_username'] = $usuario['username'];
    $_SESSION['usuario_perfil'] = $usuario['perfil'];
    $_SESSION['usuario_morador_id'] = $usuario['morador_id'];

    return [
        'id' => (int) $usuario['id'],
        'nome' => $usuario['nome'],
        'username' => $usuario['username'],
        'perfil' => $usuario['perfil'],
        'morador_id' => $usuario['morador_id'],
    ];
}

function logout(): void
{
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

function label_perfil(string $perfil): string
{
    $mapa = [
        'administrador' => 'Administrador',
        'portaria' => 'Portaria',
        'morador' => 'Morador',
        'padrao' => 'Padrão',
    ];

    return $mapa[$perfil] ?? ucfirst($perfil);
}
