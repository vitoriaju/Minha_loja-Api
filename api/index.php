<?php
header("Content-Type: application/json; charset=UTF-8");

// Descobre qual rota foi chamada:
// Exemplo:  /api/index.php?rota=produtos
$rota = $_GET['rota'] ?? '';

switch ($rota) {

    case 'produtos':
        require 'produtos.php';
        break;

    default:
        http_response_code(404);
        echo json_encode(["erro" => "Rota não encontrada"]);
        break;
}
