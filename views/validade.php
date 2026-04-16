<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_once __DIR__ . '/../pdo.php';

// VENCIDOS
$stmt_vencidos = $pdo->query("
SELECT nome, validade
FROM produtos
WHERE validade < CURDATE()
ORDER BY validade ASC
");
$vencidos = $stmt_vencidos->fetchAll();

// VENCENDO EM 7 DIAS
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

<br>

<!-- VENCIDOS -->
<div class="card" style="border-left:5px solid red;">
        <div style="cursor:pointer;"
     onclick="window.location='vencidos.php'">

    <h3> Vencidos</h3>


<br>

<?php if(count($vencidos) > 0): ?>

<ul>

<?php foreach($vencidos as $p): ?>

<li style="color:red;">
<?= htmlspecialchars($p['nome']) ?> - <?= $p['validade'] ?>
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



<div class="card" style="border-left:5px solid red;">
        <div style="cursor:pointer;">
    

    <h3> Uma semana para vencer</h3>

<?php if(count($vencer) > 0): ?>

<ul>

<?php foreach($vencer as $p): ?>

<li style="color:orange;">
<?= htmlspecialchars($p['nome']) ?> - <?= $p['validade'] ?>
</li>

<?php endforeach; ?>

</ul>

<?php else: ?>

<p style="color:green;"> Nenhum </p>

<?php endif; ?>

</div>

</div>

</div></div></div></body></html>