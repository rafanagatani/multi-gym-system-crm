<?php
/**
 * AI Growth - oportunidades detectadas nos dados reais da academia.
 *
 * Cada regra devolve: severidade, titulo, descricao, impacto estimado,
 * lista de alunos-alvo e um modelo de mensagem de WhatsApp.
 */
final class AiGrowthController
{
    public function index(): void
    {
        Auth::exigirAcademia();
        $aid = Auth::academiaId();

        $oportunidades = self::oportunidades($aid);

        $emRisco    = 0.0;
        $reativacao = 0.0;
        foreach ($oportunidades as $o) {
            $emRisco    += $o['valor_risco'] ?? 0;
            $reativacao += $o['valor_recuperavel'] ?? 0;
        }

        view('painel/ai-growth', [
            'titulo'        => 'AI Growth',
            'oportunidades' => $oportunidades,
            'emRisco'       => $emRisco,
            'reativacao'    => $reativacao,
            'academia'      => Auth::academia(),
        ], 'painel');
    }

    /**
     * Monta a lista de oportunidades da academia.
     * Usada tambem pelo dashboard.
     */
    public static function oportunidades(string $aid): array
    {
        $academia = Database::first('SELECT name FROM academy WHERE id = ?', [$aid]);
        $nomeAcademia = $academia['name'] ?? 'nossa academia';
        $lista = [];

        // ---------------------------------------------------------------
        // 1. Alunos sem check-in ha mais de 14 dias
        // ---------------------------------------------------------------
        $inativos = Database::all(
            'SELECT s.id, s.name, s.phone, s.plan_name,
                    COALESCE(p.price / GREATEST(p.duration_months,1), 0) mensalidade,
                    (SELECT MAX(c.date) FROM checkin c WHERE c.student_id = s.id) ultimo
               FROM student s
          LEFT JOIN plan p ON p.id = s.plan_id
              WHERE s.academy_id = ? AND s.status = "active"
             HAVING ultimo IS NULL OR ultimo < ?
           ORDER BY ultimo ASC LIMIT 25',
            [$aid, date('Y-m-d', strtotime('-14 days'))]
        );
        if ($inativos) {
            $valor = array_sum(array_column($inativos, 'mensalidade'));
            $lista[] = [
                'id'          => 'inativos',
                'severidade'  => 'alta',
                'icone'       => 'alunos',
                'titulo'      => count($inativos) . ' aluno(s) sem aparecer há mais de 14 dias',
                'descricao'   => 'Risco alto de cancelamento. Uma mensagem agora costuma reverter a saída.',
                'impacto'     => brl($valor) . '/mês em risco',
                'valor_risco' => $valor,
                'valor_recuperavel' => $valor * 0.4,
                'alvos'       => array_map(static function (array $a): array {
                    $dias = $a['ultimo'] ? (int) floor((time() - strtotime($a['ultimo'])) / 86400) : null;
                    return [
                        'nome'    => $a['name'],
                        'telefone' => $a['phone'],
                        'detalhe' => $dias === null ? 'Nunca fez check-in' : "Último check-in há {$dias} dias",
                        'dias'    => $dias ?? 0,
                        'valor'   => (float) $a['mensalidade'],
                    ];
                }, $inativos),
                'modelo' => "Oi {nome}! Sentimos sua falta aqui na {academia} 💪 Faz {dias} dias que você não aparece. "
                          . 'Bora marcar um treino essa semana? Me responde aqui que eu garanto um horário tranquilo pra você!',
            ];
        }

        // ---------------------------------------------------------------
        // 2. Mensalidades vencidas
        // ---------------------------------------------------------------
        $vencidas = Database::all(
            'SELECT f.id, f.amount, f.description, COALESCE(f.due_date, f.date) venc,
                    s.id student_id, s.name, s.phone
               FROM financial f
               JOIN student s ON s.id = f.student_id
              WHERE f.academy_id = ? AND f.type = "receita"
                AND f.status IN ("pendente","atrasado")
                AND COALESCE(f.due_date, f.date) < CURDATE()
           ORDER BY venc ASC LIMIT 25',
            [$aid]
        );
        if ($vencidas) {
            $valor = array_sum(array_map('floatval', array_column($vencidas, 'amount')));
            $lista[] = [
                'id'          => 'inadimplentes',
                'severidade'  => 'alta',
                'icone'       => 'carteira',
                'titulo'      => count($vencidas) . ' mensalidade(s) vencida(s)',
                'descricao'   => 'Cobrança amigável por WhatsApp com PIX recupera boa parte desse valor.',
                'impacto'     => brl($valor) . ' a recuperar',
                'valor_risco' => $valor,
                'valor_recuperavel' => $valor * 0.7,
                'alvos'       => array_map(static function (array $f): array {
                    $dias = (int) floor((time() - strtotime($f['venc'])) / 86400);
                    return [
                        'nome'     => $f['name'],
                        'telefone' => $f['phone'],
                        'detalhe'  => "Vencido há {$dias} dias · " . brl($f['amount']),
                        'dias'     => $dias,
                        'valor'    => (float) $f['amount'],
                    ];
                }, $vencidas),
                'modelo' => 'Olá {nome}, tudo bem? Aqui é da {academia} 🧡 Sua mensalidade de {valor} venceu há {dias} dias. '
                          . 'Posso te mandar o PIX agora? Se precisar ajustar a data, a gente combina.',
            ];
        }

        // ---------------------------------------------------------------
        // 3. Trials terminando nos proximos 3 dias
        // ---------------------------------------------------------------
        $trials = Database::all(
            'SELECT s.name, s.phone, s.plan_end,
                    COALESCE(p.price / GREATEST(p.duration_months,1), 0) mensalidade
               FROM student s
          LEFT JOIN plan p ON p.id = s.plan_id
              WHERE s.academy_id = ? AND s.status = "trial"
                AND s.plan_end IS NOT NULL AND s.plan_end BETWEEN CURDATE() AND ?
           ORDER BY s.plan_end LIMIT 25',
            [$aid, date('Y-m-d', strtotime('+3 days'))]
        );
        if ($trials) {
            $valor = array_sum(array_column($trials, 'mensalidade'));
            $lista[] = [
                'id'          => 'trials',
                'severidade'  => 'alta',
                'icone'       => 'relogio',
                'titulo'      => count($trials) . ' trial(s) terminando em até 3 dias',
                'descricao'   => 'Momento ideal para converter em mensalista. Oferecer matrícula grátis costuma fechar.',
                'impacto'     => '+' . brl($valor) . '/mês recorrente',
                'valor_risco' => 0,
                'valor_recuperavel' => $valor,
                'alvos'       => array_map(static function (array $t): array {
                    $dias = (int) floor((strtotime($t['plan_end']) - strtotime(date('Y-m-d'))) / 86400);
                    return [
                        'nome'     => $t['name'],
                        'telefone' => $t['phone'],
                        'detalhe'  => $dias <= 0 ? 'Termina hoje' : ($dias === 1 ? 'Termina amanhã' : "Termina em {$dias} dias"),
                        'dias'     => $dias,
                        'prazo'    => $dias <= 0 ? 'hoje' : ($dias === 1 ? 'amanhã' : "em {$dias} dias"),
                        'valor'    => (float) $t['mensalidade'],
                    ];
                }, $trials),
                'modelo' => 'Oi {nome}! Seu período experimental na {academia} termina {prazo}. Curtiu treinar com a gente? '
                          . 'Consigo liberar a matrícula GRATUITA se fechar até amanhã — qual plano combina mais com você?',
            ];
        }

        // ---------------------------------------------------------------
        // 4. Alunos com frequencia baixa no mes (1 a 3 check-ins)
        // ---------------------------------------------------------------
        $baixaFreq = Database::all(
            'SELECT s.name, s.phone, COUNT(c.id) n
               FROM student s
               JOIN checkin c ON c.student_id = s.id AND c.date >= ?
              WHERE s.academy_id = ? AND s.status = "active"
           GROUP BY s.id, s.name, s.phone
             HAVING n BETWEEN 1 AND 3
           ORDER BY n ASC LIMIT 25',
            [date('Y-m-01'), $aid]
        );
        if ($baixaFreq) {
            $lista[] = [
                'id'          => 'frequencia',
                'severidade'  => 'media',
                'icone'       => 'agenda',
                'titulo'      => count($baixaFreq) . ' aluno(s) treinando menos de 4x no mês',
                'descricao'   => 'Frequência baixa antecede cancelamento. Um treino guiado costuma reengajar.',
                'impacto'     => 'Retenção +15%',
                'valor_risco' => 0,
                'valor_recuperavel' => 0,
                'alvos'       => array_map(static fn(array $b): array => [
                    'nome'     => $b['name'],
                    'telefone' => $b['phone'],
                    'detalhe'  => $b['n'] . 'x este mês',
                    'freq'     => $b['n'] . 'x este mês',
                ], $baixaFreq),
                'modelo' => '{nome}, percebi que você treinou só {freq}. Vamos marcar um treino guiado essa semana? '
                          . 'Posso montar um plano de 30 minutos pra encaixar na sua rotina 🔥',
            ];
        }

        // ---------------------------------------------------------------
        // 5. Aniversariantes do mes
        // ---------------------------------------------------------------
        $niver = Database::all(
            'SELECT name, phone, birth_date FROM student
              WHERE academy_id = ? AND status = "active" AND birth_date IS NOT NULL
                AND MONTH(birth_date) = MONTH(CURDATE())
           ORDER BY DAY(birth_date) LIMIT 25',
            [$aid]
        );
        if ($niver) {
            $lista[] = [
                'id'          => 'aniversariantes',
                'severidade'  => 'baixa',
                'icone'       => 'estrela',
                'titulo'      => count($niver) . ' aniversariante(s) este mês',
                'descricao'   => 'Aniversário é gatilho emocional. Uma mensagem personalizada eleva o NPS.',
                'impacto'     => 'NPS +12 pts',
                'valor_risco' => 0,
                'valor_recuperavel' => 0,
                'alvos'       => array_map(static fn(array $n): array => [
                    'nome'     => $n['name'],
                    'telefone' => $n['phone'],
                    'detalhe'  => 'Dia ' . date('d', strtotime($n['birth_date'])),
                ], $niver),
                'modelo' => 'Parabéns, {nome}! 🎉 Toda a equipe da {academia} te deseja um ano cheio de PRs, saúde e treinos firmes 💪 '
                          . 'Passa aqui pra retirar seu mimo!',
            ];
        }

        // Nome da academia ja resolvido no modelo.
        foreach ($lista as &$o) {
            $o['academia'] = $nomeAcademia;
        }
        unset($o);

        return $lista;
    }

    /** Preenche o modelo de mensagem com os dados do alvo. */
    public static function mensagem(array $oportunidade, array $alvo): string
    {
        $primeiro = explode(' ', trim((string) $alvo['nome']))[0] ?? '';
        return strtr($oportunidade['modelo'], [
            '{nome}'     => $primeiro,
            '{academia}' => $oportunidade['academia'] ?? '',
            '{dias}'     => (string) ($alvo['dias'] ?? ''),
            '{valor}'    => isset($alvo['valor']) ? brl($alvo['valor']) : '',
            '{prazo}'    => (string) ($alvo['prazo'] ?? ''),
            '{freq}'     => (string) ($alvo['freq'] ?? ''),
        ]);
    }
}
