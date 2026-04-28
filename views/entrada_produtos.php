<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_once '../pdo.php';
include __DIR__ . '/layout.php';

$stmt = $pdo->query("SELECT * FROM produtos");
$produtos = $stmt->fetchAll();
?>

<div class="card">

<h2> Entrada por Nota Fiscal</h2>

<form method="POST" action="../controllers/EntradaController.php">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

<label>Número da Nota:</label>
<input type="text" name="numero_nota" required>

<label>Fornecedor:</label>
<input type="text" name="fornecedor" required>

<br><br>

<table>

<tr>
<th>Produto</th>
<th>Quantidade</th>
<th>Validade</th>
<th>Preço</th>
<th>Ação</th>
</tr>

<tbody id="itens">

<tr>

<td>
<select name="produto_id[]">
<?php foreach($produtos as $p): ?>
<option value="<?= $p['id'] ?>">
<?= $p['nome'] ?>
</option>
<?php endforeach; ?>
</select>
</td>

<td><input type="number" name="quantidade[]" required></td>
<td><input type="date" name="validade[]" required></td>
<td><input type="number" step="0.01" name="preco[]" required></td>

<td><button type="button" onclick="removerLinha(this)">❌</button></td>

</tr>

</tbody>

</table>

<br>

<button type="button" onclick="adicionarItem()"> Adicionar Produto</button>

<br><br>

<button type="submit">Registrar Nota</button>

</form>

</div>

<script>
function adicionarItem() {
    let tabela = document.getElementById("itens")
    let primeiraLinha = tabela.rows[0]
    let novaLinha = primeiraLinha.cloneNode(true)

    novaLinha.querySelectorAll('input').forEach(input => input.value = '')
    novaLinha.querySelectorAll('select').forEach(select => select.selectedIndex = 0)

    tabela.appendChild(novaLinha)
}

function removerLinha(botao) {
    botao.closest('tr').remove()
}
</script>
