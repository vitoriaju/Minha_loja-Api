<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_once __DIR__ . '/../config/conexao.php';

$stmt = $conn->query("
SELECT * FROM produtos 
WHERE estoque <= 5
");

$produtos = $stmt->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/layout.php';
?>

<div class="card">

<h2> Produtos com Estoque Baixo</h2>

<?php if(count($produtos) > 0): ?>

<table>

<tr>
<th>Nome</th>
<th>Estoque</th>
</tr>

<?php foreach($produtos as $p): ?>
<tr>
<td><?= $p['nome'] ?></td>
<td><?= $p['estoque'] ?></td>
</tr>
<?php endforeach; ?>

</table>

<?php else: ?>

<p style="color:green;">Tudo normal</p>

<?php endif; ?>

</div>

</div></div></div></body></html>