<?php
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../verifica_sessao.php';
require_admin();
require_once __DIR__ . '/../pdo.php';
require_once __DIR__ . '/../utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'msg' => 'Metodo nao permitido']);
    exit;
}

parse_str(file_get_contents('php://input'), $dados);
if (!csrf_check($dados['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'msg' => 'CSRF invalido']);
    exit;
}

$loteId = (int) ($dados['id'] ?? 0);

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("
        SELECT id, produto_id, quantidade_restante
        FROM lotes_estoque
        WHERE id = ? AND validade < CURDATE()
        FOR UPDATE
    ");
    $stmt->execute([$loteId]);
    $lote = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lote || (float) $lote['quantidade_restante'] <= 0) {
        throw new RuntimeException('Lote vencido nao encontrado ou ja baixado.');
    }

    $quantidade = (float) $lote['quantidade_restante'];
    $stmt = $pdo->prepare("
        UPDATE produtos
        SET estoque = estoque - ?
        WHERE id = ? AND estoque >= ?
    ");
    $stmt->execute([$quantidade, $lote['produto_id'], $quantidade]);
    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('Estoque agregado inconsistente com o lote.');
    }

    $stmt = $pdo->prepare('UPDATE lotes_estoque SET quantidade_restante = 0 WHERE id = ?');
    $stmt->execute([$loteId]);
    audit_log($pdo, 'baixar_vencido', 'lote_estoque', $loteId, [
        'antes' => $lote,
        'depois' => ['quantidade_restante' => 0],
    ]);
    $pdo->commit();

    echo json_encode(['status' => 'success']);
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(409);
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_exception($e, 'Falha ao baixar lote vencido');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'msg' => 'Nao foi possivel baixar o lote.']);
}
