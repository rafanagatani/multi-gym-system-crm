<?php
/**
 * Grade de aulas semanais.
 */
final class AgendaController
{
    public const MODALIDADES = ['Musculacao', 'Funcional', 'Pilates', 'Yoga', 'Crossfit', 'Aerobica', 'Outro'];

    public function index(): void
    {
        Auth::exigirAcademia();
        $aid = Auth::academiaId();

        $aulas = Database::all(
            'SELECT * FROM schedule WHERE academy_id = ? AND active = 1 ORDER BY day_of_week, start_time',
            [$aid]
        );

        $professores = Database::all(
            'SELECT id, name FROM team_member WHERE academy_id = ? AND active = 1 ORDER BY name',
            [$aid]
        );

        // Indexa por "dia:hora" para montar a grade.
        $grade = [];
        foreach ($aulas as $a) {
            $grade[(int) $a['day_of_week'] . ':' . (int) substr($a['start_time'], 0, 2)][] = $a;
        }

        view('painel/agenda', [
            'titulo'      => 'Agenda',
            'grade'       => $grade,
            'aulas'       => $aulas,
            'professores' => $professores,
        ], 'painel');
    }

    public function salvar(): void
    {
        Auth::exigirPapel(['admin', 'professor', 'recepcao']);
        csrf_validar();
        $aid = Auth::academiaId();

        $nome    = post('name');
        $inicio  = post('start_time');
        $fim     = post('end_time');

        if (mb_strlen($nome) < 2) {
            flash('erro', 'Informe o nome da aula.');
            redirect('painel/agenda');
        }
        if ($fim <= $inicio) {
            flash('erro', 'A hora de término deve ser maior que a de início.');
            redirect('painel/agenda');
        }

        $profId = post('instructor_id') ?: null;
        $profNome = null;
        if ($profId) {
            $p = Database::first('SELECT name FROM team_member WHERE id = ? AND academy_id = ?', [$profId, $aid]);
            $profNome = $p['name'] ?? null;
            if (!$p) {
                $profId = null;
            }
        }

        $dados = [
            'name'            => $nome,
            'modality'        => in_array(post('modality'), self::MODALIDADES, true) ? post('modality') : 'Outro',
            'day_of_week'     => max(0, min(6, (int) post('day_of_week', 1))),
            'start_time'      => $inicio,
            'end_time'        => $fim,
            'instructor_id'   => $profId,
            'instructor_name' => $profNome,
            'max_capacity'    => max(1, (int) post('max_capacity', 20)),
        ];

        $id = post('id');
        if ($id !== '') {
            if (!Database::value('SELECT 1 FROM schedule WHERE id = ? AND academy_id = ?', [$id, $aid])) {
                flash('erro', 'Aula não encontrada nesta academia.');
                redirect('painel/agenda');
            }
            Database::update('schedule', $id, $dados);
            flash('ok', 'Aula atualizada.');
        } else {
            $dados['academy_id'] = $aid;
            $dados['active'] = 1;
            Database::insert('schedule', $dados);
            flash('ok', 'Aula criada.');
        }

        redirect('painel/agenda');
    }

    /** Reagendamento por arrastar-e-soltar: mantem a duracao da aula. */
    public function mover(): void
    {
        Auth::exigirPapel(['admin', 'professor', 'recepcao']);
        csrf_validar();
        $aid = Auth::academiaId();

        $id   = post('id');
        $dia  = max(0, min(6, (int) post('dia', 1)));
        $hora = max(0, min(23, (int) post('hora', 6)));

        $aula = Database::first('SELECT * FROM schedule WHERE id = ? AND academy_id = ?', [$id, $aid]);
        if (!$aula) {
            redirect('painel/agenda');
        }

        $dur = strtotime($aula['end_time']) - strtotime($aula['start_time']);
        if ($dur <= 0) {
            $dur = 3600;
        }

        $novoInicio = sprintf('%02d:%s', $hora, substr($aula['start_time'], 3));
        $novoFim    = date('H:i:s', strtotime($novoInicio) + $dur);

        Database::update('schedule', $id, [
            'day_of_week' => $dia,
            'start_time'  => $novoInicio,
            'end_time'    => $novoFim,
        ]);

        flash('ok', 'Aula reagendada para ' . dia_semana($dia) . ' às ' . substr($novoInicio, 0, 5) . '.');
        redirect('painel/agenda');
    }

    public function excluir(): void
    {
        Auth::exigirPapel(['admin', 'professor', 'recepcao']);
        csrf_validar();
        $aid = Auth::academiaId();
        $id  = post('id');

        if ($id !== '' && Database::value('SELECT 1 FROM schedule WHERE id = ? AND academy_id = ?', [$id, $aid])) {
            // Desativa em vez de apagar: preserva o historico de check-ins.
            Database::update('schedule', $id, ['active' => 0]);
            flash('ok', 'Aula removida da grade.');
        }
        redirect('painel/agenda');
    }
}
