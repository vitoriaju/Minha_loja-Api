<?php
// Inicia a sessão somente se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils.php';

// Tempo máximo de inatividade em segundos (ex: 15 minutos)
define('SESSION_TIMEOUT', 10 * 60);

// Verifica se o usuário está logado
if (empty($_SESSION['usuario'])) {
    flash_set('info', 'Por favor faça login para acessar essa página.');
    header('Location: /Minha_loja2/index.php');
    exit;
}
//verefica admin :
function require_admin(string $redirect = '/index.php') {
    if (!isset($_SESSION['perfil']) || $_SESSION['perfil'] !== 'admin') {
        header('Location: /Minha_loja2/views/sem_permissao.php');
        exit;
    }
}

// Expiração de sessão por tempo de inatividade
if (isset($_SESSION['ultimo_acesso'])) {
    $tempo_inativo = time() - $_SESSION['ultimo_acesso'];
    if ($tempo_inativo > SESSION_TIMEOUT) {
        // Define mensagem antes de limpar sessão
        flash_set('info', 'Sua sessão expirou. Faça login novamente.');

        // Limpa apenas os dados do usuário, mas mantém a sessão ativa para a flash message
        unset($_SESSION['usuario']);
        unset($_SESSION['perfil']);
        unset($_SESSION['ultimo_acesso']);

        header('Location: /Minha_loja2/index.php');
        exit;
    }
}
// Atualiza o último acesso
$_SESSION['ultimo_acesso'] = time();

// Verificação de perfil (se a página exigir)
if (isset($required_perfil) && is_string($required_perfil)) {
    if (empty($_SESSION['perfil']) || $_SESSION['perfil'] !== $required_perfil) {
        header('Location: /Minha_loja2/views/sem_permissao.php');
        exit;
    }
}
