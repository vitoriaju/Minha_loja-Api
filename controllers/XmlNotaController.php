<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_admin();
require_once __DIR__ . '/../pdo.php';
require_once __DIR__ . '/../utils.php';

define('XML_MAX_BYTES', max(1024, (int) env_value('XML_MAX_BYTES', '2097152')));
define('XML_MAX_ITEMS', max(1, (int) env_value('XML_MAX_ITEMS', '500')));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/views/importar_xml.php');
    exit;
}

if (!csrf_check($_POST['csrf_token'] ?? '')) {
    flash_set('erro_xml', 'Sessão expirada. Atualize a página e tente novamente.');
    header('Location: ' . BASE_URL . '/views/importar_xml.php');
    exit;
}

$acao = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    if ($acao === 'importar') {
        importarXmlNota();
    }

    if ($acao === 'finalizar') {
        finalizarNotaXml($pdo);
    }

    flash_set('erro_xml', 'Ação inválida.');
    header('Location: ' . BASE_URL . '/views/importar_xml.php');
    exit;
} catch (PDOException $e) {
    log_exception($e, 'Falha de banco no processamento de XML');
    flash_set('erro_xml', 'Nao foi possivel processar a nota XML. Tente novamente.');
    header('Location: ' . BASE_URL . '/views/importar_xml.php');
    exit;
} catch (RuntimeException $e) {
    log_exception($e, 'XML rejeitado');
    flash_set('erro_xml', $e->getMessage());
    header('Location: ' . BASE_URL . '/views/importar_xml.php');
    exit;
} catch (Throwable $e) {
    log_exception($e, 'Falha no processamento de XML');
    flash_set('erro_xml', 'Nao foi possivel processar a nota XML. Verifique o arquivo e tente novamente.');
    header('Location: ' . BASE_URL . '/views/importar_xml.php');
    exit;
}

function importarXmlNota(): void
{
    if (empty($_FILES['xml_nota'])) {
        throw new RuntimeException('Envie um arquivo XML válido.');
    }

    $arquivo = $_FILES['xml_nota'];
    $uploadError = (int) ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE);

    if (in_array($uploadError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
        throw new RuntimeException('O arquivo XML excede o limite de tamanho permitido.');
    }

    if ($uploadError !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Nao foi possivel receber o arquivo XML.');
    }

    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

    if ($extensao !== 'xml') {
        throw new RuntimeException('O arquivo precisa estar no formato .xml.');
    }

    $tamanhoInformado = (int) ($arquivo['size'] ?? 0);
    $tamanhoReal = filesize($arquivo['tmp_name']);

    if ($tamanhoInformado <= 0 || $tamanhoReal === false || $tamanhoReal <= 0) {
        throw new RuntimeException('O arquivo XML esta vazio ou nao pode ser lido.');
    }

    if ($tamanhoInformado > XML_MAX_BYTES || $tamanhoReal > XML_MAX_BYTES) {
        throw new RuntimeException('O arquivo XML excede o limite de tamanho permitido.');
    }

    if (!is_uploaded_file($arquivo['tmp_name'])) {
        throw new RuntimeException('O arquivo recebido nao e um upload HTTP valido.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        throw new RuntimeException('Nao foi possivel verificar o tipo real do arquivo.');
    }

    $mimeType = finfo_file($finfo, $arquivo['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, ['application/xml', 'text/xml'], true)) {
        throw new RuntimeException('O conteudo enviado nao foi reconhecido como XML.');
    }

    $conteudo = file_get_contents($arquivo['tmp_name']);

    if ($conteudo === false || trim($conteudo) === '') {
        throw new RuntimeException('Não foi possível ler o XML enviado.');
    }

    $nota = extrairDadosNfe($conteudo);
    $nota['nome_arquivo'] = $arquivo['name'];

    $_SESSION['nota_xml_importada'] = $nota;

    header('Location: ' . BASE_URL . '/views/vincular_xml.php');
    exit;
}

function finalizarNotaXml(PDO $pdo): void
{
    if (empty($_SESSION['nota_xml_importada'])) {
        throw new RuntimeException('Nenhum XML foi importado. Importe o XML novamente.');
    }

    $nota = $_SESSION['nota_xml_importada'];
$produtoIds = $_POST['produto_id'] ?? [];
$valididades = $_POST['validade'] ?? [];
$quantidades = $_POST['quantidade'] ?? [];
$precos = $_POST['preco_unitario'] ?? [];
$valoresItens = $_POST['valor_total_item'] ?? [];
$codigosXml = $_POST['codigo_xml'] ?? [];
$descricoesXml = $_POST['descricao_xml'] ?? [];
$ncmItens = $_POST['ncm'] ?? [];
$cfopItens = $_POST['cfop'] ?? [];
$unidadesXml = $_POST['unidade_xml'] ?? [];

$novosNomes = $_POST['novo_nome'] ?? [];
$novasCategorias = $_POST['novo_categoria'] ?? [];
$novasUnidades = $_POST['novo_unidade_medida'] ?? [];
$novosPrecosVenda = $_POST['novo_preco_venda'] ?? [];

    if (empty($produtoIds)) {
        throw new RuntimeException('Nenhum produto foi informado para finalizar a nota.');
    }

    if (count($produtoIds) > XML_MAX_ITEMS || count($nota['itens'] ?? []) > XML_MAX_ITEMS) {
        throw new RuntimeException('A nota excede o limite de itens permitido.');
    }

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("
            INSERT INTO entradas
            (numero_nota, serie, chave_acesso, fornecedor, cnpj_fornecedor, data_emissao, valor_total, origem, xml_nome_arquivo)
            VALUES
            (?, ?, ?, ?, ?, ?, ?, 'xml', ?)
        ");

        $stmt->execute([
            $nota['numero_nota'] ?: 'SEM_NUMERO',
            $nota['serie'] ?: null,
            $nota['chave_acesso'] ?: null,
            $nota['fornecedor'] ?: 'Fornecedor não informado',
            $nota['cnpj_fornecedor'] ?: null,
            $nota['data_emissao'] ?: null,
            dinheiroParaDecimal($nota['valor_total'] ?? 0),
            $nota['nome_arquivo'] ?? null,
        ]);

        $entradaId = (int) $pdo->lastInsertId();

        $stmtItem = $pdo->prepare("
            INSERT INTO itens_entrada
            (entrada_id, produto_id, quantidade, validade, preco, descricao_xml, codigo_xml, ncm, cfop, unidade_xml, valor_total_item)
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmtLote = $pdo->prepare("
            INSERT INTO lotes_estoque
            (item_entrada_id, produto_id, validade, quantidade_inicial, quantidade_restante)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmtEstoque = $pdo->prepare("
            UPDATE produtos
            SET estoque = estoque + ?
            WHERE id = ?
        ");

        $stmtVinculo = $pdo->prepare("
            INSERT INTO produto_xml_vinculos
            (cnpj_fornecedor, codigo_xml, descricao_xml, produto_id)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                descricao_xml = VALUES(descricao_xml),
                produto_id = VALUES(produto_id),
                atualizado_em = CURRENT_TIMESTAMP
        ");

        $totalItensSalvos = 0;

        foreach ($produtoIds as $i => $produtoSelecionado) {
    $produtoSelecionado = trim((string) $produtoSelecionado);

    $quantidade = dinheiroParaDecimal($quantidades[$i] ?? 0);
    $preco = dinheiroParaDecimal($precos[$i] ?? 0);
    $valorItem = dinheiroParaDecimal($valoresItens[$i] ?? ($quantidade * $preco));
    $validade = trim($valididades[$i] ?? '');
    $codigoXml = trim($codigosXml[$i] ?? '');
    $descricaoXml = trim($descricoesXml[$i] ?? '');

    if ($quantidade <= 0) {
        continue;
    }

    $validadeBanco = $validade !== '' ? $validade : null;

    if ($produtoSelecionado === 'novo') {
        $produtoId = criarProdutoAutomaticoXml($pdo, [
            'nome' => trim($novosNomes[$i] ?? $descricaoXml),
            'categoria' => trim($novasCategorias[$i] ?? 'XML'),
            'unidade_medida' => trim($novasUnidades[$i] ?? unidadeSistemaPorXml($unidadesXml[$i] ?? '')),
            'preco' => dinheiroParaDecimal($novosPrecosVenda[$i] ?? $preco),
            'validade' => $validadeBanco,
        ]);
    } else {
        $produtoId = (int) $produtoSelecionado;
    }

    if ($produtoId <= 0) {
        throw new RuntimeException('Todos os itens da nota precisam estar vinculados ou cadastrados como novo produto.');
    }

            $stmtItem->execute([
                $entradaId,
                $produtoId,
                $quantidade,
                $validadeBanco,
                $preco,
                $descricaoXml,
                $codigoXml,
                trim($ncmItens[$i] ?? '') ?: null,
                trim($cfopItens[$i] ?? '') ?: null,
                trim($unidadesXml[$i] ?? '') ?: null,
                $valorItem,
            ]);
            $itemEntradaId = $pdo->lastInsertId();

            $stmtLote->execute([
                $itemEntradaId,
                $produtoId,
                $validadeBanco,
                $quantidade,
                $quantidade,
            ]);

            $stmtEstoque->execute([$quantidade, $produtoId]);

            if ($codigoXml !== '') {
                $stmtVinculo->execute([
                    $nota['cnpj_fornecedor'] ?: '',
                    $codigoXml,
                    $descricaoXml ?: 'Produto sem descrição no XML',
                    $produtoId,
                ]);
            }

            $totalItensSalvos++;
        }

        if ($totalItensSalvos === 0) {
            throw new RuntimeException('Nenhum item válido foi encontrado para salvar.');
        }

        audit_log($pdo, 'importar', 'entrada', $entradaId, ['origem' => 'xml', 'itens' => $totalItensSalvos]);
        $pdo->commit();
        unset($_SESSION['nota_xml_importada']);

        flash_set('sucesso_xml', 'Nota XML importada, vinculada e finalizada com sucesso.');
        header('Location: ' . BASE_URL . '/views/historico_entradas.php');
        exit;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function extrairDadosNfe(string $conteudoXml): array
{
    if (preg_match('/<!\s*(?:DOCTYPE|ENTITY)\b/i', $conteudoXml)) {
        throw new RuntimeException('XML com DTD ou declaracao de entidade nao e permitido.');
    }

    $internalErrorsAnterior = libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;

    if (!$dom->loadXML($conteudoXml, LIBXML_NONET | LIBXML_NOBLANKS)) {
        $erros = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrorsAnterior);
        $mensagem = !empty($erros) ? trim($erros[0]->message) : 'XML inválido.';
        throw new RuntimeException('Não foi possível interpretar o XML: ' . $mensagem);
    }

    if ($dom->doctype !== null) {
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrorsAnterior);
        throw new RuntimeException('XML com DTD nao e permitido.');
    }

    libxml_clear_errors();
    libxml_use_internal_errors($internalErrorsAnterior);

    $xpath = new DOMXPath($dom);

    $infNfe = $xpath->query('//*[local-name()="infNFe"]')->item(0);

    if (!$infNfe instanceof DOMElement) {
        throw new RuntimeException('Esse XML não parece ser uma NF-e válida, pois não encontrei a tag infNFe.');
    }

    $idNfe = $infNfe->getAttribute('Id');
    $chaveAcesso = preg_replace('/\D/', '', str_replace('NFe', '', $idNfe));

    $dataEmissaoCompleta = textoXml($xpath, $infNfe, './*[local-name()="ide"]/*[local-name()="dhEmi"]');

    if ($dataEmissaoCompleta === '') {
        $dataEmissaoCompleta = textoXml($xpath, $infNfe, './*[local-name()="ide"]/*[local-name()="dEmi"]');
    }

    $dataEmissao = null;

    if ($dataEmissaoCompleta !== '') {
        $timestamp = strtotime($dataEmissaoCompleta);
        $dataEmissao = $timestamp ? date('Y-m-d', $timestamp) : substr($dataEmissaoCompleta, 0, 10);
    }

    $itens = [];
    $detNodes = $xpath->query('./*[local-name()="det"]', $infNfe);

    if ($detNodes === false || $detNodes->length > XML_MAX_ITEMS) {
        throw new RuntimeException('A nota excede o limite de itens permitido.');
    }

    foreach ($detNodes as $det) {
        if (!$det instanceof DOMElement) {
            continue;
        }

        $quantidade = dinheiroParaDecimal(textoXml($xpath, $det, './*[local-name()="prod"]/*[local-name()="qCom"]'));
        $precoUnitario = dinheiroParaDecimal(textoXml($xpath, $det, './*[local-name()="prod"]/*[local-name()="vUnCom"]'));
        $valorProduto = dinheiroParaDecimal(textoXml($xpath, $det, './*[local-name()="prod"]/*[local-name()="vProd"]'));

        $itens[] = [
            'codigo_xml' => textoXml($xpath, $det, './*[local-name()="prod"]/*[local-name()="cProd"]'),
            'descricao_xml' => textoXml($xpath, $det, './*[local-name()="prod"]/*[local-name()="xProd"]'),
            'ncm' => textoXml($xpath, $det, './*[local-name()="prod"]/*[local-name()="NCM"]'),
            'cfop' => textoXml($xpath, $det, './*[local-name()="prod"]/*[local-name()="CFOP"]'),
            'unidade_xml' => textoXml($xpath, $det, './*[local-name()="prod"]/*[local-name()="uCom"]'),
            'quantidade' => $quantidade,
            'preco_unitario' => $precoUnitario,
            'valor_total_item' => $valorProduto,
        ];
    }

    if (empty($itens)) {
        throw new RuntimeException('Não encontrei produtos dentro do XML.');
    }

    return [
        'numero_nota' => textoXml($xpath, $infNfe, './*[local-name()="ide"]/*[local-name()="nNF"]'),
        'serie' => textoXml($xpath, $infNfe, './*[local-name()="ide"]/*[local-name()="serie"]'),
        'chave_acesso' => $chaveAcesso,
        'fornecedor' => textoXml($xpath, $infNfe, './*[local-name()="emit"]/*[local-name()="xNome"]'),
        'cnpj_fornecedor' => textoXml($xpath, $infNfe, './*[local-name()="emit"]/*[local-name()="CNPJ"]'),
        'data_emissao' => $dataEmissao,
        'valor_total' => dinheiroParaDecimal(textoXml($xpath, $infNfe, './*[local-name()="total"]/*[local-name()="ICMSTot"]/*[local-name()="vNF"]')),
        'itens' => $itens,
    ];
}

function textoXml(DOMXPath $xpath, DOMNode $contexto, string $consulta): string
{
    $node = $xpath->query($consulta, $contexto)->item(0);
    return $node ? trim($node->textContent) : '';
}

function dinheiroParaDecimal($valor): float
{
    if (is_float($valor) || is_int($valor)) {
        return (float) $valor;
    }

    $valor = trim((string) $valor);

    if ($valor === '') {
        return 0.0;
    }

    $valor = str_replace(['R$', ' '], '', $valor);

    if (str_contains($valor, ',') && str_contains($valor, '.')) {
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
    } else {
        $valor = str_replace(',', '.', $valor);
    }

    return (float) $valor;
}


function criarProdutoAutomaticoXml(PDO $pdo, array $dados): int
{
    $nome = trim($dados['nome'] ?? '');
    $categoria = trim($dados['categoria'] ?? '');
    $unidade = trim($dados['unidade_medida'] ?? 'unidade');
    $preco = dinheiroParaDecimal($dados['preco'] ?? 0);
    $validade = $dados['validade'] ?? null;

    if ($nome === '') {
        throw new RuntimeException('Informe o nome do novo produto que será cadastrado pelo XML.');
    }

    if (!in_array($unidade, ['unidade', 'kg'], true)) {
        $unidade = 'unidade';
    }

    if ($preco <= 0) {
        throw new RuntimeException('Informe um preço de venda válido para o novo produto: ' . $nome);
    }

    $categoriaBanco = $categoria !== '' ? $categoria : 'XML';

    /*
     * Evita cadastrar duplicado caso já exista um produto com o mesmo nome.
     */
    $stmtBusca = $pdo->prepare("
        SELECT id 
        FROM produtos 
        WHERE LOWER(TRIM(nome)) = LOWER(TRIM(?))
        LIMIT 1
    ");
    $stmtBusca->execute([$nome]);
    $produtoExistente = $stmtBusca->fetch();

    if ($produtoExistente) {
        return (int) $produtoExistente['id'];
    }

    /*
     * O estoque entra como 0 aqui.
     * Depois, no próprio fluxo da nota, o sistema soma a quantidade comprada.
     */
    $stmt = $pdo->prepare("
        INSERT INTO produtos
        (nome, preco, unidade_medida, categoria, validade, estoque, estoque_minimo, criado_em)
        VALUES
        (?, ?, ?, ?, ?, 0, 5, NOW())
    ");

    $stmt->execute([
        $nome,
        $preco,
        $unidade,
        $categoriaBanco,
        $validade,
    ]);

    return (int) $pdo->lastInsertId();
}

function unidadeSistemaPorXml($unidadeXml): string
{
    $unidadeXml = strtoupper(trim((string) $unidadeXml));

    if ($unidadeXml === 'KG' || str_contains($unidadeXml, 'KILO')) {
        return 'kg';
    }

    return 'unidade';
}
