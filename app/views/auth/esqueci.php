<div class="card">
  <div class="card-corpo">
    <h2 style="font-size:18px;margin-bottom:4px">Recuperar senha</h2>
    <p class="suave pequeno mb-4">Informe seu e-mail. Enviaremos um link válido por 1 hora.</p>

    <form method="post" action="<?= e(base_url('esqueci-senha')) ?>">
      <?= csrf_campo() ?>
      <label>E-mail
        <input type="email" name="email" required autofocus>
      </label>
      <button type="submit" class="btn btn-primario btn-bloco">Enviar link de recuperação</button>
    </form>

    <?php if (!empty($_SESSION['_link_reset'])): $lk = $_SESSION['_link_reset']; unset($_SESSION['_link_reset']); ?>
      <div class="alerta alerta-aviso mt-4">
        <strong>Envio de e-mail desativado.</strong>
        <p class="pequeno">Use o link abaixo para redefinir a senha:</p>
        <p class="mono pequeno" style="word-break:break-all"><?= e($lk) ?></p>
        <button type="button" class="btn btn-contorno btn-pq mt-2" data-copiar="<?= e($lk) ?>">Copiar link</button>
      </div>
    <?php endif; ?>

    <p class="centro mt-4 pequeno"><a class="suave" href="<?= e(base_url('entrar')) ?>">← Voltar ao login</a></p>
  </div>
</div>
