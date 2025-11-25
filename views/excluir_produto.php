<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_admin('/index.php');
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../models/Produto.php';

$produto = new Produto($conn);


if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    
   
    if ($produto->excluir($id)) {
        
        header("Location: listar_produtos.php");
        exit;
    } else {
        die("Erro ao tentar excluir o produto.");
    }
} else {
    die("ID do produto não informado ou inválido.");
}
?>
