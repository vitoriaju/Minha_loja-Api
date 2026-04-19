<?php
require_once '../pdo.php';
require_once '../verifica_sessao.php';

$stmt = $pdo->query("SELECT * FROM produtos WHERE estoque > 0");
$produtos = $stmt->fetchAll();

include __DIR__ . '/layout.php';


?>

<?php if (isset($_GET['sucesso'])): ?>
    <div style="background:#d4edda; color:#155724; padding:10px; margin-bottom:15px; border-radius:5px;">
        Venda realizada com sucesso!
    </div>
<?php endif; ?>

<div class="card">

<h2>Realizar Venda</h2>

<form method="POST" action="../controllers/VendaController.php">

<table>

<thead>
<tr>
<th>Produto</th>
<th>Estoque</th>
<th>Preço</th>
<th>Tipo</th>
<th>Quantidade/Peso</th>
<th>Total</th>
<th>Ação</th>
</tr>
</thead>

<tbody id="itens">

<tr>

<td>
<select name="produto_id[]" onchange="atualizarProduto(this)">
<?php foreach ($produtos as $produto): ?>
<option 
value="<?= $produto['id'] ?>"
data-preco="<?= $produto['preco'] ?>"
data-estoque="<?= $produto['estoque'] ?>"
data-unidade="<?= $produto['unidade_medida'] ?>">
<?= $produto['nome'] ?>
</option>
<?php endforeach; ?>
</select>
</td>

<td class="estoque">0</td>
<td class="preco">0.00</td>
<td class="tipo">-</td>

<td>
<input type="number" name="quantidade[]" class="quantidade" value="1" min="0.001" step="1" onchange="calcularTotal(this)">
</td>

<td class="total">0.00</td>

<td>
<button type="button" onclick="removerLinha(this)">Excluir</button>
</td>

</tr>

</tbody>

</table>

<br>

<button type="button" onclick="adicionarItem()">Adicionar Produto</button>

<br><br>

<div style="font-size:18px; font-weight:bold;">
Total da Venda: R$ <span id="totalVenda">0.00</span>
</div>

<br>

<label><strong>Forma de pagamento:</strong></label>
<select name="forma_pagamento" required>
    <option value="">Selecione</option>
    <option value="dinheiro">Dinheiro</option>
    <option value="cartao">Cartão</option>
    <option value="pix">Pix</option>
</select>

<br><br>

<button type="submit">Finalizar Venda</button>

</form>

</div>

</div></div></div></body></html>

<script>

function atualizarProduto(select){

    let linha = select.closest("tr")

    let preco = parseFloat(select.selectedOptions[0].dataset.preco) || 0
    let estoque = parseFloat(select.selectedOptions[0].dataset.estoque) || 0
    let unidade = select.selectedOptions[0].dataset.unidade

    linha.querySelector(".preco").innerText = preco.toFixed(2)
    linha.querySelector(".estoque").innerText = estoque
    linha.querySelector(".tipo").innerText = unidade

    let input = linha.querySelector(".quantidade")

    if(unidade === "kg"){
        input.step = "0.001"
        input.min = "0.001"
        input.value = "0.100"
    }else{
        input.step = "1"
        input.min = "1"
        input.value = "1"
    }

    calcularTotal(input)
}


function calcularTotal(input){

    let linha = input.closest("tr")

    let preco = parseFloat(linha.querySelector(".preco").innerText) || 0
    let quantidade = parseFloat(input.value) || 0

    let total = preco * quantidade

    linha.querySelector(".total").innerText = total.toFixed(2)

    calcularTotalVenda()
}


function calcularTotalVenda(){

    let totais = document.querySelectorAll(".total")
    let soma = 0

    totais.forEach(function(t){
        let valor = parseFloat(t.innerText)
        if(!isNaN(valor)){
            soma += valor
        }
    })

    document.getElementById("totalVenda").innerText = soma.toFixed(2)
}


function adicionarItem(){

    let tabela = document.getElementById("itens")
    let primeiraLinha = tabela.rows[0]

    let novaLinha = primeiraLinha.cloneNode(true)

    tabela.appendChild(novaLinha)

    let select = novaLinha.querySelector("select")
    atualizarProduto(select)
}


function removerLinha(botao){

    let linha = botao.closest("tr")
    let tabela = document.getElementById("itens")

    if(tabela.rows.length > 1){
        linha.remove()
    }

    calcularTotalVenda()
}


window.onload = function(){

    let selects = document.querySelectorAll("select[name='produto_id[]']")

    selects.forEach(function(select){
        atualizarProduto(select)
    })

}

</script>