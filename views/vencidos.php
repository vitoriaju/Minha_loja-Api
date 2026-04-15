<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../models/Produto.php';

$produto = new Produto($conn);
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
<th>Qualidade</th>
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
<td><?= $row['qualidade']; ?></td>
<td><?= $row['categoria']; ?></td>
<td><?= $row['validade']; ?></td>
<td><?= $row['estoque']; ?></td>

<td>
<a href="excluir_produto.php?id=<?= $row['id']; ?>" 
   onclick="return confirm('Deseja realmente excluir este produto?');">
   <button style="background:#e3342f;"> Excluir</button>
</a>
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