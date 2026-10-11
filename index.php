<?php
/**
 * Academia System
 * Front controller - todas as requisicoes passam por aqui.
 */

define('BASE_PATH', __DIR__);

// Servidor embutido do PHP (php -S): entrega arquivos reais (css, js, imagens)
// sem passar pelo roteador. No Apache quem faz isso e o .htaccess.
if (PHP_SAPI === 'cli-server') {
    $arquivo = BASE_PATH . parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (is_file($arquivo) && !str_ends_with($arquivo, '.php')) {
        return false;
    }
}

require BASE_PATH . '/app/core/Config.php';
Config::carregar();

$cfgApp = Config::get('app');
date_default_timezone_set($cfgApp['timezone'] ?? 'America/Sao_Paulo');

if (($cfgApp['ambiente'] ?? 'producao') === 'dev') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}

require BASE_PATH . '/app/core/helpers.php';
require BASE_PATH . '/app/core/Database.php';
require BASE_PATH . '/app/core/Auth.php';
require BASE_PATH . '/app/core/QrCode.php';

// Sessao
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);
session_name('academia_system');
session_start();

// Autoload simples dos controllers
spl_autoload_register(static function (string $classe): void {
    foreach (['/app/controllers/', '/app/core/'] as $dir) {
        $arq = BASE_PATH . $dir . $classe . '.php';
        if (is_file($arq)) {
            require $arq;
            return;
        }
    }
});

// Instalacao pendente?
if (!Config::existe() || !Database::instalado()) {
    if (is_file(BASE_PATH . '/instalar.php')) {
        redirect('/instalar.php');
    }
}

// ---------------------------------------------------------------------
// Caminho solicitado
// ---------------------------------------------------------------------
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$dirBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if ($dirBase !== '' && str_starts_with($uri, $dirBase)) {
    $uri = substr($uri, strlen($dirBase));
}
$rota = trim(rawurldecode($uri), '/');
$metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// ---------------------------------------------------------------------
// Tabela de rotas: 'METODO padrao' => [Controller, metodo]
// {x} vira parametro posicional.
// ---------------------------------------------------------------------
$rotas = [
    // Site publico
    'GET '                          => ['SiteController', 'inicio'],
    'GET agendar/{slug}'            => ['PublicoController', 'agendar'],
    'POST agendar/{slug}'           => ['PublicoController', 'registrarLead'],

    // Autenticacao
    'GET entrar'                    => ['AuthController', 'formLogin'],
    'POST entrar'                   => ['AuthController', 'login'],
    'GET sair'                      => ['AuthController', 'sair'],
    'GET esqueci-senha'             => ['AuthController', 'formEsqueci'],
    'POST esqueci-senha'            => ['AuthController', 'enviarReset'],
    'GET redefinir-senha'           => ['AuthController', 'formRedefinir'],
    'POST redefinir-senha'          => ['AuthController', 'redefinir'],

    // Painel da academia
    'GET painel'                    => ['PainelController', 'redirecionar'],
    'GET painel/dashboard'          => ['PainelController', 'dashboard'],
    'GET painel/onboarding'         => ['PainelController', 'onboarding'],
    'POST painel/onboarding'        => ['PainelController', 'salvarOnboarding'],
    'GET painel/trocar-senha'       => ['PainelController', 'trocarSenha'],
    'POST painel/trocar-senha'      => ['PainelController', 'salvarSenha'],

    'GET painel/alunos'             => ['AlunosController', 'index'],
    'POST painel/alunos/salvar'     => ['AlunosController', 'salvar'],
    'POST painel/alunos/excluir'    => ['AlunosController', 'excluir'],

    'GET painel/planos'             => ['PlanosController', 'index'],
    'POST painel/planos/salvar'     => ['PlanosController', 'salvar'],
    'POST painel/planos/excluir'    => ['PlanosController', 'excluir'],

    'GET painel/agenda'             => ['AgendaController', 'index'],
    'POST painel/agenda/salvar'     => ['AgendaController', 'salvar'],
    'POST painel/agenda/mover'      => ['AgendaController', 'mover'],
    'POST painel/agenda/excluir'    => ['AgendaController', 'excluir'],

    'GET painel/checkins'           => ['CheckinsController', 'index'],
    'GET painel/checkins/buscar'    => ['CheckinsController', 'buscar'],
    'POST painel/checkins/registrar' => ['CheckinsController', 'registrar'],

    'GET painel/financeiro'         => ['FinanceiroController', 'index'],
    'POST painel/financeiro/salvar' => ['FinanceiroController', 'salvar'],
    'POST painel/financeiro/baixar' => ['FinanceiroController', 'baixar'],

    'GET painel/relatorios'         => ['RelatoriosController', 'index'],
    'GET painel/leads'              => ['LeadsController', 'index'],
    'POST painel/leads/status'      => ['LeadsController', 'mudarStatus'],
    'GET painel/ai-growth'          => ['AiGrowthController', 'index'],

    'GET painel/equipe'             => ['EquipeController', 'index'],
    'POST painel/equipe/salvar'     => ['EquipeController', 'salvar'],
    'POST painel/equipe/excluir'    => ['EquipeController', 'excluir'],

    'GET painel/configuracoes'      => ['ConfiguracoesController', 'index'],
    'POST painel/configuracoes/salvar' => ['ConfiguracoesController', 'salvar'],

    // Area master (super admin)
    'GET master'                    => ['MasterController', 'redirecionar'],
    'GET master/painel'             => ['MasterController', 'painel'],
    'GET master/academias'          => ['MasterController', 'academias'],
    'GET master/nova-academia'      => ['MasterController', 'formNovaAcademia'],
    'POST master/nova-academia'     => ['MasterController', 'criarAcademia'],
    'POST master/academias/resetar' => ['MasterController', 'resetarSenhaAdmin'],
    'POST master/academias/status'  => ['MasterController', 'mudarStatus'],
    'GET master/configuracoes'      => ['MasterController', 'configuracoes'],
    'POST master/configuracoes'     => ['MasterController', 'salvarConfiguracoes'],
];

// ---------------------------------------------------------------------
// Despacho
// ---------------------------------------------------------------------
$alvo = null;
$params = [];

foreach ($rotas as $padrao => $destino) {
    [$m, $caminho] = array_pad(explode(' ', $padrao, 2), 2, '');
    if ($m !== $metodo) {
        continue;
    }
    if ($caminho === $rota) {
        $alvo = $destino;
        break;
    }
    if (str_contains($caminho, '{')) {
        // preg_quote escapa as chaves, por isso o padrao abaixo usa \{ ... \}
        $regex = '#^' . preg_replace('/\\\\\{[^}]+\\\\\}/', '([^/]+)', preg_quote($caminho, '#')) . '$#';
        if (preg_match($regex, $rota, $m2)) {
            $alvo = $destino;
            $params = array_slice($m2, 1);
            break;
        }
    }
}

define('ROTA_ATUAL', $rota);

if ($alvo === null) {
    http_response_code(404);
    view('site/404', [], null);
    exit;
}

[$classe, $acao] = $alvo;
if (!class_exists($classe) || !method_exists($classe, $acao)) {
    http_response_code(500);
    exit('Controller nao encontrado: ' . e($classe . '::' . $acao));
}

(new $classe())->$acao(...$params);
