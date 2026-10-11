<?php
/**
 * Relatorios gerenciais (dados reais do banco).
 */
final class RelatoriosController
{
    public function index(): void
    {
        Auth::exigirPapel(['admin', 'recepcao']);
        $aid = Auth::academiaId();

        // --- Faturamento dos ultimos 12 meses ---------------------------
        $faturamento = [];
        for ($i = 11; $i >= 0; $i--) {
            $ini = date('Y-m-01', strtotime("-$i month"));
            $fim = date('Y-m-t', strtotime("-$i month"));
            $faturamento[] = [
                'mes'     => date('m/y', strtotime($ini)),
                'receita' => (float) Database::value(
                    'SELECT COALESCE(SUM(amount),0) FROM financial WHERE academy_id = ? AND type = "receita" AND status = "pago" AND date BETWEEN ? AND ?',
                    [$aid, $ini, $fim],
                    0
                ),
                'despesa' => (float) Database::value(
                    'SELECT COALESCE(SUM(amount),0) FROM financial WHERE academy_id = ? AND type = "despesa" AND status <> "cancelado" AND date BETWEEN ? AND ?',
                    [$aid, $ini, $fim],
                    0
                ),
            ];
        }

        // --- Evolucao da base de alunos ---------------------------------
        $evolucao = [];
        for ($i = 11; $i >= 0; $i--) {
            $fim = date('Y-m-t', strtotime("-$i month"));
            $evolucao[] = [
                'mes' => date('m/y', strtotime($fim)),
                'n'   => (int) Database::value(
                    'SELECT COUNT(*) FROM student WHERE academy_id = ? AND created_at <= ?',
                    [$aid, $fim . ' 23:59:59'],
                    0
                ),
            ];
        }

        // --- Retencao mensal: % de alunos do mes anterior que treinaram ---
        $retencao = [];
        for ($i = 11; $i >= 0; $i--) {
            $ini     = date('Y-m-01', strtotime("-$i month"));
            $fim     = date('Y-m-t', strtotime("-$i month"));
            $iniAnt  = date('Y-m-01', strtotime("-" . ($i + 1) . " month"));
            $fimAnt  = date('Y-m-t', strtotime("-" . ($i + 1) . " month"));

            $base = (int) Database::value(
                'SELECT COUNT(DISTINCT student_id) FROM checkin WHERE academy_id = ? AND date BETWEEN ? AND ?',
                [$aid, $iniAnt, $fimAnt],
                0
            );
            $ficaram = $base === 0 ? 0 : (int) Database::value(
                'SELECT COUNT(DISTINCT c.student_id) FROM checkin c
                  WHERE c.academy_id = ? AND c.date BETWEEN ? AND ?
                    AND c.student_id IN (SELECT DISTINCT student_id FROM checkin WHERE academy_id = ? AND date BETWEEN ? AND ?)',
                [$aid, $ini, $fim, $aid, $iniAnt, $fimAnt],
                0
            );
            $retencao[] = [
                'mes' => date('m/y', strtotime($ini)),
                'pct' => $base === 0 ? 0 : round($ficaram / $base * 100),
            ];
        }

        // --- Modalidades mais procuradas --------------------------------
        $modalidades = Database::all(
            'SELECT COALESCE(NULLIF(c.modality,""), COALESCE(s.modality,"Sem modalidade")) modalidade, COUNT(*) n
               FROM checkin c
          LEFT JOIN schedule s ON s.id = c.schedule_id
              WHERE c.academy_id = ? AND c.date >= ?
           GROUP BY modalidade ORDER BY n DESC LIMIT 8',
            [$aid, date('Y-m-d', strtotime('-90 days'))]
        );

        // --- Horarios mais cheios ---------------------------------------
        $porHora = array_fill_keys(range(6, 22), 0);
        foreach (Database::all(
            'SELECT HOUR(time) h, COUNT(*) n FROM checkin WHERE academy_id = ? AND date >= ? GROUP BY h',
            [$aid, date('Y-m-d', strtotime('-30 days'))]
        ) as $r) {
            $porHora[(int) $r['h']] = (int) $r['n'];
        }
        ksort($porHora);

        // --- Resumo -------------------------------------------------------
        $resumo = [
            'alunos_ativos' => (int) Database::value('SELECT COUNT(*) FROM student WHERE academy_id = ? AND status = "active"', [$aid], 0),
            'mrr'           => (float) Database::value(
                'SELECT COALESCE(SUM(p.price / GREATEST(p.duration_months,1)),0)
                   FROM student s JOIN plan p ON p.id = s.plan_id
                  WHERE s.academy_id = ? AND s.status = "active"',
                [$aid],
                0
            ),
            'ticket_medio'  => (float) Database::value(
                'SELECT COALESCE(AVG(amount),0) FROM financial WHERE academy_id = ? AND type = "receita" AND status = "pago" AND date >= ?',
                [$aid, date('Y-m-01')],
                0
            ),
            'checkins_mes'  => (int) Database::value('SELECT COUNT(*) FROM checkin WHERE academy_id = ? AND date >= ?', [$aid, date('Y-m-01')], 0),
        ];

        view('painel/relatorios', [
            'titulo'      => 'Relatórios',
            'faturamento' => $faturamento,
            'evolucao'    => $evolucao,
            'retencao'    => $retencao,
            'modalidades' => $modalidades,
            'porHora'     => $porHora,
            'resumo'      => $resumo,
            'academia'    => Auth::academia(),
        ], 'painel');
    }
}
