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

                $stmt = $pdo->prepare("
                    UPDATE produtos
                    SET estoque = estoque - ?
                    WHERE id = ? AND estoque >= ?
                ");
                $stmt->execute([
                    $item['quantidade'],
                    $item['produto_id'],
                    $item['quantidade']
                ]);

                if ($stmt->rowCount() !== 1) {
                    throw new Exception("Estoque insuficiente");
                }

                self::consumirLotesFefo((int) $item['produto_id'], (float) $item['quantidade']);
            }

            $pdo->commit();
            return true;

        } catch (Exception $e) {
            $pdo->rollBack();
            return $e->getMessage();
        }
    }

    private static function consumirLotesFefo(int $produtoId, float $quantidade): void {
        global $pdo;

        $stmt = $pdo->prepare("
            SELECT id, quantidade_restante
            FROM lotes_estoque
            WHERE produto_id = ? AND quantidade_restante > 0
            ORDER BY validade IS NULL, validade ASC, id ASC
            FOR UPDATE
        ");
        $stmt->execute([$produtoId]);
        $restante = $quantidade;
        $baixa = $pdo->prepare("
            UPDATE lotes_estoque
            SET quantidade_restante = quantidade_restante - ?
            WHERE id = ? AND quantidade_restante >= ?
        ");

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $lote) {
            if ($restante <= 0.000001) break;
            $quantidadeLote = min($restante, (float) $lote['quantidade_restante']);
            $baixa->execute([$quantidadeLote, $lote['id'], $quantidadeLote]);
            if ($baixa->rowCount() !== 1) throw new Exception('Conflito ao baixar lote');
            $restante -= $quantidadeLote;
        }

        if ($restante > 0.000001) throw new Exception('Saldo por lote insuficiente');
    }
}
