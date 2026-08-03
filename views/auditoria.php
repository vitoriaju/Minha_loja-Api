<?php
require_once __DIR__ . '/../verifica_sessao.php';
require_admin();
require_once __DIR__ . '/../pdo.php';
require_once __DIR__ . '/../utils.php';

$acao = trim((string) ($_GET['acao'] ?? ''));
$entidade = trim((string) ($_GET['entidade'] ?? ''));
$usuarioId = (int) ($_GET['usuario_id'] ?? 0);
$dataInicio = trim((string) ($_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'))));
$dataFim = trim((string) ($_GET['data_fim'] ?? date('Y-m-d')));
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$porPagina = 50;
$offset = ($pagina - 1) * $porPagina;

$where = ['a.criado_em >= ?', 'a.criado_em < DATE_ADD(?, INTERVAL 1 DAY)'];
$params = [$dataInicio . ' 00:00:00', $dataFim];

if ($acao !== '') { $where[] = 'a.acao = ?'; $params[] = $acao; }
if ($entidade !== '') { $where[] = 'a.entidade = ?'; $params[] = $entidade; }
if ($usuarioId > 0) { $where[] = 'a.usuario_id = ?'; $params[] = $usuarioId; }
$whereSql = implode(' AND ', $where);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM auditoria a WHERE {$whereSql}");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$totalPaginas = max(1, (int) ceil($total / $porPagina));

$stmt = $pdo->prepare("
    SELECT a.*, u.email AS usuario_email
    FROM auditoria a
    LEFT JOIN usuarios u ON u.id = a.usuario_id
    WHERE {$whereSql}
    ORDER BY a.criado_em DESC, a.id DESC
    LIMIT {$porPagina} OFFSET {$offset}
");
$stmt->execute($params);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

$acoes = $pdo->query('SELECT DISTINCT acao FROM auditoria ORDER BY acao')->fetchAll(PDO::FETCH_COLUMN);
$entidades = $pdo->query('SELECT DISTINCT entidade FROM auditoria ORDER BY entidade')->fetchAll(PDO::FETCH_COLUMN);
$usuarios = $pdo->query('SELECT id, email FROM usuarios ORDER BY email')->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/layout.php';
?>

<style>
.audit-page{display:flex;flex-direction:column;gap:18px}.audit-card{background:#fff;padding:20px;border-radius:14px;box-shadow:0 3px 12px rgba(0,0,0,.08)}
.audit-filters{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;align-items:end}.audit-filters label{display:block;font-weight:700;margin-bottom:5px}
.audit-filters input,.audit-filters select{width:100%;padding:9px;border:1px solid #ccb49d;border-radius:8px;box-sizing:border-box}.audit-btn{padding:10px 15px;border:0;border-radius:8px;background:#7b4f27;color:#fff;cursor:pointer}
.audit-table-wrap{overflow:auto}.audit-table{width:100%;border-collapse:collapse;min-width:1050px}.audit-table th,.audit-table td{padding:10px;border-bottom:1px solid #eee;text-align:left;vertical-align:top}.audit-table th{background:#f5d0a9}
.audit-details{max-width:430px;white-space:pre-wrap;word-break:break-word;font:12px Consolas,monospace}.audit-pages{display:flex;gap:8px;justify-content:center}.audit-pages a{padding:8px 12px;background:#fff;border-radius:7px;text-decoration:none;color:#5a371a}.audit-pages .active{background:#7b4f27;color:#fff}
</style>

<div class="audit-page">
    <div><h2>Auditoria</h2><p>Eventos preservados por <?= max(30, (int) env_value('AUDIT_RETENTION_DAYS', '365')) ?> dias.</p></div>
    <div class="audit-card">
        <form method="get" class="audit-filters">
            <div><label>Início</label><input type="date" name="data_inicio" value="<?= e($dataInicio) ?>"></div>
            <div><label>Fim</label><input type="date" name="data_fim" value="<?= e($dataFim) ?>"></div>
            <div><label>Ação</label><select name="acao"><option value="">Todas</option><?php foreach($acoes as $v): ?><option value="<?= e($v) ?>" <?= $acao===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach ?></select></div>
            <div><label>Entidade</label><select name="entidade"><option value="">Todas</option><?php foreach($entidades as $v): ?><option value="<?= e($v) ?>" <?= $entidade===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach ?></select></div>
            <div><label>Usuário</label><select name="usuario_id"><option value="0">Todos</option><?php foreach($usuarios as $u): ?><option value="<?= (int)$u['id'] ?>" <?= $usuarioId===(int)$u['id']?'selected':'' ?>><?= e($u['email']) ?></option><?php endforeach ?></select></div>
            <button class="audit-btn" type="submit">Filtrar</button>
        </form>
    </div>
    <div class="audit-card audit-table-wrap">
        <p><strong><?= $total ?></strong> registro(s)</p>
        <table class="audit-table"><thead><tr><th>Data</th><th>Usuário</th><th>Ação</th><th>Entidade</th><th>ID</th><th>IP</th><th>Detalhes</th></tr></thead><tbody>
        <?php foreach($registros as $r): ?>
            <?php $detalhes = $r['detalhes'] ? json_encode(json_decode($r['detalhes'], true), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : '-'; ?>
            <tr><td><?= e(date('d/m/Y H:i:s', strtotime($r['criado_em']))) ?></td><td><?= e($r['usuario_email'] ?: 'Não autenticado') ?></td><td><?= e($r['acao']) ?></td><td><?= e($r['entidade']) ?></td><td><?= e($r['entidade_id'] ?: '-') ?></td><td><?= e($r['ip'] ?: '-') ?></td><td><div class="audit-details"><?= e($detalhes) ?></div></td></tr>
        <?php endforeach ?>
        <?php if(!$registros): ?><tr><td colspan="7">Nenhum evento encontrado.</td></tr><?php endif ?>
        </tbody></table>
    </div>
    <?php if($totalPaginas>1): ?><div class="audit-pages"><?php for($p=max(1,$pagina-2);$p<=min($totalPaginas,$pagina+2);$p++): $query=$_GET;$query['pagina']=$p; ?><a class="<?= $p===$pagina?'active':'' ?>" href="?<?= e(http_build_query($query)) ?>"><?= $p ?></a><?php endfor ?></div><?php endif ?>
</div>
</div></div></div></body></html>
