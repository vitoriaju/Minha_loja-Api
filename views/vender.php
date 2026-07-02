<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_once __DIR__ . '/../pdo.php';
require_once __DIR__ . '/../utils.php';

$stmt = $pdo->query("
    SELECT id, nome, preco, estoque, unidade_medida 
    FROM produtos 
    WHERE estoque > 0
    ORDER BY nome ASC
");

$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$produtos_js = array_map(function ($produto) {
    return [
        'id' => (int)$produto['id'],
        'nome' => $produto['nome'],
        'preco' => (float)$produto['preco'],
        'estoque' => (float)$produto['estoque'],
        'unidade' => $produto['unidade_medida']
    ];
}, $produtos);

include __DIR__ . '/layout.php';
?>

<style>
.venda-page{
    max-width:1400px;
    width:100%;
    margin:auto;
}

.venda-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:20px;
    flex-wrap:wrap;
    margin-bottom:22px;
}

.venda-title h2{
    font-size:28px;
    color:#3b2411;
    margin-bottom:6px;
}

.venda-title p{
    color:#777;
    font-size:15px;
}

.venda-status{
    background:white;
    padding:12px 18px;
    border-radius:14px;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
    color:#3b2411;
    font-weight:bold;
}

.venda-grid{
    display:grid;
    grid-template-columns:minmax(0, 1fr) 430px;
    gap:22px;
    align-items:start;
}

.venda-card{
    background:white;
    border-radius:18px;
    padding:22px;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
}

.venda-card-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
    margin-bottom:18px;
    flex-wrap:wrap;
}

.venda-card-header h3{
    font-size:21px;
    color:#3b2411;
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

.tabela-venda{
    width:100%;
    border-collapse:collapse;
    margin-top:0;
}

.tabela-venda th{
    background:#f5d0a9;
    color:#3b2411;
    font-size:14px;
    padding:12px;
}

.tabela-venda td{
    padding:12px;
    border-bottom:1px solid #eee;
    vertical-align:middle;
}

.tabela-venda tr:hover td{
    background:#fff8f1;
}

.tabela-venda select,
.tabela-venda input{
    margin:0;
}

.col-produto{
    min-width:260px;
}

.col-tipo{
    width:100px;
    text-align:center;
}

.col-qtd{
    width:130px;
}

.col-preco,
.col-subtotal{
    width:130px;
    text-align:right;
    font-weight:bold;
}

.col-acao{
    width:90px;
    text-align:center;
}

.tipo-badge{
    display:inline-block;
    padding:6px 10px;
    border-radius:20px;
    background:#fdf3e7;
    color:#7b4f27;
    font-weight:bold;
    font-size:12px;
    text-transform:uppercase;
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

.empty-venda{
    text-align:center;
    padding:35px 20px;
    color:#777;
    background:#fff8f1;
    border-radius:14px;
    border:1px dashed #d6b089;
}

.empty-venda strong{
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
    padding:28px 24px;
    margin-bottom:18px;
    min-height:145px;
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
    font-size:28px;
    font-weight:bold;
}

#totalVenda{
    font-size:58px;
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

.pagamento-area label{
    font-weight:bold;
    color:#3b2411;
    display:block;
    margin-bottom:7px;
}

.pagamento-area select,
.pagamento-area input{
    width:100%;
    margin-bottom:14px;
}

.troco-box{
    display:none;
    background:#f4fff4;
    border-left:5px solid #2e8b57;
    padding:14px;
    border-radius:12px;
    margin-bottom:14px;
}

.troco-box strong{
    color:#2e7d32;
    font-size:18px;
}

.btn-finalizar{
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

.btn-finalizar:hover{
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

@media(max-width:1000px){
    .venda-grid{
        grid-template-columns:1fr;
    }

    .resumo-card{
        position:static;
    }
}
</style>

<div class="venda-page">

    <div class="venda-header">
        <div class="venda-title">
            <h2>Caixa de Venda</h2>
            <p>Selecione os produtos, informe a quantidade e finalize a venda.</p>
        </div>

        <div class="venda-status">
            Venda em andamento
        </div>
    </div>

    <?php if (count($produtos) === 0): ?>

        <div class="sem-produtos">
            Nenhum produto disponível em estoque para venda.
        </div>

    <?php else: ?>

        <form method="POST" action="../controllers/VendaController.php" onsubmit="return validarVenda()">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="valor_recebido" id="valorRecebidoHidden">

            <div class="venda-grid">

                <div class="venda-card">
                    <div class="venda-card-header">
                        <h3>Produtos da venda</h3>

                        <button type="button" class="btn-add" onclick="adicionarItem()">
                            Adicionar produto
                        </button>
                    </div>

                    <div id="avisoVenda" class="aviso"></div>

                    <div id="emptyVenda" class="empty-venda">
                        <strong>Nenhum produto adicionado ainda.</strong>
                        Clique em “Adicionar produto” para começar a venda.
                    </div>

                    <table class="tabela-venda" id="tabelaVenda" style="display:none;">
                        <thead>
                            <tr>
                                <th class="col-produto">Produto</th>
                                <th class="col-tipo">Tipo</th>
                                <th class="col-qtd">Qtd</th>
                                <th class="col-preco">Preço</th>
                                <th class="col-subtotal">Subtotal</th>
                                <th class="col-acao"></th>
                            </tr>
                        </thead>

                        <tbody id="itens"></tbody>
                    </table>
                </div>

                <div class="venda-card resumo-card">
                    <h3 style="color:#3b2411; margin-bottom:18px;">Resumo da venda</h3>

                    <div class="total-box">
                        <span>Total da venda</span>
                     <strong>
                         <small>R$</small>
                          <span id="totalVenda">0,00</span>
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

                    <div class="pagamento-area">
                        <label>Forma de pagamento</label>

                        <select name="forma_pagamento" id="formaPagamento" required>
                            <option value="">Selecione</option>
                            <option value="dinheiro">Dinheiro</option>
                            <option value="cartao">Cartão</option>
                            <option value="pix">Pix</option>
                        </select>

                        <div id="areaTroco" class="troco-box">
                            <label>Valor recebido</label>

                            <input 
                                type="number" 
                                id="valorRecebido" 
                                step="0.01" 
                                min="0"
                                placeholder="Ex: 50.00"
                                oninput="calcularTroco()"
                            >

                            <strong>Troco: R$ <span id="troco">0,00</span></strong>
                        </div>

                        <button type="submit" class="btn-finalizar">
                            Finalizar Venda
                        </button>
                    </div>
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
    return String(texto)
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

    document.getElementById("emptyVenda").style.display = linhas === 0 ? "block" : "none";
    document.getElementById("tabelaVenda").style.display = linhas === 0 ? "none" : "table";
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

        <td class="col-tipo">
            <span class="tipo-badge tipo">-</span>
        </td>

        <td class="col-qtd">
            <input 
                type="number" 
                name="quantidade[]" 
                class="quantidade" 
                value="1" 
                min="1" 
                step="1"
                oninput="calcularTotalVenda()"
                required
            >
        </td>

        <td class="col-preco">
            R$ <span class="preco">0,00</span>
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
    atualizarResumo();
}

function atualizarProduto(select){
    const linha = select.closest("tr");
    const option = select.selectedOptions[0];

    const preco = parseFloat(option.dataset.preco) || 0;
    const unidade = option.dataset.unidade || "-";
    const estoque = parseFloat(option.dataset.estoque) || 0;

    linha.querySelector(".preco").innerText = formatarMoeda(preco);
    linha.querySelector(".tipo").innerText = unidade;

    const input = linha.querySelector(".quantidade");

    if(unidade === "kg"){
        input.step = "0.001";
        input.min = "0.001";
        input.value = "0.100";
        input.placeholder = "Ex: 0.500";
    } else {
        input.step = "1";
        input.min = "1";
        input.value = "1";
        input.placeholder = "Ex: 1";
    }

    input.max = estoque;

    calcularTotalVenda();
}

function calcularTotalVenda(){
    let soma = 0;

    document.querySelectorAll("#itens tr").forEach(function(linha){
        const select = linha.querySelector("select[name='produto_id[]']");
        const option = select.selectedOptions[0];

        const preco = parseFloat(option?.dataset.preco) || 0;
        const quantidade = parseFloat(linha.querySelector(".quantidade").value) || 0;

        const subtotal = preco * quantidade;

        linha.querySelector(".subtotal").innerText = formatarMoeda(subtotal);

        soma += subtotal;
    });

    document.getElementById("totalVenda").innerText = formatarMoeda(soma);

    atualizarResumo();
    calcularTroco();
}

function atualizarResumo(){
    const linhas = document.querySelectorAll("#itens tr");
    let qtdItens = 0;
    let produtosSelecionados = 0;

    linhas.forEach(function(linha){
        const quantidade = parseFloat(linha.querySelector(".quantidade").value) || 0;
        const produtoId = linha.querySelector("select[name='produto_id[]']").value;

        qtdItens += quantidade;

        if(produtoId !== ""){
            produtosSelecionados++;
        }
    });

    document.getElementById("qtdItens").innerText = qtdItens.toLocaleString("pt-BR", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 3
    });

    document.getElementById("qtdProdutos").innerText = produtosSelecionados;
}

function calcularTroco(){
    const totalTexto = document.getElementById("totalVenda").innerText;
    const total = parseFloat(totalTexto.replace(/\./g, "").replace(",", ".")) || 0;

    const recebido = parseFloat(document.getElementById("valorRecebido")?.value) || 0;
    const troco = recebido - total;

    document.getElementById("troco").innerText = troco > 0 ? formatarMoeda(troco) : "0,00";
    document.getElementById("valorRecebidoHidden").value = recebido;
}

function removerLinha(botao){
    botao.closest("tr").remove();

    calcularTotalVenda();
    atualizarVisibilidadeTabela();
}

function mostrarAviso(mensagem){
    const aviso = document.getElementById("avisoVenda");
    aviso.innerText = mensagem;
    aviso.style.display = "block";

    setTimeout(function(){
        aviso.style.display = "none";
    }, 4000);
}

function validarVenda(){
    const linhas = document.querySelectorAll("#itens tr");

    if(linhas.length === 0){
        mostrarAviso("Adicione pelo menos um produto antes de finalizar a venda.");
        return false;
    }

    for(const linha of linhas){
        const produto = linha.querySelector("select[name='produto_id[]']").value;
        const quantidade = parseFloat(linha.querySelector(".quantidade").value) || 0;

        if(produto === ""){
            mostrarAviso("Selecione todos os produtos antes de finalizar.");
            return false;
        }

        if(quantidade <= 0){
            mostrarAviso("Informe uma quantidade válida para todos os produtos.");
            return false;
        }
    }

    const formaPagamento = document.getElementById("formaPagamento").value;

    if(formaPagamento === ""){
        mostrarAviso("Selecione a forma de pagamento.");
        return false;
    }

    if(formaPagamento === "dinheiro"){
        const totalTexto = document.getElementById("totalVenda").innerText;
        const total = parseFloat(totalTexto.replace(/\./g, "").replace(",", ".")) || 0;
        const recebido = parseFloat(document.getElementById("valorRecebido").value) || 0;

        if(recebido < total){
            mostrarAviso("O valor recebido não pode ser menor que o total da venda.");
            return false;
        }
    }

    return true;
}

document.getElementById("formaPagamento")?.addEventListener("change", function(){
    const area = document.getElementById("areaTroco");
    const valorRecebido = document.getElementById("valorRecebido");
    const valorRecebidoHidden = document.getElementById("valorRecebidoHidden");

    if(this.value === "dinheiro"){
        area.style.display = "block";
        valorRecebido.required = true;
    } else {
        area.style.display = "none";
        valorRecebido.required = false;
        valorRecebido.value = "";
        valorRecebidoHidden.value = "0";
        document.getElementById("troco").innerText = "0,00";
    }
});

atualizarVisibilidadeTabela();
</script>

</div>
</div>
</div>
</body>
</html>
