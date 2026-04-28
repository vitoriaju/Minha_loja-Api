<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils.php';

$token = $_GET['token'] ?? '';
$erro = flash_get('erro');
$tokenValido = false;

if ($token !== '') {
    $tokenHash = hash('sha256', $token);
    $stmt = $pdo->prepare("
        SELECT id
        FROM password_resets
        WHERE token_hash = ?
          AND used_at IS NULL
          AND expires_at >= NOW()
        LIMIT 1
    ");
    $stmt->execute([$tokenHash]);
    $tokenValido = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
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
    width: 340px;
}
h2 { color: #7b4f27; }
input {
    width: 100%;
    padding: 10px;
    margin: 8px 0;
    border: 1px solid #7b4f27;
    border-radius: 8px;
    box-sizing: border-box;
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
a {
    display: block;
    margin-top: 15px;
    color: #7b4f27;
    text-decoration: none;
}
.mensagem {
    margin-bottom: 15px;
    padding: 10px;
    border-radius: 8px;
    background: #ffe6e6;
    color: #b00000;
}
</style>
</head>
<body>
<div class="container">
    <h2>Criar Nova Senha</h2>

    <?php if ($erro): ?><div class="mensagem"><?= e($erro) ?></div><?php endif; ?>

    <?php if ($tokenValido): ?>
        <form action="../controllers/atualiza_senha.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <input type="password" name="senha" placeholder="Digite a nova senha" required>
            <input type="password" name="senha_confirma" placeholder="Confirme a nova senha" required>
            <button type="submit">Atualizar Senha</button>
        </form>
    <?php else: ?>
        <div class="mensagem">Link invalido, expirado ou ausente.</div>
        <a href="recuperar.php">Solicitar novo link</a>
    <?php endif; ?>

    <a href="../index.php">Voltar ao Login</a>
</div>
</body>
</html>
