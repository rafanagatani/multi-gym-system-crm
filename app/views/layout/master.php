<?php
/**
 * Layout da area Master (super admin).
 */
require_once BASE_PATH . '/app/views/layout/icones.php';

$usuario = Auth::usuario();
$appNome = Config::get('app')['nome'] ?? 'Academia System';

$menu = [
    ['master/painel',         'Painel',                  'relatorios'],
    ['master/academias',      'Academias',               'predio'],
    ['master/nova-academia',  'Nova academia',           'mais'],
    ['master/configuracoes',  'Configurações do sistema','config'],
];
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($titulo ?? 'Master') . ' · ' . $appNome) ?></title>
<link rel="stylesheet" href="<?= e(base_url('assets/css/app.css')) ?>">
</head>
<body>
<div class="shell">
    <aside class="sidebar sidebar-master">
        <div class="sidebar-topo">
            <div class="logo-quadro"><?= icone('escudo', 18) ?></div>
            <div>
                <div class="sidebar-marca">Master</div>
                <div class="sidebar-tenant">Super admin</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-grupo">
                <?php foreach ($menu as $item): ?>
                    <a class="nav-item<?= ativo($item[0]) ?>" href="<?= e(base_url($item[0])) ?>">
                        <?= icone($item[2]) ?><span><?= e($item[1]) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php if (Auth::academiaId()): ?>
                <div class="nav-grupo">
                    <div class="nav-grupo-titulo">Minha academia</div>
                    <a class="nav-item" href="<?= e(base_url('painel/dashboard')) ?>"><?= icone('dashboard') ?><span>Voltar ao painel</span></a>
                </div>
            <?php endif; ?>
        </nav>

        <div class="sidebar-rodape">
            <div class="sidebar-email truncar"><?= e($usuario['email'] ?? '') ?></div>
            <a class="nav-item" href="<?= e(base_url('sair')) ?>"><?= icone('sair') ?><span>Sair</span></a>
        </div>
    </aside>

    <div class="area">
        <header class="topo topo-master">
            <button class="btn-menu" data-menu type="button" aria-label="Abrir menu"><?= icone('menu', 22) ?></button>
            <span>MASTER · Super admin</span>
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
