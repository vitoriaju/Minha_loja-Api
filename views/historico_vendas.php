<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_once __DIR__ . '/../pdo.php';
require_once __DIR__ . '/../utils.php';

$busca = trim($_GET['busca'] ?? '');
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';
$forma_pagamento = $_GET['forma_pagamento'] ?? '';

$where = [];
$params = [];

if ($busca !== '') {
    $where[] = "(CAST(v.id AS CHAR) LIKE ? OR u.email LIKE ?)";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

if ($data_inicio !== '') {
    $where[] = "DATE(v.data_venda) >= ?";
    $params[] = $data_inicio;
}

if ($data_fim !== '') {
    $where[] = "DATE(v.data_venda) <= ?";
    $params[] = $data_fim;
}

if (in_array($forma_pagamento, ['dinheiro', 'cartao', 'pix'], true)) {
    $where[] = "v.forma_pagamento = ?";
    $params[] = $forma_pagamento;
}

$whereSql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $pdo->prepare("
    SELECT 
        v.id,
        v.data_venda,
        v.valor_total,
        v.forma_pagamento,
        v.valor_recebido,
        v.troco,
        u.email AS usuario_email,
        COUNT(i.id) AS total_itens,
        COALESCE(SUM(i.quantidade), 0) AS total_quantidade
    FROM vendas v
    LEFT JOIN usuarios u ON u.id = v.usuario_id
    LEFT JOIN itens_venda i ON i.venda_id = v.id
    {$whereSql}
    GROUP BY 
        v.id,
        v.data_venda,
        v.valor_total,
        v.forma_pagamento,
        v.valor_recebido,
        v.troco,
        u.email
    ORDER BY v.data_venda DESC, v.id DESC
");

$stmt->execute($params);
$vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_vendas = count($vendas);
$total_geral = 0;
$total_dinheiro = 0;
$total_cartao = 0;
$total_pix = 0;
$total_itens = 0;

foreach ($vendas as $v) {
    $valor = (float)$v['valor_total'];

    $total_geral += $valor;
    $total_itens += (int)$v['total_itens'];

    if ($v['forma_pagamento'] === 'dinheiro') {
        $total_dinheiro += $valor;
    } elseif ($v['forma_pagamento'] === 'cartao') {
        $total_cartao += $valor;
    } elseif ($v['forma_pagamento'] === 'pix') {
        $total_pix += $valor;
    }
}

function nomePagamentoHistorico($pagamento)
{
    if ($pagamento === 'dinheiro') {
        return 'Dinheiro';
    }

    if ($pagamento === 'cartao') {
        return 'Cartão';
    }

    if ($pagamento === 'pix') {
        return 'Pix';
    }

    return 'Não informado';
}

function classePagamentoHistorico($pagamento)
{
    if ($pagamento === 'dinheiro') {
        return 'badge-dinheiro';
    }

    if ($pagamento === 'cartao') {
        return 'badge-cartao';
    }

    if ($pagamento === 'pix') {
        return 'badge-pix';
    }

    return 'badge-info';
}

include __DIR__ . '/layout.php';
?>

<style>
.hv-page{
    display:flex;
    flex-direction:column;
    gap:22px;
}

.hv-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:20px;
    flex-wrap:wrap;
}

.hv-title h2{
    font-size:28px;
    color:#3b2411;
    margin-bottom:6px;
}

.hv-title p{
    color:#777;
    font-size:15px;
}

.hv-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.hv-btn,
.hv-btn-link{
    border:0;
    border-radius:10px;
    padding:11px 16px;
    cursor:pointer;
    font-weight:bold;
    font-size:14px;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    transition:0.3s;
}

.hv-btn-primary{
    background:#7b4f27;
    color:white;
}

.hv-btn-primary:hover{
    background:#5a371a;
    transform:translateY(-2px);
}

.hv-btn-secondary{
    background:white;
    color:#7b4f27;
    border:1px solid #d6b089;
}

.hv-btn-secondary:hover{
    background:#fff7ef;
    transform:translateY(-2px);
}

.hv-btn-green{
    background:#2e8b57;
    color:white;
}

.hv-btn-green:hover{
    background:#247046;
    transform:translateY(-2px);
}

.hv-stats{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(210px, 1fr));
    gap:16px;
}

.hv-stat-card{
    background:white;
    border-radius:16px;
    padding:18px;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
    border-left:6px solid #7b4f27;
}

.hv-stat-card span{
    color:#777;
    font-weight:bold;
    font-size:14px;
}

.hv-stat-card h3{
    margin-top:8px;
    color:#3b2411;
    font-size:30px;
}

.hv-stat-card.green{
    border-left-color:#2e8b57;
}

.hv-stat-card.blue{
    border-left-color:#2980b9;
}

.hv-stat-card.purple{
    border-left-color:#6b3fc7;
}

.hv-filter-card{
    background:white;
    border-radius:18px;
    padding:20px;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
}

.hv-filtro-form{
    display:grid;
    grid-template-columns:1.2fr 1fr 1fr 1fr auto auto;
    gap:12px;
    align-items:end;
}

.hv-form-group label{
    display:block;
    color:#3b2411;
    font-weight:bold;
    margin-bottom:6px;
    font-size:14px;
}

.hv-form-group input,
.hv-form-group select{
    margin:0;
    width:100%;
    padding:12px;
    border-radius:10px;
    border:1px solid #ccc;
    font-size:15px;
}

.hv-form-group input:focus,
.hv-form-group select:focus{
    outline:none;
    border-color:#7b4f27;
    box-shadow:0 0 0 3px rgba(123,79,39,0.15);
}

.hv-card{
    background:white;
    border-radius:18px;
    padding:22px;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
}

.hv-card-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
    flex-wrap:wrap;
    margin-bottom:18px;
}

.hv-card-header h3{
    color:#3b2411;
    font-size:21px;
}

.hv-alerta{
    background:#fff8f1;
    color:#5a371a;
    border-left:6px solid #7b4f27;
    padding:15px;
    border-radius:14px;
    line-height:1.5;
    margin-bottom:18px;
    font-weight:bold;
}

.hv-table-wrap{
    width:100%;
    overflow-x:auto;
}

.hv-table{
    width:100%;
    border-collapse:collapse;
    margin-top:0;
}

.hv-table th{
    background:#f5d0a9;
    color:#3b2411;
    padding:13px 10px;
    font-size:14px;
    white-space:nowrap;
}

.hv-table td{
    padding:13px 10px;
    border-bottom:1px solid #eee;
    vertical-align:middle;
}

.hv-table tr:hover td{
    background:#fff8f1;
}

.venda-id{
    color:#777;
    font-weight:bold;
}

.valor{
    font-weight:bold;
    color:#3b2411;
}

.badge{
    display:inline-block;
    padding:6px 10px;
    border-radius:20px;
    font-size:12px;
    font-weight:bold;
    white-space:nowrap;
}

.badge-dinheiro{
    background:#e8f8ef;
    color:#1f7a45;
}

.badge-cartao{
    background:#eef3ff;
    color:#2c5aa0;
}

.badge-pix{
    background:#f0e9ff;
    color:#6b3fc7;
}

.badge-info{
    background:#fdf3e7;
    color:#7b4f27;
}

.hv-mini-resumo{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}

.hv-pill{
    background:#fdf3e7;
    color:#7b4f27;
    border-radius:20px;
    padding:6px 10px;
    font-size:12px;
    font-weight:bold;
}

.hv-action-cell{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}

.empty-box{
    background:white;
    border-radius:18px;
    padding:40px 20px;
    text-align:center;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
    color:#777;
    font-weight:bold;
}

.empty-box strong{
    display:block;
    color:#3b2411;
    font-size:22px;
    margin-bottom:8px;
}

@media(max-width:1200px){
    .hv-filtro-form{
        grid-template-columns:1fr 1fr 1fr;
    }
}

@media(max-width:750px){
    .hv-filtro-form{
        grid-template-columns:1fr;
    }

    .hv-actions,
    .hv-btn,
    .hv-btn-link{
        width:100%;
    }
}

@media print{
    .sidebar,
    .header,
    .hv-actions,
    .hv-filter-card,
    .hv-action-cell{
        display:none !important;
    }

    .wrapper,
    .main,
    .content{
        display:block;
        height:auto;
        padding:0;
        background:white;
    }

    .hv-card,
    .hv-stat-card{
        box-shadow:none;
    }
}
</style>

<div class="hv-page">

    <div class="hv-header">
        <div class="hv-title">
            <h2>Histórico de Vendas</h2>
            <p>Consulte vendas por data, forma de pagamento, e-mail do usuário ou número da venda.</p>
        </div>

        <div class="hv-actions">
            <a href="vender.php" class="hv-btn-link hv-btn-primary">
                Nova venda
            </a>

            <button type="button" class="hv-btn hv-btn-green" onclick="window.print()">
                Imprimir
            </button>

            <a href="dashboard.php" class="hv-btn-link hv-btn-secondary">
                Voltar
            </a>
        </div>
    </div>

    <div class="hv-stats">
        <div class="hv-stat-card">
            <span>Vendas encontradas</span>
            <h3><?= e($total_vendas) ?></h3>
        </div>

        <div class="hv-stat-card green">
            <span>Total vendido</span>
            <h3>R$ <?= number_format($total_geral, 2, ',', '.') ?></h3>
        </div>

        <div class="hv-stat-card blue">
            <span>Total de itens</span>
            <h3><?= e($total_itens) ?></h3>
        </div>

        <div class="hv-stat-card purple">
            <span>Pix / Cartão / Dinheiro</span>
            <h3 style="font-size:18px; line-height:1.5;">
                R$ <?= number_format($total_pix, 2, ',', '.') ?> /
                R$ <?= number_format($total_cartao, 2, ',', '.') ?> /
                R$ <?= number_format($total_dinheiro, 2, ',', '.') ?>
            </h3>
        </div>
    </div>

    <div class="hv-filter-card">
        <form method="GET" class="hv-filtro-form">

            <div class="hv-form-group">
                <label>Pesquisar</label>
                <input 
                    type="text" 
                    name="busca" 
                    value="<?= e($busca) ?>"
                    placeholder="Número da venda ou e-mail"
                >
            </div>

            <div class="hv-form-group">
                <label>Data inicial</label>
                <input 
                    type="date" 
                    name="data_inicio" 
                    value="<?= e($data_inicio) ?>"
                >
            </div>

            <div class="hv-form-group">
                <label>Data final</label>
                <input 
                    type="date" 
                    name="data_fim" 
                    value="<?= e($data_fim) ?>"
                >
            </div>

            <div class="hv-form-group">
                <label>Pagamento</label>
                <select name="forma_pagamento">
                    <option value="">Todos</option>

                    <option value="dinheiro" <?= $forma_pagamento === 'dinheiro' ? 'selected' : '' ?>>
                        Dinheiro
                    </option>

                    <option value="cartao" <?= $forma_pagamento === 'cartao' ? 'selected' : '' ?>>
                        Cartão
                    </option>

                    <option value="pix" <?= $forma_pagamento === 'pix' ? 'selected' : '' ?>>
                        Pix
                    </option>
                </select>
            </div>

            <button type="submit" class="hv-btn hv-btn-primary">
                Filtrar
            </button>

            <a href="historico_vendas.php" class="hv-btn-link hv-btn-secondary">
                Limpar
            </a>

        </form>
    </div>

    <?php if (count($vendas) > 0): ?>

        <div class="hv-card">
            <div class="hv-card-header">
                <h3>Vendas registradas</h3>
            </div>

            <div class="hv-alerta">
                Use os filtros acima para consultar vendas de um dia específico ou de um período.
            </div>

            <div class="hv-table-wrap">
                <table class="hv-table">
                    <thead>
                        <tr>
                            <th>Venda</th>
                            <th>Data</th>
                            <th>Usuário</th>
                            <th>Pagamento</th>
                            <th>Itens</th>
                            <th>Total</th>
                            <th>Recebido</th>
                            <th>Troco</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($vendas as $v): ?>
                            <tr>
                                <td class="venda-id">
                                    #<?= e($v['id']) ?>
                                </td>

                                <td>
                                    <?= date('d/m/Y H:i', strtotime($v['data_venda'])) ?>
                                </td>

                                <td>
                                    <?= e($v['usuario_email'] ?: '---') ?>
                                </td>

                                <td>
                                    <span class="badge <?= e(classePagamentoHistorico($v['forma_pagamento'])) ?>">
                                        <?= e(nomePagamentoHistorico($v['forma_pagamento'])) ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="hv-mini-resumo">
                                        <span class="hv-pill">
                                            <?= e($v['total_itens']) ?> item(ns)
                                        </span>
                                    </div>
                                </td>

                                <td class="valor">
                                    R$ <?= number_format((float)$v['valor_total'], 2, ',', '.') ?>
                                </td>

                                <td>
                                    <?php if ($v['forma_pagamento'] === 'dinheiro'): ?>
                                        R$ <?= number_format((float)$v['valor_recebido'], 2, ',', '.') ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($v['forma_pagamento'] === 'dinheiro'): ?>
                                        R$ <?= number_format((float)$v['troco'], 2, ',', '.') ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="hv-action-cell">
                                        <a 
                                            href="recibo.php?id=<?= e($v['id']) ?>" 
                                            class="hv-btn-link hv-btn-secondary"
                                        >
                                            Recibo
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>
        </div>

    <?php else: ?>

        <div class="empty-box">
            <strong>Nenhuma venda encontrada.</strong>
            Cadastre uma nova venda ou ajuste os filtros de data.
        </div>

    <?php endif; ?>

</div>

</div>
</div>
</div>
</body>
</html>