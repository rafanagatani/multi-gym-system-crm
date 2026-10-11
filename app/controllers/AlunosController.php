<?php
/**
 * Cadastro e listagem de alunos.
 */
final class AlunosController
{
    public function index(): void
    {
        Auth::exigirAcademia();
        $aid = Auth::academiaId();

        $busca   = get('q');
        $plano   = get('plano');
        $status  = get('status');
        $pgto    = get('pagamento');

        $sql = 'SELECT * FROM student WHERE academy_id = ?';
        $par = [$aid];

        if ($busca !== '') {
            $sql .= ' AND (name LIKE ? OR cpf LIKE ? OR email LIKE ?)';
            $like = '%' . $busca . '%';
            array_push($par, $like, $like, $like);
        }
        if ($plano !== '')  { $sql .= ' AND plan_name = ?';      $par[] = $plano; }
        if ($status !== '') { $sql .= ' AND status = ?';         $par[] = $status; }
        if ($pgto !== '')   { $sql .= ' AND payment_status = ?'; $par[] = $pgto; }

        $sql .= ' ORDER BY created_at DESC LIMIT 500';

        $alunos = Database::all($sql, $par);
        $planos = Database::all('SELECT id, name, price, duration_months FROM plan WHERE academy_id = ? AND active = 1 ORDER BY price', [$aid]);

        $nomesPlano = array_column(
            Database::all('SELECT DISTINCT plan_name FROM student WHERE academy_id = ? AND plan_name IS NOT NULL AND plan_name <> "" ORDER BY plan_name', [$aid]),
            'plan_name'
        );

        view('painel/alunos', [
            'titulo'     => 'Alunos',
            'alunos'     => $alunos,
            'planos'     => $planos,
            'nomesPlano' => $nomesPlano,
            'filtros'    => compact('busca', 'plano', 'status', 'pgto'),
        ], 'painel');
    }

    public function salvar(): void
    {
        Auth::exigirAcademia();
        csrf_validar();
        $aid = Auth::academiaId();

        $nome = post('name');
        if (mb_strlen($nome) < 2) {
            flash('erro', 'Informe o nome do aluno.');
            redirect('painel/alunos');
        }

        $planoId = post('plan_id') ?: null;
        $planoNome = null;
        $planoMeses = 1;
        if ($planoId) {
            $p = Database::first('SELECT name, duration_months FROM plan WHERE id = ? AND academy_id = ?', [$planoId, $aid]);
            if ($p) {
                $planoNome  = $p['name'];
                $planoMeses = max(1, (int) $p['duration_months']);
            } else {
                $planoId = null;
            }
        }

        $inicio = post('plan_start') ?: date('Y-m-d');
        $fim    = post('plan_end') ?: date('Y-m-d', strtotime($inicio . ' +' . $planoMeses . ' months'));

        $dados = [
            'name'              => $nome,
            'email'             => post('email') ?: null,
            'phone'             => post('phone') ?: null,
            'cpf'               => post('cpf') ?: null,
            'birth_date'        => post('birth_date') ?: null,
            'gender'            => in_array(post('gender'), ['M', 'F', 'Outro'], true) ? post('gender') : null,
            'address'           => post('address') ?: null,
            'emergency_contact' => post('emergency_contact') ?: null,
            'medical_notes'     => post('medical_notes') ?: null,
            'objetivo'          => post('objetivo') ?: null,
            'plan_id'           => $planoId,
            'plan_name'         => $planoNome,
            'plan_start'        => $planoId ? $inicio : null,
            'plan_end'          => $planoId ? $fim : null,
            'payment_status'    => in_array(post('payment_status'), ['em_dia', 'atrasado', 'pendente'], true) ? post('payment_status') : 'em_dia',
            'status'            => in_array(post('status'), ['active', 'inactive', 'blocked', 'trial'], true) ? post('status') : 'active',
        ];

        $id = post('id');
        if ($id !== '') {
            if (!Database::value('SELECT 1 FROM student WHERE id = ? AND academy_id = ?', [$id, $aid])) {
                flash('erro', 'Aluno não encontrado nesta academia.');
                redirect('painel/alunos');
            }
            Database::update('student', $id, $dados);
            flash('ok', 'Aluno atualizado.');
        } else {
            $dados['academy_id'] = $aid;
            Database::insert('student', $dados);
            flash('ok', 'Aluno cadastrado.');
        }

        redirect('painel/alunos');
    }

    public function excluir(): void
    {
        Auth::exigirPapel(['admin']);
        csrf_validar();
        $aid = Auth::academiaId();
        $id  = post('id');

        if ($id !== '' && Database::value('SELECT 1 FROM student WHERE id = ? AND academy_id = ?', [$id, $aid])) {
            Database::delete('student', $id);
            flash('ok', 'Aluno removido.');
        }
        redirect('painel/alunos');
    }
}
