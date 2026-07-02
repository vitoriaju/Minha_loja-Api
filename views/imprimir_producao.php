<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_admin();
require_once __DIR__ . '/../pdo.php';
require_once __DIR__ . '/../utils.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: producao_dia.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        pr.id AS producao_id,
        pr.data AS data_producao,
        p.nome,
        p.unidade_medida,
        i.quantidade
    FROM producao pr
    INNER JOIN itens_producao i ON i.producao_id = pr.id
    INNER JOIN produtos p ON p.id = i.produto_id
    WHERE pr.id = ?
    ORDER BY p.nome ASC
");

$stmt->execute([$id]);
$itens = $stmt->fetchAll();

function formatarQuantidadeProducao($quantidade, $unidade) {
    $quantidade = floatval($quantidade);
    $unidade = strtolower(trim($unidade ?? 'unidade'));

    if ($unidade === 'kg') {
        return number_format($quantidade, 3, ',', '.') . ' kg';
    }

    if (floor($quantidade) == $quantidade) {
        return number_format($quantidade, 0, ',', '.') . ' unidade';
    }

    return number_format($quantidade, 3, ',', '.') . ' unidade';
}

$dataProducao = count($itens) > 0
    ? date('d/m/Y', strtotime($itens[0]['data_producao']))
    : date('d/m/Y');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Lista de Produção</title>

<style>
* {
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: #f4f1ec;
    margin: 0;
    padding: 30px;
    color: #3b2412;
}

.container {
    max-width: 900px;
    margin: auto;
    background: #fff;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 8px 22px rgba(0,0,0,0.12);
}

.topo {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    align-items: center;
    border-bottom: 2px solid #eadfD2;
    padding-bottom: 18px;
    margin-bottom: 25px;
}

.topo h1 {
    margin: 0;
    font-size: 28px;
    color: #4b2e16;
}

.topo p {
    margin: 6px 0 0;
    color: #777;
}

.info {
    text-align: right;
}

.info strong {
    display: block;
    font-size: 18px;
    color: #4b2e16;
}

.resumo {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.card-resumo {
    background: #faf7f2;
    border-left: 5px solid #7b4f27;
    border-radius: 12px;
    padding: 15px;
}

.card-resumo span {
    color: #777;
    font-size: 14px;
}

.card-resumo strong {
    display: block;
    margin-top: 6px;
    font-size: 22px;
    color: #3b2412;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

thead {
    background: #7b4f27;
    color: white;
}

th, td {
    padding: 14px;
    border-bottom: 1px solid #eee;
    text-align: left;
}

tbody tr:hover {
    background: #faf7f2;
}

.quantidade {
    font-weight: bold;
    color: #1f7a1f;
    background: #e7f7e7;
    padding: 6px 12px;
    border-radius: 20px;
    display: inline-block;
}

.acoes {
    display: flex;
    gap: 12px;
    margin-top: 25px;
    flex-wrap: wrap;
}

.btn {
    display: inline-block;
    border: none;
    text-decoration: none;
    background: #7b4f27;
    color: white;
    padding: 12px 18px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: bold;
    font-size: 15px;
}

.btn:hover {
    background: #5a371a;
}

.btn-secundario {
    background: #6c757d;
}

.btn-secundario:hover {
    background: #565e64;
}

.vazio {
    background: #fff3cd;
    color: #856404;
    padding: 18px;
    border-radius: 10px;
    border-left: 5px solid #ffc107;
    margin-top: 20px;
}

.rodape {
    margin-top: 30px;
    padding-top: 15px;
    border-top: 1px solid #eee;
    color: #777;
    font-size: 14px;
    text-align: center;
}

@media print {
    body {
        background: white;
        padding: 0;
    }

    .container {
        box-shadow: none;
        border-radius: 0;
        padding: 20px;
    }

    .acoes {
        display: none;
    }

    tbody tr:hover {
        background: transparent;
    }
}
</style>

</head>

<body>

<div class="container">

    <div class="topo">
        <div>
            <h1>Lista de Produção</h1>
            <p>Resumo dos produtos confirmados para produção.</p>
        </div>

        <div class="info">
            <span>Data da produção</span>
            <strong><?= e($dataProducao) ?></strong>
            <small>Produção nº <?= e($id) ?></small>
        </div>
    </div>

    <div class="resumo">
        <div class="card-resumo">
            <span>Total de itens</span>
            <strong><?= count($itens) ?></strong>
        </div>

        <div class="card-resumo">
            <span>Gerado em</span>
            <strong><?= date('d/m/Y') ?></strong>
        </div>
    </div>

    <?php if (count($itens) > 0): ?>

        <table>
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Unidade</th>
                    <th>Quantidade produzida</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($itens as $item): ?>
                    <tr>
                        <td>
                            <strong><?= e($item['nome']) ?></strong>
                        </td>

                        <td>
                            <?= e($item['unidade_medida'] ?: 'unidade') ?>
                        </td>

                        <td>
                            <span class="quantidade">
                                <?= formatarQuantidadeProducao($item['quantidade'], $item['unidade_medida']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php else: ?>

        <div class="vazio">
            Nenhum item encontrado para essa produção.
        </div>

    <?php endif; ?>

    <div class="acoes">
        <a href="producao_dia.php" class="btn btn-secundario">
            Voltar para Produção
        </a>

        <button onclick="window.print()" class="btn">
            Imprimir Lista
        </button>
    </div>

    <div class="rodape">
        Sistema Minha Loja — Lista gerada automaticamente.
    </div>

</div>

</body>
</html>
