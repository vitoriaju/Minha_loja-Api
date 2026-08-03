<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_admin('/index.php');

require_once __DIR__ . '/../pdo.php';
require_once __DIR__ . '/../utils.php';

$mensagem = null;
$tipo_mensagem = null;

$form = [
    'nome' => '',
    'preco' => '',
    'unidade_medida' => 'unidade',
    'categoria' => '',
    'validade' => '',
    'estoque' => '',
    'estoque_minimo' => '5'
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!csrf_check($_POST['csrf_token'] ?? '')) {
        $mensagem = "Sessão expirada. Tente novamente.";
        $tipo_mensagem = "erro";
    } else {

        $form['nome'] = trim($_POST['nome'] ?? '');
        $form['preco'] = str_replace(',', '.', $_POST['preco'] ?? 0);
        $form['unidade_medida'] = $_POST['unidade_medida'] ?? 'unidade';
        $form['categoria'] = trim($_POST['categoria'] ?? '');
        $form['validade'] = $_POST['validade'] ?? '';
        $form['estoque'] = str_replace(',', '.', $_POST['estoque'] ?? 0);
        $form['estoque_minimo'] = str_replace(',', '.', $_POST['estoque_minimo'] ?? 5);

        $nome = $form['nome'];
        $preco = (float)$form['preco'];
        $unidade = $form['unidade_medida'];
        $categoria = $form['categoria'] !== '' ? $form['categoria'] : null;
        $validade = $form['validade'] !== '' ? $form['validade'] : null;
        $estoque = (float)$form['estoque'];
        $estoque_minimo = (float)$form['estoque_minimo'];

        if ($nome === '') {
            $mensagem = "Informe o nome do produto.";
            $tipo_mensagem = "erro";
        } elseif ($preco <= 0) {
            $mensagem = "Informe um preço válido.";
            $tipo_mensagem = "erro";
        } elseif (!in_array($unidade, ['unidade', 'kg'], true)) {
            $mensagem = "Selecione uma unidade válida.";
            $tipo_mensagem = "erro";
        } elseif ($estoque < 0) {
            $mensagem = "O estoque não pode ser negativo.";
            $tipo_mensagem = "erro";
        } elseif ($estoque_minimo < 0) {
            $mensagem = "O estoque mínimo não pode ser negativo.";
            $tipo_mensagem = "erro";
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("
                    INSERT INTO produtos 
                    (nome, preco, categoria, validade, estoque, estoque_minimo, unidade_medida, criado_em)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");

                $ok = $stmt->execute([
                    $nome,
                    $preco,
                    $categoria,
                    $validade,
                    $estoque,
                    $estoque_minimo,
                    $unidade
                ]);

                if ($ok) {
                    $produtoId = (int) $pdo->lastInsertId();
                    if ($estoque > 0) {
                        $stmtLote = $pdo->prepare("
                            INSERT INTO lotes_estoque
                            (item_entrada_id, produto_id, validade, quantidade_inicial, quantidade_restante, origem)
                            VALUES (NULL, ?, ?, ?, ?, 'cadastro')
                        ");
                        $stmtLote->execute([$produtoId, $validade, $estoque, $estoque]);
                    }
                    audit_log($pdo, 'criar', 'produto', $produtoId);
                    $pdo->commit();
                    $mensagem = "Produto cadastrado com sucesso!";
                    $tipo_mensagem = "sucesso";

                    $form = [
                        'nome' => '',
                        'preco' => '',
                        'unidade_medida' => 'unidade',
                        'categoria' => '',
                        'validade' => '',
                        'estoque' => '',
                        'estoque_minimo' => '5'
                    ];
                } else {
                    $pdo->rollBack();
                    $mensagem = "Erro ao cadastrar produto.";
                    $tipo_mensagem = "erro";
                }

            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $mensagem = "Erro ao cadastrar produto.";
                $tipo_mensagem = "erro";
            }
        }
    }
}

include __DIR__ . '/layout.php';
?>

<style>
.cadastro-page{
    display:flex;
    flex-direction:column;
    gap:22px;
}

.cadastro-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:20px;
    flex-wrap:wrap;
}

.cadastro-title h2{
    font-size:28px;
    color:#3b2411;
    margin-bottom:6px;
}

.cadastro-title p{
    color:#777;
    font-size:15px;
}

.cadastro-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.cadastro-btn-link,
.cadastro-btn{
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
    gap:6px;
    transition:0.3s;
}

.cadastro-btn-primary{
    background:#7b4f27;
    color:white;
}

.cadastro-btn-primary:hover{
    background:#5a371a;
    transform:translateY(-2px);
}

.cadastro-btn-secondary{
    background:white;
    color:#7b4f27;
    border:1px solid #d6b089;
}

.cadastro-btn-secondary:hover{
    background:#fff7ef;
    transform:translateY(-2px);
}

.cadastro-grid{
    display:grid;
    grid-template-columns:1.4fr 0.8fr;
    gap:22px;
    align-items:start;
}

.cadastro-card{
    background:white;
    border-radius:18px;
    padding:24px;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
}

.card-title{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:15px;
    margin-bottom:22px;
}

.card-title h3{
    color:#3b2411;
    font-size:21px;
}

.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:16px;
}

.form-group{
    display:flex;
    flex-direction:column;
}

.form-group.full{
    grid-column:1 / -1;
}

.form-group label{
    color:#3b2411;
    font-weight:bold;
    margin-bottom:7px;
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
    transition:0.2s;
}

.form-group input:focus,
.form-group select:focus{
    outline:none;
    border-color:#7b4f27;
    box-shadow:0 0 0 3px rgba(123,79,39,0.15);
}

.help-text{
    margin-top:6px;
    color:#777;
    font-size:12px;
}

.form-footer{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    flex-wrap:wrap;
    margin-top:24px;
}

.btn-submit{
    background:#28a745;
    color:white;
    border:0;
    border-radius:12px;
    padding:14px 22px;
    font-weight:bold;
    font-size:16px;
    cursor:pointer;
    transition:0.3s;
}

.btn-submit:hover{
    background:#218838;
    transform:translateY(-2px);
}

.btn-clear{
    background:white;
    color:#7b4f27;
    border:1px solid #d6b089;
    border-radius:12px;
    padding:14px 18px;
    font-weight:bold;
    cursor:pointer;
    transition:0.3s;
}

.btn-clear:hover{
    background:#fff7ef;
}

.msg{
    padding:14px 16px;
    border-radius:12px;
    font-weight:bold;
    margin-bottom:18px;
}

.msg.sucesso{
    background:#e8f8ef;
    color:#1f7a45;
    border-left:5px solid #2e8b57;
}

.msg.erro{
    background:#fff1f1;
    color:#c0392b;
    border-left:5px solid #c0392b;
}

.preview-card{
    position:sticky;
    top:20px;
}

.preview-box{
    background:#7b4f27;
    color:white;
    border-radius:18px;
    padding:22px;
    margin-bottom:16px;
}

.preview-box span{
    display:block;
    font-size:13px;
    opacity:0.9;
    margin-bottom:8px;
}

.preview-box strong{
    display:block;
    font-size:26px;
    word-break:break-word;
}

.preview-list{
    display:flex;
    flex-direction:column;
    gap:12px;
}

.preview-item{
    background:#fdf3e7;
    border-radius:14px;
    padding:14px;
    border-left:5px solid #7b4f27;
}

.preview-item span{
    display:block;
    color:#777;
    font-size:13px;
    margin-bottom:5px;
}

.preview-item strong{
    color:#3b2411;
    font-size:17px;
}

.dica-box{
    margin-top:16px;
    background:#fff8f1;
    border:1px dashed #d6b089;
    color:#5a371a;
    border-radius:14px;
    padding:14px;
    font-size:14px;
    line-height:1.5;
}

@media(max-width:1000px){
    .cadastro-grid{
        grid-template-columns:1fr;
    }

    .preview-card{
        position:static;
    }
}

@media(max-width:700px){
    .form-grid{
        grid-template-columns:1fr;
    }

    .form-footer{
        flex-direction:column;
    }

    .btn-submit,
    .btn-clear{
        width:100%;
    }
}
</style>

<div class="cadastro-page">

    <div class="cadastro-header">
        <div class="cadastro-title">
            <h2>Cadastrar Produto</h2>
            <p>Adicione novos produtos ao estoque com preço, validade e quantidade inicial.</p>
        </div>

        <div class="cadastro-actions">
            <a href="listar_produtos_api.php" class="cadastro-btn-link cadastro-btn-primary">
                Ver produtos
            </a>

            <a href="dashboard.php" class="cadastro-btn-link cadastro-btn-secondary">
                Voltar
            </a>
        </div>
    </div>

    <div class="cadastro-grid">

        <div class="cadastro-card">
            <div class="card-title">
                <h3>Dados do produto</h3>
            </div>

            <?php if ($mensagem): ?>
                <div class="msg <?= e($tipo_mensagem) ?>">
                    <?= e($mensagem) ?>
                </div>
            <?php endif; ?>

            <form method="post" id="formProduto">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="form-grid">

                    <div class="form-group full">
                        <label>Nome do produto</label>
                        <input 
                            type="text" 
                            name="nome" 
                            id="nome"
                            value="<?= e($form['nome']) ?>"
                            placeholder="Ex: Pão francês"
                            required
                        >
                        <small class="help-text">Use um nome fácil de identificar na venda.</small>
                    </div>

                    <div class="form-group">
                        <label>Preço de venda</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            min="0.01"
                            name="preco" 
                            id="preco"
                            value="<?= e($form['preco']) ?>"
                            placeholder="Ex: 0.90"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Unidade de venda</label>
                        <select name="unidade_medida" id="unidade_medida" required>
                            <option value="unidade" <?= $form['unidade_medida'] === 'unidade' ? 'selected' : '' ?>>
                                Unidade
                            </option>

                            <option value="kg" <?= $form['unidade_medida'] === 'kg' ? 'selected' : '' ?>>
                                Kg
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Categoria</label>
                        <input 
                            type="text" 
                            name="categoria" 
                            id="categoria"
                            list="categorias"
                            value="<?= e($form['categoria']) ?>"
                            placeholder="Ex: Padaria"
                        >

                        <datalist id="categorias">
                            <option value="Padaria">
                            <option value="Bebidas">
                            <option value="Laticínio">
                            <option value="Mercearia">
                            <option value="Limpeza">
                            <option value="Hortifruti">
                            <option value="Outros">
                        </datalist>
                    </div>

                    <div class="form-group">
                        <label>Data de validade</label>
                        <input 
                            type="date" 
                            name="validade" 
                            id="validade"
                            value="<?= e($form['validade']) ?>"
                        >
                        <small class="help-text">Pode deixar vazio se o produto não tiver validade.</small>
                    </div>

                    <div class="form-group">
                        <label>Estoque inicial</label>
                        <input 
                            type="number" 
                            name="estoque" 
                            id="estoque"
                            step="1"
                            min="0"
                            value="<?= e($form['estoque']) ?>"
                            placeholder="Ex: 100"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Estoque mínimo</label>
                        <input 
                            type="number" 
                            name="estoque_minimo" 
                            id="estoque_minimo"
                            step="1"
                            min="0"
                            value="<?= e($form['estoque_minimo']) ?>"
                            placeholder="Ex: 5"
                            required
                        >
                        <small class="help-text">Quando chegar nesse valor, o sistema marca como estoque baixo.</small>
                    </div>

                </div>

                <div class="form-footer">
                    <button type="button" class="btn-clear" onclick="limparFormulario()">
                        Limpar campos
                    </button>

                    <button type="submit" class="btn-submit">
                        Cadastrar Produto
                    </button>
                </div>
            </form>
        </div>

        <div class="cadastro-card preview-card">
            <div class="card-title">
                <h3>Prévia</h3>
            </div>

            <div class="preview-box">
                <span>Produto</span>
                <strong id="previewNome">Novo produto</strong>
            </div>

            <div class="preview-list">
                <div class="preview-item">
                    <span>Preço</span>
                    <strong>R$ <span id="previewPreco">0,00</span></strong>
                </div>

                <div class="preview-item">
                    <span>Unidade</span>
                    <strong id="previewUnidade">Unidade</strong>
                </div>

                <div class="preview-item">
                    <span>Categoria</span>
                    <strong id="previewCategoria">Não informada</strong>
                </div>

                <div class="preview-item">
                    <span>Estoque inicial</span>
                    <strong id="previewEstoque">0 unidade</strong>
                </div>

                <div class="preview-item">
                    <span>Estoque mínimo</span>
                    <strong id="previewEstoqueMinimo">5 unidade</strong>
                </div>
            </div>

            <div class="dica-box">
                Dica: use <strong>Kg</strong> para produtos vendidos por peso e <strong>Unidade</strong> para produtos vendidos por quantidade.
            </div>
        </div>

    </div>

</div>

<script>
function formatarMoeda(valor){
    return Number(valor || 0).toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatarQuantidade(valor, unidade){
    const numero = Number(valor || 0);

    if(unidade === "kg"){
        return numero.toLocaleString("pt-BR", {
            minimumFractionDigits: 3,
            maximumFractionDigits: 3
        }) + " kg";
    }

    return numero.toLocaleString("pt-BR", {
        maximumFractionDigits: 0
    }) + " unidade";
}

function atualizarSteps(){
    const unidade = document.getElementById("unidade_medida").value;

    const estoque = document.getElementById("estoque");
    const estoqueMinimo = document.getElementById("estoque_minimo");

    if(unidade === "kg"){
        estoque.step = "0.001";
        estoqueMinimo.step = "0.001";

        estoque.placeholder = "Ex: 5.500";
        estoqueMinimo.placeholder = "Ex: 1.000";
    }else{
        estoque.step = "1";
        estoqueMinimo.step = "1";

        estoque.placeholder = "Ex: 100";
        estoqueMinimo.placeholder = "Ex: 5";
    }
}

function atualizarPreview(){
    const nome = document.getElementById("nome").value.trim();
    const preco = document.getElementById("preco").value;
    const unidade = document.getElementById("unidade_medida").value;
    const categoria = document.getElementById("categoria").value.trim();
    const estoque = document.getElementById("estoque").value;
    const estoqueMinimo = document.getElementById("estoque_minimo").value;

    document.getElementById("previewNome").innerText = nome || "Novo produto";
    document.getElementById("previewPreco").innerText = formatarMoeda(preco);
    document.getElementById("previewUnidade").innerText = unidade === "kg" ? "Kg" : "Unidade";
    document.getElementById("previewCategoria").innerText = categoria || "Não informada";
    document.getElementById("previewEstoque").innerText = formatarQuantidade(estoque, unidade);
    document.getElementById("previewEstoqueMinimo").innerText = formatarQuantidade(estoqueMinimo, unidade);

    atualizarSteps();
}

function limparFormulario(){
    document.getElementById("nome").value = "";
    document.getElementById("preco").value = "";
    document.getElementById("unidade_medida").value = "unidade";
    document.getElementById("categoria").value = "";
    document.getElementById("validade").value = "";
    document.getElementById("estoque").value = "";
    document.getElementById("estoque_minimo").value = "5";

    atualizarPreview();
}

document.querySelectorAll("#formProduto input, #formProduto select").forEach(function(campo){
    campo.addEventListener("input", atualizarPreview);
    campo.addEventListener("change", atualizarPreview);
});

atualizarPreview();
</script>

</div>
</div>
</div>
</body>
</html>
