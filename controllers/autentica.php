<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils.php';

unset($_SESSION['flash']);

$maxEmailAttempts = max(1, (int) env_value('LOGIN_MAX_ATTEMPTS_EMAIL', '3'));
$maxIpAttempts = max(1, (int) env_value('LOGIN_MAX_ATTEMPTS_IP', '10'));
$windowMinutes = max(1, (int) env_value('LOGIN_WINDOW_MINUTES', '15'));

function login_client_ip(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    $trustCloudflare = filter_var(
        env_value('TRUST_CLOUDFLARE_PROXY', 'false'),
        FILTER_VALIDATE_BOOLEAN
    );

    if ($trustCloudflare && !empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = trim((string) $_SERVER['HTTP_CF_CONNECTING_IP']);
    }

    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function login_attempt_counts(PDO $pdo, string $emailHash, string $ip, int $windowMinutes): array
{
    $stmt = $pdo->prepare(
        'SELECT
            SUM(email_hash = :email_hash) AS email_attempts,
            SUM(ip = :ip) AS ip_attempts
         FROM login_attempts
         WHERE attempted_at >= DATE_SUB(NOW(), INTERVAL :window_minutes MINUTE)
           AND (email_hash = :email_hash_filter OR ip = :ip_filter)'
    );
    $stmt->bindValue(':email_hash', $emailHash);
    $stmt->bindValue(':ip', $ip);
    $stmt->bindValue(':window_minutes', $windowMinutes, PDO::PARAM_INT);
    $stmt->bindValue(':email_hash_filter', $emailHash);
    $stmt->bindValue(':ip_filter', $ip);
    $stmt->execute();
    $counts = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'email' => (int) ($counts['email_attempts'] ?? 0),
        'ip' => (int) ($counts['ip_attempts'] ?? 0),
    ];
}

function record_login_failure(PDO $pdo, string $emailHash, string $ip): void
{
    $stmt = $pdo->prepare('INSERT INTO login_attempts (email_hash, ip) VALUES (?, ?)');
    $stmt->execute([$emailHash, $ip]);

    // Limpeza ocasional evita crescimento ilimitado sem acrescentar trabalho a cada requisicao.
    if (random_int(1, 100) === 1) {
        $pdo->exec('DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 7 DAY)');
    }
}

function reject_login(string $message): never
{
    flash_set('erro', $message);
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if (!csrf_check($_POST['csrf_token'] ?? '')) {
    reject_login('Sessao expirada. Tente novamente.');
}

$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$senha = (string) ($_POST['senha'] ?? '');

if ($email === '' || $senha === '') {
    reject_login('Preencha e-mail e senha.');
}

$emailHash = hash('sha256', $email);
$ip = login_client_ip();

try {
    $counts = login_attempt_counts($pdo, $emailHash, $ip, $windowMinutes);
} catch (Throwable $e) {
    error_log('Falha ao consultar limite de login: ' . $e->getMessage());
    reject_login('Erro interno. Verifique se a migration de tentativas de login foi importada.');
}

if ($counts['email'] >= $maxEmailAttempts || $counts['ip'] >= $maxIpAttempts) {
    audit_log_safe($pdo, 'login_bloqueado', 'autenticacao', null, ['email_hash' => $emailHash]);
    reject_login("Muitas tentativas. Tente novamente em ate {$windowMinutes} minuto(s).");
}

try {
    $stmt = $pdo->prepare(
        'SELECT id, email, senha_hash, perfil, email_verificado
         FROM usuarios WHERE email = ? LIMIT 1'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Falha ao consultar usuario no login: ' . $e->getMessage());
    reject_login('Erro interno. Tente novamente.');
}

$validPassword = $user && password_verify($senha, (string) ($user['senha_hash'] ?? ''));
$verifiedEmail = $user && (int) ($user['email_verificado'] ?? 0) === 1;

if (!$validPassword || !$verifiedEmail) {
    try {
        record_login_failure($pdo, $emailHash, $ip);
    } catch (Throwable $e) {
        error_log('Falha ao registrar tentativa de login: ' . $e->getMessage());
        reject_login('Erro interno. Tente novamente.');
    }

    $counts['email']++;
    $counts['ip']++;
    if ($counts['email'] >= $maxEmailAttempts || $counts['ip'] >= $maxIpAttempts) {
        audit_log_safe($pdo, 'login_bloqueado', 'autenticacao', $user['id'] ?? null, ['email_hash' => $emailHash]);
        reject_login("Muitas tentativas. Tente novamente em ate {$windowMinutes} minuto(s).");
    }

    audit_log_safe($pdo, 'login_falhou', 'autenticacao', $user['id'] ?? null, ['email_hash' => $emailHash]);
    reject_login('E-mail ou senha invalidos.');
}

// Uma autenticacao valida reinicia somente o contador da conta; o limite do IP
// permanece para conter ataques que alternam entre varios e-mails.
try {
    $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE email_hash = ?');
    $stmt->execute([$emailHash]);
} catch (Throwable $e) {
    error_log('Falha ao limpar tentativas de login: ' . $e->getMessage());
}

session_regenerate_id(true);
$_SESSION['usuario'] = ['id' => $user['id'], 'email' => $user['email']];
$_SESSION['perfil'] = $user['perfil'] ?? 'user';
audit_log_safe($pdo, 'login_sucesso', 'autenticacao', $user['id']);

flash_set('sucesso', 'Login efetuado com sucesso.');
header('Location: ' . BASE_URL . '/views/dashboard.php');
exit;
