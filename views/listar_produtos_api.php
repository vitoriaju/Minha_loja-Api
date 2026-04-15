<?php
require_once __DIR__ . '/../verifica_sessao.php';

// layout
include __DIR__ . '/layout.php';
?>

<div class="card">

<h2>Produtos Cadastrados</h2>

<div class="header-actions">
    <div class="search-box">
        <input id="input-search" type="text" placeholder="Pesquisar por nome..." />
        <button id="btn-search" class="primary">Buscar</button>
        <button id="btn-reset" class="button">Limpar</button>
    </div>
   <br></br>
    <div>
        <a href="cadastrar_produto.php">
    <button> Cadastrar Produto</button>
</a>

<a href="dashboard.php">
    <button> Voltar</button>
</a>
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


<div id="modal-edit" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); justify-content:center; align-items:center;">
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

</div></div></div></body></html>

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
        <button class="button" onclick="openEdit(${p.id})"> Editar</button>
        <button class="button" onclick="confirmDelete(${p.id})"> Excluir</button>
      </td>
    </tr>
  `).join('');
}

function escapeHtml(text){ if(!text) return ''; return String(text).replace(/[&<>"']/g, function(m){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[m];}); }

document.getElementById('btn-search').addEventListener('click', ()=>{
  const q = document.getElementById('input-search').value.trim();
  loadProducts(q);
});
document.getElementById('btn-reset').addEventListener('click', ()=>{ document.getElementById('input-search').value=''; loadProducts();});

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

document.getElementById('btn-cancel').addEventListener('click', ()=> showModal(false));

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

loadProducts();

</script>