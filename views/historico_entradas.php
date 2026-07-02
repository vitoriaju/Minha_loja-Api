<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_admin();
require_once __DIR__ . '/../pdo.php';
require_once __DIR__ . '/../utils.php';

$busca = trim($_GET['busca'] ?? '');
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';

$where = [];
$params = [];

if ($busca !== '') {
    $where[] = "(e.numero_nota LIKE ? OR e.fornecedor LIKE ? OR p.nome LIKE ?)";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

if ($data_inicio !== '') {
    $where[] = "DATE(e.data_entrada) >= ?";
    $params[] = $data_inicio;
}

if ($data_fim !== '') {
    $where[] = "DATE(e.data_entrada) <= ?";
    $params[] = $data_fim;
}

$whereSql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $pdo->prepare("
    SELECT 
        e.id,
        e.numero_nota,
        e.fornecedor,
        e.data_entrada,
        i.produto_id,
        i.quantidade,
        i.validade,
        i.preco,
        p.nome,
        p.unidade_medida,
        p.categoria
    FROM entradas e
    JOIN itens_entrada i ON e.id = i.entrada_id
    JOIN produtos p ON p.id = i.produto_id
    {$whereSql}
    ORDER BY e.data_entrada DESC, e.id DESC, p.nome ASC
");

$stmt->execute($params);
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

$entradas = [];

foreach ($dados as $d) {
    $id = $d['id'];

    if (!isset($entradas[$id])) {
        $entradas[$id] = [
            'id' => $d['id'],
            'numero_nota' => $d['numero_nota'],
            'fornecedor' => $d['fornecedor'],
            'data' => $d['data_entrada'],
            'itens' => [],
            'total_valor' => 0,
            'total_quantidade' => 0
        ];
    }

    $subtotal = (float)$d['quantidade'] * (float)$d['preco'];

    $d['subtotal'] = $subtotal;

    $entradas[$id]['itens'][] = $d;
    $entradas[$id]['total_valor'] += $subtotal;
    $entradas[$id]['total_quantidade'] += (float)$d['quantidade'];
}

$total_notas = count($entradas);
$total_itens = 0;
$total_geral = 0;

foreach ($entradas as $entrada) {
    $total_itens += count($entrada['itens']);
    $total_geral += $entrada['total_valor'];
}

function formatarQuantidadeHistorico($quantidade, $unidade)
{
    if ($unidade === 'kg') {
        return number_format((float)$quantidade, 3, ',', '.') . ' kg';
    }

    return number_format((float)$quantidade, 0, ',', '.') . ' un';
}

function formatarDataHistorico($data)
{
    if (!$data) {
        return '-';
    }

    return date('d/m/Y', strtotime($data));
}

include __DIR__ . '/layout.php';
?>

<style>
.historico-page{
    display:flex;
    flex-direction:column;
    gap:22px;
}

.historico-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:20px;
    flex-wrap:wrap;
}

.historico-title h2{
    font-size:28px;
    color:#3b2411;
    margin-bottom:6px;
}

.historico-title p{
    color:#777;
    font-size:15px;
}

.historico-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.hist-btn,
.hist-btn-link{
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

.hist-btn-primary{
    background:#7b4f27;
    color:white;
}

.hist-btn-primary:hover{
    background:#5a371a;
    transform:translateY(-2px);
}

.hist-btn-secondary{
    background:white;
    color:#7b4f27;
    border:1px solid #d6b089;
}

.hist-btn-secondary:hover{
    background:#fff7ef;
    transform:translateY(-2px);
}

.hist-btn-print{
    background:#2e8b57;
    color:white;
}

.hist-btn-print:hover{
    background:#247046;
    transform:translateY(-2px);
}

.hist-stats{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(210px, 1fr));
    gap:16px;
}

.hist-stat-card{
    background:white;
    border-radius:16px;
    padding:18px;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
    border-left:6px solid #7b4f27;
}

.hist-stat-card span{
    color:#777;
    font-weight:bold;
    font-size:14px;
}

.hist-stat-card h3{
    margin-top:8px;
    color:#3b2411;
    font-size:30px;
}

.hist-stat-card.green{
    border-left-color:#2e8b57;
}

.hist-stat-card.blue{
    border-left-color:#2980b9;
}

.hist-filter-card{
    background:white;
    border-radius:18px;
    padding:20px;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
}

.filtro-form{
    display:grid;
    grid-template-columns:1.4fr 1fr 1fr auto auto;
    gap:12px;
    align-items:end;
}

.form-group label{
    display:block;
    color:#3b2411;
    font-weight:bold;
    margin-bottom:6px;
    font-size:14px;
}

.form-group input{
    margin:0;
    width:100%;
    padding:12px;
    border-radius:10px;
    border:1px solid #ccc;
    font-size:15px;
}

.form-group input:focus{
    outline:none;
    border-color:#7b4f27;
    box-shadow:0 0 0 3px rgba(123,79,39,0.15);
}

.entrada-card{
    background:white;
    border-radius:18px;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
    overflow:hidden;
    border-left:6px solid #7b4f27;
}

.entrada-card-header{
    padding:20px 22px;
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:18px;
    flex-wrap:wrap;
    background:#fffaf5;
    border-bottom:1px solid #f0dfcf;
}

.nota-info h3{
    color:#3b2411;
    font-size:22px;
    margin-bottom:8px;
}

.nota-info p{
    color:#666;
    line-height:1.5;
}

.nota-resumo{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}

.resumo-pill{
    background:white;
    border:1px solid #ead4bf;
    border-radius:14px;
    padding:12px 14px;
    min-width:130px;
}

.resumo-pill span{
    display:block;
    font-size:12px;
    color:#777;
    margin-bottom:5px;
    font-weight:bold;
}

.resumo-pill strong{
    color:#3b2411;
    font-size:17px;
}

.entrada-card-body{
    padding:20px 22px 22px;
}

.table-wrap{
    width:100%;
    overflow-x:auto;
}

.entrada-table{
    width:100%;
    border-collapse:collapse;
    margin-top:0;
}

.entrada-table th{
    background:#f5d0a9;
    color:#3b2411;
    padding:13px 10px;
    font-size:14px;
    white-space:nowrap;
}

.entrada-table td{
    padding:13px 10px;
    border-bottom:1px solid #eee;
    vertical-align:middle;
}

.entrada-table tr:hover td{
    background:#fff8f1;
}

.produto-nome{
    color:#3b2411;
    font-weight:bold;
}

.badge{
    display:inline-block;
    padding:6px 10px;
    border-radius:20px;
    font-size:12px;
    font-weight:bold;
    white-space:nowrap;
}

.badge-info{
    background:#eef3ff;
    color:#2c5aa0;
}

.badge-cat{
    background:#fdf3e7;
    color:#7b4f27;
}

.badge-vencido{
    background:#fff1f1;
    color:#c0392b;
}

.badge-ok{
    background:#e8f8ef;
    color:#1f7a45;
}

.valor{
    font-weight:bold;
    color:#3b2411;
    text-align:right;
}

.empty-box{
    background:white;
    border-radius:18px;
    padding:35px 20px;
    text-align:center;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
    color:#777;
}

.empty-box strong{
    display:block;
    color:#3b2411;
    font-size:20px;
    margin-bottom:8px;
}

@media(max-width:1000px){
    .filtro-form{
        grid-template-columns:1fr 1fr;
    }
}

@media(max-width:700px){
    .filtro-form{
        grid-template-columns:1fr;
    }

    .historico-actions,
    .hist-btn,
    .hist-btn-link{
        width:100%;
    }

    .nota-resumo{
        width:100%;
    }

    .resumo-pill{
        flex:1;
    }
}

@media print{
    .sidebar,
    .header,
    .historico-actions,
    .hist-filter-card{
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

    .entrada-card{
        box-shadow:none;
        break-inside:avoid;
        margin-bottom:20px;
    }
}
</style>

<div class="historico-page">

    <div class="historico-header">
        <div class="historico-title">
            <h2>Histórico de Entradas</h2>
            <p>Consulte as notas registradas, fornecedores, produtos recebidos e valores de entrada.</p>
        </div>

        <div class="historico-actions">
            <a href="entrada_produtos.php" class="hist-btn-link hist-btn-primary">
                Nova entrada
            </a>

            <button type="button" class="hist-btn hist-btn-print" onclick="window.print()">
                Imprimir
            </button>

            <a href="dashboard.php" class="hist-btn-link hist-btn-secondary">
                Voltar
            </a>
        </div>
    </div>

    <div class="hist-stats">
        <div class="hist-stat-card">
            <span>Notas encontradas</span>
            <h3><?= e($total_notas) ?></h3>
        </div>

        <div class="hist-stat-card blue">
            <span>Total de itens</span>
            <h3><?= e($total_itens) ?></h3>
        </div>

        <div class="hist-stat-card green">
            <span>Valor total</span>
            <h3>R$ <?= number_format($total_geral, 2, ',', '.') ?></h3>
        </div>
    </div>

    <div class="hist-filter-card">
        <form method="GET" class="filtro-form">
            <div class="form-group">
                <label>Pesquisar</label>
                <input 
                    type="text" 
                    name="busca" 
                    value="<?= e($busca) ?>"
                    placeholder="Nota, fornecedor ou produto"
                >
            </div>

            <div class="form-group">
                <label>Data inicial</label>
                <input 
                    type="date" 
                    name="data_inicio" 
                    value="<?= e($data_inicio) ?>"
                >
            </div>

            <div class="form-group">
                <label>Data final</label>
                <input 
                    type="date" 
                    name="data_fim" 
                    value="<?= e($data_fim) ?>"
                >
            </div>

            <button type="submit" class="hist-btn hist-btn-primary">
                Filtrar
            </button>

            <a href="historico_entradas.php" class="hist-btn-link hist-btn-secondary">
                Limpar
            </a>
        </form>
    </div>

    <?php if (count($entradas) > 0): ?>

        <?php foreach ($entradas as $entrada): ?>

            <div class="entrada-card">

                <div class="entrada-card-header">
                    <div class="nota-info">
                        <h3>Nota: <?= e($entrada['numero_nota']) ?></h3>

                        <p>
                            <strong>Fornecedor:</strong> <?= e($entrada['fornecedor']) ?><br>
                            <strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($entrada['data'])) ?>
                        </p>
                    </div>

                    <div class="nota-resumo">
                        <div class="resumo-pill">
                            <span>Produtos</span>
                            <strong><?= e(count($entrada['itens'])) ?></strong>
                        </div>

                        <div class="resumo-pill">
                            <span>Quantidade</span>
                            <strong>
                                <?= number_format($entrada['total_quantidade'], 3, ',', '.') ?>
                            </strong>
                        </div>

                        <div class="resumo-pill">
                            <span>Total</span>
                            <strong>
                                R$ <?= number_format($entrada['total_valor'], 2, ',', '.') ?>
                            </strong>
                        </div>
                    </div>
                </div>

                <div class="entrada-card-body">
                    <div class="table-wrap">
                        <table class="entrada-table">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Categoria</th>
                                    <th>Unidade</th>
                                    <th>Quantidade</th>
                                    <th>Preço</th>
                                    <th>Subtotal</th>
                                    <th>Validade</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($entrada['itens'] as $item): ?>

                                    <?php
                                        $validade = $item['validade'];
                                        $vencido = $validade && $validade < date('Y-m-d');
                                    ?>

                                    <tr>
                                        <td>
                                            <span class="produto-nome">
                                                <?= e($item['nome']) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <span class="badge badge-cat">
                                                <?= e($item['categoria'] ?: 'Sem categoria') ?>
                                            </span>
                                        </td>

                                        <td>
                                            <span class="badge badge-info">
                                                <?= e($item['unidade_medida']) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= e(formatarQuantidadeHistorico($item['quantidade'], $item['unidade_medida'])) ?>
                                        </td>

                                        <td>
                                            R$ <?= number_format((float)$item['preco'], 2, ',', '.') ?>
                                        </td>

                                        <td class="valor">
                                            R$ <?= number_format((float)$item['subtotal'], 2, ',', '.') ?>
                                        </td>

                                        <td>
                                            <?php if ($validade): ?>
                                                <span class="badge <?= $vencido ? 'badge-vencido' : 'badge-ok' ?>">
                                                    <?= e(formatarDataHistorico($validade)) ?>
                                                </span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="empty-box">
            <strong>Nenhuma entrada encontrada.</strong>
            Cadastre uma nova entrada ou ajuste os filtros da pesquisa.
        </div>

    <?php endif; ?>

</div>

</div>
</div>
</div>
</body>
</html>
