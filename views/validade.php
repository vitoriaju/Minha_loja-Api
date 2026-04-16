<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_once __DIR__ . '/../pdo.php';


$stmt_vencidos = $pdo->query("
SELECT nome, validade
FROM produtos
WHERE validade < CURDATE()
ORDER BY validade ASC
");
$vencidos = $stmt_vencidos->fetchAll();


$stmt_vencer = $pdo->query("
SELECT nome, validade
FROM produtos
WHERE validade BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
ORDER BY validade ASC
");
$vencer = $stmt_vencer->fetchAll();

include __DIR__ . '/layout.php';
?>

<div class="card">

<h2> Controle de Validade</h2>

<br></br>


<div class="card" style="border-left:5px solid red;">
<div style="cursor:pointer;" onclick="window.location='vencidos.php'">

<h3>Vencidos</h3>

<br>

<?php if(count($vencidos) > 0): ?>

<ul>

<?php foreach($vencidos as $p): ?>

<?php
$dias = floor((strtotime($p['validade']) - time()) / 86400);


if($dias == 0){
    $texto = "vence hoje";
}elseif($dias < 0){
    $texto = abs($dias) . " dias atrasado";
}else{
    $texto = "vence em $dias dias";
}
?>

<li style="color:red; font-weight:bold;">
<?= htmlspecialchars($p['nome']) ?> - <?= $p['validade'] ?>

(<?= $texto ?>)
</li>

<?php endforeach; ?>

</ul>

<?php else: ?>

<p style="color:green;"> Nenhum vencido</p>

<?php endif; ?>

</div>

<small>Clique para ver detalhes</small>

</div>

<br>


<div class="card" style="border-left:5px solid orange;">
<div style="cursor:pointer;">

<h3>Uma semana para vencer</h3>
<br>

<?php if(count($vencer) > 0): ?>

<ul>

<?php foreach($vencer as $p): ?>

<?php
$dias = floor((strtotime($p['validade']) - time()) / 86400);


if($dias == 0){
    $texto = "vence hoje";
}elseif($dias < 0){
    $texto = abs($dias) . " dias atrasado";
}else{
    $texto = "vence em $dias dias";
}


if($dias <= 1){
    $cor = "red";
}elseif($dias <= 3){
    $cor = "orange";
}else{
    $cor = "green";
}
?>

<li style="color:<?= $cor ?>; font-weight:bold;">
<?= htmlspecialchars($p['nome']) ?> - <?= $p['validade'] ?>

(<?= $texto ?>)
</li>

<?php endforeach; ?>

</ul>

<?php else: ?>

<p style="color:green;"> Nenhum</p>

<?php endif; ?>

</div>

</div>

</div></div></div></body></html>