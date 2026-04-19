<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_once __DIR__ . '/../pdo.php';

include __DIR__ . '/layout.php';

// TODOS PRODUTOS
$stmtAll = $pdo->query("SELECT id, nome FROM produtos WHERE categoria = 'Padaria'");
$todos = $stmtAll->fetchAll();

// SUGESTÃO
$stmt = $pdo->query("
SELECT 
    p.id,
    p.nome,
    p.unidade_medida,

    COALESCE(ontem.total, 0) as ontem,
    COALESCE(semana.total_semana / 7, 0) as media,

    CEIL((COALESCE(ontem.total,0) * 0.8) + ((COALESCE(semana.total_semana,0) / 7) * 0.2)) as sugestao

FROM produtos p

LEFT JOIN (
    SELECT i.produto_id, SUM(i.quantidade) as total
    FROM itens_venda i
    JOIN vendas v ON v.id = i.venda_id
    WHERE DATE(v.data_venda) = CURDATE() - INTERVAL 1 DAY
    GROUP BY i.produto_id
) ontem ON ontem.produto_id = p.id

LEFT JOIN (
    SELECT i.produto_id, SUM(i.quantidade) as total_semana
    FROM itens_venda i
    JOIN vendas v ON v.id = i.venda_id
    WHERE v.data_venda >= CURDATE() - INTERVAL 7 DAY
    GROUP BY i.produto_id
) semana ON semana.produto_id = p.id

WHERE p.categoria = 'Padaria'
HAVING sugestao > 0
ORDER BY sugestao DESC
");

$produtos = $stmt->fetchAll();
?>

<div class="card">

<h2>Produção do Dia</h2>

<form method="POST" action="../controllers/ProducaoController.php">

<table id="tabela">

<tr>
<th>Produto</th>
<th>Sugestão</th>
<th>Produzir</th>
</tr>

<?php foreach($produtos as $p): ?>

<tr>

<td>
<strong><?= $p['nome'] ?></strong>

<input type="hidden" name="produto_novo[]" value="">
</td>

<td><?= $p['sugestao'] ?> <?= $p['unidade_medida'] ?></td>

<td>
<input type="number" name="quantidade[]" value="<?= $p['sugestao'] ?>">
<input type="hidden" name="produto_id[]" value="<?= $p['id'] ?>">
</td>

</tr>

<?php endforeach; ?>

</table>

<br>

<button type="button" onclick="adicionarLinha()"> Adicionar Produto</button>

<br><br>

<button type="submit"> Salvar Produção</button>

</form>

</div>

<script>

function adicionarLinha(){

    let tabela = document.getElementById("tabela");

    let novaLinha = tabela.insertRow();

    let cell1 = novaLinha.insertCell(0);
    let cell2 = novaLinha.insertCell(1);
    let cell3 = novaLinha.insertCell(2);

    // PRODUTO
    cell1.innerHTML = `
    <select name="produto_id[]" onchange="toggleNovo(this)">
        <option value="">-- Novo Produto --</option>
        <?php foreach($todos as $t): ?>
        <option value="<?= $t['id'] ?>"><?= $t['nome'] ?></option>
        <?php endforeach; ?>
    </select>

    <br>

    <input type="text" name="produto_novo[]" placeholder="Digite novo produto" style="display:none;">
    `;

    // sugestão
    cell2.innerHTML = `-`;

    // quantidade
    cell3.innerHTML = `
    <input type="number" name="quantidade[]" value="1">
    `;
}


//  FUNÇÃO FORA 
function toggleNovo(select){

    let input = select.parentElement.querySelector('input[name="produto_novo[]"]');

    if(select.value !== ""){
        input.style.display = "none";
        input.value = "";
    } else {
        input.style.display = "block";
    }
}

</script>

</div></div></div></body></html>