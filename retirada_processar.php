<?php
require_once 'conexao.php';
require_once 'alertas_abandono.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Metodo nao permitido.']);
    exit;
}

$acao = isset($_POST['acao']) ? $_POST['acao'] : '';

if ($acao === 'buscar') {
    $termo = trim($_POST['termo'] ?? '');
    if ($termo === '') {
        echo json_encode(['ok' => false, 'msg' => 'Digite o numero da unidade, CPF ou codigo da etiqueta.']);
        exit;
    }

    $termoLike = '%' . $termo . '%';

    $sql = "SELECT e.id AS encomenda_id, e.codigo_etiqueta, e.transportadora_marketplace, e.data_recebimento, e.observacoes,
                   m.id AS morador_id, m.nome_completo, m.numero_unidade, m.cpf, m.telefone
            FROM encomendas e
            JOIN moradores m ON e.morador_id = m.id
            WHERE e.status = 'Pendente'
              AND (m.numero_unidade LIKE ?
                   OR m.cpf LIKE ?
                   OR e.codigo_etiqueta LIKE ?
                   OR m.nome_completo LIKE ?)
            ORDER BY e.data_recebimento DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$termoLike, $termoLike, $termoLike, $termoLike]);
    $rows = $stmt->fetchAll();

    $morador = null;
    $encomendas = [];
    foreach ($rows as $r) {
        if ($morador === null) {
            $morador = [
                'morador_id' => $r['morador_id'],
                'nome_completo' => $r['nome_completo'],
                'numero_unidade' => $r['numero_unidade'],
                'cpf' => $r['cpf'],
                'telefone' => $r['telefone'],
            ];
        }
        $encomendas[] = [
            'encomenda_id' => $r['encomenda_id'],
            'codigo_etiqueta' => $r['codigo_etiqueta'],
            'transportadora_marketplace' => $r['transportadora_marketplace'],
            'data_recebimento' => date('d/m/Y H:i', strtotime($r['data_recebimento'])),
            'observacoes' => $r['observacoes'],
        ];
    }

    echo json_encode([
        'ok' => true,
        'encontrado' => $morador !== null,
        'morador' => $morador,
        'encomendas' => $encomendas,
        'total' => count($encomendas),
    ]);
    exit;
}

if ($acao === 'confirmar') {
    $ids_post = $_POST['ids'] ?? [];
    $assinatura = trim($_POST['assinatura'] ?? '');
    $porteiro = trim($_POST['porteiro'] ?? 'Autoatendimento Morador');

    $ids = [];
    foreach ((array)$ids_post as $id) {
        $id = (int)$id;
        if ($id > 0) $ids[] = $id;
    }

    if (empty($ids)) {
        echo json_encode(['ok' => false, 'msg' => 'Selecione pelo menos uma encomenda para retirar.']);
        exit;
    }
    if ($assinatura === '') {
        echo json_encode(['ok' => false, 'msg' => 'Digite seu nome completo para assinar a retirada.']);
        exit;
    }

    $in = implode(',', array_fill(0, count($ids), '?'));

    $pdo->beginTransaction();
    try {
        $stmtSel = $pdo->prepare("SELECT e.*, m.nome_completo, m.numero_unidade
                                  FROM encomendas e JOIN moradores m ON e.morador_id = m.id
                                  WHERE e.id IN ($in) AND e.status = 'Pendente'
                                  FOR UPDATE");
        $stmtSel->execute($ids);
        $encomendas = $stmtSel->fetchAll();
        if (empty($encomendas)) {
            $pdo->rollBack();
            echo json_encode(['ok' => false, 'msg' => 'Nenhuma encomenda pendente encontrada com esses IDs.']);
            exit;
        }

        $morador_nome = $encomendas[0]['nome_completo'];
        $unidade = $encomendas[0]['numero_unidade'];

        $stmtUpd = $pdo->prepare("UPDATE encomendas SET status = 'Retirado', data_retirada = NOW() WHERE id = ?");
        $stmtLog = $pdo->prepare("INSERT INTO logs_retirada (encomenda_id, retirado_por_id, retirado_por_morador, nome_retirante_avulso, entregue_por_funcionario, protocolo_retirada)
                                  VALUES (?, NULL, 1, ?, ?, ?)");
        
        $comprovante = [
            'morador' => $morador_nome,
            'unidade' => $unidade,
            'assinatura' => $assinatura,
            'porteiro' => $porteiro,
            'data' => date('d/m/Y H:i:s'),
            'protocolo' => strtoupper(bin2hex(random_bytes(5))),
            'encomendas' => [],
        ];

        foreach ($encomendas as $e) {
            $stmtUpd->execute([$e['id']]);
            $stmtLog->execute([$e['id'], "Assinatura: " . $assinatura, $porteiro, $comprovante['protocolo']]);
            resolver_alertas_por_encomenda($pdo, $e['id']);
            $comprovante['encomendas'][] = [
                'id' => $e['id'],
                'codigo' => $e['codigo_etiqueta'],
                'origem' => $e['transportadora_marketplace'],
            ];
        }

        $pdo->commit();
        echo json_encode(['ok' => true, 'msg' => 'Retirada confirmada com sucesso!', 'comprovante' => $comprovante]);
    } catch (\Throwable $t) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['ok' => false, 'msg' => 'Erro ao confirmar retirada: ' . $t->getMessage()]);
    }
    exit;
}

echo json_encode(['ok' => false, 'msg' => 'Acao invalida.']);
?>