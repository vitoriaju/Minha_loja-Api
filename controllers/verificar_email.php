<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils.php';

$token = (string)($_GET['token'] ?? '');

if ($token === '') {
    flash_set('erro', 'Link de confirmacao invalido.');
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

$tokenHash = hash('sha256', $token);

try {
    $pdo->beginTransaction();

    // Token precisa existir, nao ter sido usado e ainda estar dentro da validade.
    $stmt = $pdo->prepare("
        SELECT id, usuario_id
        FROM email_verifications
        WHERE token_hash = ?
          AND used_at IS NULL
          AND expires_at >= NOW()
        LIMIT 1
    ");
    $stmt->execute([$tokenHash]);
    $verification = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$verification) {
        $pdo->rollBack();
        flash_set('erro', 'Link de confirmacao invalido ou expirado.');
        header("Location: " . BASE_URL . "/index.php");
        exit;
    }

    $stmt = $pdo->prepare("UPDATE usuarios SET email_verificado = 1 WHERE id = ?");
    $stmt->execute([$verification['usuario_id']]);

    $stmt = $pdo->prepare("UPDATE email_verifications SET used_at = NOW() WHERE id = ?");
    $stmt->execute([$verification['id']]);

    $pdo->commit();

    flash_set('sucesso', 'E-mail confirmado com sucesso. Agora voce ja pode fazer login.');
    header("Location: " . BASE_URL . "/index.php");
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    flash_set('erro', 'Erro ao confirmar e-mail.');
    header("Location: " . BASE_URL . "/index.php");
    exit;
}
