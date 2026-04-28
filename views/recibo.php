<?php
require_once '../pdo.php';
require_once '../verifica_sessao.php';

if (!isset($_GET['id'])) {
    echo "Venda não encontrada.";
    exit;
}

$venda_id = intval($_GET['id']);

// 🔎 BUSCAR VENDA
$stmt = $pdo->prepare("
    SELECT v.*
    FROM vendas v
    WHERE v.id = ?
");
$stmt->execute([$venda_id]);
$venda = $stmt->fetch();
$stmt->execute([$venda_id]);
$venda = $stmt->fetch();

if (!$venda) {
    echo "Venda não encontrada.";
    exit;
}

// 🔎 BUSCAR ITENS
$stmt = $pdo->prepare("
    SELECT i.*, p.nome 
    FROM itens_venda i
    JOIN produtos p ON i.produto_id = p.id
    WHERE i.venda_id = ?
");
$stmt->execute([$venda_id]);
$itens = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Recibo</title>

<style>
body {
    font-family: Arial;
    background: #f4f4f4;
    padding: 20px;
}

.recibo {
    max-width: 500px;
    margin: auto;
    background: white;
    padding: 20px;
    border-radius: 8px;
}

h2 {
    text-align: center;
}

table {
    width: 100%;
    margin-top: 10px;
    border-collapse: collapse;
}

th, td {
    border-bottom: 1px solid #ccc;
    padding: 8px;
    text-align: left;
}

.total {
    font-size: 18px;
    font-weight: bold;
    margin-top: 15px;
}

.botao {
    margin-top: 20px;
    text-align: center;
}

button {
    padding: 10px 20px;
    cursor: pointer;
}
</style>

</head>

<body>

<div class="recibo">

<h2>🧾 Recibo de Venda</h2>

<p><strong>Venda:</strong> #<?= $venda['id'] ?></p>
<p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($venda['data_venda'])) ?></p>
<p><strong>Atendente:</strong> <?= $venda['usuario_nome'] ?? '---' ?></p>

<table>
<tr>
<th>Produto</th>
<th>Qtd</th>
<th>Preço</th>
<th>Total</th>
</tr>

<?php foreach ($itens as $item): 
    $totalItem = $item['quantidade'] * $item['preco_unitario'];
?>
<tr>
<td><?= $item['nome'] ?></td>
<td><?= $item['quantidade'] ?></td>
<td>R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
<td>R$ <?= number_format($totalItem, 2, ',', '.') ?></td>
</tr>
<?php endforeach; ?>

</table>

<div class="total">
<p>Total: R$ <?= number_format($venda['valor_total'], 2, ',', '.') ?></p>
<p>Pagamento: <?= strtoupper($venda['forma_pagamento']) ?></p>

<?php if ($venda['forma_pagamento'] === 'dinheiro'): ?>
<p>Recebido: R$ <?= number_format($venda['valor_recebido'], 2, ',', '.') ?></p>
<p>Troco: R$ <?= number_format($venda['troco'], 2, ',', '.') ?></p>
<?php endif; ?>
</div>

<div class="botao">
<button onclick="window.print()">🖨️ Imprimir</button>
<br><br>
<a href="vender.php">Nova Venda</a>
</div>

</div>

</body>
</html>