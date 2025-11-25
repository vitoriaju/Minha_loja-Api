<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils.php';


$erro = flash_get('erro');
$sucesso = flash_get('sucesso');
$info = flash_get('info');

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Minha Loja</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
<style>
body {
    margin: 0;
    font-family: 'Roboto', sans-serif;
    background: linear-gradient(to bottom, #fdf3e7, #f5d0a9);
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.container {
    width: 350px;
    background-color: rgba(255,255,255,0.95);
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    text-align: center;
}

h2 {
    font-size: 26px;
    color: #7b4f27;
    margin-bottom: 25px;
}

input[type="email"], input[type="password"] {
    width: 100%;
    padding: 12px;
    margin: 10px 0;
    border: 1px solid #7b4f27;
    border-radius: 10px;
    font-size: 15px;
    outline: none;
}

button {
    width: 100%;
    padding: 12px;
    background-color: #7b4f27;
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    cursor: pointer;
    margin-top: 15px;
    transition: background 0.3s;
}

button:hover {
    background-color: #a66d3a;
}

.links {
    margin-top: 20px;
}

.links a {
    display: block;
    margin: 8px 0;
    text-decoration: none;
    color: #7b4f27;
    font-weight: bold;
    transition: color 0.3s;
}

.links a:hover {
    color: #a66d3a;
}

.msg {
    margin-bottom: 15px;
    padding: 10px;
    border-radius: 8px;
    font-weight: bold;
}
.erro { background: #ffe6e6; color: #b00000; }
.sucesso { background: #e6ffe6; color: #007a00; }
.info { background: #eef6ff; color: #004a7c; }
</style>
</head>
<body>
<div class="container">
    <h2>Login</h2>

    <!-- Mensagens -->
    <?php if ($erro): ?><div class="msg erro"><?= e($erro) ?></div><?php endif; ?>
     <?php if ($sucesso): ?><div class="msg sucesso"><?= e($sucesso) ?></div><?php endif; ?>
    <?php if ($info): ?><div class="msg info"><?= e($info) ?></div><?php endif; ?>


    <!-- Formulário -->
    <form action="controllers/autentica.php" method="post" novalidate>
        <input type="email" id="email" name="email" placeholder="Digite seu e-mail" required>
        <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required>
        <button type="submit">Entrar</button>
    </form>

    <div class="links">
        <a href="views/recuperar.php">Esqueci minha senha</a>
        <a href="views/cadastrar.php">Criar conta</a>
    </div>
</div>
</body>
</html>
