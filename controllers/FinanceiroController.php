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
        $valor = $_POST['valor'] ?? '0';
        $forma_pagamento = $_POST['forma_pagamento'] ?? 'outro';
        $data_movimento = $_POST['data_movimento'] ?? date('Y-m-d');
        $observacao = trim($_POST['observacao'] ?? '');

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

        $stmt = $pdo->prepare("
            INSERT INTO movimentacoes_financeiras
            (
                tipo,
                categoria,
                descricao,
                valor,
                forma_pagamento,
                data_movimento,
                observacao
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $tipo,
            $categoria,
            $descricao,
            $valor,
            $forma_pagamento,
            $data_movimento,
            $observacao
        ]);

        flash_set('sucesso', 'Movimentação financeira cadastrada com sucesso.');

        header("Location: " . BASE_URL . "/views/financeiro.php?data=" . $data_movimento);
        exit;
    }

    if ($acao === 'excluir') {

        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            throw new Exception('Movimentação inválida.');
        }

        $stmt = $pdo->prepare("
            DELETE FROM movimentacoes_financeiras
            WHERE id = ?
        ");
        $stmt->execute([$id]);

        flash_set('sucesso', 'Movimentação excluída com sucesso.');

        header("Location: " . BASE_URL . "/views/financeiro.php");
        exit;
    }

    throw new Exception('Ação inválida.');

} catch (Exception $e) {

    flash_set('erro', 'Erro: ' . $e->getMessage());

    header("Location: " . BASE_URL . "/views/financeiro.php");
    exit;
}
