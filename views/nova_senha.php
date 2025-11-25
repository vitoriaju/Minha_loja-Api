<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$email = $_GET['email'] ?? '';
if (empty($email)) {
    
    header("Location: recuperar.php?erro=E-mail inválido");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Criar Nova Senha</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f5d0a9;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}
.container {
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    text-align: center;
    width: 320px;
}
h2 {
    color: #7b4f27;
    margin-bottom: 20px;
}
input {
    width: 100%;
    padding: 10px;
    margin: 8px 0;
    border: 1px solid #7b4f27;
    border-radius: 8px;
}
button {
    background: #7b4f27;
    color: white;
    border: none;
    padding: 10px;
    width: 100%;
    border-radius: 8px;
    cursor: pointer;
    margin-top: 10px;
}
button:hover {
    background: #a66d3a;
}
a {
    display: block;
    margin-top: 15px;
    color: #7b4f27;
    text-decoration: none;
}
a:hover {
    color: #a66d3a;
}
.mensagem {
    margin-bottom: 15px;
    color: #e3342f;
}
</style>
</head>
<body>
<div class="container">
    <h2>Criar Nova Senha</h2>

    <form action="../controllers/atualiza_senha.php" method="post">
        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
        <input type="password" name="senha" placeholder="Digite a nova senha" required>
        <input type="password" name="senha_confirma" placeholder="Confirme a nova senha" required>
        <button type="submit">Atualizar Senha</button>
    </form>

    <a href="login.php">Voltar ao Login</a>
</div>
</body>
</html>
