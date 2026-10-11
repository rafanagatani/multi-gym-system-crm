<?php
/** Faixa de aviso de trial / bloqueio da academia. */
$ac = Auth::academia();
if (!$ac) {
    return;
}
if ($ac['status'] === 'blocked'):
?>
    <div class="faixa faixa-perigo">
        Sua academia está suspensa.
        <a href="<?= e(base_url('painel/configuracoes')) ?>">Regularizar cobrança</a>
    </div>
<?php
elseif ($ac['status'] === 'trial' && !empty($ac['trial_ate'])):
    $dias = (int) floor((strtotime($ac['trial_ate']) - strtotime(date('Y-m-d'))) / 86400);
    if ($dias < 0):
?>
    <div class="faixa faixa-laranja">
        Trial expirado.
        <a href="<?= e(base_url('painel/configuracoes')) ?>">Assinar para continuar</a>
    </div>
<?php elseif ($dias <= 3): ?>
    <div class="faixa faixa-aviso">
        Seu trial termina em <?= $dias ?> <?= $dias === 1 ? 'dia' : 'dias' ?>.
        <a href="<?= e(base_url('painel/configuracoes')) ?>">Assinar plano</a>
    </div>
<?php
    endif;
endif;
