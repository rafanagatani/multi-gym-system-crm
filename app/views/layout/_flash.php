<?php
/** Mensagens flash da sessao. */
foreach ((array) flash() as $item):
    $classe = [
        'ok'    => 'alerta-ok',
        'erro'  => 'alerta-erro',
        'aviso' => 'alerta-aviso',
        'info'  => 'alerta-info',
    ][$item['tipo']] ?? 'alerta-info';
?>
    <div class="alerta <?= $classe ?>"><?= e($item['msg']) ?></div>
<?php endforeach; ?>
