<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_once __DIR__ . '/../pdo.php';
require_once __DIR__ . '/../utils.php';

/*
    Regra:
    - Antes das 20h: mostra produção de hoje baseada no fechamento de ontem.
    - Depois das 20h: mostra produção de amanhã baseada no fechamento de hoje.
*/
$horaAtual = intval(date('H'));

$dataProducaoPadrao = $horaAtual >= 20
    ? date('Y-m-d', strtotime('+1 day'))
    : date('Y-m-d');

$dataProducao = $_GET['data_producao'] ?? $dataProducaoPadrao;

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataProducao)) {
    $dataProducao = $dataProducaoPadrao;
}

$dataBase = date('Y-m-d', strtotime($dataProducao . ' -1 day'));

$dataProducaoFormatada = date('d/m/Y', strtotime($dataProducao));
$dataBaseFormatada = date('d/m/Y', strtotime($dataBase));

function qtd_input($valor) {
    return rtrim(rtrim(number_format(floatval($valor), 3, '.', ''), '0'), '.');
}

function qtd_tela($valor) {
    return rtrim(rtrim(number_format(floatval($valor), 3, ',', '.'), '0'), ',');
}

/*
    Todos os produtos de padaria para adicionar manualmente
*/
$stmtAll = $pdo->query("
    SELECT id, nome
    FROM produtos
    WHERE categoria = 'Padaria'
    ORDER BY nome
");
$todos = $stmtAll->fetchAll();

/*
    Busca o fechamento do dia base
*/
$stmt = $pdo->prepare("
    SELECT *
    FROM fechamentos_diarios
    WHERE data_fechamento = ?
    LIMIT 1
");
$stmt->execute([$dataBase]);
$fechamento = $stmt->fetch();

$produtos = [];

if ($fechamento) {
    /*
        Busca os itens vendidos no fechamento
        e usa a sugestão gerada para montar a produção.
    */
    $stmt = $pdo->prepare("
        SELECT
            i.produto_id AS id,
            p.nome,
            p.unidade_medida,
            i.quantidade_vendida,
            i.valor_vendido,
            i.sugestao_producao AS sugestao
        FROM itens_fechamento i
        INNER JOIN produtos p ON p.id = i.produto_id
        WHERE i.fechamento_id = ?
          AND i.sugestao_producao > 0
        ORDER BY i.sugestao_producao DESC
    ");
    $stmt->execute([$fechamento['id']]);
    $produtos = $stmt->fetchAll();
}

include __DIR__ . '/layout.php';
?>

<style>
.producao-container {
    max-width: 1200px;
    margin: auto;
}

.producao-topo {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    align-items: flex-end;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.producao-topo h2 {
    margin-bottom: 5px;
}

.producao-topo p {
    color: #666;
}

.form-data {
    display: flex;
    gap: 10px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.form-data label {
    font-weight: bold;
    color: #4b2e16;
}

.form-data input {
    width: 190px;
    margin-bottom: 0;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 18px;
    margin-bottom: 20px;
}

.info-card {
    background: #fff;
    padding: 18px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
    border-left: 5px solid #7b4f27;
}

.info-card span {
    color: #666;
    font-size: 14px;
}

.info-card strong {
    display: block;
    margin-top: 8px;
    font-size: 24px;
    color: #3b2412;
}

.alerta {
    background: #fff3cd;
    color: #856404;
    padding: 14px 16px;
    border-radius: 10px;
    border-left: 5px solid #ffc107;
    margin-bottom: 20px;
}

.alerta a {
    color: #5a371a;
    font-weight: bold;
}

.badge-venda {
    display: inline-block;
    background: #eef4ff;
    color: #2452a3;
    padding: 5px 10px;
    border-radius: 20px;
    font-weight: bold;
}

.badge-sugestao {
    display: inline-block;
    background: #e7f7e7;
    color: #1f7a1f;
    padding: 5px 10px;
    border-radius: 20px;
    font-weight: bold;
}

.acoes-producao {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 18px;
}

.btn-secundario {
    background: #5a371a;
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

<div class="producao-container">

    <div class="producao-topo">

        <div>
            <h2>Produção do Dia</h2>
            <p>
                Lista de produção para <?= e($dataProducaoFormatada) ?>,
                baseada no fechamento de <?= e($dataBaseFormatada) ?>.
            </p>
        </div>

        <form class="form-data" method="GET" action="producao_dia.php">
            <div>
                <label>Data da produção</label>
                <input type="date" name="data_producao" value="<?= e($dataProducao) ?>">
            </div>

            <button type="submit">Buscar</button>
        </form>

    </div>

    <?php if ($fechamento): ?>

        <div class="info-grid">

            <div class="info-card">
                <span>Fechamento usado</span>
                <strong><?= e($dataBaseFormatada) ?></strong>
            </div>

            <div class="info-card">
                <span>Total vendido</span>
                <strong>
                    R$ <?= number_format($fechamento['total_vendido'], 2, ',', '.') ?>
                </strong>
            </div>

            <div class="info-card">
                <span>Quantidade de vendas</span>
                <strong>
                    <?= intval($fechamento['quantidade_vendas']) ?>
                </strong>
            </div>

        </div>

        <?php if (count($produtos) > 0): ?>

            <div class="card">

                <h3>Lista sugerida para produção</h3>

                <p style="color:#666; margin-top:6px;">
                    O sistema está usando os produtos vendidos no fechamento e sugerindo 10% a mais para o próximo dia.
                </p>

                <form method="POST" action="../controllers/ProducaoController.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                    <table id="tabela">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Vendeu no fechamento</th>
                                <th>Sugestão</th>
                                <th>Produzir</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($produtos as $p): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($p['nome']) ?></strong>
                                        <input type="hidden" name="produto_id[]" value="<?= e($p['id']) ?>">
                                        <input type="hidden" name="produto_novo[]" value="">
                                    </td>

                                    <td>
                                        <span class="badge-venda">
                                            <?= qtd_tela($p['quantidade_vendida']) ?>
                                            <?= e($p['unidade_medida']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="badge-sugestao">
                                            <?= qtd_tela($p['sugestao']) ?>
                                            <?= e($p['unidade_medida']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <input
                                            type="number"
                                            name="quantidade[]"
                                            value="<?= e(qtd_input($p['sugestao'])) ?>"
                                            min="0"
                                            step="0.001"
                                        >
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="acoes-producao">
                        <button type="button" class="btn-secundario" onclick="adicionarLinha()">
                            Adicionar Produto
                        </button>

                        <button type="submit">
                            Salvar Produção
                        </button>
                    </div>

                </form>

            </div>

        <?php else: ?>

            <div class="vazio">
                <h3>Nenhum produto de padaria vendido nesse fechamento.</h3>
                <p style="margin-top:8px;">
                    Você ainda pode adicionar produtos manualmente na produção.
                </p>

                <br>

                <div class="card" style="text-align:left;">
                    <form method="POST" action="../controllers/ProducaoController.php">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                        <table id="tabela">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Sugestão</th>
                                    <th>Produzir</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>

                        <div class="acoes-producao">
                            <button type="button" class="btn-secundario" onclick="adicionarLinha()">
                                Adicionar Produto
                            </button>

                            <button type="submit">
                                Salvar Produção
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        <?php endif; ?>

    <?php else: ?>

        <div class="alerta">
            Ainda não existe fechamento para o dia <?= e($dataBaseFormatada) ?>.
            Para montar a produção de <?= e($dataProducaoFormatada) ?>, primeiro gere o fechamento do dia anterior.
            <br><br>
            <a href="fechamento_dia.php?data=<?= e($dataBase) ?>">
                Gerar fechamento de <?= e($dataBaseFormatada) ?>
            </a>
        </div>

    <?php endif; ?>

</div>

<script>
function adicionarLinha() {

    let tabela = document.getElementById("tabela").getElementsByTagName("tbody")[0];

    let novaLinha = tabela.insertRow();

    let cell1 = novaLinha.insertCell(0);
    let cell2 = novaLinha.insertCell(1);
    let cell3 = novaLinha.insertCell(2);

    cell1.innerHTML = `
        <select name="produto_id[]" onchange="toggleNovo(this)">
            <option value="">-- Novo Produto --</option>
            <?php foreach($todos as $t): ?>
                <option value="<?= e($t['id']) ?>"><?= e($t['nome']) ?></option>
            <?php endforeach; ?>
        </select>

        <input
            type="text"
            name="produto_novo[]"
            placeholder="Digite novo produto"
            style="display:block;"
        >
    `;

    cell2.innerHTML = `Manual`;

    cell3.innerHTML = `
        <input
            type="number"
            name="quantidade[]"
            value="1"
            min="0"
            step="0.001"
        >
    `;
}

function toggleNovo(select) {

    let input = select.parentElement.querySelector('input[name="produto_novo[]"]');

    if (select.value !== "") {
        input.style.display = "none";
        input.value = "";
    } else {
        input.style.display = "block";
    }
}
</script>

</div>
</div>
</div>
</body>
</html>