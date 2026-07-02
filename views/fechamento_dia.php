<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_admin();
require_once __DIR__ . '/../pdo.php';
require_once __DIR__ . '/../utils.php';

$dataSelecionada = $_GET['data'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataSelecionada)) {
    $dataSelecionada = date('Y-m-d');
}

$sucesso = flash_get('sucesso');
$erro = flash_get('erro');

/*
    Busca o fechamento do dia selecionado
*/
$stmt = $pdo->prepare("
    SELECT *
    FROM fechamentos_diarios
    WHERE data_fechamento = ?
    LIMIT 1
");
$stmt->execute([$dataSelecionada]);
$fechamento = $stmt->fetch();

$itens = [];

if ($fechamento) {
    $stmt = $pdo->prepare("
        SELECT
            i.*,
            p.nome,
            p.categoria,
            p.unidade_medida
        FROM itens_fechamento i
        INNER JOIN produtos p ON p.id = i.produto_id
        WHERE i.fechamento_id = ?
        ORDER BY i.quantidade_vendida DESC
    ");
    $stmt->execute([$fechamento['id']]);
    $itens = $stmt->fetchAll();
}

$dataFormatada = date('d/m/Y', strtotime($dataSelecionada));
$dataProducao = date('d/m/Y', strtotime($dataSelecionada . ' +1 day'));

include __DIR__ . '/layout.php';
?>

<style>
.fechamento-container {
    max-width: 1200px;
    margin: auto;
}

.fechamento-topo {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    align-items: flex-end;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.fechamento-titulo h2 {
    margin-bottom: 5px;
}

.fechamento-titulo p {
    color: #666;
}

.fechamento-form {
    display: flex;
    gap: 10px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.fechamento-form label {
    font-weight: bold;
    color: #4b2e16;
}

.fechamento-form input {
    margin-bottom: 0;
    width: 180px;
}

.btn-gerar {
    height: 42px;
    white-space: nowrap;
}

.alerta-sucesso {
    background: #d4edda;
    color: #155724;
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 15px;
    border-left: 5px solid #28a745;
}

.alerta-erro {
    background: #f8d7da;
    color: #721c24;
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 15px;
    border-left: 5px solid #dc3545;
}

.cards-resumo {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 18px;
    margin-bottom: 25px;
}

.card-resumo {
    background: white;
    border-radius: 12px;
    padding: 18px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
    border-left: 5px solid #7b4f27;
}

.card-resumo span {
    color: #666;
    font-size: 14px;
}

.card-resumo strong {
    display: block;
    margin-top: 8px;
    font-size: 24px;
    color: #3b2412;
}

.card-resumo small {
    color: #777;
}

.tabela-fechamento {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}

.tabela-fechamento h3 {
    margin-bottom: 5px;
}

.tabela-fechamento p {
    color: #666;
    margin-bottom: 10px;
}

.badge-producao {
    display: inline-block;
    background: #e7f7e7;
    color: #1f7a1f;
    padding: 5px 10px;
    border-radius: 20px;
    font-weight: bold;
}

.badge-nao {
    display: inline-block;
    background: #f1f1f1;
    color: #777;
    padding: 5px 10px;
    border-radius: 20px;
}

.vazio {
    background: white;
    border-radius: 12px;
    padding: 25px;
    text-align: center;
    color: #666;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}
</style>

<div class="fechamento-container">

    <div class="fechamento-topo">

        <div class="fechamento-titulo">
            <h2>Fechamento Diário</h2>
            <p>
                Veja quanto vendeu no dia e gere a sugestão de produção para o próximo dia.
            </p>
        </div>

        <form class="fechamento-form" method="GET" action="fechamento_dia.php">
            <div>
                <label>Escolher data</label>
                <input type="date" name="data" value="<?= e($dataSelecionada) ?>">
            </div>

            <button type="submit" class="btn-gerar">Buscar</button>
        </form>

    </div>

    <?php if ($sucesso): ?>
        <div class="alerta-sucesso">
            <?= e($sucesso) ?>
        </div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="alerta-erro">
            <?= e($erro) ?>
        </div>
    <?php endif; ?>

    <div class="card" style="margin-bottom:20px;">

        <h3>Gerar fechamento</h3>

        <p style="color:#666; margin:8px 0 15px;">
            Clique no botão abaixo para calcular as vendas do dia <?= e($dataFormatada) ?>.
            Se já existir fechamento, ele será atualizado.
        </p>

        <form method="POST" action="../controllers/FechamentoController.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="data_fechamento" value="<?= e($dataSelecionada) ?>">

            <button type="submit">
                Gerar fechamento do dia <?= e($dataFormatada) ?>
            </button>
        </form>

    </div>

    <?php if ($fechamento): ?>

        <div class="cards-resumo">

            <div class="card-resumo">
                <span>Total vendido</span>
                <strong>
                    R$ <?= number_format($fechamento['total_vendido'], 2, ',', '.') ?>
                </strong>
                <small>Dia <?= e($dataFormatada) ?></small>
            </div>

            <div class="card-resumo">
                <span>Dinheiro</span>
                <strong>
                    R$ <?= number_format($fechamento['total_dinheiro'], 2, ',', '.') ?>
                </strong>
            </div>

            <div class="card-resumo">
                <span>Cartão</span>
                <strong>
                    R$ <?= number_format($fechamento['total_cartao'], 2, ',', '.') ?>
                </strong>
            </div>

            <div class="card-resumo">
                <span>Pix</span>
                <strong>
                    R$ <?= number_format($fechamento['total_pix'], 2, ',', '.') ?>
                </strong>
            </div>

            <div class="card-resumo">
                <span>Quantidade de vendas</span>
                <strong>
                    <?= intval($fechamento['quantidade_vendas']) ?>
                </strong>
            </div>

        </div>

        <div class="tabela-fechamento">

            <h3>Produtos vendidos</h3>

            <p>
                Sugestão de produção para <?= e($dataProducao) ?>.
            </p>

            <?php if (count($itens) > 0): ?>

                <table>
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th>Quantidade vendida</th>
                            <th>Valor vendido</th>
                            <th>Sugestão para produzir</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($itens as $item): ?>
                            <tr>
                                <td>
                                    <strong><?= e($item['nome']) ?></strong>
                                </td>

                                <td>
                                    <?= e($item['categoria']) ?>
                                </td>

                                <td>
                                    <?= number_format($item['quantidade_vendida'], 3, ',', '.') ?>
                                    <?= e($item['unidade_medida']) ?>
                                </td>

                                <td>
                                    R$ <?= number_format($item['valor_vendido'], 2, ',', '.') ?>
                                </td>

                                <td>
                                    <?php if ($item['sugestao_producao'] > 0): ?>

                                        <span class="badge-producao">
                                            <?= number_format($item['sugestao_producao'], 3, ',', '.') ?>
                                            <?= e($item['unidade_medida']) ?>
                                        </span>

                                    <?php else: ?>

                                        <span class="badge-nao">
                                            Não é produção
                                        </span>

                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php else: ?>

                <div class="vazio">
                    Nenhum produto vendido nesse fechamento.
                </div>

            <?php endif; ?>

        </div>

    <?php else: ?>

        <div class="vazio">
            <h3>Nenhum fechamento gerado para <?= e($dataFormatada) ?></h3>
            <p style="margin-top:8px;">
                Clique em <strong>Gerar fechamento</strong> para calcular as vendas desse dia.
            </p>
        </div>

    <?php endif; ?>

</div>

</div>
</div>
</div>
</body>
</html>
