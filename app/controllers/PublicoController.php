<?php
/**
 * Pagina publica de matricula: /agendar/{slug}
 */
final class PublicoController
{
    public function agendar(string $slug): void
    {
        $academia = $this->academiaPorSlug($slug);
        if (!$academia) {
            http_response_code(404);
            view('publico/nao-encontrada');
            return;
        }

        $planos = Database::all(
            'SELECT id, name, description, price, duration_months, featured
               FROM plan WHERE academy_id = ? AND active = 1 ORDER BY price',
            [$academia['id']]
        );

        $aulas = Database::all(
            'SELECT id, name, modality, instructor_name, day_of_week, start_time, end_time
               FROM schedule WHERE academy_id = ? AND active = 1
           ORDER BY day_of_week, start_time',
            [$academia['id']]
        );

        $enviado = $_SESSION['_lead_ok'] ?? null;
        unset($_SESSION['_lead_ok']);

        view('publico/agendar', [
            'academia' => $academia,
            'planos'   => $planos,
            'aulas'    => $aulas,
            'enviado'  => $enviado,
        ]);
    }

    public function registrarLead(string $slug): void
    {
        $academia = $this->academiaPorSlug($slug);
        if (!$academia) {
            http_response_code(404);
            view('publico/nao-encontrada');
            return;
        }
        csrf_validar();

        $tipo = post('type');
        if (!in_array($tipo, ['aula_experimental', 'trial_7dias', 'interesse_plano'], true)) {
            redirect('agendar/' . $slug);
        }

        $nome     = post('name');
        $telefone = post('phone');

        if (mb_strlen($nome) < 2 || mb_strlen(so_digitos($telefone)) < 10) {
            flash('erro', 'Informe seu nome e um WhatsApp válido com DDD.');
            redirect('agendar/' . $slug);
        }

        // Anti-spam simples: no maximo 1 envio a cada 30 segundos por sessao.
        if (isset($_SESSION['_ultimo_lead']) && (time() - $_SESSION['_ultimo_lead']) < 30) {
            flash('aviso', 'Aguarde alguns segundos antes de enviar outro cadastro.');
            redirect('agendar/' . $slug);
        }
        // Campo isca: se preenchido, provavelmente e robo.
        if (post('website') !== '') {
            redirect('agendar/' . $slug);
        }

        $planoId = null;
        $rotuloFinal = rotulo($tipo);
        if ($tipo === 'interesse_plano') {
            $p = Database::first('SELECT id, name FROM plan WHERE id = ? AND academy_id = ? AND active = 1', [post('plan_id'), $academia['id']]);
            if (!$p) {
                flash('erro', 'Escolha um plano válido.');
                redirect('agendar/' . $slug);
            }
            $planoId = $p['id'];
            $rotuloFinal = 'Plano ' . $p['name'];
        }

        $aulaId = null;
        $modalidade = null;
        if ($tipo === 'aula_experimental') {
            $a = Database::first(
                'SELECT id, modality, day_of_week, start_time FROM schedule WHERE id = ? AND academy_id = ? AND active = 1',
                [post('schedule_id'), $academia['id']]
            );
            if (!$a) {
                flash('erro', 'Escolha a modalidade e o horário da aula.');
                redirect('agendar/' . $slug);
            }
            $aulaId = $a['id'];
            $modalidade = $a['modality'];
            $rotuloFinal = $a['modality'] . ' · ' . dia_semana((int) $a['day_of_week'], true) . ' ' . hora_curta($a['start_time']);
        }

        Database::insert('lead', [
            'academy_id'  => $academia['id'],
            'type'        => $tipo,
            'name'        => $nome,
            'phone'       => $telefone,
            'email'       => filter_var(post('email'), FILTER_VALIDATE_EMAIL) ? post('email') : null,
            'modality'    => $modalidade,
            'objetivo'    => post('objetivo') ?: null,
            'idade'       => post('idade') !== '' ? (int) post('idade') : null,
            'plan_id'     => $planoId,
            'schedule_id' => $aulaId,
            'status'      => 'novo',
        ]);

        $_SESSION['_ultimo_lead'] = time();
        $_SESSION['_lead_ok'] = $rotuloFinal;

        // Avisa a academia por e-mail, se houver endereco cadastrado.
        if (!empty($academia['email'])) {
            enviar_email(
                $academia['email'],
                'Novo lead: ' . $nome,
                '<p>Novo cadastro pelo link público.</p>'
                . '<p><b>Nome:</b> ' . e($nome) . '<br>'
                . '<b>WhatsApp:</b> ' . e($telefone) . '<br>'
                . '<b>Interesse:</b> ' . e($rotuloFinal) . '</p>'
                . '<p><a href="' . e(base_url('painel/leads')) . '">Ver no sistema</a></p>'
            );
        }

        redirect('agendar/' . $slug);
    }

    private function academiaPorSlug(string $slug): ?array
    {
        return Database::first(
            'SELECT id, name, slug, cor_primaria, telefone, logo_url, email,
                    endereco_rua, endereco_numero, endereco_bairro, endereco_cidade, endereco_uf, status
               FROM academy WHERE slug = ? AND status <> "blocked" LIMIT 1',
            [slugify($slug)]
        );
    }
}
