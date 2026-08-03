<?php
// Inicia a sessão somente se ainda não estiver ativa
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils.php';

// Tempo máximo de inatividade em segundos
define('SESSION_TIMEOUT', 2 * 60 * 60); // 2 horas

// Verifica se o usuário está logado
if (empty($_SESSION['usuario'])) {
    flash_set('info', 'Por favor faça login para acessar essa página.');
   header("Location: " . BASE_URL . "/index.php");;
    exit;
}
function perfil_usuario_atual(): string {
    $perfilDireto = $_SESSION['perfil'] ?? null;
    $perfilNoUsuario = $_SESSION['usuario']['perfil'] ?? null;
    $perfil = in_array($perfilDireto, ['admin', 'user'], true)
        ? $perfilDireto
        : (in_array($perfilNoUsuario, ['admin', 'user'], true) ? $perfilNoUsuario : 'user');

    // Mantem os dois formatos de sessao sincronizados para codigo legado e novo.
    $_SESSION['perfil'] = $perfil;
    $_SESSION['usuario']['perfil'] = $perfil;

    return $perfil;
}

function usuario_eh_admin(): bool {
    return perfil_usuario_atual() === 'admin';
}

function usuario_pode(string $permissao): bool {
    static $permissoes = [
        'dashboard' => ['admin', 'user'],
        'vendas.criar' => ['admin', 'user'],
        'vendas.dia' => ['admin', 'user'],
        'produtos.ver' => ['admin', 'user'],
        'produtos.validade' => ['admin', 'user'],
        'administracao' => ['admin'],
        'vendas.historico' => ['admin'],
        'produtos.gerenciar' => ['admin'],
        'estoque.gerenciar' => ['admin'],
        'producao' => ['admin'],
        'financeiro' => ['admin'],
    ];

    return in_array(perfil_usuario_atual(), $permissoes[$permissao] ?? [], true);
}

function require_permission(string $permissao): void {
    if (!usuario_pode($permissao)) {
        header("Location: " . BASE_URL . "/views/sem_permissao.php");
        exit;
    }
}

function require_admin(string $redirect = '/index.php'): void {
    require_permission('administracao');
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

        header("Location: " . BASE_URL . "/index.php");;
        exit;
    }
}
// Atualiza o último acesso
$_SESSION['ultimo_acesso'] = time();

// Verificação de perfil (se a página exigir)
if (isset($required_perfil) && is_string($required_perfil)) {
    if (perfil_usuario_atual() !== $required_perfil) {
        header("Location: " . BASE_URL . "/views/sem_permissao.php");
        exit;
    }
}
