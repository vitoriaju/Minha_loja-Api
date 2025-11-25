<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_admin('/index.php');
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../models/Produto.php';

$produto = new Produto($conn);

if(!isset($_GET['id'])) {
    die("ID do produto não informado.");
}

$id = $_GET['id'];
$dados = $produto->buscarPorId($id);

if(!$dados) {
    die("Produto não encontrado.");
}


if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produto->atualizar($id, $_POST);
    header("Location: listar_produtos.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Editar Produto</title>
<link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
<style>
    body {
        margin: 0;
        font-family: 'Roboto', sans-serif;
        background: linear-gradient(to bottom, #fdf3e7, #f5d0a9);
        display: flex;
        justify-content: center;
        align-items: flex-start;
        padding: 50px 0;
    }

    .container {
        width: 400px;
        background-color: rgba(255,255,255,0.95);
        padding: 30px;
        border-radius: 20px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        box-sizing: border-box;
    }

    .voltar {
        display: inline-block;
        margin-bottom: 15px;
        color: #7b4f27;
        text-decoration: none;
        font-weight: bold;
    }

    .voltar:hover {
        color: #a66d3a;
    }

    h2 {
        font-family: 'Pacifico', cursive;
        text-align: center;
        margin-bottom: 20px;
        color: #7b4f27;
    }

    label {
        display: block;
        margin-top: 10px;
        color: #555;
    }

    input[type="text"], input[type="number"], input[type="date"] {
        width: 100%;
        padding: 12px 15px;
        margin-top: 5px;
        border: 2px solid #7b4f27;
        border-radius: 10px;
        font-size: 16px;
        outline: none;
        box-sizing: border-box;
    }

    input:focus {
        border-color: #a66d3a;
    }

    button {
        margin-top: 20px;
        width: 100%;
        padding: 12px;
        background-color: #7b4f27;
        color: #fff;
        border: none;
        border-radius: 12px;
        font-size: 18px;
        cursor: pointer;
        transition: background 0.3s;
    }

    button:hover {
        background-color: #a66d3a;
    }
</style>
</head>
<body>
<div class="container">
    <a href="listar_produtos.php" class="voltar">← Voltar</a>
    <h2>Editar Produto</h2>
    <form method="post" action="">
        <label>Nome:</label>
        <input type="text" name="nome" value="<?php echo $dados['nome']; ?>" required>

        <label>Preço:</label>
        <input type="number" step="0.01" name="preco" value="<?php echo $dados['preco']; ?>" required>

        <label>Qualidade:</label>
        <input type="text" name="qualidade" value="<?php echo $dados['qualidade']; ?>">

        <label>Categoria:</label>
        <input type="text" name="categoria" value="<?php echo $dados['categoria']; ?>">

        <label>Validade:</label>
        <input type="date" name="validade" value="<?php echo $dados['validade']; ?>">

        <label>Estoque:</label>
        <input type="number" name="estoque" value="<?php echo $dados['estoque']; ?>" required>

        <button type="submit">Atualizar Produto</button>
    </form>
</div>
</body>
</html>
