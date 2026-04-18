<?php
require_once __DIR__ . '/../verifica_sessao.php'; 
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils.php';

 require_admin('../views/acesso_negado.php');

$erro = flash_get('erro');
$sucesso = flash_get('sucesso');

// layout
include __DIR__ . '/layout.php';
?>

<div class="card">

<h2> Redefinir Senha</h2>

<br>

<?php if ($erro): ?>
    <div class="msg erro"><?= e($erro) ?></div>
<?php endif; ?>

<?php if ($sucesso): ?>
    <div class="msg sucesso"><?= e($sucesso) ?></div>
<?php endif; ?>

<form action="../controllers/recuperar_senha.php" method="post">

<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">

<div>
<label>E-mail:</label>
<input type="email" name="email" required>
</div>
<br>
<div>
<label>Nova senha:</label>
<input type="password" name="nova_senha" required>
</div>

</div>

<br>

<div>
<label>Confirmar senha:</label>
<input type="password" name="confirmar_senha" required>
</div>

<br>

<button type="submit">Atualizar Senha</button>

</form>

</div>

</div></div></div></body></html>