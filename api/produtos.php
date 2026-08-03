<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../pdo.php";
require_once __DIR__ . "/../utils.php";

if (empty($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "msg" => "Nao autenticado"
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && ($_SESSION['perfil'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(["status" => "error", "msg" => "Acesso negado"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $stmt = $pdo->prepare("
        SELECT p.*,
          (SELECT MIN(l.validade) FROM lotes_estoque l
           WHERE l.produto_id = p.id AND l.quantidade_restante > 0) AS validade
        FROM produtos p WHERE p.nome LIKE ? ORDER BY p.id ASC
    ");
    $stmt->execute(["%" . $_GET['search'] . "%"]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("
        SELECT p.*,
          (SELECT MIN(l.validade) FROM lotes_estoque l
           WHERE l.produto_id = p.id AND l.quantidade_restante > 0) AS validade
        FROM produtos p ORDER BY p.id ASC
    ");
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

    $id = (int) ($dados['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT id, nome, preco, unidade_medida, categoria, estoque FROM produtos WHERE id = ?');
    $stmt->execute([$id]);
    $antes = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
    $ok = $stmt->execute([$id]);
    if ($ok) audit_log($pdo, 'excluir', 'produto', $id, ['antes' => $antes]);
    echo json_encode(["status" => $ok ? "success" : "error"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    if (!csrf_check($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(["status" => "error", "msg" => "CSRF invalido"]);
        exit;
    }

    $preco = str_replace(',', '.', $_POST['preco'] ?? 0);
    $id = (int) $_POST['id'];
    $stmt = $pdo->prepare('SELECT id, nome, preco, unidade_medida, categoria, estoque FROM produtos WHERE id = ?');
    $stmt->execute([$id]);
    $antes = $stmt->fetch(PDO::FETCH_ASSOC);

    $depois = [
        'nome' => $_POST['nome'] ?? '',
        'preco' => $preco,
        'unidade_medida' => $_POST['unidade_medida'] ?? 'unidade',
        'categoria' => $_POST['categoria'] ?? '',
    ];

    $stmt = $pdo->prepare("
        UPDATE produtos
        SET nome = ?, preco = ?, unidade_medida = ?, categoria = ?
        WHERE id = ?
    ");

    $ok = $stmt->execute([
        $_POST['nome'] ?? '',
        $preco,
        $_POST['unidade_medida'] ?? 'unidade',
        $_POST['categoria'] ?? '',
        $id
    ]);

    if ($ok) audit_log($pdo, 'editar', 'produto', $id, ['antes' => $antes, 'depois' => $depois]);

    echo json_encode(["status" => $ok ? "success" : "error"]);
    exit;
}

http_response_code(404);
echo json_encode(["msg" => "Rota invalida"]);
