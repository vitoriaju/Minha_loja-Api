# Minha Loja

Um sistema simples de gerenciamento de uma padaria online desenvolvido em **PHP** com uso de **PDO** para conexão ao banco de dados e segue uma estrutura MVC simples, que permite:

**Login e autenticação de usuários (com controle de sessão).**

**Cadastro, listagem e gerenciamento de produtos.**

**Criação e acompanhamento de pedidos e itens de pedidos.**

**Organização por categorias de produtos.**

**Uso de prepared statements para evitar SQL Injection.**

# Tecnologias Utilizadas

PHP (VisualCode)

MySQL

Apache
(XAMPP/WAMP)

Estrutura MVC (Models / Controllers / Views)

PDO (PHP Data Objects)

Visual Studio Code

---

## Requisitos

- PHP 8+
- Servidor Apache (ex: XAMPP, WAMP ou similar)
- MySQL/Myadmin
- Navegador atualizado

---

## Passos para rodar o projeto

1. Clone este repositório:
   ```bash
   git clone https://github.com/vitoriaju/Minha_loja.git
2. Extrair a pasta para o diretório do servidor:
 C:\xampp\htdocs

# Como criar o banco de dados

Abra o phpMyAdmin ou MySQL.

Crie um banco com o mesmo nome do projeto (Minha_loja):
 ```bash
CREATE DATABASE minha_loja CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Importe o arquivo SQL:
file:/minha_loja.sql

# Usuário/Senha de teste

Usuário user : admin@teste.com

Senha user : 123

Usuário admin: admin@teste1.com

Senha Admin: 123456

O usuário já está incluso no arquivo minha_loja.sql.

# Fluxo do Sistema

O sistema da Minha Loja segue o padrão MVC e o fluxo principal de autenticação e gerenciamento é descrito abaixo:

**1. Acesso à aplicação**

-O usuário abre o navegador e acessa index.php.

-A página inicial apresenta o formulário de login.

**2. Processamento de login**

-Ao enviar os dados, o formulário chama autentica.php.

-Validação dos campos (e-mail e senha).

-Consulta ao banco via PDO (pdo.php).

-Verificação da senha com password_verify($senha_digitada, $hash_bd).

-Criação da sessão ($_SESSION['usuario']).

-Uso de cookies para manter a sessão ativa em páginas futuras e facilitar autenticação persistente.

-Regeneração do ID da sessão (session_regenerate_id(true)) para aumentar a segurança.

**3. Proteção das páginas internas**

-Todas as páginas privadas incluem verifica_sessao.php.

-Este arquivo verifica se a sessão e os cookies existem e são válidos, evitando acesso não autorizado.

-Caso o usuário não esteja autenticado, é redirecionado para a página de login.

**4. Área autenticada / Dashboard**

-Usuários autenticados acessam o Dashboard, com funcionalidades de gerenciamento da loja:

-Cadastro de produtos: nome, descrição, preço, estoque e categoria.

-Listagem de produtos: permite edição e exclusão.

-Gerenciamento de categorias: criar, listar, editar e excluir categorias.

-Criação e listagem de pedidos e itens de pedidos.

-Cada ação é realizada por um Controller que chama o Model correspondente para acessar ou modificar o banco via PDO, e retorna os dados para a View.

**5. Fluxo de CRUD (Produtos, Categorias e Pedidos)**

-Controller: recebe requisição → chama Model → Model executa query → Controller retorna dados → View mostra resultado.

-Model: lida diretamente com o banco usando queries preparadas (prepare() + execute()).

-View: interface HTML/PHP que exibe os dados (listas, formulários, alertas de sucesso/erro).

**6. Logout / Finalização de sessão**

-Usuário pode encerrar a sessão, destruindo $_SESSION e os cookies relacionados, e é redirecionado para index.php.

# Estrutura do projeto:

Models/ → consultas SQL e lógica de dados.

Controllers/ → recebem requisições e controlam fluxo (login, CRUDs).

Views/ → páginas HTML/PHP que exibem dados.

index.php → pagina ne login.

utils.php → funções auxiliares (formatação, redirecionamentos).

verifica_sessao.php → valida sessões.

# Segurança

Senhas salvas com password_hash e verificadas com password_verify.

Uso de prepared statements via PDO para evitar SQL Injection.

Sessões validadas em todas as páginas internas.

# Erros comuns
Quando você baixa o arquivo ZIP do GitHub, a pasta extraída vem com o nome **Minha_loja-main**.

O ideal é renomear a pasta, removendo o sufixo **"-main"**, deixando apenas **"Minha_loja"**.
