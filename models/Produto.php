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
    public function cadastrar($nome, $preco, $qualidade, $categoria, $validade, $estoque) {
        $stmt = $this->conn->prepare("INSERT INTO produtos (nome, preco, qualidade, categoria, validade, estoque) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sdsssi", $nome, $preco, $qualidade, $categoria, $validade, $estoque);
        return $stmt->execute();
    }

    // Atualizar produto
    public function atualizar($id, $dados) {
        $stmt = $this->conn->prepare("UPDATE produtos SET nome=?, preco=?, qualidade=?, categoria=?, validade=?, estoque=? WHERE id=?");
        $stmt->bind_param(
            "sdsssii",
            $dados['nome'],
            $dados['preco'],
            $dados['qualidade'],
            $dados['categoria'],
            $dados['validade'],
            $dados['estoque'],
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

//listarVencidos
public function listarVencidos() {
    $hoje = date('Y-m-d');
    $stmt = $this->conn->prepare("SELECT * FROM produtos WHERE validade < ?");
    $stmt->bind_param("s", $hoje);
    $stmt->execute();
    $result = $stmt->get_result();

    $produtos = [];
    while ($row = $result->fetch_assoc()) {
        $produtos[] = $row;
    }
    return $produtos;
}
// Criar produto via API (recebe array)
public function criar($dados) {

    $stmt = $this->conn->prepare("
        INSERT INTO produtos (nome, preco, qualidade, categoria, validade, estoque)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sdsssi",
        $dados['nome'],
        $dados['preco'],
        $dados['qualidade'],
        $dados['categoria'],
        $dados['validade'],
        $dados['estoque']
    );

    if ($stmt->execute()) {
        return ["mensagem" => "Produto criado com sucesso"];
    } else {
        return ["erro" => "Falha ao criar produto"];
    }
}


// Atualizar produto via API (recebe array)
public function atualizarAPI($id, $dados) {

    $stmt = $this->conn->prepare("
        UPDATE produtos 
        SET nome=?, preco=?, qualidade=?, categoria=?, validade=?, estoque=?
        WHERE id=?
    ");

    $stmt->bind_param(
        "sdsssii",
        $dados['nome'],
        $dados['preco'],
        $dados['qualidade'],
        $dados['categoria'],
        $dados['validade'],
        $dados['estoque'],
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
