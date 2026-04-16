<?php
// você pode pegar o nome do usuário se quiser
$usuario_nome = $_SESSION['usuario']['nome'] ?? 'Usuário';
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
    width:230px;
    background:#7b4f27;
    color:white;
    display:flex;
    flex-direction:column;
}

.sidebar h2{
    text-align:center;
    padding:20px 0;
    border-bottom:1px solid rgba(255,255,255,0.2);
}

.sidebar a{
    color:white;
    text-decoration:none;
    padding:12px 20px;
    display:block;
    transition:0.3s;
}

.sidebar a:hover{
    background:#5a371a;
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
    padding:15px 20px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.header span{
    font-weight:bold;
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

.card:hover{
    transform: scale(1.02);
    transition: 0.2s;
}

/* BOTÃO */
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

    <a href="dashboard.php"> Dashboard</a>

    <a href="vender.php"> Nova Venda</a>

    <a href="cadastrar_produto.php"> Cadastrar Produto</a>

    <a href="entrada_produtos.php"> Entrada de Produtos</a>

    <a href="listar_produtos_api.php">Listar Produtos</a>

    <a href="historico_entradas.php"> Histórico de Entradas</a>

    <a href="vencidos.php"> Produtos Vencidos</a>

     <a href="cadastrar.php"> Criar Conta</a>

    <a href="nova_senha.php"> Alterar Senha</a>

    <a href="../controllers/logout.php"> Sair</a>
    
</div>

    <!-- MAIN -->
    <div class="main">

        <!-- HEADER -->
        <div class="header">
            <span> Sistema de Gestão</span>
            <span>👤 <?php echo htmlspecialchars($usuario_nome); ?></span>
        </div>

        <!-- CONTENT -->
        <div class="content">