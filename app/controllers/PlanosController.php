<?php
/**
 * Planos da academia.
 */
final class PlanosController
{
    public function index(): void
    {
        Auth::exigirAcademia();
        $aid = Auth::academiaId();

        $planos = Database::all(
            'SELECT p.*, (SELECT COUNT(*) FROM student s WHERE s.plan_id = p.id) alunos
               FROM plan p WHERE p.academy_id = ? ORDER BY p.price',
            [$aid]
        );

        view('painel/planos', ['titulo' => 'Planos', 'planos' => $planos], 'painel');
    }

    public function salvar(): void
    {
        Auth::exigirPapel(['admin', 'recepcao']);
        csrf_validar();
        $aid = Auth::academiaId();

        $nome = post('name');
        if (mb_strlen($nome) < 2) {
            flash('erro', 'Informe o nome do plano.');
            redirect('painel/planos');
        }

        $incluidas = array_filter(array_map('trim', explode(',', (string) post('included_classes'))));

        $dados = [
            'name'             => $nome,
            'description'      => post('description') ?: null,
            'price'            => (float) str_replace(',', '.', post('price', '0')),
            'duration_months'  => max(1, (int) post('duration_months', 1)),
            'included_classes' => $incluidas ? implode(', ', $incluidas) : null,
            'total_checkins'   => post('total_checkins') !== '' ? (int) post('total_checkins') : null,
            'featured'         => isset($_POST['featured']) ? 1 : 0,
            'active'           => isset($_POST['active']) ? 1 : 0,
        ];

        $id = post('id');
        if ($id !== '') {
            if (!Database::value('SELECT 1 FROM plan WHERE id = ? AND academy_id = ?', [$id, $aid])) {
                flash('erro', 'Plano não encontrado nesta academia.');
                redirect('painel/planos');
            }
            Database::update('plan', $id, $dados);
            // Mantem o nome do plano sincronizado na ficha dos alunos.
            Database::run('UPDATE student SET plan_name = ? WHERE plan_id = ? AND academy_id = ?', [$dados['name'], $id, $aid]);
            flash('ok', 'Plano atualizado.');
        } else {
            $dados['academy_id'] = $aid;
            Database::insert('plan', $dados);
            flash('ok', 'Plano criado.');
        }

        redirect('painel/planos');
    }

    public function excluir(): void
    {
        Auth::exigirPapel(['admin']);
        csrf_validar();
        $aid = Auth::academiaId();
        $id  = post('id');

        if ($id === '' || !Database::value('SELECT 1 FROM plan WHERE id = ? AND academy_id = ?', [$id, $aid])) {
            redirect('painel/planos');
        }

        $emUso = (int) Database::value('SELECT COUNT(*) FROM student WHERE plan_id = ?', [$id], 0);
        if ($emUso > 0) {
            // Nao apaga plano em uso: apenas desativa, preservando o historico dos alunos.
            Database::update('plan', $id, ['active' => 0]);
            flash('aviso', "Plano usado por $emUso aluno(s). Ele foi desativado em vez de excluído.");
        } else {
            Database::delete('plan', $id);
            flash('ok', 'Plano excluído.');
        }

        redirect('painel/planos');
    }
}
