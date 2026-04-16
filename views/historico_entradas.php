<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_once __DIR__ . '/../pdo.php';

// BUSCAR DADOS (AGORA COM PREÇO)
$stmt = $pdo->query("
SELECT e.id, e.numero_nota, e.fornecedor, e.data_entrada,
       i.produto_id, i.quantidade, i.validade, i.preco,
       p.nome
FROM entradas e
JOIN itens_entrada i ON e.id = i.entrada_id
JOIN produtos p ON p.id = i.produto_id
ORDER BY e.id DESC
");

$dados = $stmt->fetchAll();

// AGRUPAR POR NOTA
$entradas = [];

foreach($dados as $d){
    $id = $d['id'];

    if(!isset($entradas[$id])){
        $entradas[$id] = [
            'numero_nota' => $d['numero_nota'],
            'fornecedor' => $d['fornecedor'],
            'data' => $d['data_entrada'],
            'itens' => []
        ];
    }

    $entradas[$id]['itens'][] = $d;
}

// layout
include __DIR__ . '/layout.php';
?>

<div class="card">

<h2>📥 Histórico de Entradas</h2>

<?php foreach($entradas as $e): ?>

<?php
// TOTAL DE PRODUTOS (não quantidade)
$total_itens = count($e['itens']);

// TOTAL EM R$
$total_valor = 0;

foreach($e['itens'] as $item){
    $total_valor += $item['quantidade'] * ($item['preco'] ?? 0);
}
?>

<div class="card" style="margin-bottom:15px; border-left:5px solid #7b4f27;">

<h3>Nota: <?= htmlspecialchars($e['numero_nota']) ?></h3>

<p>
<strong>Fornecedor:</strong> <?= htmlspecialchars($e['fornecedor']) ?><br>
<strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($e['data'])) ?><br>

<strong>Total de produtos:</strong> <?= $total_itens ?><br>

<strong>Valor total:</strong> 
R$ <?= number_format($total_valor,2,",",".") ?>
</p>

<ul>

<?php foreach($e['itens'] as $item): ?>

<li>
<?= htmlspecialchars($item['nome']) ?> 
- Qtd: <?= $item['quantidade'] ?> 
- Preço: R$ <?= number_format($item['preco'] ?? 0, 2, ",", ".") ?>
- Validade: <?= $item['validade'] ?>
</li>

<?php endforeach; ?>

</ul>

</div>

<?php endforeach; ?>

</div>

</div></div></div></body></html>