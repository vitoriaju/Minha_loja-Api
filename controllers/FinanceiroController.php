<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_admin();
require_once __DIR__ . '/../pdo.php';
require_once __DIR__ . '/../utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/views/financeiro.php");
    exit;
}

if (!csrf_check($_POST['csrf_token'] ?? '')) {
    flash_set('erro', 'Token de segurança inválido.');
    header("Location: " . BASE_URL . "/views/financeiro.php");
    exit;
}

$acao = $_POST['acao'] ?? 'salvar';

try {

    if ($acao === 'salvar') {

        $tipo = $_POST['tipo'] ?? '';
        $categoria = trim($_POST['categoria'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $responsavel = trim($_POST['responsavel'] ?? '');
        $valor = $_POST['valor'] ?? '0';
        $forma_pagamento = $_POST['forma_pagamento'] ?? 'outro';
        $data_movimento = $_POST['data_movimento'] ?? date('Y-m-d');
        $turno = $_POST['turno'] ?? 'geral';
        $observacao = trim($_POST['observacao'] ?? '');
        $incluir_fechamento = isset($_POST['incluir_fechamento']) ? 1 : 0;

        $valor = str_replace(',', '.', $valor);
        $valor = floatval($valor);

        if (!in_array($tipo, ['entrada', 'saida'])) {
            throw new Exception('Tipo de movimentação inválido.');
        }

        if ($categoria === '') {
            throw new Exception('Informe a categoria.');
        }

        if ($descricao === '') {
            throw new Exception('Informe a descrição.');
        }

        if ($valor <= 0) {
            throw new Exception('O valor precisa ser maior que zero.');
        }

        if (!in_array($forma_pagamento, ['dinheiro', 'cartao', 'pix', 'outro'])) {
            $forma_pagamento = 'outro';
        }

        if (!in_array($turno, ['geral', 'manha', 'tarde'], true)) {
            $turno = 'geral';
        }

        $stmt = $pdo->prepare("
            INSERT INTO movimentacoes_financeiras
            (
                tipo,
                categoria,
                descricao,
                responsavel,
                valor,
                forma_pagamento,
                data_movimento,
                turno,
                observacao,
                incluir_fechamento
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $tipo,
            $categoria,
            $descricao,
            $responsavel !== '' ? $responsavel : null,
            $valor,
            $forma_pagamento,
            $data_movimento,
            $turno,
            $observacao,
            $incluir_fechamento
        ]);

        $movimentoId = (int) $pdo->lastInsertId();
        audit_log($pdo, 'criar', 'movimentacao_financeira', $movimentoId, ['tipo' => $tipo, 'valor' => $valor, 'incluir_fechamento' => $incluir_fechamento]);

        flash_set('sucesso', 'Movimentação financeira cadastrada com sucesso.');

        header("Location: " . BASE_URL . "/views/financeiro.php?data=" . $data_movimento);
        exit;
    }

    if ($acao === 'excluir') {

        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            throw new Exception('Movimentação inválida.');
        }

        $stmt = $pdo->prepare("SELECT data_movimento FROM movimentacoes_financeiras WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $data_movimento = $stmt->fetchColumn();

        if (!$data_movimento) {
            throw new Exception('Movimentação não encontrada.');
        }

        $stmt = $pdo->prepare("
            DELETE FROM movimentacoes_financeiras
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        audit_log($pdo, 'excluir', 'movimentacao_financeira', $id);

        flash_set('sucesso', 'Movimentação excluída com sucesso.');

        header("Location: " . BASE_URL . "/views/financeiro.php?data=" . urlencode($data_movimento));
        exit;
    }

    throw new Exception('Ação inválida.');

} catch (Exception $e) {

    log_exception($e, 'Falha na operacao financeira');
    flash_set('erro', 'Nao foi possivel concluir a operacao financeira.');

    header("Location: " . BASE_URL . "/views/financeiro.php");
    exit;
}
