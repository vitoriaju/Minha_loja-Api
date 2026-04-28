# Guia do Codigo - Minha Loja API

Este guia explica o que cada parte principal do projeto faz. Ele evita comentar linha por linha dentro dos arquivos, porque comentarios demais deixam o codigo dificil de manter. Use este documento como mapa para entender o sistema.

## Estrutura Geral

- `index.php`: tela de login.
- `config/`: configuracao do sistema e conexoes com o banco.
- `controllers/`: arquivos que recebem formularios e executam regras de negocio.
- `models/`: classes de acesso a dados.
- `views/`: telas HTML/PHP exibidas no navegador.
- `api/`: endpoints JSON usados por JavaScript/fetch.
- `database/`: dump completo e migrations SQL.
- `assets/`: CSS e arquivos estaticos.

## Configuracao

### `.env`
Guarda configuracoes locais e sensiveis, como banco, URL base e dados de e-mail. Ele fica no `.gitignore`, entao nao deve ir para o Git.

### `.env.example`
Modelo publico do `.env`, sem senhas reais.

### `config/config.php`
Carrega variaveis do `.env`, define constantes como `DB_HOST`, `BASE_URL` e `MAIL_FROM_ADDRESS`, inicia a sessao e cria a conexao PDO `$pdo`.

### `config/conexao.php`
Arquivo mantido por compatibilidade com includes antigos. Ele apenas carrega `config.php`; a conexao oficial agora e PDO.

### `pdo.php`
Cria uma conexao PDO com fetch associativo. Varios controllers usam esse arquivo.

## Seguranca e Sessao

### `verifica_sessao.php`
Protege paginas internas. Se nao houver `$_SESSION['usuario']`, redireciona para o login. Tambem controla timeout de inatividade e fornece `require_admin()`.

### `utils.php`
Tem funcoes auxiliares:
- `flash_set()`: salva mensagens temporarias na sessao.
- `flash_get()`: le e apaga mensagens temporarias.
- `e()`: escapa texto antes de mostrar no HTML.

### `controllers/logout.php`
Limpa a sessao, remove cookie e redireciona para o login.

## Login e Usuarios

### `controllers/autentica.php`
Recebe e-mail e senha do login. Busca o usuario, verifica se o e-mail foi confirmado, valida a senha com `password_verify()`, regenera o ID da sessao e grava `usuario`/`perfil` na sessao.

### `views/cadastrar.php`
Tela de cadastro. Deslogado, cria apenas usuario comum. Logado como admin, permite escolher `user` ou `admin`.

### `controllers/cadastrar_usuario.php`
Valida e-mail, senha e dominio. Cria usuario com `email_verificado = 0`, gera token de confirmacao e tenta enviar e-mail.

### `controllers/verificar_email.php`
Recebe `token` pela URL, valida na tabela `email_verifications`, marca o e-mail como confirmado e permite login.

## Recuperacao de Senha

### `views/recuperar.php`
Tela para pedir link de redefinicao de senha.

### `controllers/recuperar_senha.php`
Gera token de recuperacao e salva o hash na tabela `password_resets`.

### `views/nova_senha.php`
Mostra formulario de nova senha apenas se o token for valido e nao expirou.

### `controllers/atualiza_senha.php`
Valida token e senhas, atualiza `usuarios.senha_hash` e marca o token como usado.

## API de Produtos

### `api/index.php`
Roteador simples da API. Exige sessao e redireciona chamadas para endpoints internos.

### `api/produtos.php`
Endpoint JSON protegido por sessao. Lista, busca, atualiza e exclui produtos. Hoje ainda usa `GET ?delete=ID` para excluir; o ideal futuro e mudar para `POST` ou `DELETE`.

### `views/listar_produtos_api.php`
Tela que consome `api/produtos.php` com `fetch()`. Renderiza tabela, busca produtos, abre modal de edicao e chama exclusao.

## Produtos e Estoque

### `views/cadastrar_Produto.php`
Tela/admin para cadastrar produto. Usa `mysqli` diretamente.

### `views/editar_produto.php`
Tela/admin para editar um produto usando `models/Produto.php`.

### `views/excluir_produto.php`
Tela/admin para excluir produto.

### `views/estoque_baixo.php`
Lista produtos com estoque baixo.

### `views/validade.php` e `views/vencidos.php`
Mostram produtos vencidos ou proximos do vencimento.

## Entradas de Produtos

### `views/entrada_produtos.php`
Tela protegida para registrar entrada por nota fiscal.

### `controllers/EntradaController.php`
Recebe os itens da nota, cria registro em `entradas`, salva itens em `itens_entrada` e atualiza estoque/preco/validade dos produtos.

### `views/historico_entradas.php`
Lista entradas agrupadas por nota.

## Vendas

### `views/vender.php`
Tela de caixa. Permite selecionar produtos, calcular total e troco.

### `controllers/VendaController.php`
Cria venda, salva itens, baixa estoque, calcula total, valor recebido e troco. Redireciona para recibo.

### `views/recibo.php`
Mostra recibo da venda.

### `views/historico_vendas.php`
Lista vendas registradas.

### `views/vendas_dia.php`
Lista vendas do dia e total vendido.

## Producao

### `views/producao_dia.php`
Sugere producao com base nas vendas recentes de produtos da categoria Padaria.

### `controllers/ProducaoController.php`
Cria registro de producao e itens produzidos. Tambem pode criar novo produto de Padaria.

### `views/imprimir_producao.php`
Mostra a lista de producao para impressao.

## Banco de Dados

### `database/minha_loja.sql`
Dump completo para criar o banco do zero.

### `database/2026_04_27_create_password_resets.sql`
Migration para criar a tabela de tokens de recuperacao de senha.

### `database/2026_04_27_create_email_verifications.sql`
Migration para criar confirmacao de e-mail.

### `database/2026_04_27_fix_unverified_users.sql`
Reparo para marcar como nao verificados usuarios que tinham token pendente.

## Fluxos Principais

### Login
1. Usuario envia formulario de `index.php`.
2. `controllers/autentica.php` busca o usuario.
3. Se `email_verificado != 1`, bloqueia.
4. Se senha estiver correta, cria sessao.
5. Redireciona para `views/dashboard.php`.

### Cadastro
1. Usuario abre `views/cadastrar.php`.
2. Envia e-mail/senha para `controllers/cadastrar_usuario.php`.
3. Sistema cria usuario nao verificado.
4. Gera token em `email_verifications`.
5. Tenta enviar link por e-mail.
6. Login so libera depois de `controllers/verificar_email.php`.

### Recuperacao de Senha
1. Usuario pede link em `views/recuperar.php`.
2. `controllers/recuperar_senha.php` gera token.
3. Usuario abre `views/nova_senha.php?token=...`.
4. `controllers/atualiza_senha.php` troca a senha se o token for valido.

### Venda
1. Usuario monta venda em `views/vender.php`.
2. `controllers/VendaController.php` salva venda e itens.
3. Estoque dos produtos e reduzido.
4. Usuario vai para `views/recibo.php`.

## Pontos Para Melhorar Depois

- Criar testes ou pelo menos scripts de verificacao manual.

