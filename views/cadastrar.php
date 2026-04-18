<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_admin('/minha_loja/views/sem_permissao.php');
require_once __DIR__ . '/../config/conexao.php';

$erro = flash_get('erro');
$sucesso = flash_get('sucesso');

// CHAMA LAYOUT
include __DIR__ . '/layout.php';
?>

<div class="card">

<h2>👤 Criar Conta</h2>

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

    <label>E-mail:</label>
    <input type="email" name="email" placeholder="Digite o e-mail" required>

    <label>Senha:</label>
    <input type="password" name="senha" placeholder="Digite a senha" required>

    <label>Perfil:</label>
    <select name="perfil" required>
        <option value="user">Usuário</option>
        <option value="admin">Administrador</option>
    </select>
    
    <button type="submit">Cadastrar</button>

</form>

</div>

</div></div></div></body></html>