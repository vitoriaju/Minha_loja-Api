<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_once __DIR__ . '/../utils.php';

include __DIR__ . '/layout.php';
?>

<style>
.produtos-page{
    display:flex;
    flex-direction:column;
    gap:22px;
}

.produtos-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:20px;
    flex-wrap:wrap;
}

.produtos-title h2{
    font-size:28px;
    color:#3b2411;
    margin-bottom:6px;
}

.produtos-title p{
    color:#777;
    font-size:15px;
}

.produtos-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.prod-btn,
.prod-btn-link{
    border:0;
    border-radius:10px;
    padding:11px 15px;
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

.prod-btn-primary{
    background:#7b4f27;
    color:white;
}

.prod-btn-primary:hover{
    background:#5a371a;
    transform:translateY(-2px);
}

.prod-btn-secondary{
    background:white;
    color:#7b4f27;
    border:1px solid #d6b089;
}

.prod-btn-secondary:hover{
    background:#fff7ef;
    transform:translateY(-2px);
}

.prod-btn-danger{
    background:#fff1f1;
    color:#c0392b;
    border:1px solid #f2b8b8;
}

.prod-btn-danger:hover{
    background:#c0392b;
    color:white;
}

.produtos-stats{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(210px, 1fr));
    gap:16px;
}

.prod-stat-card{
    background:white;
    border-radius:16px;
    padding:18px;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
    border-left:6px solid #7b4f27;
}

.prod-stat-card span{
    color:#777;
    font-weight:bold;
    font-size:14px;
}

.prod-stat-card h3{
    margin-top:8px;
    color:#3b2411;
    font-size:30px;
}

.prod-stat-card.red{
    border-left-color:#c0392b;
}

.prod-stat-card.orange{
    border-left-color:#e67e22;
}

.prod-stat-card.green{
    border-left-color:#2e8b57;
}

.produtos-card{
    background:white;
    border-radius:18px;
    padding:22px;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
}

.produtos-toolbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
    flex-wrap:wrap;
    margin-bottom:18px;
}

.search-area{
    display:flex;
    align-items:center;
    gap:10px;
    flex:1;
    min-width:280px;
}

.search-area input{
    margin:0;
    flex:1;
    min-width:220px;
}

.table-wrap{
    width:100%;
    overflow-x:auto;
}

.produtos-table{
    width:100%;
    border-collapse:collapse;
    margin-top:0;
}

.produtos-table th{
    background:#f5d0a9;
    color:#3b2411;
    padding:13px 10px;
    font-size:14px;
    white-space:nowrap;
}

.produtos-table td{
    padding:13px 10px;
    border-bottom:1px solid #eee;
    vertical-align:middle;
}

.produtos-table tr:hover td{
    background:#fff8f1;
}

.produto-nome{
    font-weight:bold;
    color:#3b2411;
}

.produto-id{
    color:#777;
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

.badge-ok{
    background:#e8f8ef;
    color:#1f7a45;
}

.badge-baixo{
    background:#fff4df;
    color:#b45f00;
}

.badge-vencido{
    background:#fff1f1;
    color:#c0392b;
}

.badge-vencer{
    background:#fff4df;
    color:#b45f00;
}

.badge-info{
    background:#eef3ff;
    color:#2c5aa0;
}

.actions-cell{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}

.loading-box,
.empty-box{
    text-align:center;
    padding:26px;
    color:#777;
    font-weight:bold;
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
    max-width:620px;
    border-radius:18px;
    box-shadow:0 10px 30px rgba(0,0,0,0.25);
    overflow:hidden;
}

.modal-header{
    background:#7b4f27;
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

.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
}

.form-group label{
    display:block;
    color:#3b2411;
    font-weight:bold;
    margin-bottom:6px;
}

.form-group input,
.form-group select{
    margin:0;
}

.form-group.full{
    grid-column:1 / -1;
}

.modal-actions{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    margin-top:20px;
    flex-wrap:wrap;
}

.delete-modal{
    max-width:430px;
}

.delete-text{
    color:#555;
    line-height:1.5;
}

.delete-text strong{
    color:#c0392b;
}

@media(max-width:800px){
    .form-grid{
        grid-template-columns:1fr;
    }

    .produtos-toolbar{
        align-items:stretch;
    }

    .search-area{
        flex-direction:column;
        align-items:stretch;
    }

    .produtos-actions{
        width:100%;
    }

    .prod-btn-link,
    .prod-btn{
        width:100%;
    }
}
</style>

<div class="produtos-page">

    <div class="produtos-header">
        <div class="produtos-title">
            <h2>Produtos Cadastrados</h2>
            <p>Consulte, pesquise, edite e acompanhe o estoque dos produtos.</p>
        </div>

        <div class="produtos-actions">
            <a href="cadastrar_Produto.php" class="prod-btn-link prod-btn-primary">
                Cadastrar Produto
            </a>

            <a href="dashboard.php" class="prod-btn-link prod-btn-secondary">
                Voltar
            </a>
        </div>
    </div>

    <div class="produtos-stats">
        <div class="prod-stat-card">
            <span>Total de produtos</span>
            <h3 id="stat-total">0</h3>
        </div>

        <div class="prod-stat-card orange">
            <span>Estoque baixo</span>
            <h3 id="stat-baixo">0</h3>
        </div>

        <div class="prod-stat-card red">
            <span>Produtos vencidos</span>
            <h3 id="stat-vencidos">0</h3>
        </div>

        <div class="prod-stat-card green">
            <span>Produtos em dia</span>
            <h3 id="stat-ok">0</h3>
        </div>
    </div>

    <div class="produtos-card">

        <div class="produtos-toolbar">
            <div class="search-area">
                <input 
                    id="input-search" 
                    type="text" 
                    placeholder="Pesquisar por nome do produto..."
                >

                <button type="button" id="btn-search" class="prod-btn prod-btn-primary">
                    Buscar
                </button>

                <button type="button" id="btn-reset" class="prod-btn prod-btn-secondary">
                    Limpar
                </button>
            </div>

            <button type="button" id="btn-refresh" class="prod-btn prod-btn-secondary">
                Atualizar
            </button>
        </div>

        <div class="table-wrap">
            <table class="produtos-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Produto</th>
                        <th>Preço</th>
                        <th>Unidade</th>
                        <th>Categoria</th>
                        <th>Validade</th>
                        <th>Estoque</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody id="tabela-produtos">
                    <tr>
                        <td colspan="9" class="loading-box">Carregando produtos...</td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

</div>

<div id="toast" class="toast"></div>

<!-- MODAL EDITAR -->
<div id="modal-edit" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Editar Produto</h3>
            <button type="button" class="modal-close" onclick="fecharModalEditar()">×</button>
        </div>

        <div class="modal-body">
            <form id="form-edit">
                <input type="hidden" name="id" id="edit-id">

                <div class="form-grid">
                    <div class="form-group full">
                        <label>Nome do produto</label>
                        <input type="text" name="nome" id="edit-nome" required>
                    </div>

                    <div class="form-group">
                        <label>Preço</label>
                        <input type="number" name="preco" id="edit-preco" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label>Unidade</label>
                        <select name="unidade_medida" id="edit-unidade" required>
                            <option value="unidade">Unidade</option>
                            <option value="kg">Kg</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Categoria</label>
                        <input type="text" name="categoria" id="edit-categoria">
                    </div>

                    <div class="form-group">
                        <label>Validade</label>
                        <input type="date" name="validade" id="edit-validade">
                    </div>

                    <div class="form-group">
                        <label>Estoque</label>
                        <input type="number" name="estoque" id="edit-estoque" step="0.001" min="0" required>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="prod-btn prod-btn-secondary" onclick="fecharModalEditar()">
                        Cancelar
                    </button>

                    <button type="submit" class="prod-btn prod-btn-primary">
                        Salvar alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EXCLUIR -->
<div id="modal-delete" class="modal-overlay">
    <div class="modal-box delete-modal">
        <div class="modal-header">
            <h3>Excluir Produto</h3>
            <button type="button" class="modal-close" onclick="fecharModalExcluir()">×</button>
        </div>

        <div class="modal-body">
            <p class="delete-text">
                Tem certeza que deseja excluir o produto
                <strong id="delete-produto-nome"></strong>?
                <br>
                Essa ação não poderá ser desfeita.
            </p>

            <input type="hidden" id="delete-produto-id">

            <div class="modal-actions">
                <button type="button" class="prod-btn prod-btn-secondary" onclick="fecharModalExcluir()">
                    Cancelar
                </button>

                <button type="button" class="prod-btn prod-btn-danger" onclick="confirmarExclusao()">
                    Excluir
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const API = "../api/produtos.php";
const CSRF_TOKEN = "<?= e(csrf_token()) ?>";

let produtos = [];

function escapeHTML(texto){
    return String(texto ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function formatarMoeda(valor){
    return Number(valor || 0).toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatarNumero(valor, unidade){
    const numero = Number(valor || 0);

    if(unidade === "kg"){
        return numero.toLocaleString("pt-BR", {
            minimumFractionDigits: 3,
            maximumFractionDigits: 3
        });
    }

    return numero.toLocaleString("pt-BR", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
}

function formatarData(data){
    if(!data){
        return "-";
    }

    const partes = data.split("-");
    if(partes.length !== 3){
        return data;
    }

    return `${partes[2]}/${partes[1]}/${partes[0]}`;
}

function hojeISO(){
    const hoje = new Date();
    hoje.setHours(0, 0, 0, 0);
    return hoje.toISOString().split("T")[0];
}

function dataDaquiSeteDiasISO(){
    const data = new Date();
    data.setHours(0, 0, 0, 0);
    data.setDate(data.getDate() + 7);
    return data.toISOString().split("T")[0];
}

function statusProduto(p){
    const estoque = Number(p.estoque || 0);
    const minimo = Number(p.estoque_minimo || 0);
    const validade = p.validade || "";
    const hoje = hojeISO();
    const seteDias = dataDaquiSeteDiasISO();

    if(validade && validade < hoje){
        return {
            texto: "Vencido",
            classe: "badge-vencido"
        };
    }

    if(validade && validade >= hoje && validade <= seteDias){
        return {
            texto: "Perto do vencimento",
            classe: "badge-vencer"
        };
    }

    if(estoque <= minimo){
        return {
            texto: "Estoque baixo",
            classe: "badge-baixo"
        };
    }

    return {
        texto: "Em dia",
        classe: "badge-ok"
    };
}

function mostrarToast(mensagem, tipo = ""){
    const toast = document.getElementById("toast");

    toast.innerText = mensagem;
    toast.className = "toast " + tipo;
    toast.style.display = "block";

    setTimeout(() => {
        toast.style.display = "none";
    }, 3500);
}

async function carregar(){
    const tbody = document.getElementById("tabela-produtos");

    tbody.innerHTML = `
        <tr>
            <td colspan="9" class="loading-box">Carregando produtos...</td>
        </tr>
    `;

    try{
        const res = await fetch(API);

        if(!res.ok){
            throw new Error("Erro ao carregar produtos.");
        }

        produtos = await res.json();

        render();
        atualizarEstatisticas();

    }catch(e){
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="empty-box">Erro ao carregar produtos.</td>
            </tr>
        `;

        mostrarToast("Erro ao carregar produtos.", "error");
    }
}

function render(){
    const tbody = document.getElementById("tabela-produtos");

    if(produtos.length === 0){
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="empty-box">Nenhum produto encontrado.</td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = produtos.map(p => {
        const status = statusProduto(p);

        return `
            <tr>
                <td class="produto-id">#${escapeHTML(p.id)}</td>

                <td>
                    <span class="produto-nome">${escapeHTML(p.nome)}</span>
                </td>

                <td>
                    <strong>R$ ${formatarMoeda(p.preco)}</strong>
                </td>

                <td>
                    <span class="badge badge-info">${escapeHTML(p.unidade_medida)}</span>
                </td>

                <td>${escapeHTML(p.categoria || "-")}</td>

                <td>${formatarData(p.validade)}</td>

                <td>
                    ${formatarNumero(p.estoque, p.unidade_medida)}
                    ${escapeHTML(p.unidade_medida)}
                </td>

                <td>
                    <span class="badge ${status.classe}">
                        ${status.texto}
                    </span>
                </td>

                <td>
                    <div class="actions-cell">
                        <button type="button" class="prod-btn prod-btn-secondary" onclick="abrirModalEditar(${Number(p.id)})">
                            Editar
                        </button>

                        <button type="button" class="prod-btn prod-btn-danger" onclick="abrirModalExcluir(${Number(p.id)})">
                            Excluir
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join("");
}

function atualizarEstatisticas(){
    let total = produtos.length;
    let baixo = 0;
    let vencidos = 0;
    let ok = 0;

    const hoje = hojeISO();

    produtos.forEach(p => {
        const estoque = Number(p.estoque || 0);
        const minimo = Number(p.estoque_minimo || 0);
        const validade = p.validade || "";

        if(validade && validade < hoje){
            vencidos++;
        }else if(estoque <= minimo){
            baixo++;
        }else{
            ok++;
        }
    });

    document.getElementById("stat-total").innerText = total;
    document.getElementById("stat-baixo").innerText = baixo;
    document.getElementById("stat-vencidos").innerText = vencidos;
    document.getElementById("stat-ok").innerText = ok;
}

async function buscar(){
    const q = document.getElementById("input-search").value.trim();

    const tbody = document.getElementById("tabela-produtos");
    tbody.innerHTML = `
        <tr>
            <td colspan="9" class="loading-box">Buscando produtos...</td>
        </tr>
    `;

    try{
        const res = await fetch(API + "?search=" + encodeURIComponent(q));

        if(!res.ok){
            throw new Error("Erro na busca.");
        }

        produtos = await res.json();

        render();
        atualizarEstatisticas();

    }catch(e){
        mostrarToast("Erro ao buscar produtos.", "error");
    }
}

function abrirModalEditar(id){
    const p = produtos.find(x => Number(x.id) === Number(id));

    if(!p){
        mostrarToast("Produto não encontrado.", "error");
        return;
    }

    document.getElementById("edit-id").value = p.id;
    document.getElementById("edit-nome").value = p.nome || "";
    document.getElementById("edit-preco").value = p.preco || "";
    document.getElementById("edit-unidade").value = p.unidade_medida || "unidade";
    document.getElementById("edit-categoria").value = p.categoria || "";
    document.getElementById("edit-validade").value = p.validade || "";
    document.getElementById("edit-estoque").value = p.estoque || "";

    document.getElementById("modal-edit").style.display = "flex";
}

function fecharModalEditar(){
    document.getElementById("modal-edit").style.display = "none";
}

document.getElementById("form-edit").addEventListener("submit", async function(e){
    e.preventDefault();

    const form = new FormData(this);
    form.append("csrf_token", CSRF_TOKEN);

    let preco = String(form.get("preco") || "0").replace(",", ".");
    form.set("preco", preco);

    try{
        const res = await fetch(API, {
            method: "POST",
            body: form
        });

        const data = await res.json();

        if(data.status === "success"){
            fecharModalEditar();
            mostrarToast("Produto atualizado com sucesso.", "success");
            carregar();
        }else{
            mostrarToast(data.msg || data.erro || "Erro ao atualizar produto.", "error");
        }

    }catch(err){
        mostrarToast("Erro na requisição.", "error");
    }
});

function abrirModalExcluir(id){
    const p = produtos.find(x => Number(x.id) === Number(id));

    if(!p){
        mostrarToast("Produto não encontrado.", "error");
        return;
    }

    document.getElementById("delete-produto-id").value = p.id;
    document.getElementById("delete-produto-nome").innerText = p.nome;

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
            fecharModalExcluir();
            mostrarToast("Produto excluído com sucesso.", "success");
            carregar();
        }else{
            mostrarToast(data.msg || "Erro ao excluir produto.", "error");
        }

    }catch(e){
        mostrarToast("Não foi possível excluir. O produto pode estar vinculado a uma venda.", "error");
    }
}

document.getElementById("btn-search").addEventListener("click", buscar);

document.getElementById("btn-reset").addEventListener("click", function(){
    document.getElementById("input-search").value = "";
    carregar();
});

document.getElementById("btn-refresh").addEventListener("click", carregar);

document.getElementById("input-search").addEventListener("keydown", function(e){
    if(e.key === "Enter"){
        e.preventDefault();
        buscar();
    }
});

document.getElementById("modal-edit").addEventListener("click", function(e){
    if(e.target === this){
        fecharModalEditar();
    }
});

document.getElementById("modal-delete").addEventListener("click", function(e){
    if(e.target === this){
        fecharModalExcluir();
    }
});

carregar();
</script>

</div>
</div>
</div>
</body>
</html>