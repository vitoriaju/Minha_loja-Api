# README.md – Minha Loja (Sistema + API Integrada)

Um sistema simples e funcional de gerenciamento para uma padaria online, desenvolvido em PHP, MySQL e utilizando uma estrutura MVC organizada.
O projeto agora possui uma API interna que moderniza o CRUD, tornando a aplicação mais flexível, segura e preparada para integrações como Postman, JavaScript (fetch), apps mobile e dashboards.

---
###  Funcionalidades Principais

- Login e autenticação de usuários (com controle de sessão e cookies)
- Cadastro, listagem, edição e exclusão de produtos
- Organização por categorias
- Criação e listagem de pedidos e itens de pedido
- Prepared Statements (PDO) para evitar SQL Injection
- Estrutura MVC com Controllers, Models e Views
- API REST simples integrada ao sistema
---
 ### Tecnologias Utilizadas:

- PHP 8+
- MySQL / phpMyAdmin
- Apache (XAMPP/WAMP
- PDO – PHP Data Objects
- HTML + CSS + JavaScript (fetch)
- Visual Studio Code
---
###  Requisitos:

- PHP 8 ou superior
- Apache habilitado
- MySQL / phpMyAdmin
- Navegador atualizado
- XAMPP, WAMP ou similar
---
### Como rodar o projeto:

####  1. Clone o repositório:
git clone https://github.com/vitoriaju/Minha_loja-Api.git

#### 2. Coloque o projeto no servidor:
C:\xampp\htdocs\Minha_loja2

#### 3. Criar banco de dados:

No phpMyAdmin
CREATE DATABASE minha_loja2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

Depois importe o arquivo SQL: minha_loja.sql

 Usuários de Teste:
 
| Tipo  | E-mail             | Senha   |
|-------|---------------------|---------|
| User  | admin@teste.com     | 123     |
| Admin | admin@teste1.com    | 123456  |


Esses usuários já estão incluídos no arquivo .sql.

---

### Fluxo do Sistema

#### 1. Acesso

O usuário acessa index.php, onde está a tela de login.

#### 2. Autenticação

- O formulário envia os dados para autentica.php
- Verificação com PDO (prepare, bindParam, execute)
- Senhas verificadas com password_verify()
- Criação de sessão ($_SESSION)
- Cookies são usados para sessão persistente

#### 3. Proteção

- Todas as páginas internas usam: require 'verifica_sessao.php';
- Se o usuário não estiver autenticado → volta para o login.

#### 4. Dashboard

Após login, o usuário vê um painel com:
- Cadastro de produtos
- Listagem com edição e exclusão
- Cadastro de categorias
- Listagem e criação de pedidos

#### 5. CRUD

 Fluxo MVC completo:
- Controller → Model → Banco → Controller → View

#### 6. Logout
Encerra sessão e redireciona para index.

##  API INTEGRADA AO SISTEMA

A API foi criada em:
/api/produtos.php

Ela substitui a listagem tradicional de produtos, tornando o sistema mais rápido, seguro e moderno.

A API permite:

- Listar produtos (GET)
- Buscar produtos por nome (GET)
- Excluir produto (GET delete)
- Editar produto (POST)
- Retornar JSON
- Ser testada pelo navegador ou Postman
- Ser usada pelo JavaScript com fetch()

### Endpoints da API
####  1. Listar produtos

GET:

http://localhost/Minha_loja/api/produtos.php

####  2. Buscar

GET:

http://localhost/Minha_loja/api/produtos.php?search=NOME


Exemplo:

?search=pao

####  3. Excluir

GET:

http://localhost/Minha_loja/api/produtos.php?delete=ID


Exemplo:

?delete=5


Retorno:

{"status":"success","msg":"Produto deletado"}

#### 4. Editar

POST:

http://localhost/Minha_loja/api/produtos.php


| Campo     | Descrição         |
|-----------|--------------------|
| id        | ID do produto      |
| nome      | Nome               |
| preco     | Preço              |
| qualidade | Qualidade          |
| categoria | Categoria          |
| validade  | Data               |
| estoque   | Quantidade         |



| Endpoint | Método | URL |
|----------|---------|------|
| Listar produtos | GET | [http://localhost/Minha_loja/api/produtos.php](http://localhost/Minha_loja/api/produtos.php) |
| Buscar produto | GET | [http://localhost/Minha_loja/api/produtos.php?search=pao](http://localhost/Minha_loja/api/produtos.php?search=pao) |
| Excluir | GET | [http://localhost/Minha_loja/api/produtos.php?delete=5](http://localhost/Minha_loja/api/produtos.php?delete=5) |
| Editar | POST | [http://localhost/Minha_loja/api/produtos.php](http://localhost/Minha_loja/api/produtos.php) |

## Integração da API na Interface

O arquivo:

views/listar_produtos.php foi reescrito usando:

- fetch() para carregar produtos da API
- Modal para edição
- Exclusão via API (sem reload)
- Busca com botão “Buscar”
- Atualização da tabela dinamicamente
- Mesmo CSS original
- Fluxo 100% via JavaScript

A listagem de produtos agora é:

- Totalmente dinâmica
- Rápida
- Moderna
- Sem recarregar página

 ### Segurança do Sistema

- Senhas com password_hash()
- PDO + prepared statements
- Sessões validadas em todas as páginas internas
- API protegida dentro do projeto
- Bloqueio de acesso não autorizado (verifica_sessao.php)

### Estrutura do Projeto: 
/api
   produtos.php
/controllers
/models
/views
   listar_produtos.php
   dashboard.php
/config
index.php
verifica_sessao.php
utils.php

### Erros Comuns

Baixar o ZIP do GitHub cria a pasta:
Minha_loja-main
O correto é renomear para:
Minha_loja2

Digitar a URL errada no Postman gera erro 403.
URL correta:
http://localhost/Minha_loja/api/produtos.php
