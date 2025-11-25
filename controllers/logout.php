<?php
require_once __DIR__ . '/../utils.php';

// Sempre inicia a sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpa todas as variáveis de sessão
$_SESSION = [];

// Remove cookie da sessão se existir
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

// Destrói a sessão
session_destroy();

// Mensagem de saída
flash_set('success', 'Você saiu com sucesso.');

// Redireciona para a tela inicial (login)
header("Location: /Minha_loja2/index.php");
exit;
