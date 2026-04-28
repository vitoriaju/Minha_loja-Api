<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/mailer.php';
require_once __DIR__ . '/../utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/views/recuperar.php");
    exit;
}

if (!csrf_check($_POST['csrf_token'] ?? '')) {
    flash_set('erro', 'Sessao expirada. Tente novamente.');
    header("Location: " . BASE_URL . "/views/recuperar.php");
    exit;
}

$email = trim((string)($_POST['email'] ?? ''));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash_set('erro', 'Informe um e-mail valido.');
    header("Location: " . BASE_URL . "/views/recuperar.php");
    exit;
}

if (!mailer_configurado()) {
    flash_set('erro', mailer_erro_configuracao());
    header("Location: " . BASE_URL . "/views/recuperar.php");
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuario) {
    try {
        $pdo->beginTransaction();

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        // Invalida pedidos anteriores ainda nao usados para este usuario.
        $pdo->prepare("DELETE FROM password_resets WHERE usuario_id = ? AND used_at IS NULL")
            ->execute([$usuario['id']]);

        $stmt = $pdo->prepare("
            INSERT INTO password_resets (usuario_id, token_hash, expires_at)
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))
        ");
        $stmt->execute([$usuario['id'], $tokenHash]);

        $resetLink = BASE_URL . "/views/nova_senha.php?token=" . urlencode($token);
        $assunto = 'Redefinicao de senha - Minha Loja';
        $mensagem = "Ola,\n\nRedefina sua senha acessando o link abaixo:\n\n{$resetLink}\n\nEste link expira em 1 hora.";
        $mensagemHtml = "
            <p>Ola,</p>
            <p>Redefina sua senha clicando no link abaixo:</p>
            <p><a href=\"{$resetLink}\">Criar nova senha</a></p>
            <p>Este link expira em 1 hora.</p>
        ";

        if (!enviar_email($email, $assunto, $mensagem, $mensagemHtml)) {
            throw new RuntimeException('Falha ao enviar e-mail de recuperacao.');
        }

        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        flash_set('erro', 'Nao foi possivel enviar o e-mail. Verifique as configuracoes SMTP no .env.');
        header("Location: " . BASE_URL . "/views/recuperar.php");
        exit;
    }
}

flash_set('sucesso', 'Se o e-mail existir, enviaremos um link de redefinicao.');
header("Location: " . BASE_URL . "/views/recuperar.php");
exit;
