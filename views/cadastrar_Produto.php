<?php

require_once __DIR__ . '/../verifica_sessao.php';
require_admin('/index.php');
require_once __DIR__ . '/../config/conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome = $_POST['nome'] ?? '';
    $preco = $_POST['preco'] ?? 0;
    $categoria = $_POST['categoria'] ?? '';
    $validade = $_POST['validade'] ?? null;
    $estoque = $_POST['estoque'] ?? 0;
    $unidade = $_POST['unidade_medida'] ?? 'unidade';

    $sql = "INSERT INTO produtos 
    (nome, preco, categoria, validade, estoque, unidade_medida, criado_em)
    VALUES 
    ('$nome', '$preco', '$categoria', '$validade', '$estoque', '$unidade', NOW())";

    if ($conn->query($sql) === TRUE) {
        $mensagem = "Produto cadastrado com sucesso!";
    } else {
        $mensagem = "Erro: " . $conn->error;
    }
}

// LAYOUT
include 'layout.php';
?>

<div class="card">

<h2>Cadastro de Produto</h2>

<?php if(isset($mensagem)) { ?>
    <div style="margin-bottom:15px; color:green; font-weight:bold;">
        <?php echo $mensagem; ?>
    </div>
<?php } ?>

<form method="post">

    <label>Nome:</label>
    <input type="text" name="nome" required>

    <label>Preço:</label>
    <input type="number" step="0.01" name="preco" required>

    <label>Unidade:</label>
    <select name="unidade_medida">
        <option value="unidade">Unidade</option>
        <option value="kg">Kg</option>
    </select>

    <label>Categoria:</label>
    <input type="text" name="categoria">

    <label>Validade:</label>
    <input type="date" name="validade">

    <label>Estoque:</label>
    <input type="number" name="estoque" required>

    <button type="submit">Cadastrar Produto</button>

</form>

</div>

</div></div></div></body></html>