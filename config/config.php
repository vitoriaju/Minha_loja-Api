<?php
// Carrega valores do .env da raiz sem depender de bibliotecas externas.
function env_value(string $key, string $default = ''): string {
    static $env = null;

    if ($env === null) {
        $env = [];
        $envPath = __DIR__ . '/../.env';

        if (is_file($envPath)) {
            foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);

                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }

                [$name, $value] = explode('=', $line, 2);
                $env[trim($name)] = trim($value, " \t\n\r\0\x0B\"'");
            }
        }
    }

    return $env[$key] ?? getenv($key) ?: $default;
}

// Constantes usadas por todo o sistema para banco, URL base e e-mail.
define('DB_HOST', env_value('DB_HOST', '127.0.0.1'));
define('DB_PORT', env_value('DB_PORT', '3306'));
define('DB_NAME', env_value('DB_NAME', 'minha_loja2'));
define('DB_USER', env_value('DB_USER', 'root'));
define('DB_PASS', env_value('DB_PASS', ''));
define('BASE_URL', env_value('BASE_URL', 'http://localhost/Minha_loja-Api'));
define('MAIL_FROM_ADDRESS', env_value('MAIL_FROM_ADDRESS', 'no-reply@minhaloja.local'));
define('MAIL_FROM_NAME', env_value('MAIL_FROM_NAME', 'Minha Loja'));
define('SMTP_HOST', env_value('SMTP_HOST', ''));
define('SMTP_PORT', env_value('SMTP_PORT', '587'));
define('SMTP_USER', env_value('SMTP_USER', ''));
define('SMTP_PASS', env_value('SMTP_PASS', ''));
define('SMTP_ENCRYPTION', env_value('SMTP_ENCRYPTION', 'tls'));


$tempo_inatividade = 2 * 60 * 60; // 2 horas 

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
    // Conexao principal do sistema usando PDO.
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
