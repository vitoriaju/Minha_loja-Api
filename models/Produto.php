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
        $stmt = $this->pdo->prepare("
            INSERT INTO produtos (nome, preco, categoria, validade, estoque, unidade_medida)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([$nome, $preco, $categoria, $validade, $estoque, $unidade]);
    }

    public function atualizar($id, $dados): bool {
        $stmt = $this->pdo->prepare("
            UPDATE produtos SET
                nome = ?,
                preco = ?,
                categoria = ?,
                validade = ?,
                estoque = ?,
                unidade_medida = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $dados['nome'],
            $dados['preco'],
            $dados['categoria'] ?? null,
            $dados['validade'] ?? null,
            $dados['estoque'],
            $dados['unidade_medida'] ?? 'unidade',
            (int) $id
        ]);
    }

    public function excluir($id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM produtos WHERE id = ?");
        return $stmt->execute([(int) $id]);
    }

    public function listarVencidos(): array {
        $stmt = $this->pdo->prepare("SELECT * FROM produtos WHERE validade < ?");
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
