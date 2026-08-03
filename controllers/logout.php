<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check($_POST['csrf_token'] ?? '')) {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

audit_log_safe($pdo, 'logout', 'autenticacao', $_SESSION['usuario']['id'] ?? null);

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}

session_destroy();

flash_set('sucesso', 'Voce saiu com sucesso.');

header("Location: " . BASE_URL . "/index.php");
exit;
