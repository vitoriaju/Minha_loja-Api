<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$required_perfil = null;

require_once __DIR__ . '/../verifica_sessao.php';
require_once __DIR__ . '/../pdo.php';
require_once __DIR__ . '/../utils.php';

/* =========================
   DADOS PRINCIPAIS
========================= */

$stmt = $pdo->query("SELECT COUNT(*) AS total FROM produtos");
$total_produtos = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) AS total FROM vendas");
$total_vendas = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->query("
    SELECT 
        COUNT(*) AS quantidade,
        COALESCE(SUM(valor_total), 0) AS total
    FROM vendas
    WHERE DATE(data_venda) = CURDATE()
");
$vendas_dia = $stmt->fetch();

$qtd_vendas_dia = $vendas_dia['quantidade'] ?? 0;
$total_dia = $vendas_dia['total'] ?? 0;

$ticket_medio = $qtd_vendas_dia > 0 ? $total_dia / $qtd_vendas_dia : 0;

/* =========================
   ALERTAS
========================= */

$stmt = $pdo->query("
    SELECT nome, estoque, estoque_minimo, unidade_medida
    FROM produtos
    WHERE estoque <= estoque_minimo
    ORDER BY estoque ASC
");
$alertas_estoque = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_alertas = count($alertas_estoque);

$stmt = $pdo->query("
    SELECT nome, validade
    FROM produtos
    WHERE validade IS NOT NULL
    AND validade < CURDATE()
    ORDER BY validade ASC
");
$vencidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_vencidos = count($vencidos);

$stmt = $pdo->query("
    SELECT nome, validade
    FROM produtos
    WHERE validade IS NOT NULL
    AND validade BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY validade ASC
");
$vencer = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_vencer = count($vencer);

/* =========================
   ÚLTIMAS VENDAS
========================= */

$stmt = $pdo->query("
    SELECT id, valor_total, forma_pagamento, data_venda
    FROM vendas
    ORDER BY data_venda DESC, id DESC
    LIMIT 5
");
$ultimas_vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/layout.php';
?>

<style>
.dashboard-page{
    display:flex;
    flex-direction:column;
    gap:22px;
}

.dashboard-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:20px;
    flex-wrap:wrap;
}

.dashboard-title h2{
    font-size:28px;
    color:#3b2411;
    margin-bottom:6px;
}

.dashboard-title p{
    color:#777;
    font-size:15px;
}

.dashboard-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.dash-btn{
    text-decoration:none;
    padding:11px 16px;
    border-radius:10px;
    background:#7b4f27;
    color:white;
    font-weight:bold;
    font-size:14px;
    transition:0.3s;
    display:inline-block;
}

.dash-btn:hover{
    background:#5a371a;
    transform:translateY(-2px);
}

.dash-btn.secondary{
    background:white;
    color:#7b4f27;
    border:1px solid #d6b089;
}

.dash-btn.secondary:hover{
    background:#fff7ef;
}

.stats-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
    gap:18px;
}

.stat-card{
    background:white;
    border-radius:16px;
    padding:20px;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
    cursor:pointer;
    transition:0.3s;
    border-left:6px solid #7b4f27;
}

.stat-card:hover{
    transform:translateY(-4px);
    box-shadow:0 8px 22px rgba(0,0,0,0.12);
}

.stat-info span{
    color:#777;
    font-size:14px;
    font-weight:bold;
}

.stat-info h3{
    margin-top:8px;
    font-size:30px;
    color:#3b2411;
}

.stat-info small{
    display:block;
    margin-top:6px;
    color:#888;
}

.stat-icon{
    width:50px;
    height:50px;
    border-radius:14px;
    background:#fdf3e7;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:26px;
}

.border-green{
    border-left-color:#2e8b57;
}

.border-red{
    border-left-color:#c0392b;
}

.border-orange{
    border-left-color:#e67e22;
}

.border-blue{
    border-left-color:#2980b9;
}

.dashboard-grid{
    display:grid;
    grid-template-columns:1.4fr 1fr;
    gap:20px;
}

.dashboard-panel{
    background:white;
    border-radius:16px;
    padding:20px;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
}

.panel-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
    margin-bottom:15px;
}

.panel-header h3{
    color:#3b2411;
    font-size:20px;
}

.panel-header a{
    color:#7b4f27;
    font-weight:bold;
    text-decoration:none;
    font-size:14px;
}

.panel-header a:hover{
    text-decoration:underline;
}

.dashboard-table{
    width:100%;
    border-collapse:collapse;
}

.dashboard-table th{
    background:#f5d0a9;
    color:#3b2411;
    font-size:14px;
}

.dashboard-table th,
.dashboard-table td{
    padding:12px 10px;
    border-bottom:1px solid #eee;
}

.dashboard-table tr:hover td{
    background:#fff8f1;
}

.badge{
    display:inline-block;
    padding:5px 9px;
    border-radius:20px;
    font-size:12px;
    font-weight:bold;
    text-transform:capitalize;
}

.badge.dinheiro{
    background:#e8f8ef;
    color:#1f7a45;
}

.badge.cartao{
    background:#eef3ff;
    color:#2c5aa0;
}

.badge.pix{
    background:#f0e9ff;
    color:#6b3fc7;
}

.alert-list{
    display:flex;
    flex-direction:column;
    gap:10px;
}

.alert-item{
    padding:12px;
    border-radius:12px;
    background:#fff7ef;
    border-left:5px solid #e67e22;
}

.alert-item.red{
    border-left-color:#c0392b;
    background:#fff1f1;
}

.alert-item strong{
    color:#3b2411;
}

.alert-item small{
    display:block;
    margin-top:4px;
    color:#777;
}

.empty-box{
    padding:18px;
    background:#f4fff4;
    border-radius:12px;
    color:#2e7d32;
    font-weight:bold;
    text-align:center;
}

@media(max-width:900px){
    .dashboard-grid{
        grid-template-columns:1fr;
    }
}
</style>

<div class="dashboard-page">

    <div class="dashboard-header">
        <div class="dashboard-title">
            <h2>Dashboard</h2>
            <p>Visão geral das vendas, estoque e validade dos produtos.</p>
        </div>

        <div class="dashboard-actions">
            <a href="vender.php" class="dash-btn">Nova venda</a>
            <a href="cadastrar_Produto.php" class="dash-btn secondary">Cadastrar produto</a>
        </div>
    </div>

    <div class="stats-grid">

        <div class="stat-card" onclick="window.location='listar_produtos_api.php'">
            <div class="stat-info">
                <span>Total de produtos</span>
                <h3><?= e($total_produtos) ?></h3>
                <small>Produtos cadastrados no sistema</small>
            </div>
            <div class="stat-icon">📦</div>
        </div>

        <div class="stat-card border-green" onclick="window.location='historico_vendas.php'">
            <div class="stat-info">
                <span>Total de vendas</span>
                <h3><?= e($total_vendas) ?></h3>
                <small>Vendas registradas</small>
            </div>
            <div class="stat-icon">🧾</div>
        </div>

        <div class="stat-card border-blue" onclick="window.location='vendas_dia.php'">
            <div class="stat-info">
                <span>Vendas do dia</span>
                <h3>R$ <?= number_format($total_dia, 2, ",", ".") ?></h3>
                <small><?= e($qtd_vendas_dia) ?> venda(s) hoje</small>
            </div>
            <div class="stat-icon">💰</div>
        </div>

        <div class="stat-card border-green">
            <div class="stat-info">
                <span>Ticket médio hoje</span>
                <h3>R$ <?= number_format($ticket_medio, 2, ",", ".") ?></h3>
                <small>Média por venda do dia</small>
            </div>
            <div class="stat-icon">📊</div>
        </div>

        <div class="stat-card border-red" onclick="window.location='estoque_baixo.php'">
            <div class="stat-info">
                <span>Estoque baixo</span>
                <h3><?= e($total_alertas) ?></h3>
                <small>Produtos abaixo do mínimo</small>
            </div>
            <div class="stat-icon">⚠️</div>
        </div>

        <div class="stat-card border-orange" onclick="window.location='validade.php'">
            <div class="stat-info">
                <span>Validade</span>
                <h3><?= e($total_vencidos + $total_vencer) ?></h3>
                <small>
                    <?= e($total_vencidos) ?> vencido(s) /
                    <?= e($total_vencer) ?> para vencer
                </small>
            </div>
            <div class="stat-icon">⏳</div>
        </div>

    </div>

    <div class="dashboard-grid">

        <div class="dashboard-panel">
            <div class="panel-header">
                <h3>Últimas vendas</h3>
                <a href="historico_vendas.php">Ver histórico</a>
            </div>

            <?php if (count($ultimas_vendas) > 0): ?>

                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>Venda</th>
                            <th>Data</th>
                            <th>Pagamento</th>
                            <th>Total</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($ultimas_vendas as $v): ?>
                            <tr>
                                <td>#<?= e($v['id']) ?></td>

                                <td>
                                    <?= date('d/m/Y H:i', strtotime($v['data_venda'])) ?>
                                </td>

                                <td>
                                    <span class="badge <?= e($v['forma_pagamento']) ?>">
                                        <?= e($v['forma_pagamento'] ?: 'Não informado') ?>
                                    </span>
                                </td>

                                <td>
                                    <strong>
                                        R$ <?= number_format($v['valor_total'], 2, ",", ".") ?>
                                    </strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php else: ?>

                <div class="empty-box">
                    Nenhuma venda registrada ainda.
                </div>

            <?php endif; ?>
        </div>

        <div class="dashboard-panel">
            <div class="panel-header">
                <h3>Alertas rápidos</h3>
                <a href="validade.php">Ver detalhes</a>
            </div>

            <div class="alert-list">

                <?php if ($total_alertas > 0): ?>
                    <div class="alert-item red">
                        <strong>Estoque baixo</strong>
                        <small>
                            Existem <?= e($total_alertas) ?> produto(s) abaixo do estoque mínimo.
                        </small>
                    </div>
                <?php endif; ?>

                <?php if ($total_vencidos > 0): ?>
                    <div class="alert-item red">
                        <strong>Produtos vencidos</strong>
                        <small>
                            Existem <?= e($total_vencidos) ?> produto(s) vencido(s).
                        </small>
                    </div>
                <?php endif; ?>

                <?php if ($total_vencer > 0): ?>
                    <div class="alert-item">
                        <strong>Produtos perto do vencimento</strong>
                        <small>
                            Existem <?= e($total_vencer) ?> produto(s) vencendo nos próximos 7 dias.
                        </small>
                    </div>
                <?php endif; ?>

                <?php if ($total_alertas == 0 && $total_vencidos == 0 && $total_vencer == 0): ?>
                    <div class="empty-box">
                        Tudo certo. Nenhum alerta importante no momento.
                    </div>
                <?php endif; ?>

            </div>
        </div>

    </div>

</div>

</div>
</div>
</div>
</body>
</html>