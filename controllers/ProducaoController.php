<?php
require_once __DIR__ . '/../pdo.php';

$pdo->beginTransaction();

try{

    // cria produção
    $stmt = $pdo->prepare("INSERT INTO producao (data) VALUES (CURDATE())");
    $stmt->execute();

    $producao_id = $pdo->lastInsertId();

    foreach($_POST['produto_id'] as $i => $produto_id){

        $quantidade = $_POST['quantidade'][$i];
        $produto_novo = trim($_POST['produto_novo'][$i]);

        //  NOVO PRODUTO
        if(empty($produto_id) && !empty($produto_novo)){

            // verifica se já existe
            $stmt = $pdo->prepare("SELECT id FROM produtos WHERE nome = ?");
            $stmt->execute([$produto_novo]);

            $existe = $stmt->fetch();

            if($existe){
                $produto_id = $existe['id'];
            } else {

                $stmt = $pdo->prepare("
                INSERT INTO produtos (nome, categoria)
                VALUES (?, 'Padaria')
                ");
                $stmt->execute([$produto_novo]);

                $produto_id = $pdo->lastInsertId();
            }
        }

            
        if(!empty($produto_id) && $quantidade > 0){

            $stmt = $pdo->prepare("
            INSERT INTO itens_producao (producao_id, produto_id, quantidade)
            VALUES (?, ?, ?)
            ");

            $stmt->execute([$producao_id, $produto_id, $quantidade]);
        }
    }

    $pdo->commit();

    header("Location: ../views/imprimir_producao.php?id=" . $producao_id);
    exit;

}catch(Exception $e){

    $pdo->rollBack();
    echo "Erro: " . $e->getMessage();
}