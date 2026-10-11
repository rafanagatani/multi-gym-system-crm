<?php
/**
 * Autenticacao e controle de acesso (sessao PHP).
 */
final class Auth
{
    private static ?array $usuario = null;
    private static ?array $academia = null;
    private static bool $academiaCarregada = false;

    /** Faz login. Retorna null em sucesso ou a mensagem de erro. */
    public static function login(string $email, string $senha): ?string
    {
        $email = mb_strtolower(trim($email));
        $u = Database::first('SELECT * FROM usuario WHERE email = ? LIMIT 1', [$email]);

        if (!$u || !password_verify($senha, $u['senha_hash'])) {
            return 'E-mail ou senha invalidos.';
        }
        if ((int) $u['ativo'] !== 1) {
            return 'Usuario desativado. Fale com o administrador.';
        }

        // Rehash se o algoritmo padrao mudou.
        if (password_needs_rehash($u['senha_hash'], PASSWORD_DEFAULT)) {
            Database::update('usuario', $u['id'], ['senha_hash' => password_hash($senha, PASSWORD_DEFAULT)]);
        }

        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $u['id'];
        Database::update('usuario', $u['id'], ['ultimo_login' => date('Y-m-d H:i:s')]);
        if (!empty($u['academy_id'])) {
            Database::update('academy', $u['academy_id'], ['ultimo_acesso' => date('Y-m-d H:i:s')]);
        }
        return null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        self::$usuario = null;
        self::$academia = null;
        self::$academiaCarregada = false;
    }

    /** Usuario logado ou null. */
    public static function usuario(): ?array
    {
        if (self::$usuario !== null) {
            return self::$usuario;
        }
        if (empty($_SESSION['usuario_id'])) {
            return null;
        }
        $u = Database::first('SELECT * FROM usuario WHERE id = ? LIMIT 1', [$_SESSION['usuario_id']]);
        if (!$u || (int) $u['ativo'] !== 1) {
            self::logout();
            return null;
        }
        return self::$usuario = $u;
    }

    public static function logado(): bool
    {
        return self::usuario() !== null;
    }

    public static function id(): ?string
    {
        return self::usuario()['id'] ?? null;
    }

    public static function email(): string
    {
        return self::usuario()['email'] ?? '';
    }

    public static function papel(): string
    {
        return self::usuario()['role'] ?? '';
    }

    public static function superAdmin(): bool
    {
        return self::papel() === 'super_admin';
    }

    /** ID da academia do usuario logado (null para super admin sem academia). */
    public static function academiaId(): ?string
    {
        $u = self::usuario();
        return $u && !empty($u['academy_id']) ? $u['academy_id'] : null;
    }

    /** Registro completo da academia do usuario logado. */
    public static function academia(): ?array
    {
        if (self::$academiaCarregada) {
            return self::$academia;
        }
        self::$academiaCarregada = true;
        $aid = self::academiaId();
        if (!$aid) {
            return self::$academia = null;
        }
        return self::$academia = Database::first('SELECT * FROM academy WHERE id = ? LIMIT 1', [$aid]);
    }

    /** Exige usuario logado. */
    public static function exigirLogin(): void
    {
        if (!self::logado()) {
            $_SESSION['_apos_login'] = $_SERVER['REQUEST_URI'] ?? '';
            redirect('/entrar');
        }
    }

    /** Exige super admin. */
    public static function exigirSuperAdmin(): void
    {
        self::exigirLogin();
        if (!self::superAdmin()) {
            http_response_code(403);
            exit('Acesso restrito ao super administrador.');
        }
    }

    /** Exige que o usuario pertenca a uma academia (area /painel). */
    public static function exigirAcademia(): void
    {
        self::exigirLogin();
        if (!self::academiaId()) {
            if (self::superAdmin()) {
                redirect('/master/painel');
            }
            redirect('/painel/onboarding');
        }
    }

    /** Verifica se o papel do usuario esta na lista informada. */
    public static function temPapel(array $papeis): bool
    {
        return in_array(self::papel(), $papeis, true);
    }

    /** Exige um dos papeis informados. */
    public static function exigirPapel(array $papeis): void
    {
        self::exigirLogin();
        if (self::superAdmin()) {
            return;
        }
        if (!self::temPapel($papeis)) {
            http_response_code(403);
            exit('Voce nao tem permissao para acessar esta area.');
        }
    }
}
