<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_once __DIR__ . '/../pdo.php';

// BUSCAR VENDAS
$stmt = $pdo->query("
SELECT id, valor_total, forma_pagamento, data_venda 
FROM vendas 
ORDER BY id DESC
");

$vendas = $stmt->fetchAll();

// layout
include __DIR__ . '/layout.php';
?>

<div class="card">

<h2>🧾 Histórico de Vendas</h2>

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

<p>Nenhuma venda registrada</p>

<?php endif; ?>

</div>

</div></div></div></body></html>