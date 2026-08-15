<?php
$host = 'localhost';
$db   = 'encomendas';
$user = 'root';
$pass = ''; // Insira sua senha do MySQL se houver
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Loga o erro detalhado para os desenvolvedores
    error_log("Erro de conexão com o banco de dados: " . $e->getMessage());
    // Exibe uma mensagem amigável para o usuário
    die("Ocorreu um erro crítico ao conectar com o banco de dados. Por favor, contate o administrador.");
}
?>