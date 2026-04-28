<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils.php';

$erro = flash_get('erro');
$sucesso = flash_get('sucesso');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recuperar Senha</title>
<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5d0a9;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}
.container {
    width: 360px;
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}
h2 {
    color: #7b4f27;
    text-align: center;
}
input, button {
    width: 100%;
    padding: 10px;
    margin-top: 10px;
    border-radius: 8px;
    box-sizing: border-box;
}
input {
    border: 1px solid #7b4f27;
}
button {
    background: #7b4f27;
    color: #fff;
    border: 0;
    cursor: pointer;
}
a {
    color: #7b4f27;
}
.msg {
    margin: 12px 0;
    padding: 10px;
    border-radius: 8px;
}
.erro { background: #ffe6e6; color: #b00000; }
.sucesso { background: #e6ffe6; color: #007a00; }
</style>
</head>
<body>
<div class="container">
    <h2>Recuperar Senha</h2>

    <?php if ($erro): ?><div class="msg erro"><?= e($erro) ?></div><?php endif; ?>
    <?php if ($sucesso): ?><div class="msg sucesso"><?= e($sucesso) ?></div><?php endif; ?>

    <form action="../controllers/recuperar_senha.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>E-mail:</label>
        <input type="email" name="email" required>
        <button type="submit">Enviar link de redefinicao</button>
    </form>

    <p style="text-align:center;"><a href="../index.php">Voltar ao login</a></p>
</div>
</body>
</html>
