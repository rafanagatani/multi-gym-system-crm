<?php
/**
 * Layout da area da academia (tenant).
 * Espera: $conteudo, $titulo (opcional)
 */
require_once BASE_PATH . '/app/views/layout/icones.php';

$usuario  = Auth::usuario();
$academia = Auth::academia();
$appNome  = Config::get('app')['nome'] ?? 'Academia System';

$menu = [
    'Principal' => [
        ['painel/dashboard',  'Dashboard',  'dashboard'],
        ['painel/alunos',     'Alunos',     'alunos'],
        ['painel/planos',     'Planos',     'planos'],
        ['painel/agenda',     'Agenda',     'agenda'],
        ['painel/checkins',   'Check-ins',  'checkin'],
    ],
    'Operacional' => [
        ['painel/financeiro', 'Financeiro', 'financeiro'],
        ['painel/relatorios', 'Relatórios', 'relatorios'],
        ['painel/leads',      'Leads',      'leads'],
        ['painel/ai-growth',  'AI Growth',  'ia', true],
    ],
    'Gestão' => [
        ['painel/equipe',        'Equipe',        'equipe'],
        ['painel/configuracoes', 'Configurações', 'config'],
    ],
];
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($titulo ?? 'Painel') . ' · ' . $appNome) ?></title>
<link rel="stylesheet" href="<?= e(base_url('assets/css/app.css')) ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='7' fill='%23D97634'/><text x='16' y='22' font-size='16' font-family='sans-serif' font-weight='bold' fill='white' text-anchor='middle'>A</text></svg>">
</head>
<body>
<div class="shell">

    <aside class="sidebar">
        <div class="sidebar-topo">
            <div class="logo-quadro">AS</div>
            <div class="truncar">
                <div class="sidebar-marca"><?= e($appNome) ?></div>
                <div class="sidebar-tenant truncar"><?= e($academia['name'] ?? '—') ?></div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <?php foreach ($menu as $grupo => $itens): ?>
                <div class="nav-grupo">
                    <div class="nav-grupo-titulo"><?= e($grupo) ?></div>
                    <?php foreach ($itens as $item): ?>
                        <a class="nav-item<?= ativo($item[0]) ?>" href="<?= e(base_url($item[0])) ?>">
                            <?= icone($item[2]) ?><span><?= e($item[1]) ?></span>
                            <?php if (!empty($item[3])): ?><span class="nav-tag">IA</span><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <?php if (Auth::superAdmin()): ?>
                <div class="nav-grupo">
                    <div class="nav-grupo-titulo">Plataforma</div>
                    <a class="nav-item" href="<?= e(base_url('master/painel')) ?>"><?= icone('escudo') ?><span>Área Master</span></a>
                </div>
            <?php endif; ?>
        </nav>

        <div class="sidebar-rodape">
            <div class="sidebar-email truncar"><?= e($usuario['email'] ?? '') ?></div>
            <a class="nav-item" href="<?= e(base_url('painel/trocar-senha')) ?>"><?= icone('chave') ?><span>Trocar senha</span></a>
            <a class="nav-item" href="<?= e(base_url('sair')) ?>"><?= icone('sair') ?><span>Sair</span></a>
        </div>
    </aside>

    <div class="area">
        <?php require BASE_PATH . '/app/views/layout/_faixa_trial.php'; ?>

        <header class="topo">
            <button class="btn-menu" data-menu type="button" aria-label="Abrir menu"><?= icone('menu', 22) ?></button>
            <strong class="pequeno suave"><?= e($academia['name'] ?? $appNome) ?></strong>
        </header>

        <main class="conteudo">
            <?php require BASE_PATH . '/app/views/layout/_flash.php'; ?>
            <?= $conteudo ?>
        </main>
    </div>
</div>
<script src="<?= e(base_url('assets/js/app.js')) ?>"></script>
</body>
</html>
