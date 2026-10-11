<?php
/**
 * Leads recebidos pelo link publico de matricula.
 */
final class LeadsController
{
    public function index(): void
    {
        Auth::exigirAcademia();
        $aid = Auth::academiaId();

        $tipo   = get('tipo');
        $status = get('status');

        $sql = 'SELECT l.*, p.name plano_nome, s.name aula_nome, s.day_of_week, s.start_time
                  FROM `lead` l
             LEFT JOIN plan p ON p.id = l.plan_id
             LEFT JOIN schedule s ON s.id = l.schedule_id
                 WHERE l.academy_id = ?';
        $par = [$aid];

        if (in_array($tipo, ['aula_experimental', 'trial_7dias', 'interesse_plano'], true)) {
            $sql .= ' AND l.type = ?';
            $par[] = $tipo;
        }
        if (in_array($status, ['novo', 'contatado', 'convertido', 'perdido'], true)) {
            $sql .= ' AND l.status = ?';
            $par[] = $status;
        }
        $sql .= ' ORDER BY l.created_at DESC LIMIT 300';

        view('painel/leads', [
            'titulo'  => 'Leads',
            'leads'   => Database::all($sql, $par),
            'filtros' => compact('tipo', 'status'),
            'novos'   => (int) Database::value('SELECT COUNT(*) FROM `lead` WHERE academy_id = ? AND status = "novo"', [$aid], 0),
        ], 'painel');
    }

    public function mudarStatus(): void
    {
        Auth::exigirAcademia();
        csrf_validar();
        $aid    = Auth::academiaId();
        $id     = post('id');
        $status = post('status');

        if (!in_array($status, ['novo', 'contatado', 'convertido', 'perdido'], true)) {
            redirect('painel/leads');
        }

        $lead = Database::first('SELECT * FROM `lead` WHERE id = ? AND academy_id = ?', [$id, $aid]);
        if (!$lead) {
            redirect('painel/leads');
        }

        Database::update('lead', $id, ['status' => $status]);

        // Converter lead cria a ficha do aluno automaticamente.
        if ($status === 'convertido') {
            $jaExiste = Database::value(
                'SELECT 1 FROM student WHERE academy_id = ? AND (phone = ? OR (email IS NOT NULL AND email = ?)) LIMIT 1',
                [$aid, $lead['phone'], $lead['email']]
            );
            if (!$jaExiste) {
                $plano = $lead['plan_id']
                    ? Database::first('SELECT id, name, duration_months FROM plan WHERE id = ? AND academy_id = ?', [$lead['plan_id'], $aid])
                    : null;

                Database::insert('student', [
                    'academy_id' => $aid,
                    'name'       => $lead['name'],
                    'phone'      => $lead['phone'],
                    'email'      => $lead['email'],
                    'objetivo'   => $lead['objetivo'],
                    'plan_id'    => $plano['id'] ?? null,
                    'plan_name'  => $plano['name'] ?? null,
                    'plan_start' => $plano ? date('Y-m-d') : null,
                    'plan_end'   => $plano ? date('Y-m-d', strtotime('+' . max(1, (int) $plano['duration_months']) . ' months')) : null,
                    'status'     => $lead['type'] === 'trial_7dias' ? 'trial' : 'active',
                ]);
                flash('ok', 'Lead convertido e aluno cadastrado.');
                redirect('painel/leads');
            }
        }

        flash('ok', 'Status atualizado.');
        redirect('painel/leads');
    }
}
