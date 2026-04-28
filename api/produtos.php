<?php
header("Content-Type: application/json; charset=UTF-8");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "msg" => "Nao autenticado"
    ]);
    exit;
}

require_once __DIR__ . "/../pdo.php";
require_once __DIR__ . "/../utils.php";

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE nome LIKE ? ORDER BY id ASC");
    $stmt->execute(["%" . $_GET['search'] . "%"]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("SELECT * FROM produtos ORDER BY id ASC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $dados);

    if (!csrf_check($dados['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(["status" => "error", "msg" => "CSRF invalido"]);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");

    echo json_encode([
        "status" => $stmt->execute([(int) ($dados['id'] ?? 0)]) ? "success" : "error"
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    if (!csrf_check($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(["status" => "error", "msg" => "CSRF invalido"]);
        exit;
    }

    $preco = str_replace(',', '.', $_POST['preco'] ?? 0);

    $stmt = $pdo->prepare("
        UPDATE produtos
        SET nome = ?, preco = ?, unidade_medida = ?, categoria = ?, validade = ?, estoque = ?
        WHERE id = ?
    ");

    $ok = $stmt->execute([
        $_POST['nome'] ?? '',
        $preco,
        $_POST['unidade_medida'] ?? 'unidade',
        $_POST['categoria'] ?? '',
        $_POST['validade'] ?? null,
        $_POST['estoque'] ?? 0,
        (int) $_POST['id']
    ]);

    echo json_encode(["status" => $ok ? "success" : "error"]);
    exit;
}

http_response_code(404);
echo json_encode(["msg" => "Rota invalida"]);
