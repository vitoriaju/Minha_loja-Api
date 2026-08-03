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
$totalEntradasFechamentoManuais = 0;
$totalSaidasCaixa = 0;
$totalSaidasNaoCaixa = 0;
$totalManha = 0;
$totalTarde = 0;

$totalEntradaManualDinheiro = 0;
$totalEntradaManualPix = 0;
$totalEntradaManualCartao = 0;
$totalEntradaManualOutro = 0;

foreach ($movimentacoes as $mov) {
    $valorMov = (float)$mov['valor'];
    $forma = strtolower($mov['forma_pagamento']);

    if ($mov['tipo'] === 'entrada') {
        $totalEntradasManuais += $valorMov;

        if ((int)$mov['incluir_fechamento'] === 1) {
            $totalEntradasFechamentoManuais += $valorMov;
        }

        if ($forma === 'dinheiro') {
            $totalEntradaManualDinheiro += $valorMov;
        } elseif ($forma === 'pix') {
            $totalEntradaManualPix += $valorMov;
        } elseif ($forma === 'cartao' || $forma === 'cartão') {
            $totalEntradaManualCartao += $valorMov;
        } else {
            $totalEntradaManualOutro += $valorMov;
        }

    } elseif ((int)$mov['incluir_fechamento'] === 1) {
        $totalSaidasCaixa += $valorMov;
    } else {
        $totalSaidasNaoCaixa += $valorMov;
    }

    if ($mov['tipo'] === 'entrada' || (int)$mov['incluir_fechamento'] === 1) {
        if (($mov['turno'] ?? 'geral') === 'manha') $totalManha += $valorMov;
        if (($mov['turno'] ?? 'geral') === 'tarde') $totalTarde += $valorMov;
    }
}

$totalEntrouDinheiro = $totalVendasDinheiro + $totalEntradaManualDinheiro;
$totalEntrouPix = $totalVendasPix + $totalEntradaManualPix;
$totalEntrouCartao = $totalVendasCartao + $totalEntradaManualCartao;
$totalEntrouOutro = $totalVendasOutro + $totalEntradaManualOutro;

$totalEntradas = $totalEntrouDinheiro + $totalEntrouPix + $totalEntrouCartao + $totalEntrouOutro;
$faturamentoDia = $totalVendas + $totalEntradasFechamentoManuais;
$totalDiaInformado = $totalManha + $totalTarde;

$stmt = $pdo->prepare('SELECT total_manha_informado, total_tarde_informado, total_dia_informado FROM fechamentos_diarios WHERE data_fechamento = ? LIMIT 1');
$stmt->execute([$dataSelecionada]);
$totaisDeclarados = $stmt->fetch() ?: [];
$totalManhaDeclarado = $totaisDeclarados['total_manha_informado'] ?? null;
$totalTardeDeclarado = $totaisDeclarados['total_tarde_informado'] ?? null;
$totalDiaDeclarado = $totaisDeclarados['total_dia_informado'] ?? null;
$totalManhaExibido = $totalManhaDeclarado !== null ? (float)$totalManhaDeclarado : $totalManha;
$totalTardeExibido = $totalTardeDeclarado !== null ? (float)$totalTardeDeclarado : $totalTarde;
$totalDiaExibido = $totalDiaDeclarado !== null ? (float)$totalDiaDeclarado : ($totalDiaInformado > 0 ? $totalDiaInformado : $faturamentoDia);

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
.rf-abas{display:flex;gap:8px;background:#fff;padding:8px;border-radius:12px;width:max-content;max-width:100%;box-shadow:0 3px 10px #0001}.rf-abas a{padding:10px 15px;border-radius:8px;text-decoration:none;color:#5a371a;font-weight:bold}.rf-abas a.ativa{background:#7b4f27;color:#fff}


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
    grid-template-columns:repeat(auto-fit, minmax(210px, 1fr));
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
}

@media(max-width:800px){
    .cards-financeiro{
        grid-template-columns:1fr;
    }
}
</style>

<div class="financeiro-page">

    <nav class="rf-abas"><a class="ativa" href="financeiro.php?data=<?=e($dataSelecionada)?>">Diario</a><a href="financeiro_mensal.php?mes=<?=e(substr($dataSelecionada,0,7))?>">Mensal</a><a href="financeiro_anual.php?ano=<?=e(substr($dataSelecionada,0,4))?>">Anual</a></nav>

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
            <span>Entradas Recebidas</span>
            <strong>R$ <?= number_format($totalEntradas, 2, ',', '.') ?></strong>
            <small>Cartao, Pix, dinheiro e outras entradas</small>
        </div>

        <div class="card-financeiro" style="border-left-color:#3977b7">
            <span>Cartão</span>
            <strong>R$ <?= number_format($totalEntrouCartao, 2, ',', '.') ?></strong>
            <small>Cartões da data selecionada</small>
        </div>

        <div class="card-financeiro borda-vermelha">
            <span>Total Saidas Caixa</span>
            <strong>R$ <?= number_format($totalSaidasCaixa, 2, ',', '.') ?></strong>
            <small>Saidas marcadas para o fechamento</small>
        </div>

        <div class="card-financeiro borda-laranja">
            <span>Total Saidas Nao Caixa</span>
            <strong>R$ <?= number_format($totalSaidasNaoCaixa, 2, ',', '.') ?></strong>
            <small>Saidas fora do fechamento</small>
        </div>

        <div class="card-financeiro borda-verde">
            <span>Faturamento do Dia</span>
            <strong>R$ <?= number_format($totalDiaExibido, 2, ',', '.') ?></strong>
            <small>Entradas recebidas + retiradas do caixa</small>
        </div>

        <div class="card-financeiro"><span>Total Manha</span><strong>R$ <?= number_format($totalManhaExibido, 2, ',', '.') ?></strong><small>Total informado para a manha</small></div>
        <div class="card-financeiro"><span>Total Tarde</span><strong>R$ <?= number_format($totalTardeExibido, 2, ',', '.') ?></strong><small>Total informado para a tarde</small></div>

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

                <label>Responsavel ou nome</label>
                <input type="text" name="responsavel" maxlength="120" placeholder="Ex.: Adriana, Ana Raquel...">

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

                <label>Turno</label>
                <select name="turno"><option value="geral">Geral</option><option value="manha">Manha</option><option value="tarde">Tarde</option></select>

                <label>Observação</label>
                <textarea name="observacao" placeholder="Opcional"></textarea>

                <label style="display:flex;gap:9px;align-items:flex-start;background:#fff7ef;padding:12px;border-radius:9px;margin-bottom:15px">
                    <input type="checkbox" name="incluir_fechamento" checked style="width:auto;margin:3px 0 0">
                    <span>Contabilizar no fechamento do dia<br><small style="color:#777">Desmarque para dinheiro externo ou valores que nao pertencem ao movimento do dia.</small></span>
                </label>

                <button class="btn-salvar" type="submit">Salvar lançamento</button>
            </form>
        </div>

        <div class="tabela-card">
            <h3>Movimentações do dia</h3>
            <p>Resumo das movimentações registradas no dia <?= e($dataFormatada) ?>.</p>

            <?php if ($totalVendas > 0 || count($movimentacoes) > 0): ?>
                <div class="tabela-responsiva">
                    <table>
                        <tr>
                            <th>Tipo</th>
                            <th>Categoria</th>
                            <th>Descrição</th>
                            <th>Forma</th>
                            <th>Turno</th>
                            <th>Valor</th>
                            <th>Observação</th>
                            <th>Fechamento</th>
                            <th>Ação</th>
                        </tr>

                        <?php if ($totalVendasDinheiro > 0): ?>
                            <tr>
                                <td><span class="badge badge-auto">Automático</span></td>
                                <td>Vendas</td>
                                <td>Vendas registradas no sistema</td>
                                <td>Dinheiro</td>
                                <td>Geral</td>
                                <td class="valor-entrada">
                                    + R$ <?= number_format($totalVendasDinheiro, 2, ',', '.') ?>
                                </td>
                                <td><?= $quantidadeVendas ?> venda(s) no dia</td>
                                <td>Sim</td>
                                <td>-</td>
                            </tr>
                        <?php endif; ?>

                        <?php if ($totalVendasPix > 0): ?>
                            <tr>
                                <td><span class="badge badge-auto">Automático</span></td>
                                <td>Vendas</td>
                                <td>Vendas registradas no sistema</td>
                                <td>Pix</td>
                                <td>Geral</td>
                                <td class="valor-entrada">
                                    + R$ <?= number_format($totalVendasPix, 2, ',', '.') ?>
                                </td>
                                <td><?= $quantidadeVendas ?> venda(s) no dia</td>
                                <td>Sim</td>
                                <td>-</td>
                            </tr>
                        <?php endif; ?>

                        <?php if ($totalVendasCartao > 0): ?>
                            <tr>
                                <td><span class="badge badge-auto">Automático</span></td>
                                <td>Vendas</td>
                                <td>Vendas registradas no sistema</td>
                                <td>Cartão</td>
                                <td>Geral</td>
                                <td class="valor-entrada">
                                    + R$ <?= number_format($totalVendasCartao, 2, ',', '.') ?>
                                </td>
                                <td><?= $quantidadeVendas ?> venda(s) no dia</td>
                                <td>Sim</td>
                                <td>-</td>
                            </tr>
                        <?php endif; ?>

                        <?php if ($totalVendasOutro > 0): ?>
                            <tr>
                                <td><span class="badge badge-auto">Automático</span></td>
                                <td>Vendas</td>
                                <td>Vendas registradas no sistema</td>
                                <td>Outro</td>
                                <td>Geral</td>
                                <td class="valor-entrada">
                                    + R$ <?= number_format($totalVendasOutro, 2, ',', '.') ?>
                                </td>
                                <td><?= $quantidadeVendas ?> venda(s) no dia</td>
                                <td>Sim</td>
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
                                <td><?= e($mov['descricao']) ?><?= $mov['responsavel'] ? '<br><small>' . e($mov['responsavel']) . '</small>' : '' ?></td>
                                <td><?= e(ucfirst($mov['forma_pagamento'])) ?></td>
                                <td><?= e(($mov['turno'] ?? 'geral') === 'manha' ? 'Manha' : ucfirst($mov['turno'] ?? 'geral')) ?></td>

                                <td class="<?= $mov['tipo'] === 'entrada' ? 'valor-entrada' : 'valor-saida' ?>">
                                    <?= $mov['tipo'] === 'entrada' ? '+' : '-' ?>
                                    R$ <?= number_format((float)$mov['valor'], 2, ',', '.') ?>
                                </td>

                                <td><?= e($mov['observacao'] ?: '-') ?></td>

                                <td><?= (int)$mov['incluir_fechamento'] === 1 ? 'Sim' : 'Não' ?></td>

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
