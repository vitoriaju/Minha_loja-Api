<?php
require_once __DIR__ . '/../verifica_sessao.php'; require_admin();
require_once __DIR__ . '/../pdo.php'; require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../models/RelatorioFinanceiro.php';
$mes = $_GET['mes'] ?? date('Y-m');
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes)) $mes = date('Y-m');
$inicio = $mes . '-01'; $fim = date('Y-m-t', strtotime($inicio));
$dados = RelatorioFinanceiro::porPeriodo($pdo, $inicio, $fim);
$linhas = [];
for ($dia = 1, $qtd = (int)date('t', strtotime($inicio)); $dia <= $qtd; $dia++) {
    $data = sprintf('%s-%02d', $mes, $dia);
    $linhas[$data] = $dados[$data] ?? ['data_ref'=>$data,'entradas'=>0,'saidas_caixa'=>0,'saidas_nao_caixa'=>0,'cartao'=>0,'total'=>0,'faturamento'=>0];
}
$totais = RelatorioFinanceiro::totais($linhas);
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8'); header('Content-Disposition: attachment; filename="financeiro-' . $mes . '.csv"');
    echo "\xEF\xBB\xBF"; $out=fopen('php://output','w'); fputcsv($out,['Dia','Entradas','Saidas caixa','Saidas nao caixa','Cartao','Total','Faturamento'],';','"','');
    foreach($linhas as $l) fputcsv($out,[date('d/m/Y',strtotime($l['data_ref'])),number_format((float)$l['entradas'],2,',','.'),number_format((float)$l['saidas_caixa'],2,',','.'),number_format((float)$l['saidas_nao_caixa'],2,',','.'),number_format((float)$l['cartao'],2,',','.'),number_format((float)$l['total'],2,',','.'),number_format((float)$l['faturamento'],2,',','.')],';','"','');
    fclose($out); exit;
}
include __DIR__ . '/layout.php';
?>
<style>.rf{max-width:1300px;margin:auto;display:grid;gap:18px}.rf-abas{display:flex;gap:8px;background:#fff;padding:8px;border-radius:12px;width:max-content}.rf-abas a,.rf-btn{padding:10px 15px;border-radius:8px;text-decoration:none;color:#5a371a;font-weight:bold}.rf-abas .ativa{background:#7b4f27;color:#fff}.rf-topo{display:flex;justify-content:space-between;align-items:end;gap:15px;flex-wrap:wrap}.rf-filtro{display:flex;gap:10px;align-items:end;background:#fff;padding:12px;border-radius:12px}.rf-filtro input{margin:5px 0 0}.rf-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px}.rf-card,.rf-tabela{background:#fff;padding:17px;border-radius:13px;box-shadow:0 3px 10px #0001}.rf-card strong{display:block;font-size:23px;margin-top:6px}.rf-tabela{overflow:auto}.rf-tabela tr.total{font-weight:bold;background:#fff3df}</style>
<div class="rf"><nav class="rf-abas"><a href="financeiro.php">Diario</a><a class="ativa" href="financeiro_mensal.php?mes=<?=e($mes)?>">Mensal</a><a href="financeiro_anual.php?ano=<?=e(substr($mes,0,4))?>">Anual</a></nav>
<div class="rf-topo"><div><h2>Relatorio mensal</h2><p>Totais de cada dia de <?=e(date('m/Y',strtotime($inicio)))?>.</p></div><form class="rf-filtro"><div><label>Mes</label><input type="month" name="mes" value="<?=e($mes)?>"></div><button>Buscar</button><a class="rf-btn" href="?mes=<?=e($mes)?>&export=csv">Exportar CSV</a></form></div>
<div class="rf-cards"><?php foreach(['entradas'=>'Entradas','saidas_caixa'=>'Saidas caixa','saidas_nao_caixa'=>'Saidas nao caixa','cartao'=>'Total cartao','total'=>'Total','faturamento'=>'Faturamento'] as $c=>$r):?><div class="rf-card"><span><?=e($r)?></span><strong>R$ <?=number_format($totais[$c],2,',','.')?></strong></div><?php endforeach;?></div>
<div class="rf-tabela"><table><thead><tr><th>Dia</th><th>Entradas</th><th>Saidas caixa</th><th>Saidas nao caixa</th><th>Cartao</th><th>Total</th><th>Faturamento</th><th></th></tr></thead><tbody><?php foreach($linhas as $l):?><tr><td><?=e(date('d/m/Y',strtotime($l['data_ref'])))?></td><td>R$ <?=number_format((float)$l['entradas'],2,',','.')?></td><td>R$ <?=number_format((float)$l['saidas_caixa'],2,',','.')?></td><td>R$ <?=number_format((float)$l['saidas_nao_caixa'],2,',','.')?></td><td>R$ <?=number_format((float)$l['cartao'],2,',','.')?></td><td>R$ <?=number_format((float)$l['total'],2,',','.')?></td><td>R$ <?=number_format((float)$l['faturamento'],2,',','.')?></td><td><a href="financeiro.php?data=<?=e($l['data_ref'])?>">Detalhes</a></td></tr><?php endforeach;?><tr class="total"><td>Total do mes</td><?php foreach(['entradas','saidas_caixa','saidas_nao_caixa','cartao','total','faturamento'] as $c):?><td>R$ <?=number_format($totais[$c],2,',','.')?></td><?php endforeach;?><td></td></tr></tbody></table></div></div></div></div></div></body></html>
