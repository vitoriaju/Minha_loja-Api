<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

if (!csrf_check($_POST['csrf_token'] ?? '')) {
    flash_set('erro', 'Sessao expirada. Tente novamente.');
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

$token = (string)($_POST['token'] ?? '');
$senha = (string)($_POST['senha'] ?? '');
$senhaConfirma = (string)($_POST['senha_confirma'] ?? '');

if ($token === '' || $senha === '' || $senhaConfirma === '') {
    flash_set('erro', 'Preencha todos os campos.');
    header("Location: " . BASE_URL . "/views/nova_senha.php?token=" . urlencode($token));
    exit;
}

if ($senha !== $senhaConfirma) {
    flash_set('erro', 'Senhas nao conferem.');
    header("Location: " . BASE_URL . "/views/nova_senha.php?token=" . urlencode($token));
    exit;
}

if (strlen($senha) < 6) {
    flash_set('erro', 'A senha deve ter pelo menos 6 caracteres.');
    header("Location: " . BASE_URL . "/views/nova_senha.php?token=" . urlencode($token));
    exit;
}

$tokenHash = hash('sha256', $token);

try {
    $pdo->beginTransaction();

    // Confere se o token existe, nao expirou e ainda nao foi usado.
    $stmt = $pdo->prepare("
        SELECT id, usuario_id
        FROM password_resets
        WHERE token_hash = ?
          AND used_at IS NULL
          AND expires_at >= NOW()
        LIMIT 1
    ");
    $stmt->execute([$tokenHash]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reset) {
        $pdo->rollBack();
        flash_set('erro', 'Link invalido ou expirado.');
        header("Location: " . BASE_URL . "/views/recuperar.php");
        exit;
    }

    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    // Login usa sempre senha_hash.
    $stmt = $pdo->prepare("UPDATE usuarios SET senha_hash = ? WHERE id = ?");
    $stmt->execute([$senhaHash, $reset['usuario_id']]);

    $stmt = $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = ?");
    $stmt->execute([$reset['id']]);

    $pdo->commit();

    flash_set('sucesso', 'Senha atualizada com sucesso. Faca login novamente.');
    header("Location: " . BASE_URL . "/index.php");
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    flash_set('erro', 'Erro ao atualizar senha.');
    header("Location: " . BASE_URL . "/views/nova_senha.php?token=" . urlencode($token));
    exit;
}
