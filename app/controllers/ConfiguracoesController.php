<?php
/**
 * Configuracoes da academia.
 */
final class ConfiguracoesController
{
    public function index(): void
    {
        Auth::exigirPapel(['admin']);
        $academia = Auth::academia();

        $usuarios = Database::all(
            'SELECT id, nome, email, role, ativo, ultimo_login FROM usuario WHERE academy_id = ? ORDER BY nome',
            [Auth::academiaId()]
        );

        view('painel/configuracoes', [
            'titulo'   => 'Configurações',
            'academia' => $academia,
            'usuarios' => $usuarios,
        ], 'painel');
    }

    public function salvar(): void
    {
        Auth::exigirPapel(['admin']);
        csrf_validar();
        $aid = Auth::academiaId();

        $aba = post('aba', 'academia');

        if ($aba === 'academia') {
            $nome = post('name');
            if (mb_strlen($nome) < 2) {
                flash('erro', 'Informe o nome da academia.');
                redirect('painel/configuracoes');
            }

            $slug = slugify(post('slug') ?: $nome);
            if ($slug === '') {
                $slug = slugify($nome) ?: 'academia';
            }
            $conflito = Database::value('SELECT 1 FROM academy WHERE slug = ? AND id <> ?', [$slug, $aid]);
            if ($conflito) {
                flash('erro', 'O link público "' . $slug . '" já está em uso. Escolha outro.');
                redirect('painel/configuracoes');
            }

            Database::update('academy', $aid, [
                'name'            => $nome,
                'slug'            => $slug,
                'cnpj'            => post('cnpj') ?: null,
                'email'           => post('email') ?: null,
                'telefone'        => post('telefone') ?: null,
                'endereco_cep'    => post('endereco_cep') ?: null,
                'endereco_rua'    => post('endereco_rua') ?: null,
                'endereco_numero' => post('endereco_numero') ?: null,
                'endereco_bairro' => post('endereco_bairro') ?: null,
                'endereco_cidade' => post('endereco_cidade') ?: null,
                'endereco_uf'     => mb_strtoupper(substr(post('endereco_uf'), 0, 2)) ?: null,
                'business_hours'  => post('business_hours') ?: null,
                'capacity_per_class' => max(1, (int) post('capacity_per_class', 30)),
            ]);
            flash('ok', 'Dados da academia salvos.');
        }

        if ($aba === 'aparencia') {
            $cor = post('cor_primaria');
            Database::update('academy', $aid, [
                'cor_primaria' => preg_match('/^#[0-9a-fA-F]{6}$/', $cor) ? $cor : '#D97634',
                'logo_url'     => post('logo_url') ?: null,
            ]);
            flash('ok', 'Aparência atualizada.');
        }

        redirect('painel/configuracoes');
    }
}
