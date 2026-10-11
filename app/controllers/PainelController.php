<?php
/**
 * Dashboard, onboarding e troca de senha.
 */
final class PainelController
{
    public function redirecionar(): void
    {
        Auth::exigirLogin();
        redirect(Auth::academiaId() ? 'painel/dashboard' : 'painel/onboarding');
    }

    // -----------------------------------------------------------------
    // Dashboard
    // -----------------------------------------------------------------
    public function dashboard(): void
    {
        Auth::exigirAcademia();
        $aid = Auth::academiaId();

        $hoje       = date('Y-m-d');
        $seteDias   = date('Y-m-d', strtotime('-7 days'));
        $inicioMes  = date('Y-m-01');

        $kpi = [
            'checkins_hoje'  => (int) Database::value('SELECT COUNT(*) FROM checkin WHERE academy_id = ? AND date = ?', [$aid, $hoje], 0),
            'checkins_sem'   => (int) Database::value('SELECT COUNT(*) FROM checkin WHERE academy_id = ? AND date >= ?', [$aid, $seteDias], 0),
            'alunos_ativos'  => (int) Database::value('SELECT COUNT(*) FROM student WHERE academy_id = ? AND status = "active"', [$aid], 0),
            'novos_mes'      => (int) Database::value('SELECT COUNT(*) FROM student WHERE academy_id = ? AND created_at >= ?', [$aid, $inicioMes . ' 00:00:00'], 0),
            'atrasados'      => (int) Database::value('SELECT COUNT(*) FROM student WHERE academy_id = ? AND payment_status = "atrasado"', [$aid], 0),
            'faturamento'    => (float) Database::value(
                'SELECT COALESCE(SUM(amount),0) FROM financial WHERE academy_id = ? AND type = "receita" AND status = "pago" AND date >= ?',
                [$aid, $inicioMes],
                0
            ),
        ];

        // Check-ins por hora (hoje). A faixa padrao e 6h-22h, mas horas com
        // movimento fora dela (madrugada, 23h) tambem entram no grafico.
        $linhasHora = Database::all(
            'SELECT HOUR(time) h, COUNT(*) n FROM checkin WHERE academy_id = ? AND date = ? GROUP BY h',
            [$aid, $hoje]
        );
        $porHora = array_fill_keys(range(6, 22), 0);
        foreach ($linhasHora as $r) {
            $porHora[(int) $r['h']] = (int) $r['n'];
        }
        ksort($porHora);

        // Alunos por plano
        $porPlano = Database::all(
            'SELECT COALESCE(NULLIF(plan_name, ""), "Sem plano") plano, COUNT(*) n
               FROM student WHERE academy_id = ? GROUP BY plano ORDER BY n DESC LIMIT 8',
            [$aid]
        );

        // Evolucao de alunos nos ultimos 12 meses (acumulado de cadastros)
        $evolucao = [];
        for ($i = 11; $i >= 0; $i--) {
            $fim = date('Y-m-t', strtotime("-$i month"));
            $evolucao[] = [
                'mes' => date('m/y', strtotime("-$i month")),
                'n'   => (int) Database::value(
                    'SELECT COUNT(*) FROM student WHERE academy_id = ? AND created_at <= ? AND status <> "inactive"',
                    [$aid, $fim . ' 23:59:59'],
                    0
                ),
            ];
        }

        // Aulas de hoje
        $diaSemana = (int) date('w');
        $aulasHoje = Database::all(
            'SELECT s.*, (SELECT COUNT(*) FROM checkin c WHERE c.schedule_id = s.id AND c.date = ?) inscritos
               FROM schedule s
              WHERE s.academy_id = ? AND s.active = 1 AND s.day_of_week = ?
              ORDER BY s.start_time',
            [$hoje, $aid, $diaSemana]
        );

        $oportunidades = AiGrowthController::oportunidades($aid);

        view('painel/dashboard', [
            'titulo'        => 'Dashboard',
            'kpi'           => $kpi,
            'porHora'       => $porHora,
            'porPlano'      => $porPlano,
            'evolucao'      => $evolucao,
            'aulasHoje'     => $aulasHoje,
            'oportunidades' => array_slice($oportunidades, 0, 3),
            'totalOport'    => count($oportunidades),
            'academia'      => Auth::academia(),
        ], 'painel');
    }

    // -----------------------------------------------------------------
    // Onboarding (usuario sem academia)
    // -----------------------------------------------------------------
    public function onboarding(): void
    {
        Auth::exigirLogin();
        if (Auth::academiaId()) {
            redirect('painel/dashboard');
        }
        if (Auth::superAdmin()) {
            redirect('master/painel');
        }
        view('painel/onboarding', ['titulo' => 'Bem-vindo'], 'auth');
    }

    public function salvarOnboarding(): void
    {
        Auth::exigirLogin();
        csrf_validar();

        if (Auth::academiaId()) {
            redirect('painel/dashboard');
        }

        $nome = post('nome');
        if (mb_strlen($nome) < 2) {
            flash('erro', 'Informe o nome da academia.');
            redirect('painel/onboarding');
        }

        // Slug unico
        $slug = slugify($nome) ?: 'academia';
        $base = $slug;
        $n = 2;
        while (Database::value('SELECT 1 FROM academy WHERE slug = ?', [$slug])) {
            $slug = $base . '-' . $n++;
        }

        $academyId = Database::insert('academy', [
            'name'         => $nome,
            'slug'         => $slug,
            'telefone'     => post('telefone') ?: null,
            'email'        => post('email') ?: null,
            'cor_primaria' => preg_match('/^#[0-9a-fA-F]{6}$/', post('cor_primaria')) ? post('cor_primaria') : '#D97634',
            'status'       => 'trial',
            'trial_ate'    => date('Y-m-d', strtotime('+14 days')),
            'owner_nome'   => Auth::usuario()['nome'] ?? null,
            'owner_email'  => Auth::email(),
        ]);

        // Vincula o usuario logado como admin dessa academia
        Database::update('usuario', Auth::id(), ['academy_id' => $academyId, 'role' => 'admin']);

        // Planos iniciais
        $nomes  = (array) ($_POST['plano_nome'] ?? []);
        $precos = (array) ($_POST['plano_preco'] ?? []);
        $meses  = (array) ($_POST['plano_meses'] ?? []);
        foreach ($nomes as $i => $pn) {
            $pn = trim((string) $pn);
            if ($pn === '') {
                continue;
            }
            Database::insert('plan', [
                'academy_id'      => $academyId,
                'name'            => $pn,
                'price'           => (float) ($precos[$i] ?? 0),
                'duration_months' => max(1, (int) ($meses[$i] ?? 1)),
                'active'          => 1,
            ]);
        }

        // Equipe (um e-mail por linha)
        foreach (preg_split('/[\n,;]+/', (string) post('equipe')) ?: [] as $linha) {
            $mail = mb_strtolower(trim($linha));
            if ($mail === '' || !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            Database::insert('team_member', [
                'academy_id' => $academyId,
                'name'       => strstr($mail, '@', true) ?: $mail,
                'email'      => $mail,
                'role'       => 'recepcao',
                'active'     => 1,
            ]);
        }

        flash('ok', 'Academia criada! Você tem 14 dias de trial gratuito.');
        redirect('painel/dashboard');
    }

    // -----------------------------------------------------------------
    // Troca de senha
    // -----------------------------------------------------------------
    public function trocarSenha(): void
    {
        Auth::exigirLogin();
        $obrigatoria = (int) (Auth::usuario()['must_change_password'] ?? 0) === 1;
        view(
            'painel/trocar-senha',
            ['titulo' => 'Trocar senha', 'obrigatoria' => $obrigatoria],
            Auth::academiaId() && !$obrigatoria ? 'painel' : 'auth'
        );
    }

    public function salvarSenha(): void
    {
        Auth::exigirLogin();
        csrf_validar();

        $atual   = $_POST['senha_atual'] ?? '';
        $nova    = $_POST['senha'] ?? '';
        $confirm = $_POST['confirmar'] ?? '';
        $u = Auth::usuario();
        $obrigatoria = (int) ($u['must_change_password'] ?? 0) === 1;

        if (!$obrigatoria && !password_verify($atual, $u['senha_hash'])) {
            flash('erro', 'Senha atual incorreta.');
            redirect('painel/trocar-senha');
        }
        if (strlen($nova) < 8) {
            flash('erro', 'A nova senha precisa ter pelo menos 8 caracteres.');
            redirect('painel/trocar-senha');
        }
        if ($nova !== $confirm) {
            flash('erro', 'As senhas não conferem.');
            redirect('painel/trocar-senha');
        }

        Database::update('usuario', $u['id'], [
            'senha_hash'           => password_hash($nova, PASSWORD_DEFAULT),
            'must_change_password' => 0,
        ]);

        flash('ok', 'Senha atualizada.');
        redirect(Auth::academiaId() ? 'painel/dashboard' : 'master/painel');
    }
}
