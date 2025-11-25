<?php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'minha_loja');
define('DB_USER', 'root');
define('DB_PASS', '');


$tempo_inatividade = 600; 

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        'lifetime' => $tempo_inatividade,
        'httponly' => true,
        
    ]);

    session_start();

    if (isset($_SESSION['ultima_atividade'])) {
        if (time() - $_SESSION['ultima_atividade'] > $tempo_inatividade) {
           
            session_unset();
            session_destroy();
        }
    }
    $_SESSION['ultima_atividade'] = time(); 
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
