<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_once __DIR__ . '/../pdo.php';
require_once __DIR__ . '/../utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/views/fechamento_dia.php");
    exit;
}

if (!csrf_check($_POST['csrf_token'] ?? '')) {
    flash_set('erro', 'Token de segurança inválido.');
    header("Location: " . BASE_URL . "/views/fechamento_dia.php");
    exit;
}

try {

    /*
        Se vier uma data pelo formulário, usa ela.
        Se não vier, usa a data de hoje.
    */
    $dataFechamento = $_POST['data_fechamento'] ?? date('Y-m-d');

    $pdo->beginTransaction();

    /*
        1) Busca o resumo das vendas do dia
    */
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) AS quantidade_vendas,

            COALESCE(SUM(valor_total), 0) AS total_vendido,

            COALESCE(SUM(
                CASE 
                    WHEN forma_pagamento = 'dinheiro' 
                    THEN valor_total 
                    ELSE 0 
                END
            ), 0) AS total_dinheiro,

            COALESCE(SUM(
                CASE 
                    WHEN forma_pagamento = 'cartao' 
                    THEN valor_total 
                    ELSE 0 
                END
            ), 0) AS total_cartao,

            COALESCE(SUM(
                CASE 
                    WHEN forma_pagamento = 'pix' 
                    THEN valor_total 
                    ELSE 0 
                END
            ), 0) AS total_pix

        FROM vendas
        WHERE DATE(data_venda) = ?
    ");
    $stmt->execute([$dataFechamento]);
    $resumo = $stmt->fetch();

    /*
        2) Verifica se já existe fechamento para essa data
        Se já existir, vamos atualizar.
        Assim você pode gerar de novo se tiver feito uma venda depois.
    */
    $stmt = $pdo->prepare("
        SELECT id 
        FROM fechamentos_diarios 
        WHERE data_fechamento = ?
        LIMIT 1
    ");
    $stmt->execute([$dataFechamento]);
    $fechamentoExistente = $stmt->fetch();

    if ($fechamentoExistente) {

        $fechamentoId = $fechamentoExistente['id'];

        $stmt = $pdo->prepare("
            UPDATE fechamentos_diarios
            SET 
                horario_fechamento = '20:30:00',
                total_vendido = ?,
                total_dinheiro = ?,
                total_cartao = ?,
                total_pix = ?,
                quantidade_vendas = ?,
                criado_em = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $resumo['total_vendido'],
            $resumo['total_dinheiro'],
            $resumo['total_cartao'],
            $resumo['total_pix'],
            $resumo['quantidade_vendas'],
            $fechamentoId
        ]);

        /*
            Apaga os itens antigos para gerar novamente atualizado
        */
        $stmt = $pdo->prepare("
            DELETE FROM itens_fechamento
            WHERE fechamento_id = ?
        ");
        $stmt->execute([$fechamentoId]);

    } else {

        $stmt = $pdo->prepare("
            INSERT INTO fechamentos_diarios
            (
                data_fechamento,
                horario_fechamento,
                total_vendido,
                total_dinheiro,
                total_cartao,
                total_pix,
                quantidade_vendas
            )
            VALUES (?, '20:30:00', ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $dataFechamento,
            $resumo['total_vendido'],
            $resumo['total_dinheiro'],
            $resumo['total_cartao'],
            $resumo['total_pix'],
            $resumo['quantidade_vendas']
        ]);

        $fechamentoId = $pdo->lastInsertId();
    }

    /*
        3) Busca os produtos vendidos no dia
    */
    $stmt = $pdo->prepare("
        SELECT
            p.id AS produto_id,
            p.nome,
            p.categoria,
            p.unidade_medida,
            SUM(i.quantidade) AS quantidade_vendida,
            SUM(i.quantidade * i.preco_unitario) AS valor_vendido
        FROM itens_venda i
        INNER JOIN vendas v ON v.id = i.venda_id
        INNER JOIN produtos p ON p.id = i.produto_id
        WHERE DATE(v.data_venda) = ?
        GROUP BY 
            p.id,
            p.nome,
            p.categoria,
            p.unidade_medida
        ORDER BY quantidade_vendida DESC
    ");
    $stmt->execute([$dataFechamento]);
    $produtosVendidos = $stmt->fetchAll();

    /*
        4) Salva os produtos vendidos no fechamento
        Regra:
        - Produto da categoria Padaria gera sugestão de produção
        - Outros produtos aparecem no relatório, mas sugestão fica 0
    */
    $stmtInsertItem = $pdo->prepare("
        INSERT INTO itens_fechamento
        (
            fechamento_id,
            produto_id,
            quantidade_vendida,
            valor_vendido,
            sugestao_producao
        )
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($produtosVendidos as $produto) {

        $quantidadeVendida = floatval($produto['quantidade_vendida']);
        $valorVendido = floatval($produto['valor_vendido']);
        $categoria = strtolower(trim($produto['categoria'] ?? ''));

        $sugestaoProducao = 0;

        /*
            Se for produto de padaria, sugere produzir 10% a mais.
        */
        if ($categoria === 'padaria') {

            if ($produto['unidade_medida'] === 'kg') {
                $sugestaoProducao = round($quantidadeVendida * 1.10, 3);
            } else {
                $sugestaoProducao = ceil($quantidadeVendida * 1.10);
            }
        }

        $stmtInsertItem->execute([
            $fechamentoId,
            $produto['produto_id'],
            $quantidadeVendida,
            $valorVendido,
            $sugestaoProducao
        ]);
    }

    $pdo->commit();

    flash_set('sucesso', 'Fechamento diário gerado com sucesso.');

    header("Location: " . BASE_URL . "/views/fechamento_dia.php?data=" . $dataFechamento);
    exit;

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    flash_set('erro', 'Erro ao gerar fechamento: ' . $e->getMessage());

    header("Location: " . BASE_URL . "/views/fechamento_dia.php");
    exit;
}