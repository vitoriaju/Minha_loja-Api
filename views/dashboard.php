<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$required_perfil = null;

require_once __DIR__ . '/../verifica_sessao.php';
require_once __DIR__ . '/../pdo.php';
require_once __DIR__ . '/../utils.php';

$is_admin = usuario_eh_admin();

/* Dados principais: consultas e cálculos existentes preservados. */
$stmt = $pdo->query('SELECT COUNT(*) AS total FROM produtos');
$total_produtos = $stmt->fetch()['total'] ?? 0;

$total_vendas = 0;
$qtd_vendas_dia = 0;
$total_dia = 0;
$ticket_medio = 0;
$ultimas_vendas = [];
$alertas_estoque = [];
$vencidos = [];
$vencer = [];
$total_alertas = 0;
$total_vencidos = 0;
$total_vencer = 0;

if ($is_admin) {
    $stmt = $pdo->query('SELECT COUNT(*) AS total FROM vendas');
    $total_vendas = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->query("
        SELECT COUNT(*) AS quantidade, COALESCE(SUM(valor_total), 0) AS total
        FROM vendas
        WHERE DATE(data_venda) = CURDATE()
    ");
    $vendas_dia = $stmt->fetch();
    $qtd_vendas_dia = $vendas_dia['quantidade'] ?? 0;
    $total_dia = $vendas_dia['total'] ?? 0;
    $ticket_medio = $qtd_vendas_dia > 0 ? $total_dia / $qtd_vendas_dia : 0;

    $stmt = $pdo->query("
        SELECT nome, estoque, estoque_minimo, unidade_medida
        FROM produtos
        WHERE estoque <= estoque_minimo
        ORDER BY estoque ASC
    ");
    $alertas_estoque = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_alertas = count($alertas_estoque);

    $stmt = $pdo->query("
        SELECT p.nome, l.validade
        FROM lotes_estoque l
        JOIN produtos p ON p.id = l.produto_id
        WHERE l.quantidade_restante > 0
          AND l.validade IS NOT NULL
          AND l.validade < CURDATE()
        ORDER BY l.validade ASC
    ");
    $vencidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_vencidos = count($vencidos);

    $stmt = $pdo->query("
        SELECT p.nome, l.validade
        FROM lotes_estoque l
        JOIN produtos p ON p.id = l.produto_id
        WHERE l.quantidade_restante > 0
          AND l.validade IS NOT NULL
          AND l.validade BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ORDER BY l.validade ASC
    ");
    $vencer = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_vencer = count($vencer);

    $stmt = $pdo->query("
        SELECT id, valor_total, forma_pagamento, data_venda
        FROM vendas
        ORDER BY data_venda DESC, id DESC
        LIMIT 5
    ");
    $ultimas_vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$classePagamento = static function ($forma): string {
    $normalizada = strtolower(trim((string)$forma));
    return [
        'dinheiro' => 'pagamento-dinheiro',
        'pix' => 'pagamento-pix',
        'cartao' => 'pagamento-cartao',
        'cartão' => 'pagamento-cartao',
    ][$normalizada] ?? 'pagamento-outro';
};

$rotuloPagamento = static function ($forma): string {
    $valor = trim((string)$forma);
    return $valor !== '' ? ucfirst($valor) : 'Não informado';
};

$page_styles = ['assets/dashboard.css'];
include __DIR__ . '/layout.php';
?>

<div class="dashboard-page">
    <header class="dashboard-cabecalho">
        <div>
            <span class="dashboard-eyebrow"><?= $is_admin ? 'Painel administrativo' : 'Painel operacional' ?></span>
            <h1>Visão geral</h1>
            <p><?= e(date('d/m/Y')) ?> · <?= $is_admin ? 'Vendas, produtos e alertas importantes em um só lugar.' : 'Acesso rápido às rotinas permitidas para o seu perfil.' ?></p>
        </div>
        <nav class="dashboard-acoes" aria-label="Ações rápidas">
            <?php if (usuario_pode('vendas.criar')): ?><a class="dashboard-btn principal" href="vender.php">Nova venda</a><?php endif; ?>
            <?php if (usuario_pode('produtos.gerenciar')): ?><a class="dashboard-btn secundario" href="cadastrar_Produto.php">Cadastrar produto</a><?php endif; ?>
            <?php if (usuario_pode('produtos.ver')): ?><a class="dashboard-btn discreto" href="listar_produtos_api.php">Consultar produtos</a><?php endif; ?>
        </nav>
    </header>

    <?php if ($is_admin): ?>
        <section class="dashboard-resumo" aria-label="Resumo principal do dia">
            <a class="resumo-card destaque" href="vendas_dia.php">
                <span class="resumo-icone" aria-hidden="true">$</span>
                <span class="resumo-label">Vendas de hoje</span>
                <strong>R$ <?= number_format($total_dia, 2, ',', '.') ?></strong>
                <small><?= e($qtd_vendas_dia) ?> venda(s) realizada(s)</small>
            </a>
            <article class="resumo-card">
                <span class="resumo-icone" aria-hidden="true">≈</span>
                <span class="resumo-label">Ticket médio</span>
                <strong>R$ <?= number_format($ticket_medio, 2, ',', '.') ?></strong>
                <small>Média das vendas de hoje</small>
            </article>
            <a class="resumo-card" href="listar_produtos_api.php">
                <span class="resumo-icone" aria-hidden="true">□</span>
                <span class="resumo-label">Produtos cadastrados</span>
                <strong><?= e($total_produtos) ?></strong>
                <small>Itens disponíveis no cadastro</small>
            </a>
        </section>

        <section class="dashboard-alertas" aria-labelledby="tituloAlertas">
            <div class="section-heading">
                <div><span class="section-eyebrow">Operação</span><h2 id="tituloAlertas">Atenção necessária</h2></div>
            </div>

            <?php if ($total_alertas == 0 && $total_vencidos == 0 && $total_vencer == 0): ?>
                <div class="alertas-ok"><span aria-hidden="true">✓</span><strong>Tudo certo. Nenhum alerta importante no momento.</strong></div>
            <?php else: ?>
                <div class="alertas-lista">
                    <?php if ($total_alertas > 0 && usuario_pode('estoque.gerenciar')): ?>
                        <a class="alerta-linha atencao" href="estoque_baixo.php"><span class="alerta-indicador" aria-hidden="true"></span><span><strong>Estoque baixo</strong><small>Produtos abaixo do mínimo definido</small></span><b><?= e($total_alertas) ?></b><span class="alerta-seta" aria-hidden="true">›</span></a>
                    <?php endif; ?>
                    <?php if ($total_vencidos > 0 && usuario_pode('produtos.validade')): ?>
                        <a class="alerta-linha problema" href="vencidos.php"><span class="alerta-indicador" aria-hidden="true"></span><span><strong>Produtos vencidos</strong><small>Lotes vencidos com saldo em estoque</small></span><b><?= e($total_vencidos) ?></b><span class="alerta-seta" aria-hidden="true">›</span></a>
                    <?php endif; ?>
                    <?php if ($total_vencer > 0 && usuario_pode('produtos.validade')): ?>
                        <a class="alerta-linha atencao" href="validade.php"><span class="alerta-indicador" aria-hidden="true"></span><span><strong>Próximos do vencimento</strong><small>Produtos que vencem nos próximos sete dias</small></span><b><?= e($total_vencer) ?></b><span class="alerta-seta" aria-hidden="true">›</span></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="dashboard-vendas" aria-labelledby="tituloUltimasVendas">
            <div class="section-heading vendas-heading">
                <div><span class="section-eyebrow">Movimentação</span><h2 id="tituloUltimasVendas">Últimas vendas</h2><p>Total acumulado: <strong><?= e($total_vendas) ?> vendas</strong></p></div>
                <?php if (usuario_pode('vendas.historico')): ?><a class="section-link" href="historico_vendas.php">Ver histórico</a><?php endif; ?>
            </div>

            <?php if (count($ultimas_vendas) > 0): ?>
                <div class="dashboard-tabela-wrap">
                    <table class="dashboard-tabela">
                        <thead><tr><th>Venda</th><th>Data e hora</th><th>Pagamento</th><th>Valor total</th></tr></thead>
                        <tbody>
                            <?php foreach ($ultimas_vendas as $v): ?>
                                <tr>
                                    <td data-label="Venda"><strong>#<?= e($v['id']) ?></strong></td>
                                    <td data-label="Data e hora"><?= e(date('d/m/Y H:i', strtotime($v['data_venda']))) ?></td>
                                    <td data-label="Pagamento"><span class="pagamento-badge <?= e($classePagamento($v['forma_pagamento'])) ?>"><?= e($rotuloPagamento($v['forma_pagamento'])) ?></span></td>
                                    <td data-label="Valor total" class="venda-valor">R$ <?= number_format((float)$v['valor_total'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="dashboard-vazio"><span aria-hidden="true">○</span><strong>Nenhuma venda registrada ainda.</strong><small>As vendas mais recentes aparecerão aqui.</small></div>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <section class="dashboard-operacional" aria-labelledby="tituloOperacional">
            <div class="section-heading"><div><span class="section-eyebrow">Atalhos</span><h2 id="tituloOperacional">Rotinas operacionais</h2></div></div>
            <div class="operacional-grid">
                <?php if (usuario_pode('vendas.criar')): ?><a class="operacional-link" href="vender.php"><span aria-hidden="true">$</span><strong>Nova venda</strong><small>Abra o caixa e registre uma venda.</small></a><?php endif; ?>
                <?php if (usuario_pode('produtos.ver')): ?><a class="operacional-link" href="listar_produtos_api.php"><span aria-hidden="true">□</span><strong>Consultar produtos</strong><small><?= e($total_produtos) ?> produto(s) cadastrado(s).</small></a><?php endif; ?>
                <?php if (usuario_pode('vendas.dia')): ?><a class="operacional-link" href="vendas_dia.php"><span aria-hidden="true">◷</span><strong>Vendas do dia</strong><small>Consulte os registros operacionais de hoje.</small></a><?php endif; ?>
                <?php if (usuario_pode('produtos.validade')): ?><a class="operacional-link" href="vencidos.php"><span aria-hidden="true">!</span><strong>Produtos vencidos</strong><small>Consulte os lotes que exigem atenção.</small></a><?php endif; ?>
                <?php if (usuario_pode('produtos.validade')): ?><a class="operacional-link" href="validade.php"><span aria-hidden="true">⌛</span><strong>Validade</strong><small>Veja produtos próximos do vencimento.</small></a><?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

</div></div></div></body></html>
