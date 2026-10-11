<?php
/**
 * Equipe da academia (professores, recepcao, admin).
 */
final class EquipeController
{
    public function index(): void
    {
        Auth::exigirAcademia();
        $aid = Auth::academiaId();

        $membros = Database::all(
            'SELECT t.*,
                    (SELECT COUNT(*) FROM schedule s WHERE s.instructor_id = t.id AND s.active = 1) aulas,
                    (SELECT COALESCE(SUM(s.max_capacity),0) FROM schedule s WHERE s.instructor_id = t.id AND s.active = 1) vagas
               FROM team_member t
              WHERE t.academy_id = ?
           ORDER BY t.active DESC, t.name',
            [$aid]
        );

        // Ocupacao real: check-ins das aulas do professor nos ultimos 30 dias.
        foreach ($membros as &$m) {
            $m['ocupacao'] = 0;
            if ((int) $m['vagas'] > 0) {
                $presencas = (int) Database::value(
                    'SELECT COUNT(*) FROM checkin c
                       JOIN schedule s ON s.id = c.schedule_id
                      WHERE s.instructor_id = ? AND c.date >= ?',
                    [$m['id'], date('Y-m-d', strtotime('-30 days'))],
                    0
                );
                // 4 semanas no periodo
                $m['ocupacao'] = min(100, (int) round($presencas / max(1, (int) $m['vagas'] * 4) * 100));
            }
            $m['alunos'] = (int) Database::value(
                'SELECT COUNT(DISTINCT c.student_id) FROM checkin c
                   JOIN schedule s ON s.id = c.schedule_id
                  WHERE s.instructor_id = ? AND c.date >= ?',
                [$m['id'], date('Y-m-d', strtotime('-30 days'))],
                0
            );
        }
        unset($m);

        view('painel/equipe', ['titulo' => 'Equipe', 'membros' => $membros], 'painel');
    }

    public function salvar(): void
    {
        Auth::exigirPapel(['admin']);
        csrf_validar();
        $aid = Auth::academiaId();

        $nome  = post('name');
        $email = mb_strtolower(post('email'));

        if (mb_strlen($nome) < 2) {
            flash('erro', 'Informe o nome do membro.');
            redirect('painel/equipe');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('erro', 'E-mail inválido.');
            redirect('painel/equipe');
        }

        $dados = [
            'name'          => $nome,
            'email'         => $email,
            'telefone'      => post('telefone') ?: null,
            'role'          => in_array(post('role'), ['admin', 'professor', 'recepcao', 'personal'], true) ? post('role') : 'professor',
            'especialidade' => post('especialidade') ?: null,
            'comissao_pct'  => (float) str_replace(',', '.', post('comissao_pct', '0')),
            'active'        => isset($_POST['active']) ? 1 : 0,
        ];

        $id = post('id');
        if ($id !== '') {
            if (!Database::value('SELECT 1 FROM team_member WHERE id = ? AND academy_id = ?', [$id, $aid])) {
                flash('erro', 'Membro não encontrado nesta academia.');
                redirect('painel/equipe');
            }
            Database::update('team_member', $id, $dados);
            Database::run('UPDATE schedule SET instructor_name = ? WHERE instructor_id = ?', [$dados['name'], $id]);
            flash('ok', 'Membro atualizado.');
        } else {
            $dados['academy_id'] = $aid;
            Database::insert('team_member', $dados);
            flash('ok', 'Membro adicionado.');
        }

        // Criar acesso ao sistema, se solicitado.
        if (!empty($_POST['criar_acesso'])) {
            $this->criarAcesso($aid, $dados);
        }

        redirect('painel/equipe');
    }

    public function excluir(): void
    {
        Auth::exigirPapel(['admin']);
        csrf_validar();
        $aid = Auth::academiaId();
        $id  = post('id');

        if ($id !== '' && Database::value('SELECT 1 FROM team_member WHERE id = ? AND academy_id = ?', [$id, $aid])) {
            Database::run('UPDATE schedule SET instructor_id = NULL WHERE instructor_id = ?', [$id]);
            Database::delete('team_member', $id);
            flash('ok', 'Membro removido.');
        }
        redirect('painel/equipe');
    }

    /** Cria login para o membro e mostra a senha gerada uma unica vez. */
    private function criarAcesso(string $aid, array $dados): void
    {
        if (Database::value('SELECT 1 FROM usuario WHERE email = ?', [$dados['email']])) {
            flash('aviso', 'Já existe um usuário com esse e-mail. O acesso não foi recriado.');
            return;
        }

        $senha = gerar_senha(10);
        Database::insert('usuario', [
            'academy_id'           => $aid,
            'nome'                 => $dados['name'],
            'email'                => $dados['email'],
            'senha_hash'           => password_hash($senha, PASSWORD_DEFAULT),
            'role'                 => $dados['role'] === 'personal' ? 'professor' : $dados['role'],
            'ativo'                => 1,
            'must_change_password' => 1,
        ]);

        enviar_email(
            $dados['email'],
            'Seu acesso · ' . (Config::get('app')['nome'] ?? 'Academia System'),
            '<p>Olá ' . e($dados['name']) . ',</p><p>Seu acesso foi criado.</p>'
            . '<p>E-mail: <b>' . e($dados['email']) . '</b><br>Senha: <b>' . e($senha) . '</b></p>'
            . '<p><a href="' . e(base_url('entrar')) . '">Entrar no sistema</a> — troque a senha no primeiro login.</p>'
        );

        $_SESSION['_credencial'] = ['email' => $dados['email'], 'senha' => $senha, 'nome' => $dados['name']];
        flash('ok', 'Acesso criado para ' . $dados['name'] . '. Anote a senha exibida na tela.');
    }
}
