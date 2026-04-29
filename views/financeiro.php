<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_once __DIR__ . '/../pdo.php';
require_once __DIR__ . '/../utils.php';

$dataSelecionada = $_GET['data'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataSelecionada)) {
    $dataSelecionada = date('Y-m-d');
}

$sucesso = flash_get('sucesso');
$erro = flash_get('erro');

/* =========================
   VENDAS DO DIA
========================= */
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS quantidade_vendas,

        COALESCE(SUM(valor_total), 0) AS total_vendas,

        COALESCE(SUM(
            CASE 
                WHEN LOWER(forma_pagamento) = 'dinheiro'
                THEN valor_total 
                ELSE 0 
            END
        ), 0) AS total_dinheiro,

        COALESCE(SUM(
            CASE 
                WHEN LOWER(forma_pagamento) = 'pix'
                THEN valor_total 
                ELSE 0 
            END
        ), 0) AS total_pix,

        COALESCE(SUM(
            CASE 
                WHEN LOWER(forma_pagamento) IN ('cartao', 'cartão')
                THEN valor_total 
                ELSE 0 
            END
        ), 0) AS total_cartao,

        COALESCE(SUM(
            CASE 
                WHEN LOWER(forma_pagamento) NOT IN ('dinheiro', 'pix', 'cartao', 'cartão')
                THEN valor_total 
                ELSE 0 
            END
        ), 0) AS total_outro

    FROM vendas
    WHERE DATE(data_venda) = ?
");
$stmt->execute([$dataSelecionada]);
$resumoVendas = $stmt->fetch();

$quantidadeVendas = (int)($resumoVendas['quantidade_vendas'] ?? 0);

$totalVendas = (float)($resumoVendas['total_vendas'] ?? 0);
$totalVendasDinheiro = (float)($resumoVendas['total_dinheiro'] ?? 0);
$totalVendasPix = (float)($resumoVendas['total_pix'] ?? 0);
$totalVendasCartao = (float)($resumoVendas['total_cartao'] ?? 0);
$totalVendasOutro = (float)($resumoVendas['total_outro'] ?? 0);

/* =========================
   MOVIMENTAÇÕES MANUAIS
========================= */
$stmt = $pdo->prepare("
    SELECT *
    FROM movimentacoes_financeiras
    WHERE data_movimento = ?
    ORDER BY id DESC
");
$stmt->execute([$dataSelecionada]);
$movimentacoes = $stmt->fetchAll();

$totalEntradasManuais = 0;
$totalSaidas = 0;

$totalEntradaManualDinheiro = 0;
$totalEntradaManualPix = 0;
$totalEntradaManualCartao = 0;
$totalEntradaManualOutro = 0;

$totalSaidaDinheiro = 0;
$totalSaidaPix = 0;
$totalSaidaCartao = 0;
$totalSaidaOutro = 0;

foreach ($movimentacoes as $mov) {
    $valorMov = (float)$mov['valor'];
    $forma = strtolower($mov['forma_pagamento']);

    if ($mov['tipo'] === 'entrada') {
        $totalEntradasManuais += $valorMov;

        if ($forma === 'dinheiro') {
            $totalEntradaManualDinheiro += $valorMov;
        } elseif ($forma === 'pix') {
            $totalEntradaManualPix += $valorMov;
        } elseif ($forma === 'cartao' || $forma === 'cartão') {
            $totalEntradaManualCartao += $valorMov;
        } else {
            $totalEntradaManualOutro += $valorMov;
        }

    } else {
        $totalSaidas += $valorMov;

        if ($forma === 'dinheiro') {
            $totalSaidaDinheiro += $valorMov;
        } elseif ($forma === 'pix') {
            $totalSaidaPix += $valorMov;
        } elseif ($forma === 'cartao' || $forma === 'cartão') {
            $totalSaidaCartao += $valorMov;
        } else {
            $totalSaidaOutro += $valorMov;
        }
    }
}

$totalEntrouDinheiro = $totalVendasDinheiro + $totalEntradaManualDinheiro;
$totalEntrouPix = $totalVendasPix + $totalEntradaManualPix;
$totalEntrouCartao = $totalVendasCartao + $totalEntradaManualCartao;
$totalEntrouOutro = $totalVendasOutro + $totalEntradaManualOutro;

$saldoDinheiro = $totalEntrouDinheiro - $totalSaidaDinheiro;
$saldoPix = $totalEntrouPix - $totalSaidaPix;
$saldoCartao = $totalEntrouCartao - $totalSaidaCartao;
$saldoOutro = $totalEntrouOutro - $totalSaidaOutro;

$totalEntradas = $totalEntrouDinheiro + $totalEntrouPix + $totalEntrouCartao + $totalEntrouOutro;
$saldoDia = $totalEntradas - $totalSaidas;

$dataFormatada = date('d/m/Y', strtotime($dataSelecionada));

include __DIR__ . '/layout.php';
?>

<style>
.financeiro-page{
    max-width:1300px;
    margin:auto;
    display:flex;
    flex-direction:column;
    gap:22px;
}

.financeiro-topo{
    display:flex;
    justify-content:space-between;
    align-items:flex-end;
    gap:20px;
    flex-wrap:wrap;
}

.financeiro-titulo h2{
    font-size:28px;
    color:#3b2411;
    margin-bottom:6px;
}

.financeiro-titulo p{
    color:#777;
    font-size:15px;
}

.filtro-data{
    background:white;
    padding:16px;
    border-radius:14px;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
    display:flex;
    gap:10px;
    align-items:flex-end;
    flex-wrap:wrap;
}

.filtro-data label{
    font-weight:bold;
    color:#4b2e16;
    font-size:14px;
}

.filtro-data input{
    width:180px;
    margin-bottom:0;
}

.filtro-data button{
    height:40px;
}

.alerta-sucesso{
    background:#d4edda;
    color:#155724;
    padding:12px 15px;
    border-radius:10px;
    border-left:5px solid #28a745;
}

.alerta-erro{
    background:#f8d7da;
    color:#721c24;
    padding:12px 15px;
    border-radius:10px;
    border-left:5px solid #dc3545;
}

.cards-financeiro{
    display:grid;
    grid-template-columns:repeat(3, 1fr);
    gap:18px;
}

.card-financeiro{
    background:white;
    border-radius:16px;
    padding:22px;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
    border-left:6px solid #7b4f27;
}

.card-financeiro span{
    color:#777;
    font-size:14px;
    font-weight:bold;
}

.card-financeiro strong{
    display:block;
    margin-top:8px;
    font-size:28px;
    color:#3b2411;
}

.card-financeiro small{
    display:block;
    margin-top:6px;
    color:#888;
}

.borda-verde{
    border-left-color:#2e8b57;
}

.borda-vermelha{
    border-left-color:#c0392b;
}

.borda-laranja{
    border-left-color:#e67e22;
}

.financeiro-grid{
    display:grid;
    grid-template-columns:420px 1fr;
    gap:22px;
    align-items:start;
}

.form-card,
.tabela-card{
    background:white;
    border-radius:16px;
    padding:22px;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
}

.form-card h3,
.tabela-card h3{
    color:#3b2411;
    margin-bottom:6px;
}

.form-card p,
.tabela-card p{
    color:#777;
    font-size:14px;
    margin-bottom:16px;
}

.form-card label{
    font-weight:bold;
    color:#4b2e16;
    font-size:14px;
}

textarea{
    width:100%;
    min-height:80px;
    padding:10px;
    margin-top:5px;
    margin-bottom:15px;
    border-radius:8px;
    border:1px solid #ccc;
    resize:vertical;
    font-family:Arial, sans-serif;
}

.linha-dupla{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
}

.btn-salvar{
    width:100%;
    font-weight:bold;
    font-size:15px;
    padding:12px;
}

.formas-pagamento-grid{
    display:grid;
    grid-template-columns:repeat(3, 1fr);
    gap:16px;
    margin-bottom:22px;
}

.forma-resumo-card{
    background:#fff7ef;
    border:1px solid #ead2b8;
    border-radius:16px;
    padding:16px;
}

.forma-resumo-card h4{
    color:#3b2411;
    margin-bottom:12px;
    font-size:18px;
}

.forma-linha{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    font-size:14px;
    margin-bottom:8px;
    color:#555;
}

.forma-linha strong{
    color:#3b2411;
}

.forma-linha .positivo{
    color:#1e7e34;
    font-weight:bold;
}

.forma-linha .negativo{
    color:#c0392b;
    font-weight:bold;
}

.ajuste-rapido{
    margin-top:14px;
    padding-top:14px;
    border-top:1px dashed #d6b089;
}

.ajuste-rapido label{
    font-size:13px;
    color:#4b2e16;
    font-weight:bold;
}

.ajuste-rapido select,
.ajuste-rapido input{
    width:100%;
    margin-bottom:8px;
}

.ajuste-rapido button{
    width:100%;
    padding:9px;
    font-size:13px;
    border-radius:8px;
}

.badge{
    display:inline-block;
    padding:5px 10px;
    border-radius:20px;
    font-size:12px;
    font-weight:bold;
}

.badge-entrada{
    background:#e6f4ea;
    color:#1e7e34;
}

.badge-saida{
    background:#fdecea;
    color:#c0392b;
}

.badge-auto{
    background:#eaf2ff;
    color:#2980b9;
}

.valor-entrada{
    color:#1e7e34;
    font-weight:bold;
}

.valor-saida{
    color:#c0392b;
    font-weight:bold;
}

.btn-excluir{
    background:#c0392b;
    padding:7px 10px;
    border-radius:8px;
    font-size:12px;
}

.btn-excluir:hover{
    background:#922b21;
}

.tabela-responsiva{
    overflow-x:auto;
}

.tabela-card table{
    min-width:900px;
}

.vazio{
    background:#fff7ef;
    padding:18px;
    border-radius:12px;
    color:#777;
    text-align:center;
    border:1px dashed #d6b089;
}

@media(max-width:1050px){
    .financeiro-grid{
        grid-template-columns:1fr;
    }

    .formas-pagamento-grid{
        grid-template-columns:1fr;
    }
}

@media(max-width:800px){
    .cards-financeiro{
        grid-template-columns:1fr;
    }
}
</style>

<div class="financeiro-page">

    <div class="financeiro-topo">
        <div class="financeiro-titulo">
            <h2>Controle Financeiro</h2>
            <p>Controle entradas, saídas, gastos e saldo do dia <?= e($dataFormatada) ?>.</p>
        </div>

        <form class="filtro-data" method="GET" action="financeiro.php">
            <div>
                <label>Escolher data</label>
                <input type="date" name="data" value="<?= e($dataSelecionada) ?>">
            </div>
            <button type="submit">Buscar</button>
        </form>
    </div>

    <?php if ($sucesso): ?>
        <div class="alerta-sucesso"><?= e($sucesso) ?></div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="alerta-erro"><?= e($erro) ?></div>
    <?php endif; ?>

    <div class="cards-financeiro">

        <div class="card-financeiro borda-verde">
            <span>Total de entrada</span>
            <strong>R$ <?= number_format($totalEntradas, 2, ',', '.') ?></strong>
            <small>Vendas + entradas manuais</small>
        </div>

        <div class="card-financeiro borda-vermelha">
            <span>Total de saída</span>
            <strong>R$ <?= number_format($totalSaidas, 2, ',', '.') ?></strong>
            <small>Compras, gastos e ajustes</small>
        </div>

        <div class="card-financeiro borda-laranja">
            <span>Saldo do dia</span>
            <strong>R$ <?= number_format($saldoDia, 2, ',', '.') ?></strong>
            <small>Entrada menos saída</small>
        </div>

    </div>

    <div class="financeiro-grid">

        <div class="form-card">
            <h3>Novo lançamento</h3>
            <p>Use para cadastrar uma entrada extra ou uma saída do caixa.</p>

            <form method="POST" action="../controllers/FinanceiroController.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="acao" value="salvar">

                <label>Tipo</label>
                <select name="tipo" required>
                    <option value="saida">Saída / Gasto</option>
                    <option value="entrada">Entrada extra</option>
                </select>

                <label>Categoria</label>
                <select name="categoria" required>
                    <option value="Compra de mercadoria">Compra de mercadoria</option>
                    <option value="Fornecedor">Fornecedor</option>
                    <option value="Conta fixa">Conta fixa</option>
                    <option value="Despesa do dia">Despesa do dia</option>
                    <option value="Retirada do caixa">Retirada do caixa</option>
                    <option value="Entrada extra">Entrada extra</option>
                    <option value="Ajuste financeiro">Ajuste financeiro</option>
                    <option value="Outro">Outro</option>
                </select>

                <label>Descrição</label>
                <input 
                    type="text" 
                    name="descricao" 
                    placeholder="Ex: Compra de leite, pagamento de fornecedor..." 
                    required
                >

                <div class="linha-dupla">
                    <div>
                        <label>Valor</label>
                        <input type="text" name="valor" placeholder="0,00" required>
                    </div>

                    <div>
                        <label>Forma</label>
                        <select name="forma_pagamento" required>
                            <option value="dinheiro">Dinheiro</option>
                            <option value="pix">Pix</option>
                            <option value="cartao">Cartão</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>
                </div>

                <label>Data</label>
                <input type="date" name="data_movimento" value="<?= e($dataSelecionada) ?>" required>

                <label>Observação</label>
                <textarea name="observacao" placeholder="Opcional"></textarea>

                <button class="btn-salvar" type="submit">Salvar lançamento</button>
            </form>
        </div>

        <div class="tabela-card">
            <h3>Movimentações do dia</h3>
            <p>Separação por dinheiro, Pix e cartão do dia <?= e($dataFormatada) ?>.</p>

            <div class="formas-pagamento-grid">

                <div class="forma-resumo-card">
                    <h4>Dinheiro</h4>

                    <div class="forma-linha">
                        <span>Entradas</span>
                        <strong class="positivo">R$ <?= number_format($totalEntrouDinheiro, 2, ',', '.') ?></strong>
                    </div>

                    <div class="forma-linha">
                        <span>Saídas</span>
                        <strong class="negativo">R$ <?= number_format($totalSaidaDinheiro, 2, ',', '.') ?></strong>
                    </div>

                    <div class="forma-linha">
                        <span>Saldo</span>
                        <strong>R$ <?= number_format($saldoDinheiro, 2, ',', '.') ?></strong>
                    </div>

                    <form class="ajuste-rapido" method="POST" action="../controllers/FinanceiroController.php">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="acao" value="salvar">
                        <input type="hidden" name="categoria" value="Ajuste financeiro">
                        <input type="hidden" name="descricao" value="Ajuste manual - Dinheiro">
                        <input type="hidden" name="forma_pagamento" value="dinheiro">
                        <input type="hidden" name="data_movimento" value="<?= e($dataSelecionada) ?>">
                        <input type="hidden" name="observacao" value="Ajuste rápido feito na tela financeiro">

                        <label>Ajustar dinheiro</label>
                        <select name="tipo" required>
                            <option value="entrada">Somar entrada</option>
                            <option value="saida">Diminuir / saída</option>
                        </select>

                        <input type="text" name="valor" placeholder="0,00" required>

                        <button type="submit">Aplicar ajuste</button>
                    </form>
                </div>

                <div class="forma-resumo-card">
                    <h4>Pix</h4>

                    <div class="forma-linha">
                        <span>Entradas</span>
                        <strong class="positivo">R$ <?= number_format($totalEntrouPix, 2, ',', '.') ?></strong>
                    </div>

                    <div class="forma-linha">
                        <span>Saídas</span>
                        <strong class="negativo">R$ <?= number_format($totalSaidaPix, 2, ',', '.') ?></strong>
                    </div>

                    <div class="forma-linha">
                        <span>Saldo</span>
                        <strong>R$ <?= number_format($saldoPix, 2, ',', '.') ?></strong>
                    </div>

                    <form class="ajuste-rapido" method="POST" action="../controllers/FinanceiroController.php">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="acao" value="salvar">
                        <input type="hidden" name="categoria" value="Ajuste financeiro">
                        <input type="hidden" name="descricao" value="Ajuste manual - Pix">
                        <input type="hidden" name="forma_pagamento" value="pix">
                        <input type="hidden" name="data_movimento" value="<?= e($dataSelecionada) ?>">
                        <input type="hidden" name="observacao" value="Ajuste rápido feito na tela financeiro">

                        <label>Ajustar Pix</label>
                        <select name="tipo" required>
                            <option value="entrada">Somar entrada</option>
                            <option value="saida">Diminuir / saída</option>
                        </select>

                        <input type="text" name="valor" placeholder="0,00" required>

                        <button type="submit">Aplicar ajuste</button>
                    </form>
                </div>

                <div class="forma-resumo-card">
                    <h4>Cartão</h4>

                    <div class="forma-linha">
                        <span>Entradas</span>
                        <strong class="positivo">R$ <?= number_format($totalEntrouCartao, 2, ',', '.') ?></strong>
                    </div>

                    <div class="forma-linha">
                        <span>Saídas</span>
                        <strong class="negativo">R$ <?= number_format($totalSaidaCartao, 2, ',', '.') ?></strong>
                    </div>

                    <div class="forma-linha">
                        <span>Saldo</span>
                        <strong>R$ <?= number_format($saldoCartao, 2, ',', '.') ?></strong>
                    </div>

                    <form class="ajuste-rapido" method="POST" action="../controllers/FinanceiroController.php">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="acao" value="salvar">
                        <input type="hidden" name="categoria" value="Ajuste financeiro">
                        <input type="hidden" name="descricao" value="Ajuste manual - Cartão">
                        <input type="hidden" name="forma_pagamento" value="cartao">
                        <input type="hidden" name="data_movimento" value="<?= e($dataSelecionada) ?>">
                        <input type="hidden" name="observacao" value="Ajuste rápido feito na tela financeiro">

                        <label>Ajustar cartão</label>
                        <select name="tipo" required>
                            <option value="entrada">Somar entrada</option>
                            <option value="saida">Diminuir / saída</option>
                        </select>

                        <input type="text" name="valor" placeholder="0,00" required>

                        <button type="submit">Aplicar ajuste</button>
                    </form>
                </div>

            </div>

            <?php if ($totalVendas > 0 || count($movimentacoes) > 0): ?>
                <div class="tabela-responsiva">
                    <table>
                        <tr>
                            <th>Tipo</th>
                            <th>Categoria</th>
                            <th>Descrição</th>
                            <th>Forma</th>
                            <th>Valor</th>
                            <th>Observação</th>
                            <th>Ação</th>
                        </tr>

                        <?php if ($totalVendasDinheiro > 0): ?>
                            <tr>
                                <td><span class="badge badge-auto">Automático</span></td>
                                <td>Vendas</td>
                                <td>Vendas registradas no sistema</td>
                                <td>Dinheiro</td>
                                <td class="valor-entrada">
                                    + R$ <?= number_format($totalVendasDinheiro, 2, ',', '.') ?>
                                </td>
                                <td><?= $quantidadeVendas ?> venda(s) no dia</td>
                                <td>-</td>
                            </tr>
                        <?php endif; ?>

                        <?php if ($totalVendasPix > 0): ?>
                            <tr>
                                <td><span class="badge badge-auto">Automático</span></td>
                                <td>Vendas</td>
                                <td>Vendas registradas no sistema</td>
                                <td>Pix</td>
                                <td class="valor-entrada">
                                    + R$ <?= number_format($totalVendasPix, 2, ',', '.') ?>
                                </td>
                                <td><?= $quantidadeVendas ?> venda(s) no dia</td>
                                <td>-</td>
                            </tr>
                        <?php endif; ?>

                        <?php if ($totalVendasCartao > 0): ?>
                            <tr>
                                <td><span class="badge badge-auto">Automático</span></td>
                                <td>Vendas</td>
                                <td>Vendas registradas no sistema</td>
                                <td>Cartão</td>
                                <td class="valor-entrada">
                                    + R$ <?= number_format($totalVendasCartao, 2, ',', '.') ?>
                                </td>
                                <td><?= $quantidadeVendas ?> venda(s) no dia</td>
                                <td>-</td>
                            </tr>
                        <?php endif; ?>

                        <?php if ($totalVendasOutro > 0): ?>
                            <tr>
                                <td><span class="badge badge-auto">Automático</span></td>
                                <td>Vendas</td>
                                <td>Vendas registradas no sistema</td>
                                <td>Outro</td>
                                <td class="valor-entrada">
                                    + R$ <?= number_format($totalVendasOutro, 2, ',', '.') ?>
                                </td>
                                <td><?= $quantidadeVendas ?> venda(s) no dia</td>
                                <td>-</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($movimentacoes as $mov): ?>
                            <tr>
                                <td>
                                    <?php if ($mov['tipo'] === 'entrada'): ?>
                                        <span class="badge badge-entrada">Entrada</span>
                                    <?php else: ?>
                                        <span class="badge badge-saida">Saída</span>
                                    <?php endif; ?>
                                </td>

                                <td><?= e($mov['categoria']) ?></td>
                                <td><?= e($mov['descricao']) ?></td>
                                <td><?= e(ucfirst($mov['forma_pagamento'])) ?></td>

                                <td class="<?= $mov['tipo'] === 'entrada' ? 'valor-entrada' : 'valor-saida' ?>">
                                    <?= $mov['tipo'] === 'entrada' ? '+' : '-' ?>
                                    R$ <?= number_format((float)$mov['valor'], 2, ',', '.') ?>
                                </td>

                                <td><?= e($mov['observacao'] ?: '-') ?></td>

                                <td>
                                    <form 
                                        method="POST" 
                                        action="../controllers/FinanceiroController.php" 
                                        onsubmit="return confirm('Deseja excluir este lançamento?');"
                                    >
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="id" value="<?= (int)$mov['id'] ?>">
                                        <button class="btn-excluir" type="submit">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php else: ?>
                <div class="vazio">
                    Nenhuma movimentação encontrada para esse dia.
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

</div></div></div></body></html>