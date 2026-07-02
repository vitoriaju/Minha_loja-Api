<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_admin();
require_once __DIR__ . '/../pdo.php';
include __DIR__ . '/layout.php';

$nota = $_SESSION['nota_xml_importada'] ?? null;

if (!$nota) {
    ?>
    <div class="card" style="max-width:900px; margin:auto; padding:24px; border-radius:14px;">
        <h2>Nenhum XML importado</h2>
        <p style="margin:12px 0; color:#666;">Volte para a tela de importação e envie o XML da nota fiscal.</p>
        <a href="importar_xml.php" style="display:inline-block; text-decoration:none; background:#7b4f27; color:white; padding:10px 16px; border-radius:10px;">
            Importar XML
        </a>
    </div>
    </div></div></div></body></html>
    <?php
    exit;
}

$produtos = $pdo->query("SELECT id, nome, unidade_medida, estoque FROM produtos ORDER BY nome ASC")->fetchAll();

$vinculos = [];

try {
    $stmtVinculos = $pdo->prepare("SELECT codigo_xml, produto_id FROM produto_xml_vinculos WHERE cnpj_fornecedor = ?");
    $stmtVinculos->execute([$nota['cnpj_fornecedor'] ?? '']);

    foreach ($stmtVinculos->fetchAll() as $v) {
        $vinculos[$v['codigo_xml']] = (int) $v['produto_id'];
    }
} catch (Throwable $e) {
    $vinculos = [];
}

function normalizar_nome_xml($texto) {
    $texto = trim((string) $texto);

    if (function_exists('iconv')) {
        $convertido = @iconv('UTF-8', 'ASCII//TRANSLIT', $texto);

        if ($convertido !== false) {
            $texto = $convertido;
        }
    }

    $texto = strtolower($texto);
    $texto = preg_replace('/[^a-z0-9]+/', ' ', $texto);

    return trim($texto);
}

$produtosPorNome = [];

foreach ($produtos as $p) {
    $produtosPorNome[normalizar_nome_xml($p['nome'])] = (int) $p['id'];
}

function produto_sugerido_xml(array $item, array $vinculos, array $produtosPorNome): int {
    $codigo = trim($item['codigo_xml'] ?? '');

    if ($codigo !== '' && isset($vinculos[$codigo])) {
        return (int) $vinculos[$codigo];
    }

    $nomeNormalizado = normalizar_nome_xml($item['descricao_xml'] ?? '');

    return $produtosPorNome[$nomeNormalizado] ?? 0;
}

function fmt_decimal_xml($valor, int $casas = 3): string {
    return number_format((float) $valor, $casas, '.', '');
}

function unidade_sugerida_xml(array $item): string {
    $unidadeXml = strtoupper(trim($item['unidade_xml'] ?? ''));

    if ($unidadeXml === 'KG' || str_contains($unidadeXml, 'KILO')) {
        return 'kg';
    }

    return 'unidade';
}



?>

<div style="max-width:1450px; width:96%; margin:auto;">

    <div class="card" style="padding:22px; border-radius:14px; box-shadow:0 4px 14px rgba(0,0,0,0.10); margin-bottom:18px;">
        <div style="display:flex; justify-content:space-between; gap:15px; align-items:flex-start; flex-wrap:wrap;">
            <div>
                <h2 style="margin-bottom:8px;">Vincular produtos da nota XML</h2>
                <p style="color:#666; line-height:1.5; max-width:850px;">
                    Confira os dados da nota, escolha o produto cadastrado correspondente para cada item e informe a validade quando necessário.
                    Só depois disso o estoque será atualizado.
                </p>
            </div>

            <a href="importar_xml.php" style="text-decoration:none; background:#eee; color:#3b2411; padding:10px 16px; border-radius:10px;">
                Importar outro XML
            </a>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(190px, 1fr)); gap:12px; margin-top:18px;">
            <div style="background:#fff8f1; padding:13px; border-radius:12px;">
                <strong>Número</strong><br>
                <?= e($nota['numero_nota'] ?: 'Não informado') ?>
            </div>

            <div style="background:#fff8f1; padding:13px; border-radius:12px;">
                <strong>Série</strong><br>
                <?= e($nota['serie'] ?: 'Não informada') ?>
            </div>

            <div style="background:#fff8f1; padding:13px; border-radius:12px;">
                <strong>Fornecedor</strong><br>
                <?= e($nota['fornecedor'] ?: 'Não informado') ?>
            </div>

            <div style="background:#fff8f1; padding:13px; border-radius:12px;">
                <strong>CNPJ</strong><br>
                <?= e($nota['cnpj_fornecedor'] ?: 'Não informado') ?>
            </div>

            <div style="background:#fff8f1; padding:13px; border-radius:12px;">
                <strong>Emissão</strong><br>
                <?= !empty($nota['data_emissao']) ? date('d/m/Y', strtotime($nota['data_emissao'])) : 'Não informada' ?>
            </div>

            <div style="background:#fff8f1; padding:13px; border-radius:12px;">
                <strong>Total da nota</strong><br>
                R$ <?= number_format((float) ($nota['valor_total'] ?? 0), 2, ',', '.') ?>
            </div>
        </div>

        <?php if (!empty($nota['chave_acesso'])): ?>
            <p style="margin-top:14px; color:#666; word-break:break-all;">
                <strong>Chave de acesso:</strong> <?= e($nota['chave_acesso']) ?>
            </p>
        <?php endif; ?>
    </div>

    <form method="POST" action="../controllers/XmlNotaController.php?action=finalizar">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="card" style="padding:20px; border-radius:14px; overflow:auto;">
            <table style="min-width:1180px;">
                <thead>
                    <tr>
                        <th>Produto no XML</th>
                        <th>Código</th>
                        <th>Qtd XML</th>
                        <th>Un.</th>
                        <th>Valor unit.</th>
                        <th>Total item</th>
                        <th>Vincular com produto cadastrado</th>
                        <th>Validade</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($nota['itens'] as $i => $item): ?>
                        <?php $sugerido = produto_sugerido_xml($item, $vinculos, $produtosPorNome); ?>

                        <tr>
                            <td style="min-width:260px;">
                                <strong><?= e($item['descricao_xml']) ?></strong>
                                <br>
                                <small style="color:#777;">
                                    NCM: <?= e($item['ncm'] ?: '-') ?> | CFOP: <?= e($item['cfop'] ?: '-') ?>
                                </small>

                                <input type="hidden" name="descricao_xml[]" value="<?= e($item['descricao_xml']) ?>">
                                <input type="hidden" name="ncm[]" value="<?= e($item['ncm']) ?>">
                                <input type="hidden" name="cfop[]" value="<?= e($item['cfop']) ?>">
                            </td>

                            <td>
                                <?= e($item['codigo_xml'] ?: '-') ?>
                                <input type="hidden" name="codigo_xml[]" value="<?= e($item['codigo_xml']) ?>">
                            </td>

                            <td>
                                <?= number_format((float) $item['quantidade'], 3, ',', '.') ?>
                                <input type="hidden" name="quantidade[]" value="<?= e(fmt_decimal_xml($item['quantidade'], 3)) ?>">
                            </td>

                            <td>
                                <?= e($item['unidade_xml'] ?: '-') ?>
                                <input type="hidden" name="unidade_xml[]" value="<?= e($item['unidade_xml']) ?>">
                            </td>

                            <td>
                                R$ <?= number_format((float) $item['preco_unitario'], 2, ',', '.') ?>
                                <input type="hidden" name="preco_unitario[]" value="<?= e(fmt_decimal_xml($item['preco_unitario'], 4)) ?>">
                            </td>

                            <td>
                                R$ <?= number_format((float) $item['valor_total_item'], 2, ',', '.') ?>
                                <input type="hidden" name="valor_total_item[]" value="<?= e(fmt_decimal_xml($item['valor_total_item'], 2)) ?>">
                            </td>

                            <td style="min-width:320px;">
    <?php
        $unidadeSugerida = unidade_sugerida_xml($item);
        $precoVendaSugerido = number_format((float) $item['preco_unitario'], 2, '.', '');
    ?>

    <select name="produto_id[]" class="produto-select-xml" required onchange="toggleNovoProdutoXml(this)" style="width:100%; padding:9px; border-radius:8px; border:1px solid #ccc;">
        <option value="">Selecione o produto</option>

        <option value="novo" <?= $sugerido === 0 ? 'selected' : '' ?>>
            + Cadastrar novo produto automaticamente
        </option>

        <?php foreach ($produtos as $p): ?>
            <option value="<?= (int) $p['id'] ?>" <?= $sugerido === (int) $p['id'] ? 'selected' : '' ?>>
                <?= e($p['nome']) ?> — estoque: <?= number_format((float) $p['estoque'], 3, ',', '.') ?> <?= e($p['unidade_medida']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <?php if ($sugerido > 0): ?>
        <small style="display:block; margin-top:5px; color:#1f6b37;">
            Sugestão automática encontrada.
        </small>
    <?php else: ?>
        <small style="display:block; margin-top:5px; color:#8a5a20;">
            Produto não encontrado. Ele será cadastrado automaticamente.
        </small>
    <?php endif; ?>

    <div class="novo-produto-box" style="display:<?= $sugerido === 0 ? 'grid' : 'none' ?>; grid-template-columns:1fr 1fr; gap:8px; margin-top:10px; background:#fff8f1; padding:10px; border-radius:10px; border:1px solid #e0c3a5;">
        
        <div style="grid-column:1 / -1;">
            <label style="font-size:12px; color:#5a371a; font-weight:bold;">Nome do novo produto</label>
            <input 
                type="text" 
                name="novo_nome[]" 
                value="<?= e($item['descricao_xml']) ?>" 
                style="width:100%; padding:8px; border-radius:8px; border:1px solid #ccc;"
            >
        </div>

        <div>
            <label style="font-size:12px; color:#5a371a; font-weight:bold;">Categoria</label>
            <input 
                type="text" 
                name="novo_categoria[]" 
                value="XML" 
                style="width:100%; padding:8px; border-radius:8px; border:1px solid #ccc;"
            >
        </div>

        <div>
            <label style="font-size:12px; color:#5a371a; font-weight:bold;">Unidade</label>
            <select 
                name="novo_unidade_medida[]" 
                style="width:100%; padding:8px; border-radius:8px; border:1px solid #ccc;"
            >
                <option value="unidade" <?= $unidadeSugerida === 'unidade' ? 'selected' : '' ?>>Unidade</option>
                <option value="kg" <?= $unidadeSugerida === 'kg' ? 'selected' : '' ?>>Kg</option>
            </select>
        </div>

        <div style="grid-column:1 / -1;">
            <label style="font-size:12px; color:#5a371a; font-weight:bold;">Preço de venda</label>
            <input 
                type="number" 
                step="0.01" 
                min="0.01" 
                name="novo_preco_venda[]" 
                value="<?= e($precoVendaSugerido) ?>" 
                style="width:100%; padding:8px; border-radius:8px; border:1px solid #ccc;"
            >
            <small style="color:#777;">
                Atenção: o valor do XML geralmente é preço de custo. Confira antes de vender.
            </small>
        </div>

    </div>
</td>

                            <td>
                                <input type="date" name="validade[]" style="padding:8px; border-radius:8px; border:1px solid #ccc;">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:18px; flex-wrap:wrap;">
            <a href="importar_xml.php" style="text-decoration:none; background:#eee; color:#3b2411; padding:12px 18px; border-radius:10px;">
                Cancelar
            </a>

            <button type="submit" style="background:#7b4f27; color:white; border:0; border-radius:10px; padding:12px 22px; font-size:15px; cursor:pointer;">
                Finalizar nota e atualizar estoque
            </button>
        </div>
    </form>

</div>




<script>
function toggleNovoProdutoXml(select) {
    const td = select.closest('td');
    const box = td.querySelector('.novo-produto-box');

    if (!box) {
        return;
    }

    if (select.value === 'novo') {
        box.style.display = 'grid';
    } else {
        box.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.produto-select-xml').forEach(function (select) {
        toggleNovoProdutoXml(select);
    });
});
</script>









</div></div></div></body></html>
