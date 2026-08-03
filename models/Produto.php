<?php

class Produto {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function listar(): array {
        $stmt = $this->pdo->query("SELECT * FROM produtos");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId($id): array|false {
        $stmt = $this->pdo->prepare("SELECT * FROM produtos WHERE id = ?");
        $stmt->execute([(int) $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function cadastrar($nome, $preco, $categoria, $validade, $estoque, $unidade): bool {
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("
                INSERT INTO produtos (nome, preco, categoria, validade, estoque, unidade_medida)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nome, $preco, $categoria, $validade, $estoque, $unidade]);
            $produtoId = (int) $this->pdo->lastInsertId();
            if ((float) $estoque > 0) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO lotes_estoque
                    (item_entrada_id, produto_id, validade, quantidade_inicial, quantidade_restante, origem)
                    VALUES (NULL, ?, ?, ?, ?, 'cadastro')
                ");
                $stmt->execute([$produtoId, $validade ?: null, $estoque, $estoque]);
            }
            return $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return false;
        }
    }

    public function atualizar($id, $dados): bool {
        $stmt = $this->pdo->prepare("
            UPDATE produtos SET
                nome = ?,
                preco = ?,
                categoria = ?,
                unidade_medida = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $dados['nome'],
            $dados['preco'],
            $dados['categoria'] ?? null,
            $dados['unidade_medida'] ?? 'unidade',
            (int) $id
        ]);
    }

    public function excluir($id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM produtos WHERE id = ?");
        return $stmt->execute([(int) $id]);
    }

    public function listarVencidos(): array {
        $stmt = $this->pdo->prepare("
            SELECT p.*, l.id AS lote_id, l.validade, l.quantidade_restante
            FROM lotes_estoque l JOIN produtos p ON p.id = l.produto_id
            WHERE l.quantidade_restante > 0 AND l.validade < ?
        ");
        $stmt->execute([date('Y-m-d')]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function criar($dados): array {
        $ok = $this->cadastrar(
            $dados['nome'],
            $dados['preco'],
            $dados['categoria'] ?? null,
            $dados['validade'] ?? null,
            $dados['estoque'] ?? 0,
            $dados['unidade_medida'] ?? 'unidade'
        );

        return $ok ? ["mensagem" => "Produto criado com sucesso"] : ["erro" => "Falha ao criar produto"];
    }

    public function atualizarAPI($id, $dados): array {
        $ok = $this->atualizar($id, $dados);
        return $ok ? ["mensagem" => "Produto atualizado com sucesso"] : ["erro" => "Falha ao atualizar produto"];
    }
}
