<?php
session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils.php';

$erro = flash_get('erro');
$sucesso = flash_get('sucesso');
$info = flash_get('info');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login - Minha Loja</title>

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    min-height:100vh;
    font-family:Arial, sans-serif;
    background:
        radial-gradient(circle at top left, rgba(255,255,255,0.8), transparent 38%),
        linear-gradient(135deg, #fff8f1 0%, #f8dfc4 55%, #d8a875 100%);
    display:flex;
    align-items:center;
    justify-content:center;
    padding:32px;
}

/* CONTAINER PRINCIPAL */
.login-wrapper{
    width:100%;
    max-width:1180px;
    min-height:700px;
    background:white;
    border-radius:32px;
    overflow:hidden;
    display:grid;
    grid-template-columns:1.05fr 0.95fr;
    box-shadow:0 18px 45px rgba(90,55,26,0.18);
}

/* LADO ESQUERDO */
.login-brand{
    background:
        radial-gradient(circle at top right, rgba(255,255,255,0.45), transparent 35%),
        linear-gradient(135deg, #fff1df 0%, #f5d0a9 55%, #d8a875 100%);
    color:#3b2411;
    padding:58px;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    position:relative;
    overflow:hidden;
}

.login-brand::before{
    content:"";
    position:absolute;
    width:260px;
    height:260px;
    border-radius:50%;
    background:rgba(255,255,255,0.08);
    top:-80px;
    right:-70px;
}

.login-brand::after{
    content:"";
    position:absolute;
    width:200px;
    height:200px;
    border-radius:50%;
    background:rgba(255,255,255,0.06);
    bottom:-70px;
    left:-60px;
}

.brand-content{
    position:relative;
    z-index:2;
}

.brand-badge{
    display:inline-block;
    background:white;
    color:#7b4f27;
    border:1px solid #ead4bf;
    padding:9px 16px;
    border-radius:30px;
    font-size:13px;
    font-weight:bold;
    margin-bottom:28px;
    box-shadow:0 4px 12px rgba(90,55,26,0.08);
}

.brand-content h1{
    font-size:44px;
    line-height:1.1;
    margin-bottom:18px;
}

.brand-content p{
    font-size:18px;
    line-height:1.7;
    color:#6b4a2d;
    max-width:500px;
}

.brand-cards{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
    margin-top:34px;
}

.brand-card{
    background:white;
    border:1px solid #ead4bf;
    border-radius:20px;
    padding:20px;
    box-shadow:0 6px 16px rgba(90,55,26,0.08);
}

.brand-card span{
    display:block;
    font-size:28px;
    margin-bottom:10px;
}

.brand-card strong{
    display:block;
    font-size:15px;
    margin-bottom:5px;
}

.brand-card small{
    display:block;
    color:#7a5a3b;
    line-height:1.5;
}

.brand-footer{
    position:relative;
    z-index:2;
    font-size:13px;
    color:#7a5a3b;
}

/* LADO DIREITO */
.login-area{
    padding:60px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#fffaf5;
}

.login-card{
    width:100%;
    max-width:440px;
}

.login-top{
    margin-bottom:26px;
}

.login-top h2{
    color:#3b2411;
    font-size:32px;
    margin-bottom:8px;
}

.login-top p{
    color:#777;
    font-size:15px;
    line-height:1.5;
}

/* MENSAGENS */
.msg{
    margin-bottom:15px;
    padding:13px 14px;
    border-radius:12px;
    font-weight:bold;
    font-size:14px;
    line-height:1.4;
}

.erro{
    background:#fff1f1;
    color:#b00000;
    border-left:5px solid #c0392b;
}

.sucesso{
    background:#e8f8ef;
    color:#1f7a45;
    border-left:5px solid #2e8b57;
}

.info{
    background:#eef6ff;
    color:#004a7c;
    border-left:5px solid #2980b9;
}

/* FORMULÁRIO */
.form-group{
    margin-bottom:16px;
}

.form-group label{
    display:block;
    color:#3b2411;
    font-weight:bold;
    font-size:14px;
    margin-bottom:7px;
}

.input-wrap{
    position:relative;
}

.input-wrap input{
    width:100%;
    height:48px;
    padding:0 14px;
    border:1px solid #d6b089;
    border-radius:12px;
    font-size:15px;
    outline:none;
    background:white;
    color:#3b2411;
    transition:0.2s;
}

.input-wrap input:focus{
    border-color:#7b4f27;
    box-shadow:0 0 0 3px rgba(123,79,39,0.15);
}

.password-input input{
    padding-right:58px;
}

.toggle-password{
    position:absolute;
    right:8px;
    top:50%;
    transform:translateY(-50%);
    border:0;
    background:#fdf3e7;
    color:#7b4f27;
    font-weight:bold;
    padding:8px 10px;
    border-radius:9px;
    cursor:pointer;
    font-size:12px;
}

.toggle-password:hover{
    background:#f5d0a9;
}

.login-options{
    display:flex;
    justify-content:flex-end;
    align-items:center;
    margin-bottom:18px;
}

.login-options a{
    color:#7b4f27;
    text-decoration:none;
    font-weight:bold;
    font-size:14px;
}

.login-options a:hover{
    text-decoration:underline;
}

.btn-login{
    width:100%;
    height:50px;
    border:0;
    border-radius:13px;
    background:#7b4f27;
    color:white;
    font-size:16px;
    font-weight:bold;
    cursor:pointer;
    transition:0.3s;
    box-shadow:0 6px 16px rgba(123,79,39,0.28);
}

.btn-login:hover{
    background:#5a371a;
    transform:translateY(-2px);
}

.divider{
    display:flex;
    align-items:center;
    gap:12px;
    margin:26px 0;
    color:#999;
    font-size:13px;
}

.divider::before,
.divider::after{
    content:"";
    flex:1;
    height:1px;
    background:#ead4bf;
}

.create-account{
    display:block;
    text-align:center;
    text-decoration:none;
    color:#7b4f27;
    background:white;
    border:1px solid #d6b089;
    border-radius:13px;
    padding:13px;
    font-weight:bold;
    transition:0.3s;
}

.create-account:hover{
    background:#fff7ef;
    transform:translateY(-2px);
}

.security-note{
    margin-top:20px;
    padding:13px;
    border-radius:12px;
    background:#fdf3e7;
    color:#5a371a;
    font-size:13px;
    line-height:1.5;
}

/* RESPONSIVO */
@media(max-width:900px){
    .login-wrapper{
        grid-template-columns:1fr;
        max-width:520px;
    }

    .login-brand{
        padding:34px;
    }

    .brand-content h1{
        font-size:34px;
    }

    .brand-cards{
        grid-template-columns:1fr;
    }

    .brand-footer{
        margin-top:28px;
    }

    .login-area{
        padding:34px;
    }
}

@media(max-width:480px){
    body{
        padding:12px;
    }

    .login-wrapper{
        border-radius:20px;
    }

    .login-brand{
        padding:28px 22px;
    }

    .login-area{
        padding:28px 22px;
    }

    .brand-content h1{
        font-size:30px;
    }

    .login-top h2{
        font-size:28px;
    }
}
</style>
</head>

<body>

<div class="login-wrapper">

    <section class="login-brand">
        <div class="brand-content">
            <div class="brand-badge">Sistema de Gestão</div>

            <h1>Minha Loja</h1>

            <p>
                Controle suas vendas, produtos, entradas, validade e fechamento do dia
                em um só lugar.
            </p>

            <div class="brand-cards">
                <div class="brand-card">
                    <span>📦</span>
                    <strong>Estoque</strong>
                    <small>Acompanhe produtos, quantidades e validade.</small>
                </div>

                <div class="brand-card">
                    <span>💰</span>
                    <strong>Vendas</strong>
                    <small>Registre vendas por dinheiro, cartão ou Pix.</small>
                </div>

                <div class="brand-card">
                    <span>📊</span>
                    <strong>Relatórios</strong>
                    <small>Consulte histórico e movimentações do dia.</small>
                </div>

                <div class="brand-card">
                    <span>⚠️</span>
                    <strong>Alertas</strong>
                    <small>Veja estoque baixo e produtos próximos do vencimento.</small>
                </div>
            </div>
        </div>

        <div class="brand-footer">
            Desenvolvido para facilitar o controle diário da loja.
        </div>
    </section>

    <main class="login-area">
        <div class="login-card">

            <div class="login-top">
                <h2>Entrar</h2>
                <p>Informe seu e-mail e senha para acessar o sistema.</p>
            </div>

            <?php if ($erro): ?>
                <div class="msg erro"><?= e($erro) ?></div>
            <?php endif; ?>

            <?php if ($sucesso): ?>
                <div class="msg sucesso"><?= e($sucesso) ?></div>
            <?php endif; ?>

            <?php if ($info): ?>
                <div class="msg info"><?= e($info) ?></div>
            <?php endif; ?>

            <form action="controllers/autentica.php" method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="form-group">
                    <label for="email">E-mail</label>

                    <div class="input-wrap">
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="Digite seu e-mail"
                            autocomplete="email"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="senha">Senha</label>

                    <div class="input-wrap password-input">
                        <input 
                            type="password" 
                            id="senha" 
                            name="senha" 
                            placeholder="Digite sua senha"
                            autocomplete="current-password"
                            required
                        >

                        <button 
                            type="button" 
                            class="toggle-password" 
                            onclick="toggleSenha()"
                            id="btnSenha"
                        >
                            Ver
                        </button>
                    </div>
                </div>

                <div class="login-options">
                    <a href="views/recuperar.php">Esqueci minha senha</a>
                </div>

                <button type="submit" class="btn-login">
                    Entrar no sistema
                </button>
            </form>

            <div class="divider">
                ou
            </div>

            <a href="views/cadastrar.php" class="create-account">
                Criar nova conta
            </a>

            <div class="security-note">
                Por segurança, confirme seu e-mail antes de acessar o sistema.
            </div>

        </div>
    </main>

</div>

<script>
function toggleSenha(){
    const input = document.getElementById("senha");
    const btn = document.getElementById("btnSenha");

    if(input.type === "password"){
        input.type = "text";
        btn.innerText = "Ocultar";
    }else{
        input.type = "password";
        btn.innerText = "Ver";
    }
}
</script>

</body>
</html>