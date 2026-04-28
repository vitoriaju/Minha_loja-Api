<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check($_POST['csrf_token'] ?? '')) {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

flash_set('sucesso', 'Voce saiu com sucesso.');

header("Location: " . BASE_URL . "/index.php");
exit;
