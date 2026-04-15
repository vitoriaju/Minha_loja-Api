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

?>
<!doctype html>
<html lang="pt-br">
<head>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">

<title>Painel de Controle - Minha Loja</title>

<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

<style>

:root{
--bg-top:#fdf3e7;
--bg-bottom:#f5d0a9;
--accent:#7b4f27;
--accent-strong:#a66d3a;
--card-bg:#ffffff;
--muted:#666;
}

html,body{
height:100%;
margin:0;
font-family:'Roboto',sans-serif;
background:linear-gradient(to bottom,var(--bg-top),var(--bg-bottom));
}

.wrap{
min-height:100%;
display:flex;
justify-content:center;
align-items:center;
padding:30px;
}

.dashboard{
width:1100px;
background:#fff;
border-radius:20px;
box-shadow:0 12px 30px rgba(0,0,0,0.2);
display:grid;
grid-template-columns:260px 1fr;
overflow:hidden;
}

/* SIDEBAR */

.sidebar{
background:linear-gradient(180deg,#fffefc,#fbf3e9);
padding:25px;
border-right:1px solid rgba(0,0,0,0.05);
}

.brand{
display:flex;
align-items:center;
gap:10px;
font-weight:700;
color:var(--accent);
font-size:18px;
margin-bottom:20px;
}

.avatar{
width:45px;
height:45px;
border-radius:50%;
background:var(--accent);
color:#fff;
display:flex;
align-items:center;
justify-content:center;
font-weight:bold;
}

.nav-list{
list-style:none;
padding:0;
margin-top:15px;
}

.nav-list li{
margin-bottom:10px;
}

.nav-list a{
display:block;
padding:10px;
border-radius:8px;
text-decoration:none;
color:var(--accent);
font-weight:500;
}

.nav-list a:hover{
background:#fff4e8;
}

.logout-btn{
margin-top:20px;
display:block;
padding:10px;
background:var(--accent);
color:#fff;
text-align:center;
border-radius:8px;
text-decoration:none;
}

/* MAIN */

.main{
padding:30px;
}

.header{
margin-bottom:25px;
}

.title{
font-size:22px;
font-weight:700;
color:var(--accent);
}

.subtitle{
color:var(--muted);
font-size:14px;
}

/* CARDS */

.cards{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(230px,1fr));
gap:18px;
margin-bottom:25px;
}

.card{
background:var(--card-bg);
padding:18px;
border-radius:12px;
box-shadow:0 4px 10px rgba(0,0,0,0.05);
}

.card h3{
margin:0 0 10px 0;
color:var(--accent);
}

.big-number{
font-size:28px;
font-weight:700;
color:var(--accent);
}

/* LISTA VENDAS */

.vendas-list{
padding-left:15px;
}

.vendas-list li{
margin-bottom:5px;
}

/* AÇÕES */

.actions{
margin-top:20px;
display:flex;
gap:10px;
flex-wrap:wrap;
}

.btn{
padding:10px 15px;
border-radius:8px;
text-decoration:none;
font-weight:600;
}

.btn.primary{
background:var(--accent);
color:#fff;
}

.btn.ghost{
border:1px solid rgba(123,79,39,0.2);
color:var(--accent);
}

</style>

</head>

<body>

<div class="wrap">

<div class="dashboard">

<aside class="sidebar">

<div class="brand">
<div class="avatar"><?= strtoupper(substr($usuario['email'],0,1)) ?></div>
<div>Minha Loja</div>
</div>

<ul class="nav-list">
<li><a href="<?= BASE_URL ?>/views/listar_produtos_api.php">Produtos</a></li>
<li><a href="<?= BASE_URL ?>/views/cadastrar_produto.php">Cadastrar Produto</a></li>
<li><a href="<?= BASE_URL ?>/views/vencidos.php">Produtos Vencidos</a></li>
<li><a href="<?= BASE_URL ?>/views/nova_senha.php">Alterar Senha</a></li>
</ul>

<a class="logout-btn" href="../controllers/logout.php">Sair</a>

</aside>

<main class="main">

<div class="header">
<div class="title">Painel Administrativo</div>
<div class="subtitle">Controle geral do sistema</div>
</div>

<div class="cards">

<div class="card">

<h3>Total de Produtos</h3>

<div class="big-number"><?= $total_produtos ?></div>

</div>

<div class="card">

<h3>Total de Vendas</h3>

<div class="big-number"><?= $total_vendas ?></div>

</div>

<div class="card">

<h3>Notificações de Estoque</h3>

<?php if($total_alertas > 0): ?>

<ul>

<?php foreach($alertas as $produto): ?>

<li>⚠ <?= e($produto['nome']) ?> (<?= $produto['estoque'] ?>)</li>

<?php endforeach; ?>

</ul>

<?php else: ?>

<p style="color:green">✔ Estoque normal</p>

<?php endif; ?>

</div>

<div class="card">

<h3>Últimas Vendas</h3>

<ul class="vendas-list">

<?php foreach($ultimas_vendas as $v): ?>

<li>Venda #<?= $v['id'] ?> - R$ <?= number_format($v['valor_total'],2,",",".") ?></li>

<?php endforeach; ?>

</ul>

</div>

</div>

<section>

<h3 style="color:var(--accent)">Ações rápidas</h3>

<div class="actions">

<a class="btn primary" href="<?= BASE_URL ?>/views/vender.php">
🧾 Realizar Venda
</a>

<a class="btn ghost" href="<?= BASE_URL ?>/views/cadastrar_produto.php">
➕ Novo Produto
</a>

<a class="btn ghost" href="<?= BASE_URL ?>/views/listar_produtos_api.php">
📦 Ver Produtos
</a>

</div>

</section>

</main>

</div>

</div>

</body>
</html>