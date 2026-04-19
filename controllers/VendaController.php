<?php
require_once '../pdo.php';
require_once '../verifica_sessao.php';

session_start(); 

try {

    $pdo->beginTransaction();

    $produtos = $_POST['produto_id'];
    $quantidades = $_POST['quantidade'];
    $forma_pagamento = $_POST['forma_pagamento'];

    //  PEGA USUÁRIO DA SESSÃO
    if (isset($_SESSION['usuario']['id'])) {
        $usuario_id = $_SESSION['usuario']['id'];
    } elseif (isset($_SESSION['usuario_id'])) {
        $usuario_id = $_SESSION['usuario_id'];
    } else {
        throw new Exception("Usuário não identificado.");
    }

    $totalVenda = 0;

    //  CRIA VENDA COM USUÁRIO
    $stmt = $pdo->prepare("
        INSERT INTO vendas (data_venda, forma_pagamento, usuario_id) 
        VALUES (NOW(), ?, ?)
    ");
    $stmt->execute([$forma_pagamento, $usuario_id]);

    $venda_id = $pdo->lastInsertId();

    for ($i = 0; $i < count($produtos); $i++) {

        $produto_id = $produtos[$i];
        $quantidade = floatval($quantidades[$i]);

        // buscar produto
        $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
        $stmt->execute([$produto_id]);
        $produto = $stmt->fetch();

        if (!$produto) {
            throw new Exception("Produto não encontrado.");
        }

        //  VALIDAÇÃO KG / UNIDADE
        if ($produto['unidade_medida'] == 'kg') {
            if ($quantidade <= 0) {
                throw new Exception("Peso inválido.");
            }
        } else {
            if ($quantidade < 1) {
                throw new Exception("Quantidade inválida.");
            }
        }

        $preco = floatval($produto['preco']);
        $total = $quantidade * $preco;

        // salva item
        $stmt = $pdo->prepare("
            INSERT INTO itens_venda (venda_id, produto_id, quantidade, preco_unitario)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$venda_id, $produto_id, $quantidade, $preco]);

        // atualizar estoque
        $novoEstoque = $produto['estoque'] - $quantidade;

        if ($novoEstoque < 0) {
            throw new Exception("Estoque insuficiente para o produto: " . $produto['nome']);
        }

        $stmt = $pdo->prepare("UPDATE produtos SET estoque = ? WHERE id = ?");
        $stmt->execute([$novoEstoque, $produto_id]);

        $totalVenda += $total;
    }
    $stmt = $pdo->prepare("UPDATE vendas SET valor_total = ? WHERE id = ?");
    $stmt->execute([$totalVenda, $venda_id]);



    $pdo->commit();

    header("Location: ../views/vender.php?sucesso=1");
    exit;

} catch (Exception $e) {

    $pdo->rollBack();

    echo "Erro na venda: " . $e->getMessage();
}