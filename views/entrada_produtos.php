<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_admin();
require_once __DIR__ . '/../pdo.php';
require_once __DIR__ . '/../utils.php';

$stmt = $pdo->query("
    SELECT id, nome, preco, estoque, unidade_medida, categoria
    FROM produtos
    ORDER BY nome ASC
");

$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$produtos_js = array_map(function ($p) {
    return [
        'id' => (int)$p['id'],
        'nome' => $p['nome'],
        'preco' => (float)$p['preco'],
        'estoque' => (float)$p['estoque'],
        'unidade' => $p['unidade_medida'],
        'categoria' => $p['categoria']
    ];
}, $produtos);

include __DIR__ . '/layout.php';
?>

<style>
.entrada-page{
    display:flex;
    flex-direction:column;
    gap:22px;
}

.entrada-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:20px;
    flex-wrap:wrap;
}

.entrada-title h2{
    font-size:28px;
    color:#3b2411;
    margin-bottom:6px;
}

.entrada-title p{
    color:#777;
    font-size:15px;
}

.entrada-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.entrada-btn-link,
.entrada-btn{
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

.entrada-btn-primary{
    background:#7b4f27;
    color:white;
}

.entrada-btn-primary:hover{
    background:#5a371a;
    transform:translateY(-2px);
}

.entrada-btn-secondary{
    background:white;
    color:#7b4f27;
    border:1px solid #d6b089;
}

.entrada-btn-secondary:hover{
    background:#fff7ef;
    transform:translateY(-2px);
}

.entrada-grid{
    display:grid;
    grid-template-columns:minmax(0, 1fr) 380px;
    gap:22px;
    align-items:start;
}

.entrada-card{
    background:white;
    border-radius:18px;
    padding:24px;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
}

.card-title{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
    flex-wrap:wrap;
    margin-bottom:20px;
}

.card-title h3{
    color:#3b2411;
    font-size:21px;
}

.form-top{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:16px;
    margin-bottom:22px;
}

.form-group{
    display:flex;
    flex-direction:column;
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
    padding:12px 10px;
    border-bottom:1px solid #eee;
    vertical-align:middle;
}

.entrada-table tr:hover td{
    background:#fff8f1;
}

.entrada-table select,
.entrada-table input{
    margin:0;
}

.col-produto{
    min-width:260px;
}

.col-unidade{
    width:100px;
    text-align:center;
}

.col-qtd{
    width:130px;
}

.col-validade{
    width:160px;
}

.col-preco{
    width:140px;
}

.col-subtotal{
    width:140px;
    text-align:right;
    font-weight:bold;
}

.col-acao{
    width:90px;
    text-align:center;
}

.badge-unidade{
    display:inline-block;
    padding:6px 10px;
    border-radius:20px;
    background:#fdf3e7;
    color:#7b4f27;
    font-size:12px;
    font-weight:bold;
    text-transform:uppercase;
}

.btn-add{
    background:#7b4f27;
    color:white;
    border:0;
    padding:11px 16px;
    border-radius:10px;
    cursor:pointer;
    font-weight:bold;
    transition:0.3s;
}

.btn-add:hover{
    background:#5a371a;
    transform:translateY(-2px);
}

.btn-remover{
    background:#fff1f1;
    color:#c0392b;
    border:1px solid #f2b8b8;
    padding:8px 10px;
    border-radius:9px;
    cursor:pointer;
    font-weight:bold;
    transition:0.3s;
}

.btn-remover:hover{
    background:#c0392b;
    color:white;
}

.empty-itens{
    text-align:center;
    padding:35px 20px;
    color:#777;
    background:#fff8f1;
    border-radius:14px;
    border:1px dashed #d6b089;
    margin-top:12px;
}

.empty-itens strong{
    display:block;
    color:#3b2411;
    margin-bottom:6px;
}

.resumo-card{
    position:sticky;
    top:20px;
}

.total-box{
    background:#7b4f27;
    color:white;
    border-radius:20px;
    padding:26px 22px;
    margin-bottom:18px;
    min-height:135px;
    display:flex;
    flex-direction:column;
    justify-content:center;
}

.total-box span{
    display:block;
    font-size:15px;
    opacity:0.9;
    margin-bottom:10px;
}

.total-box strong{
    display:flex;
    align-items:baseline;
    gap:10px;
    white-space:nowrap;
    line-height:1;
}

.total-box strong small{
    font-size:25px;
    font-weight:bold;
}

#totalNota{
    font-size:48px;
    font-weight:bold;
}

.resumo-info{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
    margin-bottom:18px;
}

.resumo-mini{
    background:#fdf3e7;
    border-radius:14px;
    padding:14px;
}

.resumo-mini span{
    display:block;
    color:#777;
    font-size:13px;
    margin-bottom:5px;
}

.resumo-mini strong{
    color:#3b2411;
    font-size:18px;
}

.info-box{
    background:#fff8f1;
    border:1px dashed #d6b089;
    color:#5a371a;
    border-radius:14px;
    padding:14px;
    font-size:14px;
    line-height:1.5;
    margin-bottom:16px;
}

.btn-registrar{
    width:100%;
    padding:15px;
    font-size:17px;
    font-weight:bold;
    background:#28a745;
    color:white;
    border:0;
    border-radius:12px;
    cursor:pointer;
    transition:0.3s;
}

.btn-registrar:hover{
    background:#218838;
    transform:translateY(-2px);
}

.aviso{
    padding:12px;
    border-radius:12px;
    background:#fff1f1;
    color:#b00000;
    margin-bottom:15px;
    display:none;
    font-weight:bold;
}

.sem-produtos{
    background:#fff1f1;
    color:#b00000;
    padding:18px;
    border-radius:14px;
    font-weight:bold;
    text-align:center;
}

@media(max-width:1050px){
    .entrada-grid{
        grid-template-columns:1fr;
    }

    .resumo-card{
        position:static;
    }
}

@media(max-width:700px){
    .form-top{
        grid-template-columns:1fr;
    }

    .entrada-actions{
        width:100%;
    }

    .entrada-btn-link{
        width:100%;
    }
}
</style>

<div class="entrada-page">

    <div class="entrada-header">
        <div class="entrada-title">
            <h2>Entrada de Produtos</h2>
            <p>Registre produtos recebidos por nota fiscal e atualize o estoque automaticamente.</p>
        </div>

        <div class="entrada-actions">
            <a href="historico_entradas.php" class="entrada-btn-link entrada-btn-primary">
                Histórico de Entradas
            </a>

            <a href="dashboard.php" class="entrada-btn-link entrada-btn-secondary">
                Voltar
            </a>
        </div>
    </div>

    <?php if (count($produtos) === 0): ?>

        <div class="sem-produtos">
            Nenhum produto cadastrado. Cadastre um produto antes de registrar uma entrada.
        </div>

    <?php else: ?>

        <form method="POST" action="../controllers/EntradaController.php" onsubmit="return validarEntrada()">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div class="entrada-grid">

                <div class="entrada-card">

                    <div class="card-title">
                        <h3>Dados da nota</h3>
                    </div>

                    <div class="form-top">
                        <div class="form-group">
                            <label>Número da nota</label>
                            <input 
                                type="text" 
                                name="numero_nota" 
                                id="numeroNota"
                                placeholder="Ex: NF-001234"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>Fornecedor</label>
                            <input 
                                type="text" 
                                name="fornecedor" 
                                id="fornecedor"
                                placeholder="Ex: Fornecedor São João"
                                required
                            >
                        </div>
                    </div>

                    <div class="card-title">
                        <h3>Produtos da entrada</h3>

                        <button type="button" class="btn-add" onclick="adicionarItem()">
                            Adicionar produto
                        </button>
                    </div>

                    <div id="avisoEntrada" class="aviso"></div>

                    <div id="emptyItens" class="empty-itens">
                        <strong>Nenhum produto adicionado ainda.</strong>
                        Clique em “Adicionar produto” para começar o lançamento da nota.
                    </div>

                    <div class="table-wrap">
                        <table class="entrada-table" id="tabelaEntrada" style="display:none;">
                            <thead>
                                <tr>
                                    <th class="col-produto">Produto</th>
                                    <th class="col-unidade">Unidade</th>
                                    <th class="col-qtd">Quantidade</th>
                                    <th class="col-validade">Validade</th>
                                    <th class="col-preco">Preço</th>
                                    <th class="col-subtotal">Subtotal</th>
                                    <th class="col-acao"></th>
                                </tr>
                            </thead>

                            <tbody id="itens"></tbody>
                        </table>
                    </div>

                </div>

                <div class="entrada-card resumo-card">
                    <h3 style="color:#3b2411; margin-bottom:18px;">Resumo da nota</h3>

                    <div class="total-box">
                        <span>Total estimado</span>

                        <strong>
                            <small>R$</small>
                            <span id="totalNota">0,00</span>
                        </strong>
                    </div>

                    <div class="resumo-info">
                        <div class="resumo-mini">
                            <span>Itens</span>
                            <strong id="qtdItens">0</strong>
                        </div>

                        <div class="resumo-mini">
                            <span>Produtos</span>
                            <strong id="qtdProdutos">0</strong>
                        </div>
                    </div>

                    <div class="info-box">
                        Ao registrar a nota, o sistema cria um lote com quantidade e validade próprias e atualiza o estoque total.
                    </div>

                    <button type="submit" class="btn-registrar">
                        Registrar Nota
                    </button>
                </div>

            </div>
        </form>

    <?php endif; ?>

</div>

<script>
const produtos = <?= json_encode($produtos_js, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

function formatarMoeda(valor){
    return Number(valor || 0).toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function escapeHTML(texto){
    return String(texto ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function montarOptions(){
    let options = `<option value="">Selecione o produto</option>`;

    produtos.forEach(function(produto){
        options += `
            <option 
                value="${produto.id}"
                data-preco="${produto.preco}"
                data-unidade="${escapeHTML(produto.unidade)}"
                data-estoque="${produto.estoque}">
                ${escapeHTML(produto.nome)}
            </option>
        `;
    });

    return options;
}

function atualizarVisibilidadeTabela(){
    const linhas = document.querySelectorAll("#itens tr").length;

    document.getElementById("emptyItens").style.display = linhas === 0 ? "block" : "none";
    document.getElementById("tabelaEntrada").style.display = linhas === 0 ? "none" : "table";
}

function adicionarItem(){
    const tabela = document.getElementById("itens");
    const novaLinha = document.createElement("tr");

    novaLinha.innerHTML = `
        <td class="col-produto">
            <select name="produto_id[]" onchange="atualizarProduto(this)" required>
                ${montarOptions()}
            </select>
        </td>

        <td class="col-unidade">
            <span class="badge-unidade unidade">-</span>
        </td>

        <td class="col-qtd">
            <input 
                type="number" 
                name="quantidade[]" 
                class="quantidade"
                value="1"
                min="1"
                step="1"
                oninput="calcularTotalNota()"
                required
            >
        </td>

        <td class="col-validade">
            <input 
                type="date" 
                name="validade[]" 
                class="validade"
                required
            >
        </td>

        <td class="col-preco">
            <input 
                type="number" 
                name="preco[]" 
                class="preco"
                value="0.00"
                min="0.01"
                step="0.01"
                oninput="calcularTotalNota()"
                required
            >
        </td>

        <td class="col-subtotal">
            R$ <span class="subtotal">0,00</span>
        </td>

        <td class="col-acao">
            <button type="button" class="btn-remover" onclick="removerLinha(this)">
                Remover
            </button>
        </td>
    `;

    tabela.appendChild(novaLinha);

    atualizarVisibilidadeTabela();
    calcularTotalNota();
}

function atualizarProduto(select){
    const linha = select.closest("tr");
    const option = select.selectedOptions[0];

    const preco = parseFloat(option.dataset.preco) || 0;
    const unidade = option.dataset.unidade || "-";

    linha.querySelector(".unidade").innerText = unidade;

    const inputQuantidade = linha.querySelector(".quantidade");
    const inputPreco = linha.querySelector(".preco");

    if(unidade === "kg"){
        inputQuantidade.step = "0.001";
        inputQuantidade.min = "0.001";
        inputQuantidade.value = "1.000";
        inputQuantidade.placeholder = "Ex: 5.500";
    }else{
        inputQuantidade.step = "1";
        inputQuantidade.min = "1";
        inputQuantidade.value = "1";
        inputQuantidade.placeholder = "Ex: 10";
    }

    inputPreco.value = preco.toFixed(2);

    calcularTotalNota();
}

function calcularTotalNota(){
    let total = 0;
    let qtdItens = 0;
    let qtdProdutos = 0;

    document.querySelectorAll("#itens tr").forEach(function(linha){
        const produtoId = linha.querySelector("select[name='produto_id[]']").value;
        const quantidade = parseFloat(linha.querySelector(".quantidade").value) || 0;
        const preco = parseFloat(linha.querySelector(".preco").value) || 0;

        const subtotal = quantidade * preco;

        linha.querySelector(".subtotal").innerText = formatarMoeda(subtotal);

        total += subtotal;
        qtdItens += quantidade;

        if(produtoId !== ""){
            qtdProdutos++;
        }
    });

    document.getElementById("totalNota").innerText = formatarMoeda(total);

    document.getElementById("qtdItens").innerText = qtdItens.toLocaleString("pt-BR", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 3
    });

    document.getElementById("qtdProdutos").innerText = qtdProdutos;
}

function removerLinha(botao){
    botao.closest("tr").remove();

    atualizarVisibilidadeTabela();
    calcularTotalNota();
}

function mostrarAviso(mensagem){
    const aviso = document.getElementById("avisoEntrada");

    aviso.innerText = mensagem;
    aviso.style.display = "block";

    setTimeout(function(){
        aviso.style.display = "none";
    }, 4000);
}

function validarEntrada(){
    const linhas = document.querySelectorAll("#itens tr");

    if(linhas.length === 0){
        mostrarAviso("Adicione pelo menos um produto antes de registrar a nota.");
        return false;
    }

    const numeroNota = document.getElementById("numeroNota").value.trim();
    const fornecedor = document.getElementById("fornecedor").value.trim();

    if(numeroNota === ""){
        mostrarAviso("Informe o número da nota.");
        return false;
    }

    if(fornecedor === ""){
        mostrarAviso("Informe o fornecedor.");
        return false;
    }

    for(const linha of linhas){
        const produto = linha.querySelector("select[name='produto_id[]']").value;
        const quantidade = parseFloat(linha.querySelector(".quantidade").value) || 0;
        const validade = linha.querySelector(".validade").value;
        const preco = parseFloat(linha.querySelector(".preco").value) || 0;

        if(produto === ""){
            mostrarAviso("Selecione todos os produtos da nota.");
            return false;
        }

        if(quantidade <= 0){
            mostrarAviso("Informe uma quantidade válida para todos os produtos.");
            return false;
        }

        if(validade === ""){
            mostrarAviso("Informe a validade de todos os produtos.");
            return false;
        }

        if(preco <= 0){
            mostrarAviso("Informe um preço válido para todos os produtos.");
            return false;
        }
    }

    return true;
}

atualizarVisibilidadeTabela();
calcularTotalNota();
</script>

</div>
</div>
</div>
</body>
</html>
