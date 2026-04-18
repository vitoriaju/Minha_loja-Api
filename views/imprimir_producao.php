<?php
require_once __DIR__ . '/../pdo.php';

$id = $_GET['id'];

$stmt = $pdo->prepare("
SELECT p.nome, i.quantidade
FROM itens_producao i
JOIN produtos p ON p.id = i.produto_id
WHERE i.producao_id = ?
");

$stmt->execute([$id]);
$itens = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Produção do Dia</title>

<style>
body{
font-family: Arial;
padding:20px;
}

h1{
text-align:center;
}

table{
width:100%;
border-collapse: collapse;
margin-top:20px;
}

th, td{
border:1px solid #000;
padding:10px;
text-align:left;
}

button{
margin-top:20px;
padding:10px 15px;
cursor:pointer;
}
</style>

</head>

<body>

<h1>  Lista de Produção </h1>

<p><strong>Data:</strong> <?= date('d/m/Y') ?></p>

<table>

<tr>
<th>Produto</th>
<th>Quantidade</th>
</tr>

<?php foreach($itens as $item): ?>

<tr>
<td><?= $item['nome'] ?></td>
<td><?= $item['quantidade'] ?></td>
</tr>

<?php endforeach; ?>

</table>

<button onclick="window.print()">🖨 Imprimir</button>

</body>
</html>