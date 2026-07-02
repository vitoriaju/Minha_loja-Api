<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_admin();
require_once __DIR__ . '/../pdo.php';
include __DIR__ . '/layout.php';

$erro = flash_get('erro_xml');
$sucesso = flash_get('sucesso_xml');
?>

<div style="max-width:1100px; width:95%; margin:auto;">

    <div class="card" style="padding:24px; border-radius:14px; box-shadow:0 4px 14px rgba(0,0,0,0.10);">

        <div style="display:flex; justify-content:space-between; gap:15px; align-items:center; flex-wrap:wrap; margin-bottom:20px;">
            <div>
                <h2 style="margin-bottom:6px;">Importar XML da Nota Fiscal</h2>
                <p style="color:#666; line-height:1.5;">
                    Envie o XML da NF-e. O sistema vai ler os dados da nota e depois abrir a tela para vincular os itens aos produtos cadastrados.
                </p>
            </div>

            <a href="entrada_produtos.php" style="text-decoration:none; background:#7b4f27; color:white; padding:10px 16px; border-radius:10px;">
                Entrada manual
            </a>
        </div>

        <?php if ($erro): ?>
            <div style="background:#ffe5e5; color:#8a1f1f; padding:12px 14px; border-radius:10px; margin-bottom:15px;">
                <?= e($erro) ?>
            </div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div style="background:#e7f8ec; color:#1f6b37; padding:12px 14px; border-radius:10px; margin-bottom:15px;">
                <?= e($sucesso) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="../controllers/XmlNotaController.php?action=importar" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <label style="display:block; font-weight:bold; margin-bottom:8px;">Arquivo XML da NF-e:</label>

            <div style="border:2px dashed #c8a17d; background:#fff8f1; padding:28px; border-radius:14px; text-align:center; margin-bottom:18px;">
                <input type="file" name="xml_nota" accept=".xml,text/xml,application/xml" required style="background:white; padding:12px; border-radius:10px; width:100%; max-width:520px;">

                <p style="margin-top:12px; color:#77563a; font-size:14px;">
                    Use o arquivo XML original da nota fiscal eletrônica.
                </p>
            </div>

            <button type="submit" style="background:#7b4f27; color:white; border:0; border-radius:10px; padding:13px 22px; font-size:15px; cursor:pointer;">
                Ler XML e continuar
            </button>
        </form>

    </div>

    <div class="card" style="padding:20px; border-radius:14px; margin-top:18px; background:#fff;">
        <h3 style="margin-bottom:10px;">Como vai funcionar</h3>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:12px;">
            <div style="background:#fafafa; border-radius:12px; padding:14px;">
                <strong>1. Importar</strong>
                <p style="color:#666; margin-top:6px;">Você envia o XML da nota.</p>
            </div>

            <div style="background:#fafafa; border-radius:12px; padding:14px;">
                <strong>2. Conferir</strong>
                <p style="color:#666; margin-top:6px;">O sistema mostra fornecedor, número, total e produtos.</p>
            </div>

            <div style="background:#fafafa; border-radius:12px; padding:14px;">
                <strong>3. Vincular</strong>
                <p style="color:#666; margin-top:6px;">Você liga cada item do XML ao produto cadastrado.</p>
            </div>

            <div style="background:#fafafa; border-radius:12px; padding:14px;">
                <strong>4. Finalizar</strong>
                <p style="color:#666; margin-top:6px;">O estoque é atualizado e a entrada entra no histórico.</p>
            </div>
        </div>
    </div>

</div>

</div></div></div></body></html>
