<?php
/**
 * Receitas, despesas e inadimplencia.
 */
final class FinanceiroController
{
    public function index(): void
    {
        Auth::exigirPapel(['admin', 'recepcao']);
        $aid = Auth::academiaId();

        // Mes selecionado (formato YYYY-MM)
        $mes = get('mes');
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $mes = date('Y-m');
        }
        $inicio = $mes . '-01';
        $fim    = date('Y-m-t', strtotime($inicio));

        $lancamentos = Database::all(
            'SELECT * FROM financial WHERE academy_id = ? AND date BETWEEN ? AND ? ORDER BY date DESC, created_at DESC',
            [$aid, $inicio, $fim]
        );

        $kpi = ['prevista' => 0.0, 'recebida' => 0.0, 'inadimplencia' => 0.0, 'despesas' => 0.0];
        foreach ($lancamentos as $l) {
            $valor = (float) $l['amount'];
            if ($l['type'] === 'receita') {
                $kpi['prevista'] += $valor;
                if ($l['status'] === 'pago') {
                    $kpi['recebida'] += $valor;
                } elseif (in_array($l['status'], ['pendente', 'atrasado'], true)) {
                    $kpi['inadimplencia'] += $valor;
                }
            } elseif ($l['type'] === 'despesa' && $l['status'] !== 'cancelado') {
                $kpi['despesas'] += $valor;
            }
        }
        $kpi['lucro'] = $kpi['recebida'] - $kpi['despesas'];

        $alunos = Database::all('SELECT id, name FROM student WHERE academy_id = ? ORDER BY name LIMIT 500', [$aid]);

        view('painel/financeiro', [
            'titulo'      => 'Financeiro',
            'mes'         => $mes,
            'lancamentos' => $lancamentos,
            'kpi'         => $kpi,
            'alunos'      => $alunos,
        ], 'painel');
    }

    public function salvar(): void
    {
        Auth::exigirPapel(['admin', 'recepcao']);
        csrf_validar();
        $aid = Auth::academiaId();

        $valor = (float) str_replace(',', '.', post('amount', '0'));
        if ($valor <= 0) {
            flash('erro', 'Informe um valor maior que zero.');
            redirect('painel/financeiro');
        }

        $alunoId = post('student_id') ?: null;
        $alunoNome = null;
        if ($alunoId) {
            $a = Database::first('SELECT name FROM student WHERE id = ? AND academy_id = ?', [$alunoId, $aid]);
            $alunoNome = $a['name'] ?? null;
            if (!$a) {
                $alunoId = null;
            }
        }

        $dados = [
            'type'           => post('type') === 'despesa' ? 'despesa' : 'receita',
            'category'       => post('category') ?: null,
            'description'    => post('description') ?: null,
            'amount'         => $valor,
            'date'           => post('date') ?: date('Y-m-d'),
            'due_date'       => post('due_date') ?: null,
            'payment_method' => post('payment_method') ?: null,
            'status'         => in_array(post('status'), ['pendente', 'pago', 'atrasado', 'cancelado'], true) ? post('status') : 'pendente',
            'student_id'     => $alunoId,
            'student_name'   => $alunoNome,
        ];

        $id = post('id');
        if ($id !== '') {
            if (!Database::value('SELECT 1 FROM financial WHERE id = ? AND academy_id = ?', [$id, $aid])) {
                flash('erro', 'Lançamento não encontrado nesta academia.');
                redirect('painel/financeiro');
            }
            Database::update('financial', $id, $dados);
            flash('ok', 'Lançamento atualizado.');
        } else {
            $dados['academy_id'] = $aid;
            Database::insert('financial', $dados);
            flash('ok', 'Lançamento registrado.');
        }

        $this->sincronizarAluno($alunoId, $aid);
        redirect('painel/financeiro?mes=' . urlencode(substr($dados['date'], 0, 7)));
    }

    /** Marca uma receita como paga. */
    public function baixar(): void
    {
        Auth::exigirPapel(['admin', 'recepcao']);
        csrf_validar();
        $aid = Auth::academiaId();
        $id  = post('id');

        $l = Database::first('SELECT * FROM financial WHERE id = ? AND academy_id = ?', [$id, $aid]);
        if ($l) {
            Database::update('financial', $id, ['status' => 'pago', 'date' => date('Y-m-d')]);
            $this->sincronizarAluno($l['student_id'], $aid);
            flash('ok', 'Pagamento confirmado.');
        }
        redirect('painel/financeiro?mes=' . urlencode(get('mes', date('Y-m'))));
    }

    /** Atualiza o status de pagamento do aluno conforme as pendencias vencidas. */
    private function sincronizarAluno(?string $alunoId, string $aid): void
    {
        if (!$alunoId) {
            return;
        }
        $pendente = (int) Database::value(
            'SELECT COUNT(*) FROM financial
              WHERE academy_id = ? AND student_id = ? AND type = "receita"
                AND status IN ("pendente","atrasado")
                AND COALESCE(due_date, date) < CURDATE()',
            [$aid, $alunoId],
            0
        );
        Database::update('student', $alunoId, ['payment_status' => $pendente > 0 ? 'atrasado' : 'em_dia']);
    }
}
