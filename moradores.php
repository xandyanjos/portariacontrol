<?php
require_once 'conexao.php';
require_once 'auth.php';

$usuario = exigir_login(['administrador']);

$mensagem = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'cadastrar_morador') {
    $bloco_quadra = trim($_POST['bloco_quadra']);
    $numero_unidade = trim($_POST['numero_unidade']);
    $nome_completo = trim($_POST['nome_completo']);
    $cpf = trim($_POST['cpf']);
    $telefone = trim($_POST['telefone']);
    $email = trim($_POST['email']);
    $cep = trim($_POST['cep']);
    $endereco = trim($_POST['endereco']);
    $cidade = trim($_POST['cidade']);
    $estado = trim($_POST['estado']);

    $foto_nome = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $extensao = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if (in_array($extensao, ['jpg', 'jpeg', 'png', 'webp'])) {
            $pasta_uploads = 'uploads/';
            if (!is_dir($pasta_uploads)) mkdir($pasta_uploads, 0755, true);
            $foto_nome = 'morador_' . time() . '.' . $extensao;
            move_uploaded_file($_FILES['foto']['tmp_name'], $pasta_uploads . $foto_nome);
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO moradores (bloco_quadra, numero_unidade, nome_completo, cpf, telefone, email, cep, endereco, cidade, estado, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$bloco_quadra, $numero_unidade, $nome_completo, $cpf, $telefone, $email, $cep, $endereco, $cidade, $estado, $foto_nome]);
        $sucesso = "Morador cadastrado com sucesso!";
    } catch (\PDOException $e) {
        $mensagem = "Erro ao cadastrar morador: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'cadastrar_autorizado') {
    $morador_id = $_POST['morador_id'];
    $nome_completo = trim($_POST['nome_autorizado']);
    $rg_cpf = trim($_POST['rg_cpf_autorizado']);
    $parentesco = trim($_POST['parentesco']);

    try {
        $stmt = $pdo->prepare("INSERT INTO pessoas_autorizadas (morador_id, nome_completo, rg_cpf, parentesco_funcao) VALUES (?, ?, ?, ?)");
        $stmt->execute([$morador_id, $nome_completo, $rg_cpf, $parentesco]);
        $sucesso = "Pessoa autorizada cadastrada com sucesso!";
    } catch (\PDOException $e) {
        $mensagem = "Erro ao cadastrar pessoa autorizada: " . $e->getMessage();
    }
}

$moradores = $pdo->query("SELECT * FROM moradores ORDER BY bloco_quadra, numero_unidade")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Moradores - PortariaControl</title>
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
        .form-control, .form-select { padding: 10px 14px; border-radius: 10px; border: 1px solid #cbd5e1; }
        .avatar-img { width: 45px; height: 45px; object-fit: cover; border-radius: 50%; border: 2px solid #e2e8f0; }
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
            <li><a href="moradores.php" class="active"><i class="bi bi-people-fill"></i> Gestão de Moradores</a></li>
            <li><a href="historico.php"><i class="bi bi-clock-history"></i> Histórico de Retiradas</a></li>
            <?php if ($usuario['perfil'] === 'administrador'): ?>
                <li><a href="cadastrar_usuario.php"><i class="bi bi-person-plus-fill"></i> Cadastro de Usuários</a></li>
            <?php endif; ?>
        </ul>
        <div class="text-muted small text-center pt-3 border-top border-secondary opacity-75">Sistema v1.0</div>
    </nav>

    <main class="main-content">
        <div class="container-fluid px-0">

            <?php if ($sucesso): ?>
                <div class="alert alert-success d-flex align-items-center gap-2 shadow-sm" role="alert"><i class="bi bi-check-circle-fill"></i><div><?= htmlspecialchars($sucesso) ?></div></div>
            <?php endif; ?>
            <?php if ($mensagem): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 shadow-sm" role="alert"><i class="bi bi-exclamation-triangle-fill"></i><div><?= htmlspecialchars($mensagem) ?></div></div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Cadastro -->
                <div class="col-lg-5">
                    <div class="card card-custom bg-white p-4">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning"><i class="bi bi-person-plus-fill fs-3"></i></div>
                            <div>
                                <h4 class="fw-bold text-dark mb-0">Novo Morador</h4>
                                <p class="text-muted small mb-0">Dados pessoais e residenciais</p>
                            </div>
                        </div>

                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="acao" value="cadastrar_morador">
                            <div class="row g-2 mb-3">
                                <div class="col-md-6"><label class="form-label fw-semibold text-secondary small text-uppercase">Bloco / Qd</label><input type="text" name="bloco_quadra" class="form-control" required></div>
                                <div class="col-md-6"><label class="form-label fw-semibold text-secondary small text-uppercase">Nº Unidade</label><input type="text" name="numero_unidade" class="form-control" required></div>
                            </div>
                            <div class="mb-3"><label class="form-label fw-semibold text-secondary small text-uppercase">Nome Completo</label><input type="text" name="nome_completo" class="form-control" required></div>
                            <div class="row g-2 mb-3">
                                <div class="col-md-6"><label class="form-label fw-semibold text-secondary small text-uppercase">CPF</label><input type="text" name="cpf" class="form-control"></div>
                                <div class="col-md-6"><label class="form-label fw-semibold text-secondary small text-uppercase">Telefone</label><input type="text" name="telefone" class="form-control"></div>
                            </div>
                            <div class="mb-3"><label class="form-label fw-semibold text-secondary small text-uppercase">E-mail</label><input type="email" name="email" class="form-control"></div>
                            <div class="row g-2 mb-3">
                                <div class="col-md-4"><label class="form-label fw-semibold text-secondary small text-uppercase">CEP</label><input type="text" name="cep" class="form-control"></div>
                                <div class="col-md-8"><label class="form-label fw-semibold text-secondary small text-uppercase">Endereço</label><input type="text" name="endereco" class="form-control"></div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-md-8"><label class="form-label fw-semibold text-secondary small text-uppercase">Cidade</label><input type="text" name="cidade" class="form-control"></div>
                                <div class="col-md-4"><label class="form-label fw-semibold text-secondary small text-uppercase">Estado</label><input type="text" name="estado" class="form-control"></div>
                            </div>
                            <div class="mb-4"><label class="form-label fw-semibold text-secondary small text-uppercase">Foto</label><input type="file" name="foto" class="form-control" accept="image/*"></div>
                            <button type="submit" class="btn btn-warning text-dark w-100 fw-semibold py-2 shadow-sm">Cadastrar Morador</button>
                        </form>
                    </div>
                </div>

                <!-- Lista -->
                <div class="col-lg-7">
                    <div class="card card-custom bg-white p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h4 class="fw-bold text-dark mb-0">Moradores Cadastrados</h4>
                                <p class="text-muted small mb-0">Unidades e pessoas autorizadas</p>
                            </div>
                            <span class="badge bg-dark px-3 py-2"><?= count($moradores) ?> Registrados</span>
                        </div>

                        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Foto</th>
                                        <th>Unidade</th>
                                        <th>Morador</th>
                                        <th class="text-end">Autorizados</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($moradores) > 0): ?>
                                        <?php foreach ($moradores as $m): ?>
                                            <tr>
                                                <td>
                                                    <?php if (!empty($m['foto']) && file_exists('uploads/' . $m['foto'])): ?>
                                                        <img src="uploads/<?= htmlspecialchars($m['foto']) ?>" class="avatar-img" alt="Foto">
                                                    <?php else: ?>
                                                        <div class="avatar-img bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center text-secondary fw-bold"><i class="bi bi-person"></i></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="fw-bold text-dark"><?= htmlspecialchars($m['bloco_quadra'] . ' - ' . $m['numero_unidade']) ?></span></td>
                                                <td>
                                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($m['nome_completo']) ?></div>
                                                    <div class="text-muted small"><?= htmlspecialchars($m['telefone'] ?? '') ?></div>
                                                </td>
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#modalAutorizados<?= $m['id'] ?>">
                                                        <i class="bi bi-people-fill me-1"></i> Autorizados
                                                    </button>
                                                </td>
                                            </tr>

                                            <!-- Modal Autorizados -->
                                            <div class="modal fade" id="modalAutorizados<?= $m['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content border-0 shadow">
                                                        <div class="modal-header bg-dark text-white">
                                                            <h5 class="modal-title fs-6">Unidade <?= htmlspecialchars($m['bloco_quadra'] . ' / ' . $m['numero_unidade']) ?></h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p class="text-muted small mb-3">Titular: <strong><?= htmlspecialchars($m['nome_completo']) ?></strong></p>
                                                            <?php
                                                            $stmt_aut = $pdo->prepare("SELECT * FROM pessoas_autorizadas WHERE morador_id = ?");
                                                            $stmt_aut->execute([$m['id']]);
                                                            $autorizados_lista = $stmt_aut->fetchAll();
                                                            ?>
                                                            <h6 class="fw-bold text-secondary small text-uppercase mb-2">Quem já pode retirar:</h6>
                                                            <?php if (count($autorizados_lista) > 0): ?>
                                                                <ul class="list-group mb-3">
                                                                    <?php foreach ($autorizados_lista as $aut): ?>
                                                                        <li class="list-group-item d-flex justify-content-between align-items-center small">
                                                                            <div><strong><?= htmlspecialchars($aut['nome_completo']) ?></strong> <span class="text-muted">(<?= htmlspecialchars($aut['parentesco_funcao']) ?>)</span></div>
                                                                        </li>
                                                                    <?php endforeach; ?>
                                                                </ul>
                                                            <?php else: ?>
                                                                <p class="text-muted small fst-italic mb-3">Nenhum autorizado cadastrado.</p>
                                                            <?php endif; ?>
                                                            <hr>
                                                            <form method="POST">
                                                                <input type="hidden" name="acao" value="cadastrar_autorizado">
                                                                <input type="hidden" name="morador_id" value="<?= $m['id'] ?>">
                                                                <h6 class="fw-bold text-secondary small text-uppercase mb-2">Adicionar Autorizado</h6>
                                                                <div class="mb-2"><input type="text" name="nome_autorizado" class="form-control form-control-sm" placeholder="Nome completo" required></div>
                                                                <div class="row g-2 mb-2">
                                                                    <div class="col-md-6"><input type="text" name="rg_cpf_autorizado" class="form-control form-control-sm" placeholder="RG ou CPF" required></div>
                                                                    <div class="col-md-6"><input type="text" name="parentesco" class="form-control form-control-sm" placeholder="Ex: Babá" required></div>
                                                                </div>
                                                                <button type="submit" class="btn btn-sm btn-dark w-100 mt-1">Salvar Autorizado</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center py-4 text-muted">Nenhum morador cadastrado.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="text-center py-4 mt-4 text-muted small">
        © 2026 Desenvolvido por Alexandre Anjosa. Todos os direitos reservados.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>