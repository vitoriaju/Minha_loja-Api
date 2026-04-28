<?php
require_once '../verifica_sessao.php';
require_once '../pdo.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/views/dashboard.php");
    exit;
}

if (!csrf_check($_POST['csrf_token'] ?? '')) {
    header("Location: " . BASE_URL . "/views/entrada_produtos.php");
    exit;
}

$pdo->beginTransaction();

try{

$numero_nota = $_POST['numero_nota'];
$fornecedor = $_POST['fornecedor'];

// cria entrada
$stmt = $pdo->prepare("
INSERT INTO entradas (numero_nota, fornecedor)
VALUES (?, ?)
");
$stmt->execute([$numero_nota, $fornecedor]);

$entrada_id = $pdo->lastInsertId();

foreach($_POST['produto_id'] as $i => $produto_id){

    $quantidade = $_POST['quantidade'][$i];
    $validade = $_POST['validade'][$i];
    $preco = $_POST['preco'][$i];

    // salva item
    $stmt = $pdo->prepare("
    INSERT INTO itens_entrada (entrada_id, produto_id, quantidade, validade, preco)
    VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$entrada_id, $produto_id, $quantidade, $validade, $preco]);

    // atualiza estoque e preço do produto
    $stmt = $pdo->prepare("
    UPDATE produtos 
    SET estoque = estoque + ?,
        validade = ?,
        preco = ?
    WHERE id = ?
    ");
    $stmt->execute([$quantidade, $validade, $preco, $produto_id]);

}


$pdo->commit();

header("Location: ../views/dashboard.php");

}catch(Exception $e){

    $pdo->rollBack();
    echo "Erro: " . $e->getMessage();
}
