<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$required_perfil = null; 
require_once __DIR__ . '/../verifica_sessao.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils.php';

$usuario = $_SESSION['usuario'] ?? ['email' => 'Usuário'];
$perfil = $_SESSION['perfil'] ?? 'user';
$mensagem = flash_get('sucesso');
$info = flash_get('info');
$erro = flash_get('erro');
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard - Minha Loja</title>

  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">

  <style>
    :root{
      --bg-top: #fdf3e7;
      --bg-bottom: #f5d0a9;
      --accent: #7b4f27;
      --accent-strong: #a66d3a;
      --card-bg: rgba(255,255,255,0.98);
      --success-bg: #e6ffe6;
      --error-bg: #ffe6e6;
      --info-bg: #eef6ff;
      --muted: #666;
    }

    html,body{
      height:100%;
      margin:0;
      font-family: 'Roboto', sans-serif;
      background: linear-gradient(to bottom, var(--bg-top), var(--bg-bottom));
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
    }

    .wrap {
      min-height:100%;
      display:flex;
      align-items:center;
      justify-content:center;
      padding: 32px;
    }

    .dashboard {
      width: 100%;
      max-width: 1100px; 
      min-height: 600px; 
      background: var(--card-bg);
      border-radius: 18px;
      box-shadow: 0 12px 30px rgba(0,0,0,0.18);
      overflow: hidden;
      display: grid;
      grid-template-columns: 280px 1fr;
      gap: 0;
    }

    .sidebar {
      background: linear-gradient(180deg, #fffefc, #fbf3e9);
      padding: 28px 20px;
      border-right: 1px solid rgba(0,0,0,0.04);
    }

    .brand {
      font-size:18px;
      font-weight:700;
      color: var(--accent);
      display:flex;
      align-items:center;
      gap:10px;
      margin-bottom: 18px;
    }

    .avatar {
      width:48px;
      height:48px;
      border-radius:50%;
      background: linear-gradient(135deg, var(--accent), var(--accent-strong));
      color: #fff;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      font-weight:700;
    }

    .user-info { margin: 12px 0 20px 0; }
    .user-info p { margin:4px 0; color:var(--muted); font-size:14px; }
    .user-info strong { color:var(--accent); display:block; font-size:15px; }

    .nav-list { list-style:none; padding:0; margin:12px 0 0;}
    .nav-list li { margin-bottom:10px; }
    .nav-list a {
      display:block;
      padding:10px 12px;
      border-radius:10px;
      text-decoration:none;
      color:var(--accent);
      font-weight:500;
      transition: background .18s, color .18s;
    }
    .nav-list a:hover { background:#fff4e8; color:var(--accent-strong); }

    .logout-btn {
      display:block;
      margin-top:22px;
      padding:10px 12px;
      background:var(--accent);
      color:#fff;
      text-align:center;
      border-radius:10px;
      text-decoration:none;
      font-weight:600;
    }

    .main { padding: 28px; }
    .header {
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      margin-bottom:18px;
    }
    .title { font-size:20px; font-weight:700; color:var(--accent); }
    .subtitle { color:var(--muted); font-size:14px; }

    .cards {
      display:grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap:14px;
      margin-bottom:18px;
    }
    .card {
      background: #fff;
      border-radius:12px;
      padding:14px;
      box-shadow: 0 6px 14px rgba(0,0,0,0.05);
      border:1px solid rgba(0,0,0,0.03);
    }
    .card h3 { margin:0 0 8px 0; color:var(--accent); font-size:15px; }
    .card p { margin:0; color:var(--muted); font-size:13px; }

    .messages { margin-bottom:16px; }
    .msg { padding:10px; border-radius:8px; font-weight:600; margin-bottom:8px; }
    .msg.sucesso { background: var(--success-bg); color: #007a00; }
    .msg.erro { background: var(--error-bg); color: #b00000; }
    .msg.info { background: var(--info-bg); color: #004a7c; }

    .actions { margin-top:12px; display:flex; gap:10px; flex-wrap:wrap; }
    .btn {
      padding:10px 14px;
      border-radius:10px;
      text-decoration:none;
      font-weight:600;
      border:none;
      cursor:pointer;
    }
    .btn.primary { background:var(--accent); color:#fff; }
    .btn.ghost { background:transparent; color:var(--accent); border:1px solid rgba(123,79,39,0.08); }

    @media (max-width:800px){
      .dashboard{ grid-template-columns: 1fr; }
      .sidebar{ order:2; border-right:0; border-top:1px solid rgba(0,0,0,0.04); }
      .main{ order:1; }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="dashboard">
      <aside class="sidebar">
        <div class="brand">
          <div class="avatar"><?= e(strtoupper(substr($usuario['email'],0,1))) ?></div>
          <div>
            Minha Loja
            <div style="font-size:12px;color:var(--muted);font-weight:500">Painel</div>
          </div>
        </div>

        <div class="user-info">
          <strong><?= e($usuario['email']) ?></strong>
          <p>Perfil: <?= e($perfil) ?></p>
        </div>

        <ul class="nav-list">
          <li><a href="listar_produtos_api.php">Listar Produtos</a></li>
          <li><a href="cadastrar_produto.php">Cadastrar Produto</a></li>
          <li><a href="vencidos.php">Produtos Vencidos</a></li>
          <li><a href="nova_senha.php">Alterar Senha</a></li>
        </ul>

        <a class="logout-btn" href="../controllers/logout.php">Sair</a>
      </aside>

      <main class="main">
        <div class="header">
          <div>
            <div class="title">Bem-vindo, <?= e($usuario['email']) ?></div>
            <div class="subtitle">Gerencie seus produtos, vendas e usuários aqui.</div>
          </div>
          <div>
            
            <a class="btn primary" href="cadastrar.php">Novo Cadastro</a>
          </div>
        </div>

        <div class="messages">
          <?php if ($mensagem): ?>
            <div class="msg sucesso"><?= e($mensagem) ?></div>
          <?php endif; ?>
          <?php if ($info): ?>
            <div class="msg info"><?= e($info) ?></div>
          <?php endif; ?>
          <?php if ($erro): ?>
            <div class="msg erro"><?= e($erro) ?></div>
          <?php endif; ?>
        </div>

        <div class="cards">
          <div class="card">
            <h3>Resumo de estoque</h3>
            <p>Aqui você pode colocar resumo rápido: total de produtos, produtos críticos, etc.</p>
          </div>

          <div class="card">
            <h3>Vendas recentes</h3>
            <p>Últimas vendas cadastradas (exibir em tabela na versão completa).</p>
          </div>

          <div class="card">
            <h3>Notificações</h3>
            <p>Avisos sobre produtos vencidos ou estoque baixo.</p>
          </div>
        </div>

        <section>
          <h3 style="color:var(--accent); margin:0 0 8px 0">Ações rápidas</h3>
          <div class="actions">
            <a class="btn primary" href="cadastrar_produto.php">Cadastrar Produto</a>
            <a class="btn ghost" href="listar_produtos_api.php">Listar Produtos</a>
            <a class="btn ghost" href="vencidos.php">Ver Vencidos</a>
          </div>
        </section>
      </main>
    </div>
  </div>
</body>
</html>
