<?php

class RelatorioFinanceiro
{
    public static function porPeriodo(PDO $pdo, string $inicio, string $fim): array
    {
        $sql = "SELECT data_ref,
                    SUM(entradas) AS entradas,
                    SUM(saidas_caixa) AS saidas_caixa,
                    SUM(saidas_nao_caixa) AS saidas_nao_caixa,
                    SUM(cartao) AS cartao,
                    SUM(faturamento) AS faturamento
                FROM (
                    SELECT DATE(data_venda) AS data_ref,
                           valor_total AS entradas, 0 AS saidas_caixa,
                           0 AS saidas_nao_caixa,
                           CASE WHEN LOWER(forma_pagamento) IN ('cartao', 'cartão') THEN valor_total ELSE 0 END AS cartao,
                           valor_total AS faturamento
                    FROM vendas
                    WHERE DATE(data_venda) BETWEEN ? AND ?
                    UNION ALL
                    SELECT data_movimento AS data_ref,
                           CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END AS entradas,
                           CASE WHEN tipo = 'saida' AND incluir_fechamento = 1 THEN valor ELSE 0 END AS saidas_caixa,
                           CASE WHEN tipo = 'saida' AND incluir_fechamento = 0 THEN valor ELSE 0 END AS saidas_nao_caixa,
                           CASE WHEN tipo = 'entrada' AND LOWER(forma_pagamento) IN ('cartao', 'cartão') THEN valor ELSE 0 END AS cartao,
                           CASE WHEN tipo = 'entrada' AND incluir_fechamento = 1 THEN valor ELSE 0 END AS faturamento
                    FROM movimentacoes_financeiras
                    WHERE data_movimento BETWEEN ? AND ?
                ) movimentos
                GROUP BY data_ref ORDER BY data_ref";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$inicio, $fim, $inicio, $fim]);
        $resultado = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $linha['total'] = (float)$linha['entradas'] + (float)$linha['saidas_caixa'];
            $linha['faturamento'] = $linha['total']
                - (float)$linha['saidas_caixa']
                - (float)$linha['saidas_nao_caixa'];
            $resultado[$linha['data_ref']] = $linha;
        }
        $informados = $pdo->prepare('SELECT data_fechamento, total_dia_informado FROM fechamentos_diarios WHERE data_fechamento BETWEEN ? AND ? AND total_dia_informado IS NOT NULL');
        $informados->execute([$inicio, $fim]);
        foreach ($informados->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $data = $linha['data_fechamento'];
            if (!isset($resultado[$data])) $resultado[$data] = ['data_ref'=>$data,'entradas'=>0,'saidas_caixa'=>0,'saidas_nao_caixa'=>0,'cartao'=>0,'total'=>0,'faturamento'=>0];
            $resultado[$data]['total'] = (float)$linha['total_dia_informado'];
            $resultado[$data]['faturamento'] = $resultado[$data]['total']
                - (float)$resultado[$data]['saidas_caixa']
                - (float)$resultado[$data]['saidas_nao_caixa'];
        }
        ksort($resultado);
        return $resultado;
    }

    public static function totais(array $linhas): array
    {
        $total = ['entradas'=>0.0,'saidas_caixa'=>0.0,'saidas_nao_caixa'=>0.0,'cartao'=>0.0,'total'=>0.0,'faturamento'=>0.0];
        foreach ($linhas as $linha) {
            foreach ($total as $campo => $valor) $total[$campo] += (float)($linha[$campo] ?? 0);
        }
        return $total;
    }
}
