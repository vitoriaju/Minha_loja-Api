<?php
require_once __DIR__ . '/../utils.php';

$usuario_email = $_SESSION['usuario']['email'] ?? 'Usuário';
$usuario_nome = $_SESSION['usuario']['nome'] ?? $usuario_email;
$perfil_usuario = perfil_usuario_atual();
$primeira_letra = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr($usuario_nome, 0, 1, 'UTF-8'), 'UTF-8')
    : strtoupper(substr($usuario_nome, 0, 1));
$pagina_atual = basename($_SERVER['PHP_SELF']);

$modulos = [
    ['id' => 'inicio', 'titulo' => 'Início', 'icone' => '⌂', 'itens' => [
        ['arquivo' => 'dashboard.php', 'rotulo' => 'Dashboard', 'permissao' => 'dashboard'],
    ]],
    ['id' => 'vendas', 'titulo' => 'Vendas', 'icone' => '▣', 'itens' => [
        ['arquivo' => 'vender.php', 'rotulo' => 'Nova Venda', 'permissao' => 'vendas.criar'],
        ['arquivo' => 'vendas_dia.php', 'rotulo' => 'Vendas do Dia', 'permissao' => 'vendas.dia'],
        ['arquivo' => 'historico_vendas.php', 'rotulo' => 'Histórico de Vendas', 'permissao' => 'vendas.historico'],
    ]],
    ['id' => 'estoque', 'titulo' => 'Estoque', 'icone' => '□', 'itens' => [
        ['arquivo' => 'listar_produtos_api.php', 'rotulo' => 'Listar Produtos', 'permissao' => 'produtos.ver'],
        ['arquivo' => 'cadastrar_Produto.php', 'rotulo' => 'Cadastrar Produto', 'permissao' => 'produtos.gerenciar'],
        ['arquivo' => 'estoque_baixo.php', 'rotulo' => 'Estoque Baixo', 'permissao' => 'estoque.gerenciar'],
        ['arquivo' => 'vencidos.php', 'rotulo' => 'Produtos Vencidos', 'permissao' => 'produtos.validade'],
        ['arquivo' => 'validade.php', 'rotulo' => 'Validade dos Produtos', 'permissao' => 'produtos.validade'],
        ['arquivo' => 'entrada_produtos.php', 'rotulo' => 'Entrada de Produtos', 'permissao' => 'estoque.gerenciar'],
        ['arquivo' => 'historico_entradas.php', 'rotulo' => 'Histórico de Entradas', 'permissao' => 'estoque.gerenciar'],
        ['arquivo' => 'importar_xml.php', 'rotulo' => 'Importar XML', 'permissao' => 'estoque.gerenciar'],
    ]],
    ['id' => 'producao', 'titulo' => 'Produção', 'icone' => '◇', 'itens' => [
        ['arquivo' => 'producao_dia.php', 'rotulo' => 'Produção do Dia', 'permissao' => 'producao'],
        ['arquivo' => 'imprimir_producao.php', 'rotulo' => 'Impressão da Produção', 'permissao' => 'producao'],
    ]],
    ['id' => 'financeiro', 'titulo' => 'Financeiro', 'icone' => '$', 'itens' => [
        ['arquivo' => 'fechamento_dia.php', 'rotulo' => 'Fechamento do Dia', 'permissao' => 'financeiro'],
        ['arquivo' => 'financeiro.php', 'rotulo' => 'Visão Geral', 'permissao' => 'financeiro'],
        ['arquivo' => 'financeiro_mensal.php', 'rotulo' => 'Financeiro Mensal', 'permissao' => 'financeiro'],
        ['arquivo' => 'financeiro_anual.php', 'rotulo' => 'Financeiro Anual', 'permissao' => 'financeiro'],
    ]],
    ['id' => 'administracao', 'titulo' => 'Administração', 'icone' => '⚙', 'itens' => [
        ['arquivo' => 'cadastrar.php', 'rotulo' => 'Criar Usuário', 'permissao' => 'administracao'],
        ['arquivo' => 'recuperar.php', 'rotulo' => 'Alterar Senha', 'permissao' => 'administracao'],
        ['arquivo' => 'auditoria.php', 'rotulo' => 'Auditoria', 'permissao' => 'administracao'],
    ]],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Minha Loja</title>
<link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/painel.css">
<script defer src="<?= e(BASE_URL) ?>/assets/painel.js"></script>
</head>
<body>
<div class="wrapper">
    <aside class="sidebar" id="sidebar" aria-label="Navegação principal">
        <div class="sidebar-brand"><strong>Minha Loja</strong><small>Painel de gestão</small></div>
        <nav class="sidebar-menu">
            <?php foreach ($modulos as $modulo): ?>
                <?php
                $itensVisiveis = array_values(array_filter($modulo['itens'], fn($item) => usuario_pode($item['permissao'])));
                if (!$itensVisiveis) continue;
                $aberto = in_array($pagina_atual, array_column($itensVisiveis, 'arquivo'), true);
                ?>
                <section class="menu-group<?= $aberto ? ' is-open' : '' ?>" data-menu-group>
                    <button class="menu-group-toggle" type="button" aria-expanded="<?= $aberto ? 'true' : 'false' ?>">
                        <span class="menu-group-icon" aria-hidden="true"><?= e($modulo['icone']) ?></span>
                        <span><?= e($modulo['titulo']) ?></span><span class="menu-chevron" aria-hidden="true">⌄</span>
                    </button>
                    <div class="menu-group-items">
                        <?php foreach ($itensVisiveis as $item): ?>
                            <a class="menu-link<?= $pagina_atual === $item['arquivo'] ? ' active' : '' ?>"
                               href="<?= e(BASE_URL) ?>/views/<?= e($item['arquivo']) ?>"
                               <?= $pagina_atual === $item['arquivo'] ? 'aria-current="page"' : '' ?>>
                                <?= e($item['rotulo']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-bottom">
            <form class="logout-form" action="<?= e(BASE_URL) ?>/controllers/logout.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button type="submit">Sair do sistema</button>
            </form>
        </div>
    </aside>
    <button class="sidebar-overlay" type="button" data-menu-close aria-label="Fechar menu"></button>
    <div class="main">
        <header class="header">
            <div class="header-start">
                <button class="menu-mobile-toggle" type="button" data-menu-toggle aria-controls="sidebar" aria-expanded="false" aria-label="Abrir menu">☰</button>
                <span class="header-title">Sistema de Gestão</span>
            </div>
            <div class="user-profile">
                <button type="button" class="user-button" aria-label="Opções da conta">
                    <span class="user-avatar"><?= e($primeira_letra) ?></span>
                    <span class="user-info"><span class="user-name"><?= e($usuario_nome) ?></span><span class="user-role"><?= usuario_eh_admin() ? 'Administrador' : 'Usuário' ?></span></span>
                </button>
                <div class="user-dropdown">
                    <div class="dropdown-title">Minha conta</div>
                    <a href="<?= e(BASE_URL) ?>/views/recuperar.php">Alterar senha</a>
                    <?php if (usuario_pode('administracao')): ?><a href="<?= e(BASE_URL) ?>/views/cadastrar.php">Criar novo usuário</a><?php endif; ?>
                </div>
            </div>
        </header>
        <div class="content">
