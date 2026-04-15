<?php
require_once __DIR__ . '/../pdo.php';

class Venda {

    public static function criarVenda($usuario_id, $itens) {
        global $pdo;

        try {
            $pdo->beginTransaction();

            $valor_total = 0;

            // Calcula total
            foreach ($itens as $item) {
                $stmt = $pdo->prepare("SELECT preco, estoque FROM produtos WHERE id = ?");
                $stmt->execute([$item['produto_id']]);
                $produto = $stmt->fetch();

                if (!$produto) {
                    throw new Exception("Produto não encontrado");
                }

                if ($produto['estoque'] < $item['quantidade']) {
                    throw new Exception("Estoque insuficiente");
                }

                $valor_total += $produto['preco'] * $item['quantidade'];
            }

            // Insere venda
            $stmt = $pdo->prepare("INSERT INTO vendas (usuario_id, valor_total) VALUES (?, ?)");
            $stmt->execute([$usuario_id, $valor_total]);
            $venda_id = $pdo->lastInsertId();

            // Insere itens e atualiza estoque
            foreach ($itens as $item) {

                $stmt = $pdo->prepare("SELECT preco, estoque FROM produtos WHERE id = ?");
                $stmt->execute([$item['produto_id']]);
                $produto = $stmt->fetch();

                $stmt = $pdo->prepare("INSERT INTO itens_venda (venda_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $venda_id,
                    $item['produto_id'],
                    $item['quantidade'],
                    $produto['preco']
                ]);

                $novoEstoque = $produto['estoque'] - $item['quantidade'];

                $stmt = $pdo->prepare("UPDATE produtos SET estoque = ? WHERE id = ?");
                $stmt->execute([$novoEstoque, $item['produto_id']]);
            }

            $pdo->commit();
            return true;

        } catch (Exception $e) {
            $pdo->rollBack();
            return $e->getMessage();
        }
    }
}