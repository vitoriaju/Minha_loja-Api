<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_once __DIR__ . '/../pdo.php';

// BUSCAR VENDAS DO DIA
$stmt = $pdo->query("
SELECT id, valor_total, forma_pagamento, data_venda
FROM vendas
WHERE DATE(data_venda) = CURDATE()
ORDER BY id DESC
");

$vendas = $stmt->fetchAll();

// TOTAL DO DIA
$total_dia = array_sum(array_column($vendas, 'valor_total'));

// layout
include __DIR__ . '/layout.php';
?>

<div class="card">

<h2> Vendas do Dia</h2>

<p style="font-size:18px; font-weight:bold;">
Total hoje: R$ <?= number_format($total_dia,2,",",".") ?>
</p>

<br>

<?php if(count($vendas) > 0): ?>

<table>

<tr>
<th>ID</th>
<th>Data</th>
<th>Total</th>
<th>Pagamento</th>
</tr>

<?php foreach($vendas as $v): ?>

<tr>
<td><?= $v['id'] ?></td>

<td>
<?= date('d/m/Y H:i', strtotime($v['data_venda'])) ?>
</td>

<td>
R$ <?= number_format($v['valor_total'],2,",",".") ?>
</td>

<td>
<?= htmlspecialchars($v['forma_pagamento']) ?>
</td>

</tr>

<?php endforeach; ?>

</table>

<?php else: ?>

<p style="color:green;"> Nenhuma venda hoje</p>

<?php endif; ?>

</div>

</div></div></div></body></html>