<?php
/**
 * Funcoes utilitarias globais do Academia System.
 */

/** Escapa saida HTML. */
function e($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Gera um UUID v4. */
function uuid(): string
{
    $d = random_bytes(16);
    $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
    $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
}

/** URL base da aplicacao (sem barra final). */
function base_url(string $caminho = ''): string
{
    static $base = null;
    if ($base === null) {
        $cfg = Config::get('app');
        if (!empty($cfg['url'])) {
            $base = rtrim($cfg['url'], '/');
        } else {
            $esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
                $esquema = 'https';
            }
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $dir  = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
            $base = $esquema . '://' . $host . $dir;
        }
    }
    return $base . ($caminho ? '/' . ltrim($caminho, '/') : '');
}

/** Redireciona e encerra. */
function redirect(string $caminho): void
{
    header('Location: ' . (preg_match('#^https?://#', $caminho) ? $caminho : base_url($caminho)));
    exit;
}

/** Formata valor em reais. */
function brl($valor): string
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

/** Formata data (Y-m-d ou datetime) para dd/mm/aaaa. */
function data_br(?string $data, string $formato = 'd/m/Y'): string
{
    if (empty($data) || str_starts_with($data, '0000')) {
        return '—';
    }
    $ts = strtotime($data);
    return $ts ? date($formato, $ts) : '—';
}

/** Hora HH:MM a partir de HH:MM:SS. */
function hora_curta(?string $hora): string
{
    return $hora ? substr($hora, 0, 5) : '—';
}

/** Cria slug a partir de um texto. */
function slugify(string $texto): string
{
    $t = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
    $t = strtolower($t);
    $t = preg_replace('/[^a-z0-9]+/', '-', $t) ?? '';
    return trim($t, '-');
}

/** Gera senha aleatoria legivel. */
function gerar_senha(int $tamanho = 12): string
{
    $maiusc = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $minusc = 'abcdefghijkmnpqrstuvwxyz';
    $nums   = '23456789';
    $simb   = '!@#$%&*?';
    $todos  = $maiusc . $minusc . $nums . $simb;
    $pega = static fn(string $s): string => $s[random_int(0, strlen($s) - 1)];

    $saida = $pega($maiusc) . $pega($minusc) . $pega($nums) . $pega($simb);
    while (strlen($saida) < $tamanho) {
        $saida .= $pega($todos);
    }
    $chars = str_split($saida);
    shuffle($chars);
    return implode('', $chars);
}

/** Somente digitos (telefone/CPF). */
function so_digitos(?string $v): string
{
    return preg_replace('/\D+/', '', (string) $v) ?? '';
}

/** Iniciais do nome, para avatar. */
function iniciais(?string $nome): string
{
    $nome = trim((string) $nome);
    if ($nome === '') {
        return '--';
    }
    $partes = preg_split('/\s+/', $nome) ?: [];
    $ini = mb_substr($partes[0], 0, 1);
    if (count($partes) > 1) {
        $ini .= mb_substr($partes[count($partes) - 1], 0, 1);
    } else {
        $ini .= mb_substr($partes[0], 1, 1);
    }
    return mb_strtoupper($ini);
}

/** Valor de $_POST com trim. */
function post(string $campo, $default = '')
{
    $v = $_POST[$campo] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

/** Valor de $_GET com trim. */
function get(string $campo, $default = '')
{
    $v = $_GET[$campo] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

/** Mensagem flash (gravar / ler). */
function flash(?string $tipo = null, ?string $mensagem = null)
{
    if ($tipo !== null && $mensagem !== null) {
        $_SESSION['_flash'][] = ['tipo' => $tipo, 'msg' => $mensagem];
        return null;
    }
    $itens = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $itens;
}

/** Token CSRF da sessao. */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

/** Campo hidden com o token CSRF. */
function csrf_campo(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/** Valida o token CSRF do POST; encerra se invalido. */
function csrf_validar(): void
{
    $enviado = $_POST['_csrf'] ?? '';
    if (!is_string($enviado) || !hash_equals($_SESSION['_csrf'] ?? '', $enviado)) {
        http_response_code(419);
        exit('Sessao expirada. Recarregue a pagina e tente novamente.');
    }
}

/** Resposta JSON. */
function json($dados, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Nomes dos dias da semana. */
function dia_semana(int $i, bool $curto = false): string
{
    $longos = [0 => 'Domingo', 1 => 'Segunda', 2 => 'Terça', 3 => 'Quarta', 4 => 'Quinta', 5 => 'Sexta', 6 => 'Sábado'];
    $curtos = [0 => 'Dom', 1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex', 6 => 'Sáb'];
    return ($curto ? $curtos : $longos)[$i] ?? '';
}

/** Rotulos amigaveis para status/enums. */
function rotulo(string $chave): string
{
    $mapa = [
        'active' => 'Ativo', 'inactive' => 'Inativo', 'blocked' => 'Bloqueado', 'trial' => 'Trial',
        'em_dia' => 'Em dia', 'atrasado' => 'Atrasado', 'pendente' => 'Pendente',
        'pago' => 'Pago', 'cancelado' => 'Cancelado',
        'receita' => 'Receita', 'despesa' => 'Despesa',
        'novo' => 'Novo', 'contatado' => 'Contatado', 'convertido' => 'Convertido', 'perdido' => 'Perdido',
        'aula_experimental' => 'Aula experimental', 'trial_7dias' => 'Trial 7 dias', 'interesse_plano' => 'Interesse plano',
        'super_admin' => 'Super admin', 'admin' => 'Admin', 'professor' => 'Professor',
        'recepcao' => 'Recepção', 'personal' => 'Personal', 'owner' => 'Dono',
        'starter' => 'Starter', 'pro' => 'Professional', 'premium' => 'Enterprise',
        'catraca' => 'Catraca', 'manual' => 'Manual', 'app' => 'App',
    ];
    return $mapa[$chave] ?? ucfirst(str_replace('_', ' ', $chave));
}

/** Renderiza uma view dentro do layout informado. */
function view(string $arquivo, array $dados = [], ?string $layout = null): void
{
    extract($dados, EXTR_SKIP);
    $__view = BASE_PATH . '/app/views/' . $arquivo . '.php';
    if (!is_file($__view)) {
        http_response_code(500);
        exit('View nao encontrada: ' . e($arquivo));
    }
    if ($layout === null) {
        require $__view;
        return;
    }
    ob_start();
    require $__view;
    $conteudo = ob_get_clean();
    require BASE_PATH . '/app/views/layout/' . $layout . '.php';
}

/** Marca o link ativo da navegacao. */
function ativo(string $rota): string
{
    return (defined('ROTA_ATUAL') && ROTA_ATUAL === $rota) ? ' ativo' : '';
}

/** Envia e-mail simples (mail() do cPanel). Silencioso se desativado. */
function enviar_email(string $para, string $assunto, string $htmlCorpo): bool
{
    $cfg = Config::get('email');
    if (empty($cfg['ativo'])) {
        return false;
    }
    $de = $cfg['remetente'] ?: ('nao-responda@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $nome = $cfg['nome_from'] ?: 'Academia System';
    $headers = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . $nome . ' <' . $de . '>',
        'Reply-To: ' . $de,
    ]);
    return @mail($para, '=?UTF-8?B?' . base64_encode($assunto) . '?=', $htmlCorpo, $headers);
}
