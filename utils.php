<?php
require_once __DIR__ . '/config/config.php';

function flash_set($key, $msg) {
    $_SESSION['flash'][$key] = $msg;
}

function flash_get($key) {
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
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_check($token) {
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function audit_log(PDO $pdo, string $acao, string $entidade, $entidadeId = null, array $detalhes = []): int {
    $usuarioId = $_SESSION['usuario']['id'] ?? null;
    $json = $detalhes ? json_encode($detalhes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    $stmt = $pdo->prepare('INSERT INTO auditoria (usuario_id, acao, entidade, entidade_id, detalhes, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$usuarioId, $acao, $entidade, $entidadeId, $json, $_SERVER['REMOTE_ADDR'] ?? null, substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255)]);
    $auditId = (int) $pdo->lastInsertId();

    // Politica padrao: remove registros que ultrapassaram o periodo de retencao.
    $retentionDays = max(30, (int) env_value('AUDIT_RETENTION_DAYS', '365'));
    $cleanup = $pdo->prepare('DELETE FROM auditoria WHERE criado_em < DATE_SUB(NOW(), INTERVAL ? DAY)');
    $cleanup->execute([$retentionDays]);

    return $auditId;
}

function audit_log_safe(PDO $pdo, string $acao, string $entidade, $entidadeId = null, array $detalhes = []): void {
    try {
        audit_log($pdo, $acao, $entidade, $entidadeId, $detalhes);
    } catch (Throwable $e) {
        log_exception($e, 'Falha ao registrar auditoria');
    }
}

function log_exception(Throwable $erro, string $contexto = '') {
    error_log(($contexto !== '' ? $contexto . ': ' : '') . $erro->getMessage());
}
