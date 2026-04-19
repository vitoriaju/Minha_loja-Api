<?php
require_once __DIR__ . '/../config/conexao.php';

class Produto {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Listar produtos
    public function listar() {
        $sql = "SELECT * FROM produtos";
        $result = $this->conn->query($sql);
        $produtos = [];

        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $produtos[] = $row;
            }
        }

        return $produtos;
    }

    // Buscar produto pelo ID
    public function buscarPorId($id) {
        $stmt = $this->conn->prepare("SELECT * FROM produtos WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Cadastrar produto
    public function cadastrar($nome, $preco, $categoria, $validade, $estoque, $unidade) {

        $stmt = $this->conn->prepare("
            INSERT INTO produtos (nome, preco, categoria, validade, estoque, unidade_medida) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param("sdssis", 
            $nome, 
            $preco, 
            $categoria, 
            $validade, 
            $estoque,
            $unidade
        );

        return $stmt->execute();
    }

    // Atualizar produto
    public function atualizar($id, $dados) {

        $stmt = $this->conn->prepare("
            UPDATE produtos SET 
                nome=?, 
                preco=?, 
                categoria=?, 
                validade=?, 
                estoque=?, 
                unidade_medida=? 
            WHERE id=?
        ");

        $stmt->bind_param(
            "sdssisi",
            $dados['nome'],
            $dados['preco'],
            $dados['categoria'],
            $dados['validade'],
            $dados['estoque'],
            $dados['unidade_medida'],
            $id
        );

        return $stmt->execute();
    }

    // Excluir produto
    public function excluir($id) {
        $stmt = $this->conn->prepare("DELETE FROM produtos WHERE id=?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Listar vencidos
    public function listarVencidos() {
        $hoje = date('Y-m-d');

        $stmt = $this->conn->prepare("
            SELECT * FROM produtos WHERE validade < ?
        ");

        $stmt->bind_param("s", $hoje);
        $stmt->execute();
        $result = $stmt->get_result();

        $produtos = [];

        while ($row = $result->fetch_assoc()) {
            $produtos[] = $row;
        }

        return $produtos;
    }

    // Criar produto via API
    public function criar($dados) {

        $stmt = $this->conn->prepare("
            INSERT INTO produtos (nome, preco, categoria, validade, estoque, unidade_medida)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "sdssis",
            $dados['nome'],
            $dados['preco'],
            $dados['categoria'],
            $dados['validade'],
            $dados['estoque'],
            $dados['unidade_medida']
        );

        if ($stmt->execute()) {
            return ["mensagem" => "Produto criado com sucesso"];
        } else {
            return ["erro" => "Falha ao criar produto"];
        }
    }

    // Atualizar produto via API
    public function atualizarAPI($id, $dados) {

        $stmt = $this->conn->prepare("
            UPDATE produtos 
            SET nome=?, preco=?, categoria=?, validade=?, estoque=?, unidade_medida=?
            WHERE id=?
        ");

        $stmt->bind_param(
            "sdssisi",
            $dados['nome'],
            $dados['preco'],
            $dados['categoria'],
            $dados['validade'],
            $dados['estoque'],
            $dados['unidade_medida'],
            $id
        );

        if ($stmt->execute()) {
            return ["mensagem" => "Produto atualizado com sucesso"];
        } else {
            return ["erro" => "Falha ao atualizar produto"];
        }
    }

}
?>