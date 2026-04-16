<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$required_perfil = null;

require_once __DIR__ . '/../verifica_sessao.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils.php';

/* ALERTA ESTOQUE */

$stmt_alerta = $pdo->query("
SELECT nome, estoque
FROM produtos
WHERE estoque <= estoque_minimo
");


$alertas = $stmt_alerta->fetchAll();



$stmt_total_dia = $pdo->query("
SELECT SUM(valor_total) as total
FROM vendas
WHERE DATE(data_venda) = CURDATE()
");

$result = $stmt_total_dia->fetch();
$total_dia = $result['total'] ?? 0; 

$stmt_vencidos = $pdo->query("
SELECT nome, validade
FROM produtos
WHERE validade < CURDATE()
");

$vencidos = $stmt_vencidos->fetchAll();
$total_vencidos = count($vencidos); 


$stmt_vencer = $pdo->query("
SELECT nome, validade
FROM produtos
WHERE validade BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
");

$vencer = $stmt_vencer->fetchAll();
$total_vencer = count($vencer);




/* DADOS DO DASHBOARD */

$stmt_total_prod = $pdo->query("SELECT COUNT(*) as total FROM produtos");
$total_produtos = $stmt_total_prod->fetch()['total'];

$stmt_total_vendas = $pdo->query("SELECT COUNT(*) as total FROM vendas");
$total_vendas = $stmt_total_vendas->fetch()['total'];

$total_alertas = count($alertas);

$stmt_ultimas = $pdo->query("
SELECT id, valor_total
FROM vendas
ORDER BY id DESC
LIMIT 5
");

$ultimas_vendas = $stmt_ultimas->fetchAll();

$usuario = $_SESSION['usuario'] ?? ['email' => 'Usuário'];
$perfil = $_SESSION['perfil'] ?? 'user';


 include __DIR__ . '/layout.php'; ?>

<div class="card">

<h2> Dashboard</h2>
<p style="color:#666;">Visão geral do sistema</p>

<br>

<div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px;">

    <!-- PRODUTOS -->
    <div class="card" style="border-left:5px solid #7b4f27;">
        <div style="cursor:pointer;"
     onclick="window.location='listar_produtos_api.php'">
        <h3> Produtos</h3>
        <div style="font-size:30px; font-weight:bold;">
            <?= $total_produtos ?>
        </div>
    </div>
    </div>

    <!-- VENDAS -->
    <div class="card" style="border-left:5px solid green;">
        <div style="cursor:pointer;"
     onclick="window.location='historico_vendas.php'">

    <h3> Vendas</h3>
        <div style="font-size:30px; font-weight:bold;">
            <?= $total_vendas ?>
        </div>
    </div>
    </div>

    <!-- ALERTAS -->
    <div class="card" style="border-left:5px solid red; cursor:pointer;"
         onclick="window.location='estoque_baixo.php'">

        <h3> Estoque Baixo</h3>

        <div style="font-size:30px; font-weight:bold; color:red;">
            <?= $total_alertas ?>
        </div>

        

    </div>

</div>
<br></br>

<div class="card" style="border-left:5px solid blue;"
     onclick="window.location='vendas_dia.php'">
<h3>Vendas do dia</h3>

<div style="font-size:28px; font-weight:bold;">
R$ <?= number_format($total_dia,2,",",".") ?>
</div>

</div>

<br>

<div class="card" style="border-left:5px solid orange; cursor:pointer;"
     onclick="window.location='validade.php'">

<h3> Validade dos Produtos</h3>

<div style="font-size:20px; margin-top:10px;">

<span style="color:red; font-weight:bold;">
 Vencidos: <?= $total_vencidos ?>
</span>

<br>

<span style="color:orange; font-weight:bold;">
 Pra vencer: <?= $total_vencer ?>
</span>

</div>

<small>Clique para ver detalhes</small>

</div>

<br>

<!-- ÚLTIMAS VENDAS -->
<div class="card">

<h3> Últimas vendas</h3>

<ul>

<?php foreach($ultimas_vendas as $v): ?>

<li>
Venda #<?= $v['id'] ?> —
R$ <?= number_format($v['valor_total'],2,",",".") ?>
</li>

<?php endforeach; ?>

</ul>

</div>

</div>

<script>

function atualizarTotalDia(){

    fetch("../api/total_dia.php")
    .then(res => res.json())
    .then(data => {
        document.getElementById("totalDia").innerText = 
            "R$ " + parseFloat(data.total).toFixed(2).replace(".", ",")
    })

}

// atualiza a cada 5 segundos
setInterval(atualizarTotalDia, 5000);

</script>




</div></div></div></body></html>