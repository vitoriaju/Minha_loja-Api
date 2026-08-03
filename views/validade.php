<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_once __DIR__ . '/../pdo.php';
require_once __DIR__ . '/../utils.php';

$busca = trim($_GET['busca'] ?? '');
$categoria = trim($_GET['categoria'] ?? '');
$dias_filtro = $_GET['dias'] ?? '7';

$dias_permitidos = ['1', '3', '7', '15', '30'];

if (!in_array($dias_filtro, $dias_permitidos, true)) {
    $dias_filtro = '7';
}

$where = [
    "l.quantidade_restante > 0",
    "l.validade IS NOT NULL",
    "l.validade >= CURDATE()",
    "l.validade <= DATE_ADD(CURDATE(), INTERVAL {$dias_filtro} DAY)"
];

$params = [];

if ($busca !== '') {
    $where[] = "p.nome LIKE ?";
    $params[] = "%{$busca}%";
}

if ($categoria !== '') {
    $where[] = "p.categoria = ?";
    $params[] = $categoria;
}

$whereSql = implode(" AND ", $where);

$stmt = $pdo->prepare("
    SELECT 
        l.id,
        p.nome,
        p.preco,
        p.unidade_medida,
        p.categoria,
        l.validade,
        l.quantidade_restante AS estoque,
        p.estoque_minimo,
        DATEDIFF(l.validade, CURDATE()) AS dias_restantes,
        (p.preco * l.quantidade_restante) AS valor_em_estoque
    FROM lotes_estoque l
    JOIN produtos p ON p.id = l.produto_id
    WHERE {$whereSql}
    ORDER BY l.validade ASC, p.nome ASC, l.id ASC
");

$stmt->execute($params);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtCategorias = $pdo->query("
    SELECT DISTINCT categoria
    FROM produtos
    WHERE categoria IS NOT NULL
    AND categoria <> ''
    ORDER BY categoria ASC
");

$categorias = $stmtCategorias->fetchAll(PDO::FETCH_COLUMN);

$total_produtos = count($produtos);
$total_estoque = 0;
$total_valor = 0;
$vencem_hoje = 0;

foreach ($produtos as $p) {
    $total_estoque += (float)$p['estoque'];
    $total_valor += (float)$p['valor_em_estoque'];

    if ((int)$p['dias_restantes'] === 0) {
        $vencem_hoje++;
    }
}

function formatarQuantidadeValidade($quantidade, $unidade)
{
    if ($unidade === 'kg') {
        return number_format((float)$quantidade, 3, ',', '.') . ' kg';
    }

    return number_format((float)$quantidade, 0, ',', '.') . ' un';
}

function formatarDataValidade($data)
{
    if (!$data) {
        return '-';
    }

    return date('d/m/Y', strtotime($data));
}

function textoDiasRestantes($dias)
{
    $dias = (int)$dias;

    if ($dias === 0) {
        return 'Vence hoje';
    }

    if ($dias === 1) {
        return 'Vence amanhã';
    }

    return "Vence em {$dias} dias";
}

function classeUrgencia($dias)
{
    $dias = (int)$dias;

    if ($dias <= 1) {
        return 'badge-danger';
    }

    if ($dias <= 3) {
        return 'badge-orange';
    }

    return 'badge-green';
}

include __DIR__ . '/layout.php';
?>

<style>
.validade-page{
    display:flex;
    flex-direction:column;
    gap:22px;
}

.validade-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:20px;
    flex-wrap:wrap;
}

.validade-title h2{
    font-size:28px;
    color:#3b2411;
    margin-bottom:6px;
}

.validade-title p{
    color:#777;
    font-size:15px;
}

.validade-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.val-btn,
.val-btn-link{
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

.val-btn-primary{
    background:#7b4f27;
    color:white;
}

.val-btn-primary:hover{
    background:#5a371a;
    transform:translateY(-2px);
}

.val-btn-secondary{
    background:white;
    color:#7b4f27;
    border:1px solid #d6b089;
}

.val-btn-secondary:hover{
    background:#fff7ef;
    transform:translateY(-2px);
}

.val-btn-danger{
    background:#fff1f1;
    color:#c0392b;
    border:1px solid #f2b8b8;
}

.val-btn-danger:hover{
    background:#c0392b;
    color:white;
    transform:translateY(-2px);
}

.val-stats{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(210px, 1fr));
    gap:16px;
}

.val-stat-card{
    background:white;
    border-radius:16px;
    padding:18px;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
    border-left:6px solid #e67e22;
}

.val-stat-card span{
    color:#777;
    font-weight:bold;
    font-size:14px;
}

.val-stat-card h3{
    margin-top:8px;
    color:#3b2411;
    font-size:30px;
}

.val-stat-card.red{
    border-left-color:#c0392b;
}

.val-stat-card.green{
    border-left-color:#2e8b57;
}

.val-stat-card.blue{
    border-left-color:#2980b9;
}

.val-filter-card{
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

.form-group input,
.form-group select{
    margin:0;
    width:100%;
    padding:12px;
    border-radius:10px;
    border:1px solid #ccc;
    font-size:15px;
}

.form-group input:focus,
.form-group select:focus{
    outline:none;
    border-color:#7b4f27;
    box-shadow:0 0 0 3px rgba(123,79,39,0.15);
}

.validade-card{
    background:white;
    border-radius:18px;
    padding:22px;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
}

.card-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
    flex-wrap:wrap;
    margin-bottom:18px;
}

.card-header h3{
    color:#3b2411;
    font-size:21px;
}

.alerta-box{
    background:#fff4df;
    color:#8a4b00;
    border-left:6px solid #e67e22;
    padding:15px;
    border-radius:14px;
    line-height:1.5;
    margin-bottom:18px;
    font-weight:bold;
}

.table-wrap{
    width:100%;
    overflow-x:auto;
}

.validade-table{
    width:100%;
    border-collapse:collapse;
    margin-top:0;
}

.validade-table th{
    background:#f5d0a9;
    color:#3b2411;
    padding:13px 10px;
    font-size:14px;
    white-space:nowrap;
}

.validade-table td{
    padding:13px 10px;
    border-bottom:1px solid #eee;
    vertical-align:middle;
}

.validade-table tr:hover td{
    background:#fff8f1;
}

.produto-id{
    color:#777;
    font-weight:bold;
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

.badge-danger{
    background:#fff1f1;
    color:#c0392b;
}

.badge-orange{
    background:#fff4df;
    color:#b45f00;
}

.badge-green{
    background:#e8f8ef;
    color:#1f7a45;
}

.badge-info{
    background:#eef3ff;
    color:#2c5aa0;
}

.badge-cat{
    background:#fdf3e7;
    color:#7b4f27;
}

.valor{
    font-weight:bold;
    color:#3b2411;
}

.empty-box{
    background:white;
    border-radius:18px;
    padding:40px 20px;
    text-align:center;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
    color:#2e7d32;
    font-weight:bold;
}

.empty-box strong{
    display:block;
    color:#2e7d32;
    font-size:22px;
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

    .validade-actions,
    .val-btn,
    .val-btn-link{
        width:100%;
    }
}

@media print{
    .sidebar,
    .header,
    .validade-actions,
    .val-filter-card{
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

    .validade-card,
    .val-stat-card{
        box-shadow:none;
    }
}
</style>

<div class="validade-page">

    <div class="validade-header">
        <div class="validade-title">
            <h2>Produtos Próximos do Vencimento</h2>
            <p>
                Produtos que ainda não venceram, mas vencem nos próximos 
                <?= e($dias_filtro) ?> dia(s).
            </p>
        </div>

        <div class="validade-actions">
            <a href="vencidos.php" class="val-btn-link val-btn-danger">
                Ver vencidos
            </a>

            <button type="button" class="val-btn val-btn-primary" onclick="window.print()">
                Imprimir
            </button>

            <a href="dashboard.php" class="val-btn-link val-btn-secondary">
                Voltar
            </a>
        </div>
    </div>

    <div class="val-stats">
        <div class="val-stat-card">
            <span>Produtos para vencer</span>
            <h3><?= e($total_produtos) ?></h3>
        </div>

        <div class="val-stat-card red">
            <span>Vencem hoje</span>
            <h3><?= e($vencem_hoje) ?></h3>
        </div>

        <div class="val-stat-card blue">
            <span>Quantidade em estoque</span>
            <h3><?= number_format($total_estoque, 3, ',', '.') ?></h3>
        </div>

        <div class="val-stat-card green">
            <span>Valor em estoque</span>
            <h3>R$ <?= number_format($total_valor, 2, ',', '.') ?></h3>
        </div>
    </div>

    <div class="val-filter-card">
        <form method="GET" class="filtro-form">
            <div class="form-group">
                <label>Pesquisar produto</label>
                <input 
                    type="text" 
                    name="busca" 
                    value="<?= e($busca) ?>"
                    placeholder="Digite o nome do produto"
                >
            </div>

            <div class="form-group">
                <label>Categoria</label>
                <select name="categoria">
                    <option value="">Todas</option>

                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= e($cat) ?>" <?= $categoria === $cat ? 'selected' : '' ?>>
                            <?= e($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Período</label>
                <select name="dias">
                    <option value="1" <?= $dias_filtro === '1' ? 'selected' : '' ?>>
                        Até 1 dia
                    </option>

                    <option value="3" <?= $dias_filtro === '3' ? 'selected' : '' ?>>
                        Até 3 dias
                    </option>

                    <option value="7" <?= $dias_filtro === '7' ? 'selected' : '' ?>>
                        Até 7 dias
                    </option>

                    <option value="15" <?= $dias_filtro === '15' ? 'selected' : '' ?>>
                        Até 15 dias
                    </option>

                    <option value="30" <?= $dias_filtro === '30' ? 'selected' : '' ?>>
                        Até 30 dias
                    </option>
                </select>
            </div>

            <button type="submit" class="val-btn val-btn-primary">
                Filtrar
            </button>

            <a href="validade.php" class="val-btn-link val-btn-secondary">
                Limpar
            </a>
        </form>
    </div>

    <?php if (count($produtos) > 0): ?>

        <div class="validade-card">
            <div class="card-header">
                <h3>Produtos que ainda vão vencer</h3>
            </div>

            <div class="alerta-box">
                Atenção: esta tela mostra somente produtos que ainda não venceram e estão próximos da validade.
            </div>

            <div class="table-wrap">
                <table class="validade-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th>Preço</th>
                            <th>Unidade</th>
                            <th>Estoque</th>
                            <th>Validade</th>
                            <th>Situação</th>
                            <th>Valor em estoque</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($produtos as $p): ?>
                            <tr>
                                <td class="produto-id">#<?= e($p['id']) ?></td>

                                <td>
                                    <span class="produto-nome">
                                        <?= e($p['nome']) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="badge badge-cat">
                                        <?= e($p['categoria'] ?: 'Sem categoria') ?>
                                    </span>
                                </td>

                                <td>
                                    <strong>R$ <?= number_format((float)$p['preco'], 2, ',', '.') ?></strong>
                                </td>

                                <td>
                                    <span class="badge badge-info">
                                        <?= e($p['unidade_medida']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= e(formatarQuantidadeValidade($p['estoque'], $p['unidade_medida'])) ?>
                                </td>

                                <td>
                                    <?= e(formatarDataValidade($p['validade'])) ?>
                                </td>

                                <td>
                                    <span class="badge <?= e(classeUrgencia($p['dias_restantes'])) ?>">
                                        <?= e(textoDiasRestantes($p['dias_restantes'])) ?>
                                    </span>
                                </td>

                                <td class="valor">
                                    R$ <?= number_format((float)$p['valor_em_estoque'], 2, ',', '.') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php else: ?>

        <div class="empty-box">
            <strong>Nenhum produto próximo do vencimento.</strong>
            Não há produtos vencendo dentro do período selecionado.
        </div>

    <?php endif; ?>

</div>

</div>
</div>
</div>
</body>
</html>
