<?php
/**
 * Leitura do arquivo config/config.php.
 */
final class Config
{
    private static ?array $dados = null;

    public static function carregar(): void
    {
        if (self::$dados !== null) {
            return;
        }
        $arquivo = BASE_PATH . '/config/config.php';
        if (!is_file($arquivo)) {
            // Sem configuracao: manda para o instalador.
            if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'instalar.php') {
                header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/') . '/instalar.php');
                exit;
            }
            self::$dados = [
                'db'    => ['host' => 'localhost', 'porta' => 3306, 'nome' => '', 'usuario' => '', 'senha' => '', 'charset' => 'utf8mb4'],
                'app'   => ['nome' => 'Academia System', 'url' => '', 'timezone' => 'America/Sao_Paulo', 'ambiente' => 'producao'],
                'email' => ['ativo' => false, 'remetente' => '', 'nome_from' => 'Academia System'],
            ];
            return;
        }
        self::$dados = require $arquivo;
    }

    public static function get(string $secao, $default = null)
    {
        self::carregar();
        return self::$dados[$secao] ?? $default;
    }

    public static function existe(): bool
    {
        return is_file(BASE_PATH . '/config/config.php');
    }
}
