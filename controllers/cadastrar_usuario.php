<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_admin();

require_once __DIR__ . '/../config/mailer.php';
require_once __DIR__ . '/../utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/cadastrar.php');
    exit;
}

if (!csrf_check($_POST['csrf_token'] ?? '')) {
    flash_set('erro', 'Sessao expirada. Tente novamente.');
    header('Location: ../views/cadastrar.php');
    exit;
}

$email = strtolower(trim($_POST['email'] ?? ''));
$senha = trim($_POST['senha'] ?? '');
$perfil = trim($_POST['perfil'] ?? 'user');

if (!$email || !$senha || !in_array($perfil, ['user', 'admin'], true)) {
    flash_set('erro', 'Preencha todos os campos corretamente.');
    header('Location: ../views/cadastrar.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash_set('erro', 'Informe um e-mail valido.');
    header('Location: ../views/cadastrar.php');
    exit;
}

$dominio = substr(strrchr($email, "@"), 1);
if (!$dominio || (!checkdnsrr($dominio, 'MX') && !checkdnsrr($dominio, 'A'))) {
    flash_set('erro', 'Informe um e-mail com dominio existente.');
    header('Location: ../views/cadastrar.php');
    exit;
}

if (!mailer_configurado()) {
    flash_set('erro', mailer_erro_configuracao());
    header('Location: ../views/cadastrar.php');
    exit;
}

$hash = password_hash($senha, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();

    // Cria usuario bloqueado ate confirmar o e-mail.
    $stmt = $pdo->prepare("
        INSERT INTO usuarios (email, senha_hash, perfil, email_verificado)
        VALUES (?, ?, ?, 0)
    ");
    $stmt->execute([$email, $hash, $perfil]);

    $usuarioId = $pdo->lastInsertId();
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);

    // Salva somente o hash do token; o token real vai no link enviado ao e-mail.
    $stmt = $pdo->prepare("
        INSERT INTO email_verifications (usuario_id, token_hash, expires_at)
        VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))
    ");
    $stmt->execute([$usuarioId, $tokenHash]);

    $verifyLink = BASE_URL . "/controllers/verificar_email.php?token=" . urlencode($token);
    $assunto = 'Confirme seu e-mail - Minha Loja';
    $mensagem = "Ola,\n\nConfirme seu e-mail acessando o link abaixo:\n\n{$verifyLink}\n\nEste link expira em 24 horas.";
    $mensagemHtml = "
        <p>Ola,</p>
        <p>Confirme seu e-mail clicando no link abaixo:</p>
        <p><a href=\"{$verifyLink}\">Confirmar e-mail</a></p>
        <p>Este link expira em 24 horas.</p>
    ";

    if (!enviar_email($email, $assunto, $mensagem, $mensagemHtml)) {
        throw new RuntimeException('Falha ao enviar e-mail de confirmacao.');
    }

    audit_log($pdo, 'criar', 'usuario', $usuarioId, [
        'email' => $email,
        'perfil' => $perfil,
    ]);

    $pdo->commit();

    flash_set('sucesso', 'Conta criada. Enviamos um link de confirmacao para seu e-mail.');
    header('Location: ../views/cadastrar.php');
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($e->getCode() === "23000") {
        flash_set('erro', 'Este e-mail ja esta cadastrado.');
    } else {
        flash_set('erro', 'Erro ao criar conta. Verifique se as migrations foram importadas.');
    }

    header('Location: ../views/cadastrar.php');
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    flash_set('erro', 'Nao foi possivel enviar o e-mail. Verifique as configuracoes SMTP no .env.');
    header('Location: ../views/cadastrar.php');
    exit;
}
