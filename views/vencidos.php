<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_once __DIR__ . '/../pdo.php';
require_once __DIR__ . '/../models/Produto.php';

$produto = new Produto($pdo);
$produtos = $produto->listarVencidos();

// layout
include __DIR__ . '/layout.php';
?>

<div class="card">

<h2> Produtos Vencidos</h2>

<?php if (!empty($produtos)): ?>

<table>

<thead>
<tr>
<th>ID</th>
<th>Nome</th>
<th>Preço</th>
<th>Unidade</th>
<th>Categoria</th>
<th>Validade</th>
<th>Estoque</th>
<th>Ações</th>
</tr>
</thead>

<tbody>

<?php foreach ($produtos as $row): ?>

<tr>
<td><?= $row['id']; ?></td>
<td><?= $row['nome']; ?></td>
<td>R$ <?= number_format($row['preco'], 2, ',', '.'); ?></td>
<td><?= $row['unidade_medida']; ?></td>
<td><?= $row['categoria']; ?></td>
<td><?= $row['validade']; ?></td>
<td><?= $row['estoque']; ?></td>

<td>
<form method="post" action="excluir_produto.php" onsubmit="return confirm('Deseja realmente excluir este produto?');">
   <input type="hidden" name="id" value="<?= $row['id']; ?>">
   <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
   <button type="submit" style="background:#e3342f;"> Excluir</button>
</form>
</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

<?php else: ?>

<p style="color:green; font-weight:bold;">
 Nenhum produto vencido
</p>

<?php endif; ?>

</div>

</div></div></div></body></html>
