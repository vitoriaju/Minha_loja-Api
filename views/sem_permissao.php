<?php 
require_once __DIR__ . '/../config/config.php'; 
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Acesso negado</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8d7da;
            color: #721c24;
            padding: 40px;
        }
        .alert {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 5px;
            max-width: 400px;
        }
        a {
            display: inline-block;
            margin-top: 15px;
            text-decoration: none;
            color: #155724;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="alert">
        <h2>🚫 Acesso negado</h2>
        <p>Você não tem permissão para acessar esta página.</p>
        <p><a href="/Minha_loja2/views/dashboard.php">Voltar a Dashboard</a></p>
    </div>
</body>
</html>
