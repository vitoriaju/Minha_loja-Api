<?php
function flash_set($key, $msg) {
    if (!isset($_SESSION)) session_start();
    $_SESSION['flash'][$key] = $msg;
}

function flash_get($key) {
    if (!isset($_SESSION)) session_start();
    if (!empty($_SESSION['flash'][$key])) {
        $v = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $v;
    }
    return null;
}

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function csrf_token() {
    if (!isset($_SESSION)) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_check($token) {
    if (!isset($_SESSION)) session_start();
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function audit_log(PDO $pdo, string $acao, string $entidade, $entidadeId = null, array $detalhes = []) {
    $usuarioId = $_SESSION['usuario']['id'] ?? null;
    $json = $detalhes ? json_encode($detalhes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    $stmt = $pdo->prepare('INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$usuarioId, $acao, $entidade, $entidadeId, $json, $_SERVER['REMOTE_ADDR'] ?? null, substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255)]);
}

function log_exception(Throwable $erro, string $contexto = '') {
    error_log(($contexto !== '' ? $contexto . ': ' : '') . $erro->getMessage());
}
