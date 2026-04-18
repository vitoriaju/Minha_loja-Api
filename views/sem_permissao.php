<?php 
require_once __DIR__ . '/../config/config.php';

include __DIR__ . '/layout.php';
?>

<div style="display:flex; justify-content:center; align-items:center; height:70vh;">

<div class="card" style="max-width:500px; width:100%; text-align:center; border-left:6px solid #e3342f;">

<div style="font-size:60px; margin-bottom:10px;">🚫</div>

<h2 style="color:#e3342f;">Acesso Negado</h2>

<p style="color:#555; font-size:16px;">
Você não tem permissão para acessar esta página.
</p>

<br>

<a href="<?= BASE_URL ?>/views/dashboard.php" 
   style="
   display:inline-block;
   padding:12px 20px;
   background:#7b4f27;
   color:#fff;
   border-radius:10px;
   text-decoration:none;
   font-weight:600;
   ">
 Voltar ao Dashboard
</a>

</div>

</div>

</div></div></div></body></html>