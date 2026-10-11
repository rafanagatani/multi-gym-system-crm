<?php
/**
 * Layout das telas de autenticacao.
 */
require_once BASE_PATH . '/app/views/layout/icones.php';
$appNome = Config::get('app')['nome'] ?? 'Academia System';
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($titulo ?? 'Entrar') . ' · ' . $appNome) ?></title>
<link rel="stylesheet" href="<?= e(base_url('assets/css/app.css')) ?>">
</head>
<body class="tela-auth">
<div class="caixa-auth">
    <div class="marca-auth">
        <div class="logo-quadro">AS</div>
        <span><?= e($appNome) ?></span>
    </div>
    <?php require BASE_PATH . '/app/views/layout/_flash.php'; ?>
    <?= $conteudo ?>
    <p class="centro mt-4 pequeno">
        <a class="suave" href="<?= e(base_url('/')) ?>">← Voltar ao site</a>
    </p>
</div>
<script src="<?= e(base_url('assets/js/app.js')) ?>"></script>
</body>
</html>
