<?php
function garantir_tabela_alertas_abandono(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS alertas_abandono (
            id INT AUTO_INCREMENT PRIMARY KEY,
            encomenda_id INT NOT NULL,
            morador_id INT NOT NULL,
            codigo_etiqueta VARCHAR(100) NOT NULL,
            dias_na_portaria INT NOT NULL,
            mensagem TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'Ativo',
            data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            data_resolucao DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

function calcular_dias_na_portaria(string $dataRecebimento): int
{
    if (empty($dataRecebimento)) {
        return 0;
    }

    $inicio = strtotime($dataRecebimento);
    $fim = time();

    if ($inicio === false) {
        return 0;
    }

    return max(0, (int) floor(($fim - $inicio) / 86400));
}

function gerar_alertas_abandono(PDO $pdo, int $diasAlerta = 7, int $diasRealerta = 3): array
{
    garantir_tabela_alertas_abandono($pdo);

    $stmt = $pdo->prepare(
        "SELECT e.id, e.codigo_etiqueta, e.data_recebimento, e.morador_id, m.nome_completo, m.bloco_quadra, m.numero_unidade
         FROM encomendas e
         JOIN moradores m ON e.morador_id = m.id
         WHERE e.status = 'Pendente'
           AND DATEDIFF(CURDATE(), DATE(e.data_recebimento)) >= ?"
    );
    $stmt->execute([$diasAlerta]);
    $pendentes = $stmt->fetchAll();

    foreach ($pendentes as $enc) {
        $dias = calcular_dias_na_portaria($enc['data_recebimento']);

        $stmtUltimo = $pdo->prepare(
            "SELECT id, data_criacao
             FROM alertas_abandono
             WHERE encomenda_id = ? AND status = 'Ativo'
             ORDER BY data_criacao DESC
             LIMIT 1"
        );
        $stmtUltimo->execute([$enc['id']]);
        $ultimo = $stmtUltimo->fetch();

        $criar = false;
        if (!$ultimo) {
            $criar = true;
        } else {
            $diasDesdeUltimo = calcular_dias_na_portaria($ultimo['data_criacao']);
            if ($diasDesdeUltimo >= $diasRealerta) {
                $criar = true;
            }
        }

        if ($criar) {
            $mensagem = "Encomenda em abandono há {$dias} dias na portaria. O morador {$enc['nome_completo']} ({$enc['bloco_quadra']} - {$enc['numero_unidade']}) deve ser avisado para retirar o pacote.";

            $insert = $pdo->prepare(
                "INSERT INTO alertas_abandono (encomenda_id, morador_id, codigo_etiqueta, dias_na_portaria, mensagem, status)
                 VALUES (?, ?, ?, ?, ?, 'Ativo')"
            );
            $insert->execute([
                $enc['id'],
                $enc['morador_id'],
                $enc['codigo_etiqueta'],
                $dias,
                $mensagem
            ]);
        }
    }

    $stmtAtivos = $pdo->query(
        "SELECT id, encomenda_id, morador_id, codigo_etiqueta, dias_na_portaria, mensagem, data_criacao
         FROM alertas_abandono
         WHERE status = 'Ativo'
         ORDER BY data_criacao DESC"
    );

    return $stmtAtivos->fetchAll();
}

function resolver_alertas_por_encomenda(PDO $pdo, int $encomendaId): void
{
    $stmt = $pdo->prepare(
        "UPDATE alertas_abandono
         SET status = 'Resolvido', data_resolucao = NOW()
         WHERE encomenda_id = ? AND status = 'Ativo'"
    );
    $stmt->execute([$encomendaId]);
}
?>
