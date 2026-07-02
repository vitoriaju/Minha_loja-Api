<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_admin();
require_once __DIR__ . '/../pdo.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/views/dashboard.php");
    exit;
}

if (!csrf_check($_POST['csrf_token'] ?? '')) {
    header("Location: " . BASE_URL . "/views/producao_dia.php");
    exit;
}

try {

    if (
        !isset($_POST['produto_id']) ||
        !isset($_POST['quantidade']) ||
        !isset($_POST['produto_novo'])
    ) {
        throw new Exception("Dados da produção não enviados corretamente.");
    }

    $pdo->beginTransaction();

    $produtos = $_POST['produto_id'];
    $quantidades = $_POST['quantidade'];
    $produtosNovos = $_POST['produto_novo'];

    $itensValidos = 0;

    // Cria produção
    $stmt = $pdo->prepare("INSERT INTO producao (data) VALUES (CURDATE())");
    $stmt->execute();

    $producao_id = $pdo->lastInsertId();

    foreach ($produtos as $i => $produto_id) {

        $produto_id = trim($produto_id ?? '');
        $produto_novo = trim($produtosNovos[$i] ?? '');
        $quantidade = str_replace(',', '.', $quantidades[$i] ?? 0);
        $quantidade = floatval($quantidade);

        if ($quantidade <= 0) {
            continue;
        }

        // Caso seja produto novo
        if (empty($produto_id) && !empty($produto_novo)) {

            $stmt = $pdo->prepare("SELECT id FROM produtos WHERE nome = ?");
            $stmt->execute([$produto_novo]);

            $existe = $stmt->fetch();

            if ($existe) {
                $produto_id = $existe['id'];
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO produtos 
                    (nome, categoria, preco, unidade_medida, estoque, estoque_minimo, criado_em)
                    VALUES (?, 'Padaria', 0.00, 'unidade', 0.000, 5.000, NOW())
                ");
                $stmt->execute([$produto_novo]);

                $produto_id = $pdo->lastInsertId();
            }
        }

        if (empty($produto_id)) {
            continue;
        }

        // Salva item produzido
        $stmt = $pdo->prepare("
            INSERT INTO itens_producao 
            (producao_id, produto_id, quantidade)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$producao_id, $produto_id, $quantidade]);

        // Atualiza estoque do produto produzido
        $stmt = $pdo->prepare("
            UPDATE produtos
            SET estoque = estoque + ?
            WHERE id = ?
        ");
        $stmt->execute([$quantidade, $produto_id]);

        $itensValidos++;
    }

    if ($itensValidos === 0) {
        throw new Exception("Nenhum produto válido foi informado para produção.");
    }

    $pdo->commit();

    header("Location: ../views/imprimir_producao.php?id=" . $producao_id);
    exit;

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "Erro na produção: " . $e->getMessage();
}
