<?php
/**
 * Camada de acesso ao banco (PDO / MySQL).
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function conn(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $cfg = Config::get('db');
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['host'],
            (int) ($cfg['porta'] ?? 3306),
            $cfg['nome'],
            $cfg['charset'] ?? 'utf8mb4'
        );

        try {
            self::$pdo = new PDO($dsn, $cfg['usuario'], $cfg['senha'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            if (Config::get('app')['ambiente'] === 'dev') {
                throw $e;
            }
            http_response_code(500);
            exit('Nao foi possivel conectar ao banco de dados. Verifique config/config.php.');
        }

        return self::$pdo;
    }

    /** Executa um comando e devolve o statement. */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $st = self::conn()->prepare($sql);
        $st->execute($params);
        return $st;
    }

    /** Retorna todas as linhas. */
    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /** Retorna a primeira linha ou null. */
    public static function first(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** Retorna o primeiro valor da primeira linha. */
    public static function value(string $sql, array $params = [], $default = null)
    {
        $v = self::run($sql, $params)->fetchColumn();
        return $v === false ? $default : $v;
    }

    /**
     * INSERT a partir de um array associativo. Gera o id (UUID) se nao vier.
     * @return string id inserido
     */
    public static function insert(string $tabela, array $dados): string
    {
        if (!isset($dados['id'])) {
            $dados['id'] = uuid();
        }
        $cols = array_keys($dados);
        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $tabela,
            '`' . implode('`,`', $cols) . '`',
            ':' . implode(',:', $cols)
        );
        self::run($sql, $dados);
        return (string) $dados['id'];
    }

    /** UPDATE por id. */
    public static function update(string $tabela, string $id, array $dados): void
    {
        if (!$dados) {
            return;
        }
        $sets = [];
        foreach (array_keys($dados) as $c) {
            $sets[] = "`$c` = :$c";
        }
        $dados['__id'] = $id;
        self::run(
            sprintf('UPDATE `%s` SET %s WHERE id = :__id', $tabela, implode(', ', $sets)),
            $dados
        );
    }

    /** DELETE por id. */
    public static function delete(string $tabela, string $id): void
    {
        self::run(sprintf('DELETE FROM `%s` WHERE id = ?', $tabela), [$id]);
    }

    /** Verifica se as tabelas ja existem (usado pelo instalador). */
    public static function instalado(): bool
    {
        try {
            self::value('SELECT 1 FROM `academy` LIMIT 1');
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
