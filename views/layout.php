<?php
require_once __DIR__ . '/../utils.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$usuario_email = $_SESSION['usuario']['email'] ?? 'Usuário';
$usuario_nome = $_SESSION['usuario']['nome'] ?? $usuario_email;
$perfil_usuario = $_SESSION['perfil'] ?? 'user';

$primeira_letra = strtoupper(substr($usuario_nome, 0, 1));

$pagina_atual = basename($_SERVER['PHP_SELF']);

$ativo = function ($arquivo) use ($pagina_atual) {
    return $pagina_atual === $arquivo ? ' active' : '';
};
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Minha Loja</title>

<style>

/* RESET */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family: Arial, sans-serif;
}

/* LAYOUT PRINCIPAL */
.wrapper{
    display:flex;
    height:100vh;
}

/* SIDEBAR */
.sidebar{
    width:240px;
    background:#7b4f27;
    color:white;
    display:flex;
    flex-direction:column;
}

.sidebar h2{
    text-align:center;
    padding:22px 0;
    border-bottom:1px solid rgba(255,255,255,0.2);
    font-size:22px;
}

/* MENU LATERAL */
.sidebar-menu{
    flex:1;
    padding:16px 0;
    display:flex;
    flex-direction:column;
    gap:8px;
}

.menu-link{
    color:white;
    text-decoration:none;
    padding:13px 18px;
    display:block;
    transition:0.3s;
    font-size:15px;
    margin:0 12px;
    border-radius:10px;
}

.menu-link:hover{
    background:#5a371a;
    transform:translateX(4px);
}

.menu-link.active{
    background:#5a371a;
    font-weight:bold;
}

/* BOTÃO SAIR EMBAIXO */
.sidebar-bottom{
    border-top:1px solid rgba(255,255,255,0.2);
    padding:12px 0;
}

.logout-form{
    margin:0;
}

.logout-form button{
    width:calc(100% - 24px);
    margin:0 12px;
    color:white;
    text-align:left;
    padding:13px 18px;
    display:block;
    transition:0.3s;
    background:transparent;
    border:0;
    border-radius:10px;
    font:inherit;
    cursor:pointer;
    font-size:15px;
}

.logout-form button:hover{
    background:#5a371a;
    transform:translateX(4px);
}

/* MAIN */
.main{
    flex:1;
    display:flex;
    flex-direction:column;
}

/* HEADER */
.header{
    background:#f5d0a9;
    padding:14px 24px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 2px 8px rgba(0,0,0,0.08);
    position:relative;
    z-index:20;
}

.header-title{
    font-weight:bold;
    font-size:18px;
    color:#3b2411;
}

/* PERFIL DO USUÁRIO */
.user-profile{
    position:relative;
}

.user-button{
    display:flex;
    align-items:center;
    gap:10px;
    background:white;
    color:#3b2411;
    border:none;
    border-radius:30px;
    padding:8px 14px 8px 8px;
    cursor:pointer;
    box-shadow:0 2px 8px rgba(0,0,0,0.12);
}

.user-button:hover{
    background:#fff7ef;
}

.user-avatar{
    width:34px;
    height:34px;
    border-radius:50%;
    background:#7b4f27;
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:bold;
}

.user-info{
    display:flex;
    flex-direction:column;
    align-items:flex-start;
    line-height:1.1;
}

.user-name{
    font-weight:bold;
    font-size:14px;
    max-width:180px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

.user-role{
    font-size:12px;
    color:#777;
}

/* MENU QUE ABRE NA CARINHA */
.user-dropdown{
    position:absolute;
    right:0;
    top:54px;
    width:230px;
    background:white;
    border-radius:12px;
    box-shadow:0 8px 22px rgba(0,0,0,0.18);
    overflow:hidden;
    display:none;
}

.user-profile:hover .user-dropdown,
.user-profile:focus-within .user-dropdown{
    display:block;
}

.dropdown-title{
    padding:14px 16px;
    background:#7b4f27;
    color:white;
    font-weight:bold;
    font-size:14px;
}

.user-dropdown a{
    display:block;
    padding:13px 16px;
    text-decoration:none;
    color:#3b2411;
    font-size:14px;
    border-bottom:1px solid #eee;
}

.user-dropdown a:hover{
    background:#fdf3e7;
}

/* CONTENT */
.content{
    flex:1;
    padding:20px;
    background:#fdf3e7;
    overflow:auto;
}

/* CARD */
.card{
    background:white;
    padding:20px;
    border-radius:12px;
    box-shadow:0 4px 10px rgba(0,0,0,0.1);
}

/* BOTÃO PADRÃO */
button{
    padding:10px 15px;
    background:#7b4f27;
    color:white;
    border:none;
    border-radius:8px;
    cursor:pointer;
}

button:hover{
    background:#5a371a;
}

/* INPUTS */
input, select{
    width:100%;
    padding:10px;
    margin-top:5px;
    margin-bottom:15px;
    border-radius:8px;
    border:1px solid #ccc;
}

/* TABELA */
table{
    width:100%;
    border-collapse:collapse;
    margin-top:15px;
}

th, td{
    padding:10px;
    border-bottom:1px solid #ddd;
    text-align:left;
}

th{
    background:#f5d0a9;
}

</style>
</head>

<body>

<div class="wrapper">

    <div class="sidebar">
        <h2>Minha Loja</h2>

        <div class="sidebar-menu">
            <a class="menu-link<?= $ativo('dashboard.php') ?>" href="dashboard.php">Dashboard</a>

            <a class="menu-link<?= $ativo('vender.php') ?>" href="vender.php">Nova Venda</a>

            <a class="menu-link<?= $ativo('cadastrar_Produto.php') ?>" href="cadastrar_Produto.php">Cadastrar Produto</a>

            <a class="menu-link<?= $ativo('importar_xml.php') ?>" href="importar_xml.php">Importar XML</a>

            <a class="menu-link<?= $ativo('entrada_produtos.php') ?>" href="entrada_produtos.php">Entrada de Produtos</a>

            <a class="menu-link<?= $ativo('listar_produtos_api.php') ?>" href="listar_produtos_api.php">Listar Produtos</a>

            <a class="menu-link<?= $ativo('historico_entradas.php') ?>" href="historico_entradas.php">Histórico de Entradas</a>

            <a class="menu-link<?= $ativo('vencidos.php') ?>" href="vencidos.php">Produtos Vencidos</a>

              <a class="menu-link<?= $ativo('fechamento_dia.php') ?>" href="fechamento_dia.php">Fechamento do Dia</a>

            <a class="menu-link<?= $ativo('producao_dia.php') ?>" href="producao_dia.php">Produção</a>

            
            
        </div>

        <div class="sidebar-bottom">
            <form class="logout-form" action="../controllers/logout.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button type="submit">Sair</button>
            </form>
        </div>
    </div>

    <div class="main">

        <div class="header">
            <span class="header-title">Sistema de Gestão</span>

            <div class="user-profile">
                <button type="button" class="user-button">
                    <span class="user-avatar"><?= e($primeira_letra) ?></span>

                    <span class="user-info">
                        <span class="user-name"><?= e($usuario_nome) ?></span>
                        <span class="user-role">
                            <?= $perfil_usuario === 'admin' ? 'Administrador' : 'Usuário' ?>
                        </span>
                    </span>
                </button>

                <div class="user-dropdown">
                    <div class="dropdown-title">Minha conta</div>

                    <a href="recuperar.php">Alterar senha</a>

                    <?php if ($perfil_usuario === 'admin'): ?>
                        <a href="cadastrar.php">Criar novo usuário</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="content">