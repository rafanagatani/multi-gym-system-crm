<?php
/**
 * Cartao do link publico de matricula, com QR Code gerado no proprio servidor.
 * Espera: $slug
 */
if (empty($slug)) {
    return;
}
$url = base_url('agendar/' . $slug);
$qr = QrCode::svg($url, 104, 2);
?>
<div class="card card-link">
  <div class="card-corpo flex gap-4" style="align-items:flex-start">
    <?php if ($qr !== ''): ?>
      <div class="qr-caixa"><?= $qr ?></div>
    <?php endif; ?>
    <div class="g-1" style="min-width:0">
      <div class="flex itens-centro gap-2 forte" style="color:#B45309;font-size:13px">
        <?= icone('link', 15) ?> Link público de matrícula
      </div>
      <div class="mono pequeno suave truncar mt-1"><?= e($url) ?></div>
      <div class="flex gap-2 mt-3" style="flex-wrap:wrap">
        <button type="button" class="btn btn-contorno btn-pq" data-copiar="<?= e($url) ?>"><?= icone('copiar', 13) ?> Copiar</button>
        <a class="btn btn-contorno btn-pq" href="<?= e($url) ?>" target="_blank" rel="noopener"><?= icone('externo', 13) ?> Abrir</a>
        <button type="button" class="btn btn-contorno btn-pq" data-baixar-qr="qrcode-<?= e($slug) ?>"><?= icone('download', 13) ?> Baixar QR</button>
      </div>
      <p class="minusculo suave mt-2">Imprima o QR Code e deixe no balcão: o aluno se matricula pelo celular.</p>
    </div>
  </div>
</div>
