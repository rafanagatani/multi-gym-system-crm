<?php
/**
 * Academia System - instalador.
 *
 * Abra este arquivo no navegador apos subir os arquivos para a hospedagem.
 * Ele cria config/config.php, monta as tabelas e cadastra o super administrador.
 * APAGUE este arquivo depois de instalar.
 */

define('BASE_PATH', __DIR__);
ini_set('display_errors', '1');
error_reporting(E_ALL);

require BASE_PATH . '/app/core/Config.php';
require BASE_PATH . '/app/core/helpers.php';
require BASE_PATH . '/app/core/Database.php';

session_name('academia_system_install');
session_start();

$erros = [];
$sucesso = false;
$jaConfigurado = is_file(BASE_PATH . '/config/config.php');

$f = [
    'db_host'   => post('db_host', 'localhost'),
    'db_porta'  => post('db_porta', '3306'),
    'db_nome'   => post('db_nome'),
    'db_user'   => post('db_user'),
    'db_senha'  => $_POST['db_senha'] ?? '',
    'app_nome'  => post('app_nome', 'Academia System'),
    'app_url'   => post('app_url'),
    'adm_nome'  => post('adm_nome'),
    'adm_email' => post('adm_email'),
    'adm_senha' => $_POST['adm_senha'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ---- Validacao -------------------------------------------------
    if ($f['db_nome'] === '')  { $erros[] = 'Informe o nome do banco de dados.'; }
    if ($f['db_user'] === '')  { $erros[] = 'Informe o usuario do banco de dados.'; }
    if ($f['adm_nome'] === '') { $erros[] = 'Informe o nome do administrador.'; }
    if (!filter_var($f['adm_email'], FILTER_VALIDATE_EMAIL)) { $erros[] = 'E-mail do administrador invalido.'; }
    if (strlen($f['adm_senha']) < 8) { $erros[] = 'A senha do administrador precisa ter pelo menos 8 caracteres.'; }

    // ---- Conexao ---------------------------------------------------
    $pdo = null;
    if (!$erros) {
        try {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $f['db_host'], (int) $f['db_porta'], $f['db_nome']);
            $pdo = new PDO($dsn, $f['db_user'], $f['db_senha'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            $erros[] = 'Falha ao conectar no banco: ' . $e->getMessage();
        }
    }

    // ---- Criacao das tabelas --------------------------------------
    if (!$erros && $pdo) {
        try {
            $sql = file_get_contents(BASE_PATH . '/sql/schema.sql');
            if ($sql === false) {
                throw new RuntimeException('Arquivo sql/schema.sql nao encontrado.');
            }
            // Remove as linhas de comentario antes de separar os comandos,
            // senao os blocos "-- ----" grudam no CREATE TABLE seguinte.
            $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
            $executados = 0;
            foreach (explode(';', $sql) as $comando) {
                $comando = trim($comando, " \t\n\r");
                if ($comando === '') {
                    continue;
                }
                $pdo->exec($comando);
                $executados++;
            }
            if ($executados < 10) {
                throw new RuntimeException("Apenas $executados comando(s) executado(s). O arquivo sql/schema.sql parece incompleto.");
            }
        } catch (Throwable $e) {
            $erros[] = 'Erro ao criar as tabelas: ' . $e->getMessage();
        }
    }

    // ---- Super administrador --------------------------------------
    if (!$erros && $pdo) {
        try {
            $email = mb_strtolower($f['adm_email']);
            $st = $pdo->prepare('SELECT id FROM usuario WHERE email = ? LIMIT 1');
            $st->execute([$email]);
            $existente = $st->fetchColumn();

            if ($existente) {
                $up = $pdo->prepare('UPDATE usuario SET nome = ?, senha_hash = ?, role = "super_admin", ativo = 1, academy_id = NULL WHERE id = ?');
                $up->execute([$f['adm_nome'], password_hash($f['adm_senha'], PASSWORD_DEFAULT), $existente]);
            } else {
                $ins = $pdo->prepare('INSERT INTO usuario (id, academy_id, nome, email, senha_hash, role, ativo) VALUES (?, NULL, ?, ?, ?, "super_admin", 1)');
                $ins->execute([uuid(), $f['adm_nome'], $email, password_hash($f['adm_senha'], PASSWORD_DEFAULT)]);
            }

            $cfgNome = $pdo->prepare('INSERT INTO app_config (chave, valor) VALUES ("app_name", ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)');
            $cfgNome->execute([$f['app_nome'] ?: 'Academia System']);
        } catch (Throwable $e) {
            $erros[] = 'Erro ao criar o administrador: ' . $e->getMessage();
        }
    }

    // ---- Grava config/config.php ----------------------------------
    if (!$erros) {
        $conteudo = "<?php\n"
            . "/**\n * Academia System - configuracao gerada pelo instalador em " . date('d/m/Y H:i') . ".\n */\n\n"
            . "return [\n"
            . "    'db' => [\n"
            . "        'host'    => " . var_export($f['db_host'], true) . ",\n"
            . "        'porta'   => " . (int) $f['db_porta'] . ",\n"
            . "        'nome'    => " . var_export($f['db_nome'], true) . ",\n"
            . "        'usuario' => " . var_export($f['db_user'], true) . ",\n"
            . "        'senha'   => " . var_export($f['db_senha'], true) . ",\n"
            . "        'charset' => 'utf8mb4',\n"
            . "    ],\n"
            . "    'app' => [\n"
            . "        'nome'     => " . var_export($f['app_nome'] ?: 'Academia System', true) . ",\n"
            . "        'url'      => " . var_export(rtrim($f['app_url'], '/'), true) . ",\n"
            . "        'timezone' => 'America/Sao_Paulo',\n"
            . "        'ambiente' => 'producao',\n"
            . "    ],\n"
            . "    'email' => [\n"
            . "        'ativo'     => false,\n"
            . "        'remetente' => '',\n"
            . "        'nome_from' => " . var_export($f['app_nome'] ?: 'Academia System', true) . ",\n"
            . "    ],\n"
            . "];\n";

        if (!is_dir(BASE_PATH . '/config')) {
            @mkdir(BASE_PATH . '/config', 0755, true);
        }
        if (@file_put_contents(BASE_PATH . '/config/config.php', $conteudo) === false) {
            $erros[] = 'Nao foi possivel gravar config/config.php. Ajuste a permissao da pasta config para 755 e tente de novo.';
        } else {
            @chmod(BASE_PATH . '/config/config.php', 0640);
            $sucesso = true;
        }
    }
}

$urlPadrao = $f['app_url'] ?: (
    ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/')
);
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instalação · Academia System</title>
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="tela-auth">
<div class="instalador">
    <div class="instalador-marca">
        <div class="logo-quadro">AS</div>
        <div>
            <h1>Academia System</h1>
            <p>Instalação em 1 passo</p>
        </div>
    </div>

    <?php if ($sucesso): ?>
        <div class="alerta alerta-ok">
            <strong>Instalação concluída!</strong>
            <p>O banco foi criado e o super administrador está pronto.</p>
        </div>
        <div class="card" style="margin-top:16px">
            <div class="card-corpo">
                <p><strong>Acesso:</strong></p>
                <p>E-mail: <code><?= e(mb_strtolower($f['adm_email'])) ?></code></p>
                <p>Senha: a que você acabou de definir.</p>
                <hr>
                <p class="aviso-vermelho">
                    Por segurança, <strong>apague o arquivo <code>instalar.php</code></strong> do servidor agora.
                </p>
                <a class="btn btn-primario" href="<?= e(rtrim($urlPadrao, '/')) ?>/entrar">Ir para o login</a>
            </div>
        </div>
    <?php else: ?>

        <?php if ($jaConfigurado): ?>
            <div class="alerta alerta-aviso">
                Já existe um <code>config/config.php</code>. Continuar vai <strong>sobrescrever</strong> esse arquivo.
            </div>
        <?php endif; ?>

        <?php foreach ($erros as $erro): ?>
            <div class="alerta alerta-erro"><?= e($erro) ?></div>
        <?php endforeach; ?>

        <form method="post" class="card" autocomplete="off">
            <div class="card-corpo">
                <h2 class="secao-titulo">Banco de dados MySQL</h2>
                <p class="dica">No cPanel: <em>Bancos de dados MySQL</em> → crie o banco, o usuário e associe os dois com <strong>todos os privilégios</strong>.</p>
                <div class="grid-2">
                    <label>Servidor
                        <input type="text" name="db_host" value="<?= e($f['db_host']) ?>" required>
                    </label>
                    <label>Porta
                        <input type="number" name="db_porta" value="<?= e($f['db_porta']) ?>">
                    </label>
                    <label>Nome do banco
                        <input type="text" name="db_nome" value="<?= e($f['db_nome']) ?>" placeholder="conta_academia" required>
                    </label>
                    <label>Usuário do banco
                        <input type="text" name="db_user" value="<?= e($f['db_user']) ?>" placeholder="conta_usuario" required>
                    </label>
                    <label class="col-2">Senha do banco
                        <input type="password" name="db_senha" value="">
                    </label>
                </div>

                <h2 class="secao-titulo">Aplicação</h2>
                <div class="grid-2">
                    <label>Nome do sistema
                        <input type="text" name="app_nome" value="<?= e($f['app_nome']) ?>">
                    </label>
                    <label>URL do sistema
                        <input type="url" name="app_url" value="<?= e($urlPadrao) ?>">
                    </label>
                </div>

                <h2 class="secao-titulo">Super administrador</h2>
                <p class="dica">Esta conta gerencia todas as academias da plataforma.</p>
                <div class="grid-2">
                    <label class="col-2">Nome
                        <input type="text" name="adm_nome" value="<?= e($f['adm_nome']) ?>" required>
                    </label>
                    <label>E-mail
                        <input type="email" name="adm_email" value="<?= e($f['adm_email']) ?>" required>
                    </label>
                    <label>Senha (mín. 8)
                        <input type="password" name="adm_senha" minlength="8" required>
                    </label>
                </div>

                <button type="submit" class="btn btn-primario btn-bloco">Instalar Academia System</button>
            </div>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
