<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Produtos Cadastrados (API)</title>
<link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
<style>
body {
    margin: 0;
    font-family: 'Roboto', sans-serif;
    background: linear-gradient(to bottom, #fdf3e7, #f5d0a9);
    display: flex;
    justify-content: center;
    padding: 50px 0;
}

.container {
    width: 750px;
    background-color: rgba(255,255,255,0.95);
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    text-align: center;
}

h2 {
    font-family: 'Pacifico', cursive;
    font-size: 28px;
    color: #7b4f27;
    margin-bottom: 25px;
}

.table-wrap { overflow-x: auto; }

table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

table, th, td {
    border: 1px solid #7b4f27;
}

th, td {
    padding: 10px;
    text-align: left;
}

th {
    background-color: #7b4f27;
    color: #fff;
}

tr:nth-child(even) {
    background-color: rgba(123,79,39,0.1);
}

a.button {
    display: inline-block;
    padding: 8px 12px;
    margin: 2px 2px;
    background-color: #7b4f27;
    color: #fff;
    text-decoration: none;
    border-radius: 12px;
    font-size: 14px;
    transition: background 0.3s;
}

a.button:hover { background-color: #a66d3a; }

.mensagem {
    text-align: center;
    margin-bottom: 20px;
    font-weight: bold;
    color: #7b4f27;
}

.header-actions { display:flex; gap:10px; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.search-box { display:flex; gap:8px; align-items:center; }
input[type="text"], input[type="number"], input[type="date"], select {
    padding: 8px; border-radius: 6px; border:1px solid #ddd; width: 220px;
}

button.primary {
    background: #7b4f27; color: #fff; border: none; padding:8px 12px; border-radius:6px; cursor:pointer;
}
button.primary:hover { background:#a66d3a }

/* modal */
.modal { position: fixed; inset:0; display:none; justify-content:center; align-items:center; background: rgba(0,0,0,0.4); }
.modal .card { background:#fff; width: 520px; padding:20px; border-radius:12px; }
.form-row { display:flex; gap:8px; margin-bottom:10px; }
.form-row > * { flex:1 }

@media (max-width: 780px) {
    .container { width: 95%; }
    table { font-size: 14px; }
    .form-row { flex-direction: column }
}
</style>
</head>
<body>
<div class="container">
    <h2>Produtos Cadastrados</h2>

    <div class="header-actions">
        <div class="search-box">
            <input id="input-search" type="text" placeholder="Pesquisar por nome..." />
            <button id="btn-search" class="primary">Buscar</button>
            <button id="btn-reset" class="button">Limpar</button>
        </div>
        <div>
            <a href="cadastrar_produto.php" class="button">Cadastrar Novo Produto</a>
            <a href="../views/dashboard.php" class="button">Voltar</a>
        </div>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Preço</th>
                <th>Qualidade</th>
                <th>Categoria</th>
                <th>Validade</th>
                <th>Estoque</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody id="tabela-produtos">
            <tr><td colspan="8" class="mensagem">Carregando produtos...</td></tr>
        </tbody>
      </table>
    </div>
</div>

<!-- Modal de edição -->
<div id="modal-edit" class="modal" role="dialog" aria-hidden="true">
  <div class="card">
    <h3>Editar Produto</h3>
    <form id="form-edit">
      <input type="hidden" name="id" id="edit-id" />
      <div class="form-row">
        <input type="text" name="nome" id="edit-nome" placeholder="Nome" required />
        <input type="number" name="preco" id="edit-preco" step="0.01" placeholder="Preço" required />
      </div>
      <div class="form-row">
        <input type="text" name="qualidade" id="edit-qualidade" placeholder="Qualidade" />
        <input type="text" name="categoria" id="edit-categoria" placeholder="Categoria" />
      </div>
      <div class="form-row">
        <input type="date" name="validade" id="edit-validade" />
        <input type="number" name="estoque" id="edit-estoque" placeholder="Estoque" />
      </div>
      <div style="text-align:right; margin-top:10px">
        <button type="button" id="btn-cancel" class="button">Cancelar</button>
        <button type="submit" class="primary">Salvar</button>
      </div>
    </form>
  </div>
</div>

<script>
const API_BASE = '/Minha_loja2/api/produtos.php';
let produtosCache = [];

async function loadProducts(search=''){
  try{
    let url = API_BASE;
    if(search) url += '?search=' + encodeURIComponent(search);
    const res = await fetch(url);
    const data = await res.json();
    produtosCache = data;
    renderTable(data);
  }catch(err){
    console.error(err);
    document.getElementById('tabela-produtos').innerHTML = '<tr><td colspan="8" class="mensagem">Erro ao carregar produtos.</td></tr>';
  }
}

function renderTable(produtos){
  const tbody = document.getElementById('tabela-produtos');
  if(!produtos || produtos.length === 0){
    tbody.innerHTML = '<tr><td colspan="8" class="mensagem">Nenhum produto encontrado.</td></tr>';
    return;
  }
  tbody.innerHTML = produtos.map(p => `
    <tr>
      <td>${p.id}</td>
      <td>${escapeHtml(p.nome)}</td>
      <td>R$ ${parseFloat(p.preco).toFixed(2).replace('.', ',')}</td>
      <td>${escapeHtml(p.qualidade)}</td>
      <td>${escapeHtml(p.categoria)}</td>
      <td>${escapeHtml(p.validade)}</td>
      <td>${p.estoque}</td>
      <td>
        <button class="button" onclick="openEdit(${p.id})">✏️ Editar</button>
        <button class="button" onclick="confirmDelete(${p.id})">🗑️ Excluir</button>
      </td>
    </tr>
  `).join('');
}

function escapeHtml(text){ if(!text) return ''; return String(text).replace(/[&<>"']/g, function(m){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[m];}); }

// Busca com clique no botão
document.getElementById('btn-search').addEventListener('click', ()=>{
  const q = document.getElementById('input-search').value.trim();
  loadProducts(q);
});
document.getElementById('btn-reset').addEventListener('click', ()=>{ document.getElementById('input-search').value=''; loadProducts();});

// Excluir
function confirmDelete(id){
  if(!confirm('Tem certeza que deseja excluir este produto?')) return;
  fetch(API_BASE + '?delete=' + id)
    .then(r => r.json())
    .then(res => {
      if(res.status === 'success'){
        loadProducts(document.getElementById('input-search').value.trim());
        alert('Produto excluído');
      } else alert('Erro ao excluir');
    }).catch(err=>{console.error(err); alert('Erro na requisição');});
}

// Editar: abrir modal e preencher com dados do cache
function openEdit(id){
  const prod = produtosCache.find(p=>parseInt(p.id)===parseInt(id));
  if(!prod) return alert('Produto não encontrado');
  document.getElementById('edit-id').value = prod.id;
  document.getElementById('edit-nome').value = prod.nome || '';
  document.getElementById('edit-preco').value = parseFloat(prod.preco) || '';
  document.getElementById('edit-qualidade').value = prod.qualidade || '';
  document.getElementById('edit-categoria').value = prod.categoria || '';
  document.getElementById('edit-validade').value = prod.validade || '';
  document.getElementById('edit-estoque').value = prod.estoque || '';
  showModal(true);
}

function showModal(show){
  const m = document.getElementById('modal-edit');
  m.style.display = show? 'flex':'none';
  m.setAttribute('aria-hidden', !show);
}

// cancelar modal
document.getElementById('btn-cancel').addEventListener('click', ()=> showModal(false));

// submit do form de edição
document.getElementById('form-edit').addEventListener('submit', function(e){
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);

  fetch(API_BASE, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
      if(res.status === 'success'){
        showModal(false);
        loadProducts(document.getElementById('input-search').value.trim());
        alert('Produto atualizado com sucesso');
      } else {
        alert('Erro ao atualizar');
      }
    }).catch(err=>{console.error(err); alert('Erro na requisição');});
});

// inicial
loadProducts();
</script>
</body>
</html>