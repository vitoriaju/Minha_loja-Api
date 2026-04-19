<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . "/../config/conexao.php";


// ==========================
// LISTAR COM BUSCA
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";

    $sql = $conn->prepare("SELECT * FROM produtos WHERE nome LIKE ?");
    $sql->bind_param("s", $search);
    $sql->execute();

    $result = $sql->get_result();
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    exit;
}

// ==========================
// LISTAR NORMAL
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['delete'])) {
    $sql = $conn->query("SELECT * FROM produtos ORDER BY id ASC");
    echo json_encode($sql->fetch_all(MYSQLI_ASSOC));
    exit;
}

// ==========================
// EXCLUIR PRODUTO
// ==========================
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $sql = $conn->prepare("DELETE FROM produtos WHERE id=?");
    $sql->bind_param("i", $id);

    if ($sql->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error"]);
    }
    exit;
}

// ==========================
// EDITAR PRODUTO
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {

    $id = $_POST['id'];
    $nome = $_POST['nome'] ?? '';
    $preco = $_POST['preco'] ?? 0;
    $preco = str_replace(',', '.', $preco); // 🔥 corrige vírgula
    $unidade = $_POST['unidade_medida'] ?? 'unidade';
    $categoria = $_POST['categoria'] ?? '';
    $validade = $_POST['validade'] ?? null;
    $estoque = $_POST['estoque'] ?? 0;

    $sql = $conn->prepare("
        UPDATE produtos 
        SET nome=?, preco=?, unidade_medida=?, categoria=?, validade=?, estoque=? 
        WHERE id=?
    ");

    $sql->bind_param("sdssssi",
        $nome,
        $preco,
        $unidade,
        $categoria,
        $validade,
        $estoque,
        $id
    );

    if ($sql->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode([
            "status" => "error",
            "erro" => $conn->error
        ]);
    }

    exit;
}

echo json_encode(["msg" => "Rota inválida"]);
?>