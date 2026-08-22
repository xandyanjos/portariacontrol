<?php
require_once 'conexao.php';
require_once 'auth.php';

// Inclui as classes PhpSpreadsheet para que suas constantes e métodos possam ser usados
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\AdvancedValueBinder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as SpreadsheetReaderException; // Alias para evitar conflito com outras Exceptions

// Inclui o autoload do Composer para carregar a biblioteca PhpSpreadsheet
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
    Cell::setValueBinder(new AdvancedValueBinder());
}

$usuario = exigir_login(['administrador', 'portaria']);

$mensagem = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'adicionar_morador') {
    $nome = trim($_POST['nome_completo'] ?? '');
    $unidade = trim($_POST['numero_unidade'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $tipo = trim($_POST['tipo'] ?? 'Morador');

    if (empty($nome) || empty($unidade)) {
        $mensagem = "O nome e o número da unidade são obrigatórios.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO moradores (nome_completo, numero_unidade, email, telefone, cpf, tipo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $unidade, $email, $telefone, $cpf, $tipo]);
            $sucesso = "Morador cadastrado com sucesso!";
        } catch (PDOException $e) {
            $mensagem = "Erro ao cadastrar morador: " . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'editar_morador') {
    $id = (int)($_POST['id'] ?? 0);
    $nome = trim($_POST['nome_completo'] ?? '');
    $unidade = trim($_POST['numero_unidade'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $tipo = trim($_POST['tipo'] ?? 'Morador');

    if (empty($nome) || empty($unidade) || $id === 0) {
        $mensagem = "O nome, a unidade e o ID do morador são obrigatórios para a edição.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE moradores SET nome_completo = ?, numero_unidade = ?, email = ?, telefone = ?, cpf = ?, tipo = ? WHERE id = ?");
            $stmt->execute([$nome, $unidade, $email, $telefone, $cpf, $tipo, $id]);
            $sucesso = "Morador atualizado com sucesso!";
        } catch (PDOException $e) {
            $mensagem = "Erro ao atualizar morador: " . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'deletar_morador') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM moradores WHERE id = ?");
            $stmt->execute([$id]);
            $sucesso = "Morador deletado com sucesso!";
        } catch (PDOException $e) {
            $mensagem = "Erro ao deletar morador. Verifique se ele não possui encomendas associadas.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'importar_planilha') {
    if (isset($_FILES['planilha_moradores']) && $_FILES['planilha_moradores']['error'] == UPLOAD_ERR_OK) {
        $caminhoArquivo = $_FILES['planilha_moradores']['tmp_name'];
        $nomeArquivo = $_FILES['planilha_moradores']['name'];
        $extensao = strtolower(pathinfo($nomeArquivo, PATHINFO_EXTENSION));

        if (!in_array($extensao, ['csv', 'xls', 'xlsx'])) {
            $mensagem = "Formato de arquivo inválido. Por favor, envie um arquivo CSV, XLS ou XLSX.";
        } elseif ($extensao !== 'csv' && !class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            $mensagem = "A biblioteca para ler arquivos XLS/XLSX não foi encontrada. Execute 'composer require phpoffice/phpspreadsheet' na pasta do projeto.";
        } else {
            $pdo->beginTransaction();
            try {
                $contador = 0;
                $stmt = $pdo->prepare("INSERT INTO moradores (nome_completo, numero_unidade, email, telefone, cpf, tipo) VALUES (?, ?, ?, ?, ?, ?)");

                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($caminhoArquivo);
                $sheet = $spreadsheet->getActiveSheet();
                $highestRow = $sheet->getHighestRow();

                // Começa da linha 2 para pular o cabeçalho
                for ($row = 2; $row <= $highestRow; $row++) {
                    // Pega os dados da linha. O 'true' no final formata os valores.
                    $nome = trim($sheet->getCell('A' . $row)->getValue());
                    $unidade = trim($sheet->getCell('B' . $row)->getValue());
                    $email = trim($sheet->getCell('C' . $row)->getValue());
                    $telefone = trim($sheet->getCell('D' . $row)->getValue());
                    $cpf = trim($sheet->getCell('E' . $row)->getValue());
                    $tipo = trim($sheet->getCell('F' . $row)->getValue());

                    // Validação mínima para não inserir linhas vazias
                    if (empty($nome) || empty($unidade)) {
                        continue; // Pula para a próxima linha
                    }

                    // Define um valor padrão para o tipo se estiver vazio
                    if (empty($tipo)) {
                        $tipo = 'Morador';
                    }

                    $stmt->execute([
                        $nome,
                        $unidade,
                        $email,
                        $telefone,
                        $cpf,
                        $tipo
                    ]);
                    $contador++;
                }

                $pdo->commit();
                $sucesso = "$contador moradores foram importados com sucesso!";

            } catch (Exception $e) {
                $pdo->rollBack(); // Certifique-se de que a transação seja revertida em caso de erro
                // Fornece uma mensagem de erro mais amigável
                if ($e instanceof \PhpOffice\PhpSpreadsheet\Reader\Exception) {
                    $mensagem = "Ocorreu um erro ao ler o arquivo da planilha. Verifique se o formato está correto e se o arquivo não está corrompido.";
                } elseif ($e instanceof PDOException) {
                    $mensagem = "Ocorreu um erro ao salvar os dados no banco. Verifique se as colunas da planilha correspondem às do sistema.";
                } else {
                    $mensagem = "Ocorreu um erro inesperado ao processar a planilha: " . $e->getMessage();
                }
            }
        }
    } else {
        $mensagem = "Erro no upload do arquivo. Por favor, tente novamente.";
    }
}

// Lógica para buscar moradores
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

if ($busca !== '') {
    $stmt = $pdo->prepare(
        "SELECT * FROM moradores 
         WHERE nome_completo LIKE ? OR numero_unidade LIKE ? OR email LIKE ? OR cpf LIKE ?
         ORDER BY numero_unidade"
    );
    $stmt->execute(["%$busca%", "%$busca%", "%$busca%", "%$busca%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM moradores ORDER BY numero_unidade");
}
$moradores = $stmt->fetchAll();
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

    <!-- Menu Lateral -->
    <nav class="sidebar-wrapper shadow" id="sidebarPrincipal">
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
                <li><a href="listar_usuarios.php"><i class="bi bi-list-ul"></i> Listar Usuários</a></li>
            <?php endif; ?>
        </ul>
        <div class="sidebar-footer">
            <div class="user-name"><?= htmlspecialchars($usuario['nome']) ?></div>
            <div class="user-role"><?= htmlspecialchars(label_perfil($usuario['perfil'])) ?></div>
            <a href="logout.php" class="btn btn-outline-light btn-sm mt-3 btn-sair">
                <i class="bi bi-box-arrow-right me-1"></i> Sair
            </a>
        </div>
    </nav>

    <!-- Modal Adicionar Morador -->
    <div class="modal fade" id="modalAdicionarMorador" tabindex="-1" aria-labelledby="modalAdicionarMoradorLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="acao" value="adicionar_morador">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAdicionarMoradorLabel">Adicionar Novo Morador</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nome_completo" class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" id="nome_completo" name="nome_completo" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="numero_unidade" class="form-label">Número da Unidade</label>
                                <input type="text" class="form-control" id="numero_unidade" name="numero_unidade" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">E-mail (Opcional)</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="telefone" class="form-label">Telefone (Opcional)</label>
                                <input type="text" class="form-control" id="telefone" name="telefone">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cpf" class="form-label">CPF (Opcional)</label>
                                <input type="text" class="form-control" id="cpf" name="cpf">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select class="form-select" id="tipo" name="tipo">
                                <option value="Proprietário">Proprietário</option>
                                <option value="Inquilino">Inquilino</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning text-dark">Salvar Morador</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Importar Planilha -->
    <div class="modal fade" id="modalImportarPlanilha" tabindex="-1" aria-labelledby="modalImportarPlanilhaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="acao" value="importar_planilha">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalImportarPlanilhaLabel">Importar Planilha de Moradores</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">Selecione um arquivo (CSV, XLS, XLSX) para importar os moradores em massa. Certifique-se de que a planilha tenha as colunas: <strong>nome_completo, numero_unidade, email, telefone, cpf, tipo</strong>.</p>
                        <div class="mb-3">
                            <label for="planilha_moradores" class="form-label">Arquivo da Planilha</label>
                            <input class="form-control" type="file" id="planilha_moradores" name="planilha_moradores" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning text-dark">Processar Importação</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Morador -->
    <div class="modal fade" id="modalEditarMorador" tabindex="-1" aria-labelledby="modalEditarMoradorLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="acao" value="editar_morador">
                    <input type="hidden" name="id" id="edit_morador_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditarMoradorLabel">Editar Morador</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_nome_completo" class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" id="edit_nome_completo" name="nome_completo" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_numero_unidade" class="form-label">Número da Unidade</label>
                                <input type="text" class="form-control" id="edit_numero_unidade" name="numero_unidade" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_email" class="form-label">E-mail (Opcional)</label>
                                <input type="email" class="form-control" id="edit_email" name="email">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_telefone" class="form-label">Telefone (Opcional)</label>
                                <input type="text" class="form-control" id="edit_telefone" name="telefone">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_cpf" class="form-label">CPF (Opcional)</label>
                                <input type="text" class="form-control" id="edit_cpf" name="cpf">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_tipo" class="form-label">Tipo</label>
                            <select class="form-select" id="edit_tipo" name="tipo">
                                <option value="Proprietário">Proprietário</option>
                                <option value="Inquilino">Inquilino</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning text-dark">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Conteúdo Principal -->
    <main class="main-content">
        <div class="container-fluid px-0">
            
            <div class="page-header">
                <div>
                    <h2 class="mb-1">Gestão de Moradores</h2>
                    <p class="subtitle">Adicione, edite ou importe moradores do condomínio.</p>
                </div>
                <div class="header-actions">
                    <button type="button" class="btn btn-outline-dark fw-semibold shadow-sm btn-full-mobile" data-bs-toggle="modal" data-bs-target="#modalImportarPlanilha">
                        <i class="bi bi-file-earmark-arrow-up"></i> Importar Planilha
                    </button>
                    <button type="button" class="btn btn-warning text-dark fw-semibold shadow-sm btn-full-mobile" data-bs-toggle="modal" data-bs-target="#modalAdicionarMorador">
                        <i class="bi bi-plus-lg"></i> Adicionar Morador
                    </button>
                </div>
            </div>

            <?php if ($sucesso): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($sucesso) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($mensagem): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($mensagem) ?></div>
            <?php endif; ?>

            <!-- Barra de Pesquisa -->
            <div class="card card-search">
                <form method="GET" class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-search fs-5"></i>
                    </span>
                    <input type="text" name="busca" class="form-control" placeholder="Busque por nome, unidade, e-mail ou CPF..." value="<?= htmlspecialchars($busca) ?>">
                    <?php if ($busca !== ''): ?>
                        <a href="moradores.php" class="btn btn-light border-0 text-danger px-3 d-flex align-items-center"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                    <button class="btn btn-dark btn-pesquisar fw-medium" type="submit">Pesquisar</button>
                </form>
            </div>

            <!-- Tabela de Moradores -->
            <div class="card card-table">
                <div class="table-responsive">
                    <table class="table table-hover table-custom table-responsive-stack mb-0 align-middle">
                        <thead>
                            <tr>
                                <th style="width: 25%;">Nome Completo</th>
                                <th style="width: 8%;">Unidade</th>
                                <th>E-mail</th>
                                <th style="width: 13%;">Telefone</th>
                                <th style="width: 13%;">CPF</th>
                                <th style="width: 10%;">Tipo</th>
                                <th class="text-end" style="width: 15%;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($moradores) > 0): ?>
                                <?php foreach ($moradores as $morador): ?>
                                    <tr>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($morador['nome_completo']) ?></td>
                                        <td><?= htmlspecialchars($morador['numero_unidade']) ?></td>
                                        <td><?= htmlspecialchars($morador['email'] ?: 'N/I') ?></td>
                                        <td><?= htmlspecialchars($morador['telefone'] ?: 'N/I') ?></td>
                                        <td><?= htmlspecialchars($morador['cpf'] ?: 'N/I') ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($morador['tipo'] ?? 'N/D') ?></span></td>
                                        <td class="text-end">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalEditarMorador"
                                                    data-id="<?= $morador['id'] ?>"
                                                    data-nome="<?= htmlspecialchars($morador['nome_completo']) ?>"
                                                    data-unidade="<?= htmlspecialchars($morador['numero_unidade']) ?>"
                                                    data-email="<?= htmlspecialchars($morador['email'] ?? '') ?>"
                                                    data-telefone="<?= htmlspecialchars($morador['telefone'] ?? '') ?>"
                                                    data-cpf="<?= htmlspecialchars($morador['cpf'] ?? '') ?>"
                                                    data-tipo="<?= htmlspecialchars($morador['tipo'] ?? 'Proprietário') ?>"
                                                >
                                                    <i class="bi bi-pencil-fill"></i> Editar
                                                </button>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja deletar este morador? Esta ação não pode ser desfeita.');">
                                                    <input type="hidden" name="acao" value="deletar_morador">
                                                    <input type="hidden" name="id" value="<?= $morador['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash-fill"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">Nenhum morador cadastrado.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <footer class="global-footer">
        © 2026 Desenvolvido por Alexandre Anjos. Todos os direitos reservados.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
    <script>
        const modalEditarMorador = document.getElementById('modalEditarMorador');
        modalEditarMorador.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            
            const id = button.getAttribute('data-id');
            const nome = button.getAttribute('data-nome');
            const unidade = button.getAttribute('data-unidade');
            const email = button.getAttribute('data-email');
            const telefone = button.getAttribute('data-telefone');
            const cpf = button.getAttribute('data-cpf');
            const tipo = button.getAttribute('data-tipo');

            document.getElementById('edit_morador_id').value = id;
            document.getElementById('edit_nome_completo').value = nome;
            document.getElementById('edit_numero_unidade').value = unidade;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_telefone').value = telefone;
            document.getElementById('edit_cpf').value = cpf;
            document.getElementById('edit_tipo').value = tipo;
        });
    </script>
</body>
</html>