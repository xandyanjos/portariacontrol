<?php
require_once 'conexao.php';
require_once 'auth.php';
require_once 'alertas_abandono.php';

$usuario = exigir_login(['administrador', 'portaria']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Buscar dados da encomenda e do morador
$stmt = $pdo->prepare("
    SELECT e.*, m.id as morador_id, m.nome_completo, m.numero_unidade 
    FROM encomendas e 
    JOIN moradores m ON e.morador_id = m.id 
    WHERE e.id = ? AND e.status = 'Pendente'
");
$stmt->execute([$id]);
$encomenda = $stmt->fetch();

if (!$encomenda) {
    header("Location: index.php");
    exit;
}

// Buscar pessoas autorizadas para este morador
$stmt_auth = $pdo->prepare("SELECT * FROM pessoas_autorizadas WHERE morador_id = ?");
$stmt_auth->execute([$encomenda['morador_id']]);
$autorizadas = $stmt_auth->fetchAll();

$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_retirada = $_POST['tipo_retirada']; 
    $porteiro = trim($_POST['entregue_por_funcionario']);
    
    $retirado_por_id = null;
    $retirado_por_morador = 0;
    $nome_avulso = null;

    if ($tipo_retirada === 'morador') {
        $retirado_por_morador = 1;
    } elseif ($tipo_retirada === 'autorizado') {
        $retirado_por_id = $_POST['retirado_por_id'];
    } else {
        $nome_avulso = trim($_POST['nome_retirante_avulso']);
    }

    try {
        $pdo->beginTransaction();

        $upd = $pdo->prepare("UPDATE encomendas SET status = 'Retirado' WHERE id = ?");
        $upd->execute([$id]);

        $log = $pdo->prepare("
            INSERT INTO logs_retirada (encomenda_id, retirado_por_id, retirado_por_morador, nome_retirante_avulso, entregue_por_funcionario) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $log->execute([$id, $retirado_por_id, $retirado_por_morador, $nome_avulso, $porteiro]);

        resolver_alertas_por_encomenda($pdo, $id);

        $pdo->commit();
        header("Location: index.php?baixa=1");
        exit;

    } catch (\PDOException $e) {
        $pdo->rollBack();
        $mensagem = "Erro ao dar baixa: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dar Baixa em Encomenda - PortariaControl</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; color: #334155; }
        .card-custom { border: none; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .info-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; }
        .form-control, .form-select { padding: 10px 14px; border-radius: 10px; border: 1px solid #cbd5e1; }
        .form-control:focus, .form-select:focus { box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.15); border-color: #198754; }
        .code-tag { background-color: #e2e8f0; color: #0f172a; padding: 3px 8px; border-radius: 6px; font-family: monospace; font-weight: 600; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark bg-dark shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <i class="bi bi-box-seam text-warning fs-4"></i>
                <span>Portaria<strong class="text-warning">Control</strong></span>
            </a>
            <a href="index.php" class="btn btn-outline-light btn-sm px-3 d-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> Voltar ao Painel
            </a>
        </div>
    </nav>

    <div class="container my-5" style="max-width: 680px;">
        <div class="card card-custom bg-white p-4 p-md-5 border-top border-success border-4">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                    <i class="bi bi-check2-circle fs-3"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0">Liberar Encomenda (Baixa)</h3>
                    <p class="text-muted small mb-0">Confira os dados do pacote e identifique quem está retirando</p>
                </div>
            </div>

            <!-- Dados do Pacote -->
            <div class="info-box mb-4">
                <div class="row g-2">
                    <div class="col-sm-6">
                        <span class="text-muted small d-block">Etiqueta / Rastreio</span>
                        <span class="code-tag"><?= htmlspecialchars($encomenda['codigo_etiqueta']) ?></span>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted small d-block">Unidade</span>
                        <strong class="text-dark"><?= htmlspecialchars($encomenda['numero_unidade']) ?></strong>
                    </div>
                    <div class="col-sm-6 mt-2">
                        <span class="text-muted small d-block">Morador Titular</span>
                        <span class="fw-semibold text-dark"><?= htmlspecialchars($encomenda['nome_completo']) ?></span>
                    </div>
                    <div class="col-sm-6 mt-2">
                        <span class="text-muted small d-block">Origem</span>
                        <span class="badge bg-secondary"><?= htmlspecialchars($encomenda['transportadora_marketplace']) ?></span>
                    </div>
                </div>
            </div>

            <?php if ($mensagem): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($mensagem) ?></div>
            <?php endif; ?>

            <form method="POST">
                <h6 class="fw-bold text-secondary text-uppercase small mb-3">Quem está retirando o pacote?</h6>
                
                <!-- Opção 1: Morador -->
                <div class="form-check p-3 border rounded-3 mb-2 bg-light">
                    <input class="form-check-input mt-1" type="radio" name="tipo_retirada" id="ret_morador" value="morador" checked>
                    <label class="form-check-label d-block ms-2 cursor-pointer" for="ret_morador">
                        <span class="fw-semibold text-dark">Próprio Morador Titular</span>
                        <span class="d-text text-muted small d-block"><?= htmlspecialchars($encomenda['nome_completo']) ?></span>
                    </label>
                </div>

                <!-- Opção 2: Pessoas Autorizadas -->
                <?php if (count($autorizadas) > 0): ?>
                    <div class="form-check p-3 border rounded-3 mb-2 bg-light">
                        <input class="form-check-input mt-1" type="radio" name="tipo_retirada" id="ret_autorizado" value="autorizado">
                        <label class="form-check-label d-block ms-2 cursor-pointer" for="ret_autorizado">
                            <span class="fw-semibold text-dark">Pessoa Autorizada Cadastrada</span>
                        </label>
                        <div class="ms-4 mt-2">
                            <select name="retirado_por_id" class="form-select form-select-sm" onclick="document.getElementById('ret_autorizado').checked = true;">
                                <?php foreach ($autorizadas as $aut): ?>
                                    <option value="<?= $aut['id'] ?>">
                                        <?= htmlspecialchars($aut['nome_completo'] . ' (' . $aut['parentesco_funcao'] . ' — Doc: ' . $aut['rg_cpf'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Opção 3: Avulso -->
                <div class="form-check p-3 border rounded-3 mb-4 bg-light">
                    <input class="form-check-input mt-1" type="radio" name="tipo_retirada" id="ret_avulso" value="avulso">
                    <label class="form-check-label d-block ms-2 cursor-pointer" for="ret_avulso">
                        <span class="fw-semibold text-dark">Outro (Autorização Pontual / Visitante)</span>
                    </label>
                    <div class="ms-4 mt-2">
                        <input type="text" name="nome_retirante_avulso" class="form-control form-control-sm" placeholder="Nome completo e documento de quem retirou..." onfocus="document.getElementById('ret_avulso').checked = true;">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold text-secondary small text-uppercase">Nome do Porteiro (Liberação)</label>
                    <input type="text" name="entregue_por_funcionario" class="form-control" placeholder="Seu nome" required>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <a href="index.php" class="btn btn-light text-secondary px-4 fw-medium">Cancelar</a>
                    <button type="submit" class="btn btn-success px-4 fw-semibold shadow-sm">Confirmar Retirada</button>
                </div>
            </form>
        </div>
    </div>

    <footer class="text-center py-4 mt-4 text-muted small">
        © 2026 Desenvolvido por Alexandre Anjosa. Todos os direitos reservados.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>