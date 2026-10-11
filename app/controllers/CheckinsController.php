<?php
/**
 * Terminal de check-in.
 */
final class CheckinsController
{
    public function index(): void
    {
        Auth::exigirAcademia();
        $aid  = Auth::academiaId();
        $hoje = date('Y-m-d');

        $total = (int) Database::value('SELECT COUNT(*) FROM checkin WHERE academy_id = ? AND date = ?', [$aid, $hoje], 0);

        $recentes = Database::all(
            'SELECT id, student_name, time, date FROM checkin WHERE academy_id = ? ORDER BY created_at DESC LIMIT 20',
            [$aid]
        );

        view('painel/checkins', [
            'titulo'   => 'Check-ins',
            'total'    => $total,
            'recentes' => $recentes,
        ], 'painel');
    }

    /** Busca de alunos (JSON) usada pelo campo de check-in. */
    public function buscar(): void
    {
        Auth::exigirAcademia();
        $aid   = Auth::academiaId();
        $termo = get('q');

        if (mb_strlen($termo) < 2) {
            json([]);
        }

        $like = '%' . $termo . '%';
        $alunos = Database::all(
            'SELECT id, name, cpf FROM student
              WHERE academy_id = ? AND status <> "inactive" AND (name LIKE ? OR cpf LIKE ?)
              ORDER BY name LIMIT 8',
            [$aid, $like, $like]
        );

        json($alunos);
    }

    public function registrar(): void
    {
        Auth::exigirAcademia();
        csrf_validar();
        $aid  = Auth::academiaId();
        $hoje = date('Y-m-d');

        $studentId = post('student_id');
        $aluno = Database::first('SELECT id, name, status FROM student WHERE id = ? AND academy_id = ?', [$studentId, $aid]);

        if (!$aluno) {
            flash('erro', 'Aluno não encontrado.');
            redirect('painel/checkins');
        }
        if ($aluno['status'] === 'blocked') {
            flash('erro', 'Aluno bloqueado. Regularize o cadastro antes do check-in.');
            redirect('painel/checkins');
        }

        // Evita duplicidade na mesma hora.
        $duplicado = Database::value(
            'SELECT 1 FROM checkin WHERE academy_id = ? AND student_id = ? AND date = ? AND HOUR(time) = ?',
            [$aid, $aluno['id'], $hoje, (int) date('H')]
        );
        if ($duplicado) {
            flash('aviso', $aluno['name'] . ' já fez check-in nesta hora.');
            redirect('painel/checkins');
        }

        Database::insert('checkin', [
            'academy_id'   => $aid,
            'student_id'   => $aluno['id'],
            'student_name' => $aluno['name'],
            'date'         => $hoje,
            'time'         => date('H:i:s'),
            'source'       => 'manual',
        ]);

        flash('ok', 'Check-in registrado: ' . $aluno['name'] . '.');
        redirect('painel/checkins');
    }
}
