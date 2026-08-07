<?php
require_once 'conexao.php';
require_once 'auth.php';

$usuario = exigir_login(['administrador', 'portaria']);

$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo_etiqueta']);
    $morador_id = $_POST['morador_id'];
    $origem = trim($_POST['transportadora_marketplace']);
    $porteiro = trim($_POST['recebido_por_funcionario']);
    $obs = trim($_POST['observacoes']);

    try {
        $stmt = $pdo->prepare("INSERT INTO encomendas (codigo_etiqueta, morador_id, transportadora_marketplace, recebido_por_funcionario, observacoes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$codigo, $morador_id, $origem, $porteiro, $obs]);
        header("Location: index.php?sucesso=1");
        exit;
    } catch (\PDOException $e) {
        $mensagem = "Erro ao cadastrar (O código da etiqueta pode já estar cadastrado): " . $e->getMessage();
    }
}

// Buscar moradores para o select
$moradores = $pdo->query("SELECT id, bloco_quadra, numero_unidade, nome_completo FROM moradores ORDER BY bloco_quadra, numero_unidade")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Encomenda - PortariaControl</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; color: #334155; }
        .card-custom { border: none; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); }
        .form-control, .form-select { padding: 12px 16px; border-radius: 10px; border: 1px solid #cbd5e1; }
        .form-control:focus, .form-select:focus { box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15); border-color: #f59e0b; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark bg-dark shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <i class="bi bi-box-seam text-warning fs-4"></i>
                <span>Portaria<strong class="text-warning">Control</strong></span>
            </a>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-warning text-dark"><?= htmlspecialchars(label_perfil($usuario['perfil'])) ?></span>
                <a href="index.php" class="btn btn-outline-light btn-sm px-3 d-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i> Voltar ao Painel
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-5" style="max-width: 650px;">
        <div class="card card-custom bg-white p-4 p-md-5">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                    <i class="bi bi-box-arrow-in-down fs-3"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0">Registrar Encomenda</h3>
                    <p class="text-muted small mb-0">Preencha os dados da etiqueta e destino do pacote</p>
                </div>
            </div>

            <?php if ($mensagem): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div><?= htmlspecialchars($mensagem) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary small text-uppercase">Código da Etiqueta / Rastreio</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-upc-scan text-muted"></i></span>
                        <input type="text" name="codigo_etiqueta" class="form-control border-start-0 ps-0" placeholder="Use o leitor ou digite o código..." required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary small text-uppercase">Morador / Unidade Destino</label>
                    <select name="morador_id" class="form-select" required>
                        <option value="">Selecione a unidade ou morador...</option>
                        <?php foreach ($moradores as $m): ?>
                            <option value="<?= $m['id'] ?>">
                                <?= "Unidade: " . $m['numero_unidade'] . (!empty($m['bloco_quadra']) ? " (Bloco/Qd: " . $m['bloco_quadra'] . ")" : "") . " — " . $m['nome_completo'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary small text-uppercase">Transportadora / Marketplace</label>
                    <input type="text" name="transportadora_marketplace" class="form-control" placeholder="Ex: Correios, Mercado Livre, Shopee, Amazon..." required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary small text-uppercase">Nome do Porteiro (Recebimento)</label>
                    <input type="text" name="recebido_por_funcionario" class="form-control" placeholder="Seu nome" required>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold text-secondary small text-uppercase">Observações (Opcional)</label>
                    <textarea name="observacoes" class="form-control" rows="2" placeholder="Ex: Caixa avariada, volume grande..."></textarea>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-2">
                    <a href="index.php" class="btn btn-light text-secondary px-4 fw-medium">Cancelar</a>
                    <button type="submit" class="btn btn-warning text-dark px-4 fw-semibold shadow-sm">Salvar Encomenda</button>
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