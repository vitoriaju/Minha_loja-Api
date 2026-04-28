<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_once __DIR__ . '/../pdo.php';
require_once __DIR__ . '/../utils.php';

$busca = trim($_GET['busca'] ?? '');
$categoria = trim($_GET['categoria'] ?? '');
$ordenar = $_GET['ordenar'] ?? 'mais_antigo';

$where = [
    "validade IS NOT NULL",
    "validade < CURDATE()"
];

$params = [];

if ($busca !== '') {
    $where[] = "nome LIKE ?";
    $params[] = "%{$busca}%";
}

if ($categoria !== '') {
    $where[] = "categoria = ?";
    $params[] = $categoria;
}

$orderSql = "validade ASC";

if ($ordenar === 'nome') {
    $orderSql = "nome ASC";
} elseif ($ordenar === 'estoque') {
    $orderSql = "estoque DESC";
} elseif ($ordenar === 'valor') {
    $orderSql = "(preco * estoque) DESC";
}

$whereSql = implode(" AND ", $where);

$stmt = $pdo->prepare("
    SELECT 
        id,
        nome,
        preco,
        unidade_medida,
        categoria,
        validade,
        estoque,
        estoque_minimo,
        DATEDIFF(CURDATE(), validade) AS dias_vencido,
        (preco * estoque) AS valor_em_estoque
    FROM produtos
    WHERE {$whereSql}
    ORDER BY {$orderSql}
");

$stmt->execute($params);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtCategorias = $pdo->query("
    SELECT DISTINCT categoria
    FROM produtos
    WHERE categoria IS NOT NULL
    AND categoria <> ''
    AND validade IS NOT NULL
    AND validade < CURDATE()
    ORDER BY categoria ASC
");

$categorias = $stmtCategorias->fetchAll(PDO::FETCH_COLUMN);

$total_produtos = count($produtos);
$total_estoque = 0;
$total_valor = 0;
$maior_atraso = 0;

foreach ($produtos as $p) {
    $total_estoque += (float)$p['estoque'];
    $total_valor += (float)$p['valor_em_estoque'];

    if ((int)$p['dias_vencido'] > $maior_atraso) {
        $maior_atraso = (int)$p['dias_vencido'];
    }
}

function formatarQuantidadeVencidos($quantidade, $unidade)
{
    if ($unidade === 'kg') {
        return number_format((float)$quantidade, 3, ',', '.') . ' kg';
    }

    return number_format((float)$quantidade, 0, ',', '.') . ' un';
}

function formatarDataVencidos($data)
{
    if (!$data) {
        return '-';
    }

    return date('d/m/Y', strtotime($data));
}

include __DIR__ . '/layout.php';
?>

<style>
.vencidos-page{
    display:flex;
    flex-direction:column;
    gap:22px;
}

.vencidos-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:20px;
    flex-wrap:wrap;
}

.vencidos-title h2{
    font-size:28px;
    color:#3b2411;
    margin-bottom:6px;
}

.vencidos-title p{
    color:#777;
    font-size:15px;
}

.vencidos-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.venc-btn,
.venc-btn-link{
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

.venc-btn-primary{
    background:#7b4f27;
    color:white;
}

.venc-btn-primary:hover{
    background:#5a371a;
    transform:translateY(-2px);
}

.venc-btn-secondary{
    background:white;
    color:#7b4f27;
    border:1px solid #d6b089;
}

.venc-btn-secondary:hover{
    background:#fff7ef;
    transform:translateY(-2px);
}

.venc-btn-danger{
    background:#fff1f1;
    color:#c0392b;
    border:1px solid #f2b8b8;
}

.venc-btn-danger:hover{
    background:#c0392b;
    color:white;
}

.venc-btn-print{
    background:#2e8b57;
    color:white;
}

.venc-btn-print:hover{
    background:#247046;
    transform:translateY(-2px);
}

.venc-stats{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(210px, 1fr));
    gap:16px;
}

.venc-stat-card{
    background:white;
    border-radius:16px;
    padding:18px;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
    border-left:6px solid #c0392b;
}

.venc-stat-card span{
    color:#777;
    font-weight:bold;
    font-size:14px;
}

.venc-stat-card h3{
    margin-top:8px;
    color:#3b2411;
    font-size:30px;
}

.venc-stat-card.orange{
    border-left-color:#e67e22;
}

.venc-stat-card.green{
    border-left-color:#2e8b57;
}

.venc-filter-card{
    background:white;
    border-radius:18px;
    padding:20px;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
}

.filtro-form{
    display:grid;
    grid-template-columns:1.5fr 1fr 1fr auto auto;
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

.vencidos-card{
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
    background:#fff1f1;
    color:#a02121;
    border-left:6px solid #c0392b;
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

.vencidos-table{
    width:100%;
    border-collapse:collapse;
    margin-top:0;
}

.vencidos-table th{
    background:#f5d0a9;
    color:#3b2411;
    padding:13px 10px;
    font-size:14px;
    white-space:nowrap;
}

.vencidos-table td{
    padding:13px 10px;
    border-bottom:1px solid #eee;
    vertical-align:middle;
}

.vencidos-table tr:hover td{
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

.actions-cell{
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
    color:#2e7d32;
    font-weight:bold;
}

.empty-box strong{
    display:block;
    color:#2e7d32;
    font-size:22px;
    margin-bottom:8px;
}

.toast{
    position:fixed;
    top:20px;
    right:20px;
    background:#3b2411;
    color:white;
    padding:14px 18px;
    border-radius:12px;
    box-shadow:0 6px 18px rgba(0,0,0,0.25);
    display:none;
    z-index:9999;
    font-weight:bold;
}

.toast.success{
    background:#2e8b57;
}

.toast.error{
    background:#c0392b;
}

/* MODAL */
.modal-overlay{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.55);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:9998;
    padding:20px;
}

.modal-box{
    background:white;
    width:100%;
    max-width:430px;
    border-radius:18px;
    box-shadow:0 10px 30px rgba(0,0,0,0.25);
    overflow:hidden;
}

.modal-header{
    background:#c0392b;
    color:white;
    padding:18px 22px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.modal-header h3{
    margin:0;
    font-size:20px;
}

.modal-close{
    background:transparent;
    border:0;
    color:white;
    font-size:24px;
    cursor:pointer;
    padding:0;
}

.modal-body{
    padding:22px;
}

.modal-body p{
    color:#555;
    line-height:1.5;
}

.modal-body strong{
    color:#c0392b;
}

.modal-actions{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    margin-top:20px;
    flex-wrap:wrap;
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

    .vencidos-actions,
    .venc-btn,
    .venc-btn-link{
        width:100%;
    }
}

@media print{
    .sidebar,
    .header,
    .vencidos-actions,
    .venc-filter-card,
    .actions-cell,
    .toast,
    .modal-overlay{
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

    .vencidos-card,
    .venc-stat-card{
        box-shadow:none;
    }
}
</style>

<div class="vencidos-page">

    <div class="vencidos-header">
        <div class="vencidos-title">
            <h2>Produtos Vencidos</h2>
            <p>Consulte os produtos com validade vencida e acompanhe o prejuízo estimado em estoque.</p>
        </div>

        <div class="vencidos-actions">
            <a href="validade.php" class="venc-btn-link venc-btn-primary">
                Controle de validade
            </a>

            <button type="button" class="venc-btn venc-btn-print" onclick="window.print()">
                Imprimir
            </button>

            <a href="dashboard.php" class="venc-btn-link venc-btn-secondary">
                Voltar
            </a>
        </div>
    </div>

    <div class="venc-stats">
        <div class="venc-stat-card">
            <span>Produtos vencidos</span>
            <h3><?= e($total_produtos) ?></h3>
        </div>

        <div class="venc-stat-card orange">
            <span>Quantidade em estoque</span>
            <h3><?= number_format($total_estoque, 3, ',', '.') ?></h3>
        </div>

        <div class="venc-stat-card">
            <span>Maior atraso</span>
            <h3><?= e($maior_atraso) ?> dia(s)</h3>
        </div>

        <div class="venc-stat-card green">
            <span>Valor estimado</span>
            <h3>R$ <?= number_format($total_valor, 2, ',', '.') ?></h3>
        </div>
    </div>

    <div class="venc-filter-card">
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
                <label>Ordenar por</label>
                <select name="ordenar">
                    <option value="mais_antigo" <?= $ordenar === 'mais_antigo' ? 'selected' : '' ?>>
                        Mais antigo
                    </option>

                    <option value="nome" <?= $ordenar === 'nome' ? 'selected' : '' ?>>
                        Nome
                    </option>

                    <option value="estoque" <?= $ordenar === 'estoque' ? 'selected' : '' ?>>
                        Maior estoque
                    </option>

                    <option value="valor" <?= $ordenar === 'valor' ? 'selected' : '' ?>>
                        Maior valor
                    </option>
                </select>
            </div>

            <button type="submit" class="venc-btn venc-btn-primary">
                Filtrar
            </button>

            <a href="vencidos.php" class="venc-btn-link venc-btn-secondary">
                Limpar
            </a>
        </form>
    </div>

    <?php if (count($produtos) > 0): ?>

        <div class="vencidos-card">
            <div class="card-header">
                <h3>Lista de produtos vencidos</h3>
            </div>

            <div class="alerta-box">
                Atenção: produtos vencidos devem ser separados do estoque de venda para evitar comercialização indevida.
            </div>

            <div class="table-wrap">
                <table class="vencidos-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th>Preço</th>
                            <th>Unidade</th>
                            <th>Estoque</th>
                            <th>Validade</th>
                            <th>Atraso</th>
                            <th>Valor em estoque</th>
                            <?php if (($_SESSION['perfil'] ?? '') === 'admin'): ?>
                                <th>Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($produtos as $p): ?>

                            <?php
                                $nomeJson = json_encode($p['nome'], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
                            ?>

                            <tr id="produto-row-<?= e($p['id']) ?>">
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
                                    <?= e(formatarQuantidadeVencidos($p['estoque'], $p['unidade_medida'])) ?>
                                </td>

                                <td>
                                    <span class="badge badge-danger">
                                        <?= e(formatarDataVencidos($p['validade'])) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="badge badge-orange">
                                        <?= e($p['dias_vencido']) ?> dia(s)
                                    </span>
                                </td>

                                <td class="valor">
                                    R$ <?= number_format((float)$p['valor_em_estoque'], 2, ',', '.') ?>
                                </td>

                                <?php if (($_SESSION['perfil'] ?? '') === 'admin'): ?>
                                    <td>
                                        <div class="actions-cell">
                                            <button 
                                                type="button" 
                                                class="venc-btn venc-btn-danger"
                                                onclick='abrirModalExcluir(<?= (int)$p["id"] ?>, <?= $nomeJson ?>)'
                                            >
                                                Excluir
                                            </button>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php else: ?>

        <div class="empty-box">
            <strong>Nenhum produto vencido encontrado.</strong>
            Seu estoque não possui produtos vencidos com os filtros selecionados.
        </div>

    <?php endif; ?>

</div>

<div id="toast" class="toast"></div>

<div id="modal-delete" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Excluir produto</h3>
            <button type="button" class="modal-close" onclick="fecharModalExcluir()">×</button>
        </div>

        <div class="modal-body">
            <p>
                Tem certeza que deseja excluir o produto
                <strong id="delete-produto-nome"></strong>?
                <br>
                Essa ação remove o cadastro do produto.
            </p>

            <input type="hidden" id="delete-produto-id">

            <div class="modal-actions">
                <button type="button" class="venc-btn venc-btn-secondary" onclick="fecharModalExcluir()">
                    Cancelar
                </button>

                <button type="button" class="venc-btn venc-btn-danger" onclick="confirmarExclusao()">
                    Excluir
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const CSRF_TOKEN = "<?= e(csrf_token()) ?>";
const API = "../api/produtos.php";

function mostrarToast(mensagem, tipo = ""){
    const toast = document.getElementById("toast");

    toast.innerText = mensagem;
    toast.className = "toast " + tipo;
    toast.style.display = "block";

    setTimeout(() => {
        toast.style.display = "none";
    }, 3500);
}

function abrirModalExcluir(id, nome){
    document.getElementById("delete-produto-id").value = id;
    document.getElementById("delete-produto-nome").innerText = nome;
    document.getElementById("modal-delete").style.display = "flex";
}

function fecharModalExcluir(){
    document.getElementById("modal-delete").style.display = "none";
}

async function confirmarExclusao(){
    const id = document.getElementById("delete-produto-id").value;

    const body = new URLSearchParams();
    body.set("id", id);
    body.set("csrf_token", CSRF_TOKEN);

    try{
        const res = await fetch(API, {
            method: "DELETE",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: body.toString()
        });

        const data = await res.json();

        if(data.status === "success"){
            const row = document.getElementById("produto-row-" + id);

            if(row){
                row.remove();
            }

            fecharModalExcluir();
            mostrarToast("Produto excluído com sucesso.", "success");

            setTimeout(() => {
                window.location.reload();
            }, 800);

        }else{
            mostrarToast(data.msg || "Erro ao excluir produto.", "error");
        }

    }catch(e){
        mostrarToast("Não foi possível excluir. O produto pode estar vinculado a vendas ou entradas.", "error");
    }
}

document.getElementById("modal-delete")?.addEventListener("click", function(e){
    if(e.target === this){
        fecharModalExcluir();
    }
});
</script>

</div>
</div>
</div>
</body>
</html>