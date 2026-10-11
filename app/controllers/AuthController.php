<?php
/**
 * Login, logout e recuperacao de senha.
 */
final class AuthController
{
    public function formLogin(): void
    {
        if (Auth::logado()) {
            redirect(Auth::academiaId() ? 'painel/dashboard' : 'master/painel');
        }
        view('auth/entrar', ['titulo' => 'Entrar'], 'auth');
    }

    public function login(): void
    {
        csrf_validar();

        $email = post('email');
        $senha = $_POST['senha'] ?? '';

        // Trava simples contra forca bruta: 6 tentativas por 10 minutos.
        $agora = time();
        $tent = $_SESSION['_tentativas'] ?? ['n' => 0, 'ate' => 0];
        if ($tent['n'] >= 6 && $agora < $tent['ate']) {
            flash('erro', 'Muitas tentativas. Aguarde ' . ceil(($tent['ate'] - $agora) / 60) . ' minuto(s).');
            redirect('entrar');
        }

        $erro = Auth::login($email, $senha);

        if ($erro !== null) {
            $_SESSION['_tentativas'] = ['n' => $tent['n'] + 1, 'ate' => $agora + 600];
            flash('erro', $erro);
            redirect('entrar');
        }

        unset($_SESSION['_tentativas']);

        $usuario = Auth::usuario();
        if ((int) ($usuario['must_change_password'] ?? 0) === 1) {
            flash('aviso', 'Defina uma nova senha para continuar.');
            redirect('painel/trocar-senha');
        }

        $destino = $_SESSION['_apos_login'] ?? '';
        unset($_SESSION['_apos_login']);
        if ($destino && str_contains($destino, '/painel/')) {
            redirect($destino);
        }

        redirect(Auth::academiaId() ? 'painel/dashboard' : 'master/painel');
    }

    public function sair(): void
    {
        Auth::logout();
        redirect('entrar');
    }

    public function formEsqueci(): void
    {
        view('auth/esqueci', ['titulo' => 'Recuperar senha'], 'auth');
    }

    public function enviarReset(): void
    {
        csrf_validar();
        $email = mb_strtolower(post('email'));
        $u = Database::first('SELECT id, nome FROM usuario WHERE email = ? AND ativo = 1 LIMIT 1', [$email]);

        if ($u) {
            $token = bin2hex(random_bytes(32));
            Database::update('usuario', $u['id'], [
                'reset_token'  => hash('sha256', $token),
                'reset_expira' => date('Y-m-d H:i:s', time() + 3600),
            ]);

            $link = base_url('redefinir-senha?token=' . $token);
            $enviado = enviar_email(
                $email,
                'Recuperação de senha · ' . (Config::get('app')['nome'] ?? 'Academia System'),
                '<p>Olá ' . e($u['nome'] ?? '') . ',</p>'
                . '<p>Recebemos um pedido para redefinir sua senha. O link abaixo vale por 1 hora:</p>'
                . '<p><a href="' . e($link) . '">' . e($link) . '</a></p>'
                . '<p>Se não foi você, ignore esta mensagem.</p>'
            );

            // Sem envio de e-mail configurado: mostra o link na tela para o admin.
            if (!$enviado) {
                $_SESSION['_link_reset'] = $link;
            }
        }

        flash('info', 'Se o e-mail existir no sistema, enviamos as instruções de recuperação.');
        redirect('esqueci-senha');
    }

    public function formRedefinir(): void
    {
        $token = get('token');
        $u = $this->usuarioPorToken($token);
        if (!$u) {
            flash('erro', 'Link inválido ou expirado. Peça um novo.');
            redirect('esqueci-senha');
        }
        view('auth/redefinir', ['titulo' => 'Nova senha', 'token' => $token], 'auth');
    }

    public function redefinir(): void
    {
        csrf_validar();
        $token   = post('token');
        $senha   = $_POST['senha'] ?? '';
        $confirm = $_POST['confirmar'] ?? '';

        $u = $this->usuarioPorToken($token);
        if (!$u) {
            flash('erro', 'Link inválido ou expirado. Peça um novo.');
            redirect('esqueci-senha');
        }
        if (strlen($senha) < 8) {
            flash('erro', 'A senha precisa ter pelo menos 8 caracteres.');
            redirect('redefinir-senha?token=' . urlencode($token));
        }
        if ($senha !== $confirm) {
            flash('erro', 'As senhas não conferem.');
            redirect('redefinir-senha?token=' . urlencode($token));
        }

        Database::update('usuario', $u['id'], [
            'senha_hash'           => password_hash($senha, PASSWORD_DEFAULT),
            'reset_token'          => null,
            'reset_expira'         => null,
            'must_change_password' => 0,
        ]);

        flash('ok', 'Senha atualizada. Faça login com a nova senha.');
        redirect('entrar');
    }

    private function usuarioPorToken(string $token): ?array
    {
        if ($token === '') {
            return null;
        }
        return Database::first(
            'SELECT id FROM usuario WHERE reset_token = ? AND reset_expira > NOW() AND ativo = 1 LIMIT 1',
            [hash('sha256', $token)]
        );
    }
}
