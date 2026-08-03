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

/* Vendas do dia: consulta e cálculos preservados. */
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS quantidade_vendas,
        COALESCE(SUM(valor_total), 0) AS total_vendas,
        COALESCE(SUM(CASE WHEN LOWER(forma_pagamento) = 'dinheiro' THEN valor_total ELSE 0 END), 0) AS total_dinheiro,
        COALESCE(SUM(CASE WHEN LOWER(forma_pagamento) = 'pix' THEN valor_total ELSE 0 END), 0) AS total_pix,
        COALESCE(SUM(CASE WHEN LOWER(forma_pagamento) IN ('cartao', 'cartão') THEN valor_total ELSE 0 END), 0) AS total_cartao,
        COALESCE(SUM(CASE WHEN LOWER(forma_pagamento) NOT IN ('dinheiro', 'pix', 'cartao', 'cartão') THEN valor_total ELSE 0 END), 0) AS total_outro
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

/* Movimentações manuais: consulta e cálculos preservados. */
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
$totalEntrouCartaoEPix = $totalEntrouCartao + $totalEntrouPix;
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

// Indicador visual; não altera fechamento, persistência ou demais cálculos.
$resultadoDia = $totalEntradas - $totalSaidasCaixa;
$dataFormatada = date('d/m/Y', strtotime($dataSelecionada));
$page_styles = ['assets/financeiro.css'];
$page_scripts = ['assets/financeiro.js'];

include __DIR__ . '/layout.php';
?>

<div class="financeiro-page" data-financeiro>
    <section class="financeiro-cabecalho">
        <div class="financeiro-identificacao">
            <span class="financeiro-eyebrow">Gestão financeira</span>
            <h1>Financeiro</h1>
            <p>Movimentações de <strong><?= e($dataFormatada) ?></strong></p>
        </div>

        <div class="financeiro-controles">
            <a class="financeiro-btn financeiro-btn-secundario" href="financeiro.php?data=<?= e(date('Y-m-d')) ?>">Hoje</a>
            <form class="financeiro-data-form" method="GET" action="financeiro.php">
                <label for="financeiroData">Consultar data</label>
                <div>
                    <input id="financeiroData" type="date" name="data" value="<?= e($dataSelecionada) ?>">
                    <button type="submit">Buscar</button>
                </div>
            </form>
        </div>

        <nav class="financeiro-abas" aria-label="Período do relatório">
            <a class="ativa" aria-current="page" href="financeiro.php?data=<?= e($dataSelecionada) ?>">Diário</a>
            <a href="financeiro_mensal.php?mes=<?= e(substr($dataSelecionada, 0, 7)) ?>">Mensal</a>
            <a href="financeiro_anual.php?ano=<?= e(substr($dataSelecionada, 0, 4)) ?>">Anual</a>
        </nav>
    </section>

    <?php if ($sucesso): ?>
        <div class="financeiro-alerta sucesso" role="status"><?= e($sucesso) ?></div>
    <?php endif; ?>
    <?php if ($erro): ?>
        <div class="financeiro-alerta erro" role="alert"><?= e($erro) ?></div>
    <?php endif; ?>

    <section class="financeiro-cards" aria-label="Resumo do dia">
        <article class="financeiro-card positivo">
            <span>Entradas do dia</span>
            <strong>R$ <?= number_format($totalEntradas, 2, ',', '.') ?></strong>
            <small><?= e($quantidadeVendas) ?> venda(s) e entradas manuais</small>
        </article>
        <article class="financeiro-card negativo">
            <span>Saídas do caixa</span>
            <strong>R$ <?= number_format($totalSaidasCaixa, 2, ',', '.') ?></strong>
            <small>Movimentos incluídos no fechamento</small>
        </article>
        <article class="financeiro-card <?= $resultadoDia < 0 ? 'negativo' : 'positivo' ?>">
            <span>Resultado do dia</span>
            <strong><?= $resultadoDia < 0 ? '- ' : '' ?>R$ <?= number_format(abs($resultadoDia), 2, ',', '.') ?></strong>
            <small>Entradas menos saídas do caixa</small>
        </article>
    </section>

    <section class="financeiro-resumo-compacto" aria-labelledby="resumoPagamentos">
        <div class="resumo-compacto-titulo">
            <h2 id="resumoPagamentos">Resumo de pagamentos</h2>
            <span>Totais recebidos e informados</span>
        </div>
        <dl>
            <div><dt>Dinheiro</dt><dd>R$ <?= number_format($totalEntrouDinheiro, 2, ',', '.') ?></dd></div>
            <div><dt>Cartão</dt><dd>R$ <?= number_format($totalEntrouCartaoEPix, 2, ',', '.') ?></dd><small>Pix + cartão</small></div>
            <div><dt>Total da manhã</dt><dd>R$ <?= number_format($totalManhaExibido, 2, ',', '.') ?></dd></div>
            <div><dt>Total da tarde</dt><dd>R$ <?= number_format($totalTardeExibido, 2, ',', '.') ?></dd></div>
        </dl>
        <p class="resumo-compacto-nota">Fora do caixa: <strong>R$ <?= number_format($totalSaidasNaoCaixa, 2, ',', '.') ?></strong> · Total diário informado: <strong>R$ <?= number_format($totalDiaExibido, 2, ',', '.') ?></strong></p>
    </section>

    <section class="financeiro-lancamento">
        <button class="financeiro-toggle-form" type="button" data-form-toggle aria-expanded="<?= $erro ? 'true' : 'false' ?>" aria-controls="novoLancamento">
            <span data-toggle-icon><?= $erro ? '−' : '+' ?></span>
            <span data-toggle-label><?= $erro ? 'Fechar lançamento' : 'Novo lançamento' ?></span>
        </button>

        <div id="novoLancamento" class="financeiro-form-panel" data-form-panel <?= $erro ? '' : 'hidden' ?>>
            <div class="financeiro-section-heading">
                <div><h2>Novo lançamento</h2><p>Cadastre uma entrada extra ou uma saída.</p></div>
            </div>
            <form method="POST" action="../controllers/FinanceiroController.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="acao" value="salvar">

                <div class="financeiro-form-grid">
                    <div class="financeiro-campo"><label for="tipo">Tipo</label><select id="tipo" name="tipo" required><option value="saida">Saída / Gasto</option><option value="entrada">Entrada extra</option></select></div>
                    <div class="financeiro-campo"><label for="categoria">Categoria</label><select id="categoria" name="categoria" required><option value="Compra de mercadoria">Compra de mercadoria</option><option value="Fornecedor">Fornecedor</option><option value="Conta fixa">Conta fixa</option><option value="Despesa do dia">Despesa do dia</option><option value="Retirada do caixa">Retirada do caixa</option><option value="Entrada extra">Entrada extra</option><option value="Ajuste financeiro">Ajuste financeiro</option><option value="Outro">Outro</option></select></div>
                    <div class="financeiro-campo campo-largo"><label for="descricao">Descrição</label><input id="descricao" type="text" name="descricao" placeholder="Ex: Compra de leite, pagamento de fornecedor..." required></div>
                    <div class="financeiro-campo campo-largo"><label for="responsavel">Responsável ou nome</label><input id="responsavel" type="text" name="responsavel" maxlength="120" placeholder="Ex.: Adriana, Ana Raquel..."></div>
                    <div class="financeiro-campo"><label for="valor">Valor</label><input id="valor" type="text" name="valor" inputmode="decimal" placeholder="0,00" required></div>
                    <div class="financeiro-campo"><label for="formaPagamento">Forma</label><select id="formaPagamento" name="forma_pagamento" required><option value="dinheiro">Dinheiro</option><option value="pix">Pix</option><option value="cartao">Cartão</option><option value="outro">Outro</option></select></div>
                    <div class="financeiro-campo"><label for="dataMovimento">Data</label><input id="dataMovimento" type="date" name="data_movimento" value="<?= e($dataSelecionada) ?>" required></div>
                    <div class="financeiro-campo"><label for="turno">Turno</label><select id="turno" name="turno"><option value="geral">Geral</option><option value="manha">Manhã</option><option value="tarde">Tarde</option></select></div>
                    <div class="financeiro-campo campo-largo"><label for="observacao">Observação</label><textarea id="observacao" name="observacao" placeholder="Opcional"></textarea></div>
                    <label class="financeiro-check campo-largo"><input type="checkbox" name="incluir_fechamento" checked><span>Contabilizar no fechamento do dia<small>Desmarque para dinheiro externo ou valores que não pertencem ao movimento do dia.</small></span></label>
                </div>
                <div class="financeiro-form-actions"><button class="financeiro-btn-salvar" type="submit">Salvar lançamento</button></div>
            </form>
        </div>
    </section>

    <section class="financeiro-historico">
        <div class="financeiro-section-heading historico-heading">
            <div><h2>Movimentações do dia</h2><p>Registros de <?= e($dataFormatada) ?>.</p></div>
            <?php if ($totalVendas > 0 || count($movimentacoes) > 0): ?>
                <div class="financeiro-filtros" role="group" aria-label="Filtrar movimentações">
                    <button type="button" class="ativo" data-filter="all">Todas</button>
                    <button type="button" data-filter="entrada">Entradas</button>
                    <button type="button" data-filter="saida">Saídas</button>
                    <button type="button" data-filter="dinheiro">Dinheiro</button>
                    <button type="button" data-filter="pix">Pix</button>
                    <button type="button" data-filter="cartao">Cartão</button>
                    <button type="button" data-filter="clear">Limpar</button>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($totalVendas > 0 || count($movimentacoes) > 0): ?>
            <div class="financeiro-tabela-wrap">
                <table class="financeiro-tabela">
                    <thead><tr><th>Descrição</th><th>Categoria</th><th>Forma</th><th>Valor</th><th>Ação</th></tr></thead>
                    <tbody data-movement-rows>
                        <?php
                        $vendasPorForma = [
                            ['forma' => 'dinheiro', 'rotulo' => 'Dinheiro', 'valor' => $totalVendasDinheiro],
                            ['forma' => 'pix', 'rotulo' => 'Pix', 'valor' => $totalVendasPix],
                            ['forma' => 'cartao', 'rotulo' => 'Cartão', 'valor' => $totalVendasCartao],
                            ['forma' => 'outro', 'rotulo' => 'Outro', 'valor' => $totalVendasOutro],
                        ];
                        foreach ($vendasPorForma as $vendaForma):
                            if ($vendaForma['valor'] <= 0) continue;
                        ?>
                            <tr data-movement-row data-tipo="entrada" data-forma="<?= e($vendaForma['forma']) ?>">
                                <td><strong>Vendas registradas no sistema</strong><details><summary>Ver detalhes</summary><dl><div><dt>Tipo</dt><dd>Automático</dd></div><div><dt>Turno</dt><dd>Geral</dd></div><div><dt>Observação</dt><dd><?= e($quantidadeVendas) ?> venda(s) no dia</dd></div><div><dt>Fechamento</dt><dd>Sim</dd></div></dl></details></td>
                                <td>Vendas</td><td><?= e($vendaForma['rotulo']) ?></td><td class="valor-entrada">+ R$ <?= number_format($vendaForma['valor'], 2, ',', '.') ?></td><td><span class="acao-indisponivel">—</span></td>
                            </tr>
                        <?php endforeach; ?>

                        <?php foreach ($movimentacoes as $mov): ?>
                            <?php $formaFiltro = strtolower($mov['forma_pagamento']) === 'cartão' ? 'cartao' : strtolower($mov['forma_pagamento']); ?>
                            <tr data-movement-row data-tipo="<?= e($mov['tipo']) ?>" data-forma="<?= e($formaFiltro) ?>">
                                <td>
                                    <strong><?= e($mov['descricao']) ?></strong>
                                    <details><summary>Ver detalhes</summary><dl>
                                        <div><dt>Tipo</dt><dd><?= $mov['tipo'] === 'entrada' ? 'Entrada' : 'Saída' ?></dd></div>
                                        <div><dt>Data</dt><dd><?= e(date('d/m/Y', strtotime($mov['data_movimento']))) ?></dd></div>
                                        <div><dt>Turno</dt><dd><?= e(($mov['turno'] ?? 'geral') === 'manha' ? 'Manhã' : ucfirst($mov['turno'] ?? 'geral')) ?></dd></div>
                                        <div><dt>Responsável</dt><dd><?= e($mov['responsavel'] ?: '—') ?></dd></div>
                                        <div><dt>Observação</dt><dd><?= e($mov['observacao'] ?: '—') ?></dd></div>
                                        <div><dt>Fechamento</dt><dd><?= (int)$mov['incluir_fechamento'] === 1 ? 'Sim' : 'Não' ?></dd></div>
                                    </dl></details>
                                </td>
                                <td><?= e($mov['categoria']) ?></td>
                                <td><?= e(ucfirst($mov['forma_pagamento'])) ?></td>
                                <td class="<?= $mov['tipo'] === 'entrada' ? 'valor-entrada' : 'valor-saida' ?>"><?= $mov['tipo'] === 'entrada' ? '+' : '-' ?> R$ <?= number_format((float)$mov['valor'], 2, ',', '.') ?></td>
                                <td><form method="POST" action="../controllers/FinanceiroController.php" onsubmit="return confirm('Deseja excluir este lançamento?');"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= (int)$mov['id'] ?>"><button class="financeiro-btn-excluir" type="submit">Excluir</button></form></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="financeiro-sem-filtro" data-filter-empty hidden>Nenhuma movimentação corresponde ao filtro selecionado.</div>
        <?php else: ?>
            <div class="financeiro-vazio"><strong>Nenhuma movimentação neste dia.</strong><span>Use “Novo lançamento” para adicionar o primeiro registro.</span></div>
        <?php endif; ?>
    </section>
</div>

</div></div></div></body></html>
