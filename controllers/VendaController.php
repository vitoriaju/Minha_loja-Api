<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_once __DIR__ . '/../pdo.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check($_POST['csrf_token'] ?? '')) {
    header("Location: " . BASE_URL . "/views/vender.php");
    exit;
}

try {

    if (!isset($_POST['produto_id'], $_POST['quantidade'], $_POST['forma_pagamento'])) {
        throw new Exception("Dados da venda não enviados corretamente.");
    }

    $produtos = $_POST['produto_id'];
    $quantidades = $_POST['quantidade'];
    $forma_pagamento = $_POST['forma_pagamento'];

    // 💳 NOVO
    $valorRecebido = isset($_POST['valor_recebido']) 
        ? floatval(str_replace(',', '.', $_POST['valor_recebido'])) 
        : 0;

    if (count($produtos) == 0) {
        throw new Exception("Nenhum produto foi selecionado.");
    }

    if (isset($_SESSION['usuario']['id'])) {
        $usuario_id = $_SESSION['usuario']['id'];
    } elseif (isset($_SESSION['usuario_id'])) {
        $usuario_id = $_SESSION['usuario_id'];
    } else {
        throw new Exception("Usuário não identificado.");
    }

    $pdo->beginTransaction();

    $totalVenda = 0;

    $stmt = $pdo->prepare("
        INSERT INTO vendas (data_venda, forma_pagamento, usuario_id, valor_total, valor_recebido, troco) 
        VALUES (NOW(), ?, ?, 0, 0, 0)
    ");
    $stmt->execute([$forma_pagamento, $usuario_id]);

    $venda_id = $pdo->lastInsertId();

    for ($i = 0; $i < count($produtos); $i++) {

        if (!isset($quantidades[$i]) || $quantidades[$i] === "") {
            throw new Exception("Quantidade não informada para um dos produtos.");
        }

        $produto_id = intval($produtos[$i]);
        $quantidade = floatval(str_replace(',', '.', $quantidades[$i]));

        $stmt = $pdo->prepare("SELECT id, nome, preco, estoque, unidade_medida FROM produtos WHERE id = ?");
        $stmt->execute([$produto_id]);
        $produto = $stmt->fetch();

        if (!$produto) {
            throw new Exception("Produto não encontrado.");
        }

        if ($produto['unidade_medida'] == 'kg') {
            if ($quantidade <= 0) {
                throw new Exception("Peso inválido para o produto: " . $produto['nome']);
            }
        } else {
            if ($quantidade < 1) {
                throw new Exception("Quantidade inválida para o produto: " . $produto['nome']);
            }
        }

        $preco = floatval($produto['preco']);
        $total = $quantidade * $preco;

        if ($total <= 0) {
            throw new Exception("Erro no cálculo do total para o produto: " . $produto['nome']);
        }

        $stmt = $pdo->prepare("
            INSERT INTO itens_venda (venda_id, produto_id, quantidade, preco_unitario)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$venda_id, $produto_id, $quantidade, $preco]);

        // Baixa atomica: outra venda concorrente nao consegue usar o mesmo saldo.
        $stmt = $pdo->prepare("
            UPDATE produtos
            SET estoque = estoque - ?
            WHERE id = ? AND estoque >= ?
        ");
        $stmt->execute([$quantidade, $produto_id, $quantidade]);

        if ($stmt->rowCount() !== 1) {
            throw new Exception("Estoque insuficiente para o produto: " . $produto['nome']);
        }

        consumirLotesFefo($pdo, $produto_id, $quantidade, $produto['nome']);

        $totalVenda += $total;
    }

    if ($totalVenda <= 0) {
        throw new Exception("Erro: total da venda ficou zerado.");
    }

    // 💳 CALCULO DO TROCO
    $troco = 0;

    if ($forma_pagamento === 'dinheiro') {

        if ($valorRecebido < $totalVenda) {
            throw new Exception("Valor recebido é menor que o total.");
        }

        $troco = $valorRecebido - $totalVenda;
    }

    // 💾 SALVA FINAL
    $stmt = $pdo->prepare("
        UPDATE vendas 
        SET valor_total = ?, valor_recebido = ?, troco = ?
        WHERE id = ?
    ");
    $stmt->execute([$totalVenda, $valorRecebido, $troco, $venda_id]);

    audit_log($pdo, 'criar', 'venda', $venda_id, ['valor_total' => $totalVenda]);
    $pdo->commit();

    // 🧾 REDIRECIONA PRA RECIBO
    header("Location: ../views/recibo.php?id=" . $venda_id);
    exit;

} catch (Exception $e) {

    if ($pdo->inTransaction()) $pdo->rollBack();
    log_exception($e, 'Falha ao registrar venda');
    http_response_code(500);
    echo "Nao foi possivel registrar a venda.";
}

function consumirLotesFefo(PDO $pdo, int $produtoId, float $quantidade, string $produtoNome): void
{
    $stmt = $pdo->prepare("
        SELECT id, quantidade_restante
        FROM lotes_estoque
        WHERE produto_id = ? AND quantidade_restante > 0
        ORDER BY validade IS NULL, validade ASC, id ASC
        FOR UPDATE
    ");
    $stmt->execute([$produtoId]);
    $restante = $quantidade;
    $stmtBaixa = $pdo->prepare("
        UPDATE lotes_estoque
        SET quantidade_restante = quantidade_restante - ?
        WHERE id = ? AND quantidade_restante >= ?
    ");

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $lote) {
        if ($restante <= 0.000001) {
            break;
        }

        $baixa = min($restante, (float) $lote['quantidade_restante']);
        $stmtBaixa->execute([$baixa, $lote['id'], $baixa]);
        if ($stmtBaixa->rowCount() !== 1) {
            throw new RuntimeException('Conflito ao baixar lote do produto: ' . $produtoNome);
        }
        $restante -= $baixa;
    }

    if ($restante > 0.000001) {
        throw new RuntimeException('Saldo por lote insuficiente para o produto: ' . $produtoNome);
    }
}
