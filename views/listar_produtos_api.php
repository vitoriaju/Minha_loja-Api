<?php
require_once __DIR__ . '/../verifica_sessao.php';
include __DIR__ . '/layout.php';
?>

<div class="card">

<h2>Produtos Cadastrados</h2>

<div class="header-actions">
    <div class="search-box">
        <input id="input-search" type="text" placeholder="Pesquisar por nome..." />
        <button id="btn-search">Buscar</button>
        <button id="btn-reset">Limpar</button>
    </div>

    <br>

    <div>
        <a href="cadastrar_produto.php"><button>Cadastrar Produto</button></a>
        <a href="dashboard.php"><button>Voltar</button></a>
    </div>
</div>

<div class="table-wrap">
<table>
<thead>
<tr>
    <th>ID</th>
    <th>Nome</th>
    <th>Preço</th>
    <th>Unidade</th>
    <th>Categoria</th>
    <th>Validade</th>
    <th>Estoque</th>
    <th>Ações</th>
</tr>
</thead>

<tbody id="tabela-produtos">
<tr><td colspan="8">Carregando...</td></tr>
</tbody>

</table>
</div>

</div>

<!-- MODAL -->
<div id="modal-edit" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); justify-content:center; align-items:center;">
<div class="card">

<h3>Editar Produto</h3>

<form id="form-edit">

<input type="hidden" name="id" id="edit-id">

<input type="text" name="nome" id="edit-nome" placeholder="Nome" required>

<input type="number" name="preco" id="edit-preco" step="0.01" placeholder="Preço" required>

<select name="unidade_medida" id="edit-unidade">
    <option value="unidade">Unidade</option>
    <option value="kg">Kg</option>
</select>

<input type="text" name="categoria" id="edit-categoria" placeholder="Categoria">

<input type="date" name="validade" id="edit-validade">

<input type="number" name="estoque" id="edit-estoque" placeholder="Estoque">

<br><br>

<button type="button" onclick="fecharModal()">Cancelar</button>
<button type="submit">Salvar</button>

</form>

</div>
</div>

<script>

const API = "../api/produtos.php"; // 🔥 CORRIGIDO
let produtos = [];

// carregar produtos
async function carregar(){
    try{
        const res = await fetch(API);
        produtos = await res.json();
        render();
    }catch(e){
        console.error("Erro carregar:", e);
        document.getElementById("tabela-produtos").innerHTML = "<tr><td colspan='8'>Erro ao carregar</td></tr>";
    }
}

// renderizar tabela
function render(){
    const tbody = document.getElementById("tabela-produtos");

    tbody.innerHTML = produtos.map(p => `
    <tr>
        <td>${p.id}</td>
        <td>${p.nome}</td>
        <td>R$ ${parseFloat(p.preco).toFixed(2).replace('.', ',')}</td>
        <td>${p.unidade_medida}</td>
        <td>${p.categoria}</td>
        <td>${p.validade || ''}</td>
        <td>${p.estoque}</td>
        <td>
            <button onclick="editar(${p.id})">Editar</button>
            <button onclick="deletar(${p.id})">Excluir</button>
        </td>
    </tr>
    `).join("");
}

// abrir modal
function editar(id){
    const p = produtos.find(x => x.id == id);

    document.getElementById("edit-id").value = p.id;
    document.getElementById("edit-nome").value = p.nome;
    document.getElementById("edit-preco").value = p.preco;
    document.getElementById("edit-unidade").value = p.unidade_medida;
    document.getElementById("edit-categoria").value = p.categoria;
    document.getElementById("edit-validade").value = p.validade;
    document.getElementById("edit-estoque").value = p.estoque;

    document.getElementById("modal-edit").style.display = "flex";
}

// fechar modal
function fecharModal(){
    document.getElementById("modal-edit").style.display = "none";
}

// salvar
document.getElementById("form-edit").addEventListener("submit", async function(e){
    e.preventDefault();

    let form = new FormData(this);

    // corrigir preço
    let preco = form.get("preco").replace(",", ".");
    form.set("preco", preco);

    try{
        const res = await fetch(API, {
            method: "POST",
            body: form
        });

        const data = await res.json();

        if(data.status === "success"){
            alert("Atualizado com sucesso!");
            fecharModal();
            carregar();
        }else{
            alert("Erro: " + (data.erro || ""));
        }

    }catch(err){
        console.error(err);
        alert("Erro na requisição");
    }
});

// deletar
function deletar(id){
    if(!confirm("Excluir?")) return;

    fetch(API + "?delete=" + id)
    .then(r => r.json())
    .then(() => carregar());
}

// busca
document.getElementById("btn-search").onclick = () => {
    let q = document.getElementById("input-search").value;

    fetch(API + "?search=" + q)
    .then(r => r.json())
    .then(data => {
        produtos = data;
        render();
    });
};

document.getElementById("btn-reset").onclick = carregar;

// iniciar
carregar();

</script>

</div></div></div></body></html>