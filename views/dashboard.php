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


include __DIR__ . '/layout.php';
?>

<div class="card">

<h2> Painel Administrativo</h2>
<p style="color:#666;">Controle geral do sistema</p>

<br>

<div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:15px;">

    <div class="card">
        <h3>Total de Produtos</h3>
        <div style="font-size:26px; font-weight:bold;">
            <?= $total_produtos ?>
        </div>
    </div>

    <div class="card">
        <h3>Total de Vendas</h3>
        <div style="font-size:26px; font-weight:bold;">
            <?= $total_vendas ?>
        </div>
    </div>

    <div class="card">
        <h3>
<a href="estoque_baixo.php" style="text-decoration:none; color:#7b4f27;"> Estoque Baixo</a>
</h3>

        <?php if($total_alertas > 0): ?>
           <ul>

<?php foreach($alertas as $produto): ?>

<li style="color:red; font-weight:bold;">
<?= e($produto['nome']) ?> (<?= $produto['estoque'] ?>)
</li>

<?php endforeach; ?>

</ul>
        <?php else: ?>
            <p style="color:green;"> Tudo OK</p>
        <?php endif; ?>

    </div>

    <div class="card">
        <h3> Últimas Vendas</h3>

        <ul>
        <?php foreach($ultimas_vendas as $v): ?>
            <li>
                Venda #<?= $v['id'] ?> -
                R$ <?= number_format($v['valor_total'],2,",",".") ?>
            </li>
        <?php endforeach; ?>
        </ul>

    </div>

</div>

</div>

</div></div></div></body></html>