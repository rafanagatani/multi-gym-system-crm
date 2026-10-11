<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Página não encontrada · <?= e(Config::get('app')['nome'] ?? 'Academia System') ?></title>
<link rel="stylesheet" href="<?= e(base_url('assets/css/app.css')) ?>">
</head>
<body class="tela-auth">
<div class="caixa-auth centro">
    <div class="marca-auth">
        <div class="logo-quadro">AS</div>
        <span><?= e(Config::get('app')['nome'] ?? 'Academia System') ?></span>
    </div>
    <div class="card"><div class="card-corpo">
        <h1 style="font-size:46px">404</h1>
        <p class="suave">Essa página não existe ou foi movida.</p>
        <a class="btn btn-primario mt-3" href="<?= e(base_url('/')) ?>">Voltar ao início</a>
    </div></div>
</div>
</body>
</html>
