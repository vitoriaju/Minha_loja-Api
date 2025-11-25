<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../models/Produto.php';

$produto = new Produto($conn);
$produtos = $produto->listarVencidos();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Produtos Vencidos</title>
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

th, td {
    padding: 10px;
    text-align: left;
}

th {
    background-color: #7b4f27;
    color: #fff;
}

tr:nth-child(even) {
    background-color: rgba(123,79,39,0.1);
}

.mensagem {
    text-align: center;
    margin-bottom: 20px;
    font-weight: bold;
    color: #7b4f27;
}

a.button {
    display: inline-block;
    padding: 12px 20px;
    margin: 10px 5px;
    background-color: #7b4f27;
    color: #fff;
    text-decoration: none;
    border-radius: 12px;
    font-size: 16px;
    transition: background 0.3s;
}

a.button:hover {
    background-color: #a66d3a;
}

a.excluir {
    background-color: #e3342f;
}

a.excluir:hover {
    background-color: #c22a26;
}

@media (max-width: 780px) {
    .container {
        width: 95%;
    }
    table {
        font-size: 14px;
    }
}
</style>
</head>
<body>
<div class="container">
    <h2>Produtos Vencidos</h2>

    <?php if (!empty($produtos)): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Preço</th>
                    <th>Qualidade</th>
                    <th>Categoria</th>
                    <th>Validade</th>
                    <th>Estoque</th>
                    <th>Ações</th> <!-- Nova coluna -->
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produtos as $row): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['nome']; ?></td>
                        <td>R$ <?php echo number_format($row['preco'], 2, ',', '.'); ?></td>
                        <td><?php echo $row['qualidade']; ?></td>
                        <td><?php echo $row['categoria']; ?></td>
                        <td><?php echo $row['validade']; ?></td>
                        <td><?php echo $row['estoque']; ?></td>
                        <td>
                            <a href="excluir_produto.php?id=<?php echo $row['id']; ?>" class="button excluir" onclick="return confirm('Deseja realmente excluir este produto?');">🗑️ Excluir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="mensagem">Nenhum produto vencido no momento.</div>
    <?php endif; ?>

    <a href="../views/dashboard.php" class="button">Voltar</a>
</div>
</body>
</html>
