<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
unset($_SESSION['flash']);

$MAX_ATTEMPTS = 3;
$LOCK_MINUTES = 1;

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_last_try'] = null;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /Minha_loja2/index.php');
    exit;
}

$email = trim((string)($_POST['email'] ?? ''));
$senha = (string)($_POST['senha'] ?? '');

if ($email === '' || $senha === '') {
    flash_set('erro', 'Preencha e-mail e senha.');
    header('Location: /Minha_loja2/index.php');
    exit;
}


if ($_SESSION['login_attempts'] >= $MAX_ATTEMPTS) {
    $last = $_SESSION['login_last_try'];
    if ($last && (time() - $last) < ($LOCK_MINUTES * 60)) {
        flash_set('erro', "Muitas tentativas. Tente novamente em {$LOCK_MINUTES} minuto(s).");
        header('Location: /Minha_loja2/index.php');
        exit;
    } else {
        
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_last_try'] = null;
    }
}

try {
    $stmt = $pdo->prepare('SELECT id, email, senha_hash, perfil FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    
    flash_set('erro', 'Erro interno. Tente novamente.');
    header('Location: /Minha_loja2/index.php');
    exit;
}

if (!$user) {
   
    $_SESSION['login_attempts']++;
    $_SESSION['login_last_try'] = time();
    flash_set('erro', 'E-mail ou senha inválidos.');
    header('Location: /Minha_loja2/index.php');
    exit;
}


$stored = $user['senha_hash'] ?? '';

if (password_verify($senha, $stored)) {
    
} else {
    
    $looks_like_hash = false;
    if (is_string($stored) && strlen($stored) >= 60 && (strpos($stored, '$2y$') === 0 || strpos($stored, '$argon') === 0)) {
        $looks_like_hash = true;
    }

    if (!$looks_like_hash && $stored === $senha) {
        
        $newHash = password_hash($senha, PASSWORD_DEFAULT);
        try {
            $u = $pdo->prepare('UPDATE usuarios SET senha_hash = ? WHERE id = ?');
            $u->execute([$newHash, $user['id']]);
           
            $stored = $newHash;
            
        } catch (Exception $e) {
            
            $_SESSION['login_attempts']++;
            $_SESSION['login_last_try'] = time();
            flash_set('erro', 'Erro interno ao atualizar senha. Tente novamente.');
            header('Location: /Minha_loja2/index.php');
            exit;
        }
    } else {
        
        $_SESSION['login_attempts']++;
        $_SESSION['login_last_try'] = time();
        $remaining = max(0, $MAX_ATTEMPTS - $_SESSION['login_attempts']);
        flash_set('erro', "E-mail ou senha inválidos. Tentativas restantes: $remaining");
        header('Location: /Minha_loja2/index.php');
        exit;
    }

    
    if (!password_verify($senha, $stored)) {
        
        $_SESSION['login_attempts']++;
        $_SESSION['login_last_try'] = time();
        flash_set('erro', 'E-mail ou senha inválidos.');
        header('Location: /Minha_loja2/index.php');
        exit;
    }
}


$_SESSION['login_attempts'] = 0;
$_SESSION['login_last_try'] = null;

session_regenerate_id(true);
$_SESSION['usuario'] = ['id' => $user['id'], 'email' => $user['email']];
$_SESSION['perfil'] = $user['perfil'] ?? 'user';

flash_set('sucesso', 'Login efetuado com sucesso.');
header('Location: /Minha_loja2/views/dashboard.php');
exit;
