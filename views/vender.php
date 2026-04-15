<?php
require_once '../pdo.php';
require_once '../verifica_sessao.php';

$stmt = $pdo->query("SELECT * FROM produtos WHERE estoque > 0");
$produtos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Realizar Venda</title>

<link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Roboto:wght@400;500&display=swap" rel="stylesheet">

<style>

body {
    margin: 0;
    font-family: 'Roboto', sans-serif;
    background: linear-gradient(to bottom, #fdf3e7, #f5d0a9);
    display: flex;
    justify-content: center;
    padding: 50px 0;
}

.container {
    width: 750px;
    background-color: rgba(255,255,255,0.95);
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    text-align: center;
}

h2 {
    font-family: 'Pacifico', cursive;
    font-size: 28px;
    color: #7b4f27;
    margin-bottom: 25px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

table, th, td {
    border: 1px solid #7b4f27;
}

th {
    background-color: #7b4f27;
    color: white;
}

th, td {
    padding: 10px;
}

tr:nth-child(even) {
    background-color: rgba(123,79,39,0.1);
}

.button {
    display: inline-block;
    padding: 8px 12px;
    background-color: #7b4f27;
    color: #fff;
    text-decoration: none;
    border-radius: 12px;
    font-size: 14px;
    cursor: pointer;
}

.button:hover {
    background-color: #a66d3a;
}

.total-box {
    font-size: 20px;
    font-weight: bold;
    color: #7b4f27;
}

</style>
</head>

<body>

<div class="container">

<h2>Realizar Venda</h2>

<form method="POST" action="../controllers/VendaController.php">

<table>

<thead>
<tr>
<th>Produto</th>
<th>Estoque</th>
<th>Preço</th>
<th>Quantidade</th>
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
selected>
<?= $produto['nome'] ?>
</option>
<?php endforeach; ?>
</select>
</td>




<td class="estoque">0</td>

<td class="preco">0.00</td>

<td>
<input type="number" name="quantidade[]" class="quantidade" min="1" value="1" onchange="calcularTotal(this)">
</td>

<td class="total">0.00</td>

<td>
<button type="button" onclick="removerLinha(this)" class="button">Cancelar Item</button>
</td>

</tr>

</tbody>

</table>

<br>

<button type="button" class="button" onclick="adicionarItem()">Adicionar Produto</button>

<br><br>

<div class="total-box">
Total da Venda: R$ <span id="totalVenda">0.00</span>
</div>

<div style="margin-top:15px;">
    <label><strong>Forma de pagamento:</strong></label><br>

    <select name="forma_pagamento" required
        style="padding:8px; border-radius:8px; width:100%; margin-top:5px;">
        
        <option value="">Selecione</option>
        <option value="dinheiro"> Dinheiro</option>
        <option value="cartao">Cartão</option>
        <option value="pix"> Pix</option>

    </select>
</div>

<br>

<button type="submit" class="button">Finalizar Venda</button>

<a href="../views/dashboard.php" class="button">Cancelar</a>

</form>

</div>

<script>

// Atualiza preço e estoque
function atualizarProduto(select){

let linha = select.closest("tr")

let preco = select.selectedOptions[0].dataset.preco
let estoque = select.selectedOptions[0].dataset.estoque

linha.querySelector(".preco").innerText = parseFloat(preco).toFixed(2)
linha.querySelector(".estoque").innerText = estoque

calcularTotal(linha.querySelector(".quantidade"))

}


// Calcula total da linha
function calcularTotal(input){

let linha = input.closest("tr")

let preco = parseFloat(linha.querySelector(".preco").innerText) || 0
let quantidade = parseInt(input.value) || 0

if(quantidade < 1){
quantidade = 1
input.value = 1
}

let total = preco * quantidade

linha.querySelector(".total").innerText = total.toFixed(2)

calcularTotalVenda()

}

// Calcula total da venda
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

// Adiciona nova linha
function adicionarItem(){

let tabela = document.getElementById("itens")

let primeiraLinha = tabela.rows[0]

let novaLinha = primeiraLinha.cloneNode(true)

novaLinha.querySelector(".estoque").innerText = "0"
novaLinha.querySelector(".preco").innerText = "0.00"
novaLinha.querySelector(".total").innerText = "0.00"
novaLinha.querySelector(".quantidade").value = 1

tabela.appendChild(novaLinha)

}

// Remove linha
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

</body>
</html>