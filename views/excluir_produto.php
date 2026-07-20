<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_admin('/index.php');
require_once __DIR__ . '/../pdo.php';
require_once __DIR__ . '/../models/Produto.php';

$produto = new Produto($pdo);


if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check($_POST['csrf_token'] ?? '')) {
    die("Requisicao invalida.");
}

if (isset($_POST['id']) && is_numeric($_POST['id'])) {
    $id = intval($_POST['id']);
    
   
    if ($produto->excluir($id)) {
        audit_log($pdo, 'excluir', 'produto', $id, ['origem' => 'formulario']);
        
        header("Location: listar_produtos_api.php");
        exit;
    } else {
        die("Erro ao tentar excluir o produto.");
    }
} else {
    die("ID do produto não informado ou inválido.");
}
?>
