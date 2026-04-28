<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils.php';

$logado = !empty($_SESSION['usuario']);
$isAdmin = $logado && (($_SESSION['perfil'] ?? '') === 'admin');

$erro = flash_get('erro');
$sucesso = flash_get('sucesso');

if ($logado) {
    include __DIR__ . '/layout.php';
}
?>

<?php if (!$logado): ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Criar Conta</title>
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
.card {
    width: 360px;
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}
input, select, button {
    width: 100%;
    padding: 10px;
    margin-top: 8px;
    margin-bottom: 14px;
    border-radius: 8px;
    box-sizing: border-box;
}
input, select { border: 1px solid #7b4f27; }
button {
    background: #7b4f27;
    color: #fff;
    border: 0;
    cursor: pointer;
}
a { color: #7b4f27; }
</style>
</head>
<body>
<?php endif; ?>

<div class="card">

<h2>Criar Conta</h2>

<?php if ($erro): ?>
    <div style="margin-bottom:15px; padding:10px; background:#ffe6e6; color:#b00000; border-radius:8px;">
        <?= e($erro) ?>
    </div>
<?php endif; ?>

<?php if ($sucesso): ?>
    <div style="margin-bottom:15px; padding:10px; background:#e6ffe6; color:#007a00; border-radius:8px;">
        <?= e($sucesso) ?>
    </div>
<?php endif; ?>

<form action="../controllers/cadastrar_usuario.php" method="post">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <label>E-mail:</label>
    <input type="email" name="email" placeholder="Digite o e-mail" required>

    <label>Senha:</label>
    <input type="password" name="senha" placeholder="Digite a senha" required>

    <?php if ($isAdmin): ?>
        <label>Perfil:</label>
        <select name="perfil" required>
            <option value="user">Usuario</option>
            <option value="admin">Administrador</option>
        </select>
    <?php else: ?>
        <input type="hidden" name="perfil" value="user">
    <?php endif; ?>
    
    <button type="submit">Cadastrar</button>

</form>

<?php if (!$logado): ?>
    <p style="text-align:center;"><a href="../index.php">Voltar ao login</a></p>
<?php endif; ?>

</div>

<?php if ($logado): ?>
</div></div></div></body></html>
<?php else: ?>
</body>
</html>
<?php endif; ?>
