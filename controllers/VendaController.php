<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../pdo.php';
require_once '../verifica_sessao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Acesso inválido.";
    exit;
}

try {

    $pdo->beginTransaction();

    // pega o usuário logado
    $usuario_id = $_SESSION['usuario']['id'];

    // =============================
    // CAPTURAR FORMA DE PAGAMENTO
    // =============================
    $forma_pagamento = $_POST['forma_pagamento'] ?? null;

    if (!$forma_pagamento) {
        throw new Exception("Forma de pagamento obrigatória.");
    }

    $formas_validas = ['dinheiro', 'cartao', 'pix'];

    if (!in_array($forma_pagamento, $formas_validas)) {
        throw new Exception("Forma de pagamento inválida.");
    }

    $valor_total = 0;

    // =============================
    // CALCULAR VALOR TOTAL DA VENDA
    // =============================
    foreach ($_POST['produto_id'] as $index => $produto_id) {

        $quantidade = (int) $_POST['quantidade'][$index];

        if ($quantidade <= 0) {
            throw new Exception("Quantidade inválida.");
        }

        $stmt = $pdo->prepare("SELECT preco, estoque FROM produtos WHERE id = ?");
        $stmt->execute([$produto_id]);

        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$produto) {
            throw new Exception("Produto não encontrado.");
        }

        if ($produto['estoque'] < $quantidade) {
            throw new Exception("Estoque insuficiente para o produto ID $produto_id");
        }

        $valor_total += $produto['preco'] * $quantidade;
    }

    // =============================
    // CRIAR VENDA (AGORA COM PAGAMENTO)
    // =============================
    $stmt = $pdo->prepare("
        INSERT INTO vendas (usuario_id, valor_total, forma_pagamento)
        VALUES (?, ?, ?)
    ");

    $stmt->execute([$usuario_id, $valor_total, $forma_pagamento]);

    $venda_id = $pdo->lastInsertId();

    // =============================
    // INSERIR ITENS E ATUALIZAR ESTOQUE
    // =============================
    foreach ($_POST['produto_id'] as $index => $produto_id) {

        $quantidade = (int) $_POST['quantidade'][$index];

        $stmt = $pdo->prepare("SELECT preco, estoque FROM produtos WHERE id = ?");
        $stmt->execute([$produto_id]);

        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        // salva item da venda
        $stmt = $pdo->prepare("
            INSERT INTO itens_venda (venda_id, produto_id, quantidade, preco_unitario)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $venda_id,
            $produto_id,
            $quantidade,
            $produto['preco']
        ]);

        // atualiza estoque
        $novo_estoque = $produto['estoque'] - $quantidade;

        $stmt = $pdo->prepare("UPDATE produtos SET estoque = ? WHERE id = ?");
        $stmt->execute([$novo_estoque, $produto_id]);
    }

    $pdo->commit();

} catch (Exception $e) {

    $pdo->rollBack();

    echo "Erro na venda: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">

<style>

body{
font-family:Roboto;
background: linear-gradient(to bottom,#fdf3e7,#f5d0a9);
display:flex;
justify-content:center;
align-items:center;
height:100vh;
}

.box{
background:white;
padding:40px;
border-radius:20px;
box-shadow:0 8px 20px rgba(0,0,0,0.2);
text-align:center;
}

h2{
color:#7b4f27;
}

</style>

</head>

<body>

<div class="box">

<h2> Venda realizada com sucesso!</h2>

<p>Forma de pagamento: <strong><?php echo htmlspecialchars($forma_pagamento); ?></strong></p>

<p>Redirecionando ...</p>

</div>

<script>

setTimeout(function(){
    window.location.href='../views/vender.php';
},2000);

</script>

</body>
</html>