<?php
/**
 * Area Master - gestao de todas as academias da plataforma.
 */
final class MasterController
{
    public function redirecionar(): void
    {
        Auth::exigirSuperAdmin();
        redirect('master/painel');
    }

    // -----------------------------------------------------------------
    // Painel consolidado
    // -----------------------------------------------------------------
    public function painel(): void
    {
        Auth::exigirSuperAdmin();

        $academias = Database::all(
            'SELECT a.*,
                    (SELECT COUNT(*) FROM student s WHERE s.academy_id = a.id AND s.status = "active") alunos,
                    (SELECT COUNT(*) FROM checkin c WHERE c.academy_id = a.id AND c.date >= ?) checkins_mes
               FROM academy a
           ORDER BY a.created_at DESC',
            [date('Y-m-01')]
        );

        $kpi = ['total' => 0, 'ativas' => 0, 'trial' => 0, 'bloqueadas' => 0, 'mrr' => 0.0, 'alunos' => 0];
        foreach ($academias as $a) {
            $kpi['total']++;
            $kpi['alunos'] += (int) $a['alunos'];
            if ($a['status'] === 'active') {
                $kpi['ativas']++;
                $kpi['mrr'] += (float) $a['valor_mensal'];
            } elseif ($a['status'] === 'trial') {
                $kpi['trial']++;
            } else {
                $kpi['bloqueadas']++;
            }
        }

        // Crescimento de academias nos ultimos 12 meses
        $crescimento = [];
        for ($i = 11; $i >= 0; $i--) {
            $fim = date('Y-m-t', strtotime("-$i month"));
            $crescimento[] = [
                'mes' => date('m/y', strtotime($fim)),
                'n'   => (int) Database::value('SELECT COUNT(*) FROM academy WHERE created_at <= ?', [$fim . ' 23:59:59'], 0),
            ];
        }

        view('master/painel', [
            'titulo'      => 'Painel Master',
            'academias'   => $academias,
            'kpi'         => $kpi,
            'crescimento' => $crescimento,
        ], 'master');
    }

    // -----------------------------------------------------------------
    // Lista de academias
    // -----------------------------------------------------------------
    public function academias(): void
    {
        Auth::exigirSuperAdmin();

        $busca  = get('q');
        $status = get('status');

        $sql = 'SELECT a.*,
                       (SELECT COUNT(*) FROM student s WHERE s.academy_id = a.id) alunos,
                       (SELECT u.email FROM usuario u WHERE u.academy_id = a.id AND u.role = "admin" AND u.ativo = 1 ORDER BY u.created_at LIMIT 1) admin_email
                  FROM academy a WHERE 1 = 1';
        $par = [];

        if ($busca !== '') {
            $sql .= ' AND (a.name LIKE ? OR a.slug LIKE ? OR a.owner_email LIKE ?)';
            $like = '%' . $busca . '%';
            array_push($par, $like, $like, $like);
        }
        if (in_array($status, ['active', 'trial', 'blocked'], true)) {
            $sql .= ' AND a.status = ?';
            $par[] = $status;
        }
        $sql .= ' ORDER BY a.created_at DESC';

        view('master/academias', [
            'titulo'    => 'Academias',
            'academias' => Database::all($sql, $par),
            'filtros'   => compact('busca', 'status'),
        ], 'master');
    }

    // -----------------------------------------------------------------
    // Nova academia + admin
    // -----------------------------------------------------------------
    public function formNovaAcademia(): void
    {
        Auth::exigirSuperAdmin();
        view('master/nova-academia', ['titulo' => 'Nova academia'], 'master');
    }

    public function criarAcademia(): void
    {
        Auth::exigirSuperAdmin();
        csrf_validar();

        $nome       = post('nome');
        $slug       = slugify(post('slug') ?: $nome);
        $emailAdmin = mb_strtolower(post('email_admin'));
        $nomeAdmin  = post('nome_admin');

        $erros = [];
        if (mb_strlen($nome) < 2)         { $erros[] = 'Informe o nome da academia.'; }
        if ($slug === '')                 { $erros[] = 'Informe um link público válido (apenas letras, números e hífen).'; }
        if (mb_strlen($nomeAdmin) < 2)    { $erros[] = 'Informe o nome do administrador.'; }
        if (!filter_var($emailAdmin, FILTER_VALIDATE_EMAIL)) { $erros[] = 'E-mail do administrador inválido.'; }
        if ($slug !== '' && Database::value('SELECT 1 FROM academy WHERE slug = ?', [$slug])) {
            $erros[] = 'O link público "' . $slug . '" já está em uso.';
        }
        if (Database::value('SELECT 1 FROM usuario WHERE email = ?', [$emailAdmin])) {
            $erros[] = 'Já existe um usuário com o e-mail ' . $emailAdmin . '.';
        }

        if ($erros) {
            foreach ($erros as $erro) {
                flash('erro', $erro);
            }
            redirect('master/nova-academia');
        }

        $senha = gerar_senha(12);
        $pdo = Database::conn();
        $pdo->beginTransaction();

        try {
            $academyId = Database::insert('academy', [
                'name'            => $nome,
                'slug'            => $slug,
                'cnpj'            => post('cnpj') ?: null,
                'email'           => post('email') ?: null,
                'telefone'        => post('telefone') ?: null,
                'cor_primaria'    => preg_match('/^#[0-9a-fA-F]{6}$/', post('cor_primaria')) ? post('cor_primaria') : '#D97634',
                'endereco_cep'    => post('endereco_cep') ?: null,
                'endereco_rua'    => post('endereco_rua') ?: null,
                'endereco_numero' => post('endereco_numero') ?: null,
                'endereco_bairro' => post('endereco_bairro') ?: null,
                'endereco_cidade' => post('endereco_cidade') ?: null,
                'endereco_uf'     => mb_strtoupper(substr(post('endereco_uf'), 0, 2)) ?: null,
                'capacity_per_class' => max(1, (int) post('capacidade', 30)),
                'plano'           => in_array(post('plano'), ['starter', 'pro', 'premium'], true) ? post('plano') : 'pro',
                'valor_mensal'    => (float) str_replace(',', '.', post('valor_mensal', '0')),
                'dia_vencimento'  => max(1, min(28, (int) post('dia_vencimento', 10))),
                'status'          => 'active',
                'owner_nome'      => $nomeAdmin,
                'owner_email'     => $emailAdmin,
                'owner_telefone'  => post('whatsapp') ?: post('telefone') ?: null,
            ]);

            Database::insert('usuario', [
                'academy_id'           => $academyId,
                'nome'                 => $nomeAdmin,
                'email'                => $emailAdmin,
                'senha_hash'           => password_hash($senha, PASSWORD_DEFAULT),
                'role'                 => 'admin',
                'ativo'                => 1,
                'must_change_password' => 1,
            ]);

            // Plano basico inicial, se informado.
            $mensalidade = (float) str_replace(',', '.', post('mensalidade_basica', '0'));
            if ($mensalidade > 0) {
                Database::insert('plan', [
                    'academy_id'      => $academyId,
                    'name'            => 'Mensal',
                    'description'     => 'Plano mensal padrão.',
                    'price'           => $mensalidade,
                    'duration_months' => 1,
                    'active'          => 1,
                ]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('erro', 'Não foi possível criar a academia: ' . $e->getMessage());
            redirect('master/nova-academia');
        }

        $linkPublico = base_url('agendar/' . $slug);

        enviar_email(
            $emailAdmin,
            'Sua academia foi criada · ' . (Config::get('app')['nome'] ?? 'Academia System'),
            '<p>Olá ' . e($nomeAdmin) . ',</p>'
            . '<p>A academia <b>' . e($nome) . '</b> foi criada.</p>'
            . '<p>Acesso:<br>E-mail: <b>' . e($emailAdmin) . '</b><br>Senha: <b>' . e($senha) . '</b></p>'
            . '<p>Link público de matrícula: <a href="' . e($linkPublico) . '">' . e($linkPublico) . '</a></p>'
            . '<p>Troque sua senha no primeiro acesso: <a href="' . e(base_url('entrar')) . '">' . e(base_url('entrar')) . '</a></p>'
        );

        $_SESSION['_credencial_academia'] = [
            'academia'  => $nome,
            'nome'      => $nomeAdmin,
            'email'     => $emailAdmin,
            'senha'     => $senha,
            'link'      => $linkPublico,
            'whatsapp'  => so_digitos(post('whatsapp') ?: post('telefone')),
        ];

        redirect('master/academias');
    }

    // -----------------------------------------------------------------
    // Reset de senha do admin da academia
    // -----------------------------------------------------------------
    public function resetarSenhaAdmin(): void
    {
        Auth::exigirSuperAdmin();
        csrf_validar();

        $academyId = post('academy_id');
        $academia = Database::first('SELECT id, name FROM academy WHERE id = ?', [$academyId]);
        if (!$academia) {
            redirect('master/academias');
        }

        $admin = Database::first(
            'SELECT id, nome, email FROM usuario WHERE academy_id = ? AND role = "admin" AND ativo = 1 ORDER BY created_at LIMIT 1',
            [$academyId]
        );
        if (!$admin) {
            flash('erro', 'Nenhum administrador ativo nesta academia.');
            redirect('master/academias');
        }

        $senha = gerar_senha(12);
        Database::update('usuario', $admin['id'], [
            'senha_hash'           => password_hash($senha, PASSWORD_DEFAULT),
            'must_change_password' => 1,
            'reset_token'          => null,
            'reset_expira'         => null,
        ]);

        enviar_email(
            $admin['email'],
            'Sua senha foi redefinida · ' . (Config::get('app')['nome'] ?? 'Academia System'),
            '<p>Olá ' . e($admin['nome'] ?? '') . ',</p>'
            . '<p>A senha de acesso da academia <b>' . e($academia['name']) . '</b> foi redefinida.</p>'
            . '<p>Nova senha: <b>' . e($senha) . '</b></p>'
            . '<p>Troque a senha logo após entrar.</p>'
        );

        $_SESSION['_credencial_academia'] = [
            'academia' => $academia['name'],
            'nome'     => $admin['nome'] ?? '',
            'email'    => $admin['email'],
            'senha'    => $senha,
            'link'     => '',
            'whatsapp' => '',
        ];

        flash('ok', 'Senha redefinida. Anote os dados exibidos — eles não aparecem de novo.');
        redirect('master/academias');
    }

    // -----------------------------------------------------------------
    // Suspender / reativar academia
    // -----------------------------------------------------------------
    public function mudarStatus(): void
    {
        Auth::exigirSuperAdmin();
        csrf_validar();

        $id     = post('academy_id');
        $status = post('status');

        if (!in_array($status, ['active', 'trial', 'blocked'], true)) {
            redirect('master/academias');
        }
        if (!Database::value('SELECT 1 FROM academy WHERE id = ?', [$id])) {
            redirect('master/academias');
        }

        $dados = ['status' => $status];
        if ($status === 'trial') {
            $dados['trial_ate'] = date('Y-m-d', strtotime('+14 days'));
        }
        Database::update('academy', $id, $dados);

        flash('ok', 'Status da academia atualizado para "' . rotulo($status) . '".');
        redirect('master/academias');
    }

    // -----------------------------------------------------------------
    // Configuracoes globais
    // -----------------------------------------------------------------
    public function configuracoes(): void
    {
        Auth::exigirSuperAdmin();

        $config = [];
        foreach (Database::all('SELECT chave, valor FROM app_config') as $c) {
            $config[$c['chave']] = $c['valor'];
        }

        view('master/configuracoes', [
            'titulo'   => 'Configurações do sistema',
            'config'   => $config,
            'emailCfg' => Config::get('email'),
            'appCfg'   => Config::get('app'),
        ], 'master');
    }

    public function salvarConfiguracoes(): void
    {
        Auth::exigirSuperAdmin();
        csrf_validar();

        foreach (['app_name', 'email_remetente', 'email_from_name'] as $chave) {
            Database::run(
                'INSERT INTO app_config (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)',
                [$chave, post($chave)]
            );
        }

        flash('ok', 'Configurações salvas. O nome exibido no sistema vem de config/config.php — ajuste lá para trocar o título das páginas.');
        redirect('master/configuracoes');
    }
}
