<?php

require_once __DIR__ . '/../verifica_sessao.php';
require_admin('/index.php');
require_once __DIR__ . '/../config/conexao.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $preco = $_POST['preco'];
    $qualidade = $_POST['qualidade'];
    $categoria = $_POST['categoria'];
    $validade = $_POST['validade'];
    $estoque = $_POST['estoque'];

    $sql = "INSERT INTO produtos (nome, preco, qualidade, categoria, validade, estoque, criado_em)
            VALUES ('$nome', '$preco', '$qualidade', '$categoria', '$validade', '$estoque', NOW())";

    if ($conn->query($sql) === TRUE) {
        $mensagem = "Produto cadastrado com sucesso!";
    } else {
        $mensagem = "Erro: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cadastro de Produto</title>
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

    .mensagem {
        text-align: center;
        margin-top: 15px;
        font-weight: bold;
        color: #7b4f27;
    }
</style>
</head>
<body>
<div class="container">
    <a href="../views/dashboard.php" class="voltar">← Voltar</a>
    <h2>Cadastro de Produto</h2>
    <?php if(isset($mensagem)) { echo "<div class='mensagem'>$mensagem</div>"; } ?>
    <form method="post" action="">
        <label>Nome:</label>
        <input type="text" name="nome" required>

        <label>Preço:</label>
        <input type="number" step="0.01" name="preco" required>

        <label>Qualidade:</label>
        <input type="text" name="qualidade">

        <label>Categoria:</label>
        <input type="text" name="categoria">

        <label>Validade:</label>
        <input type="date" name="validade">

        <label>Estoque:</label>
        <input type="number" name="estoque" required>

        <button type="submit">Cadastrar Produto</button>
    </form>
</div>
</body>
</html>

