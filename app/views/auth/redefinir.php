<div class="card">
  <div class="card-corpo">
    <h2 style="font-size:18px;margin-bottom:4px">Definir nova senha</h2>
    <p class="suave pequeno mb-4">Mínimo de 8 caracteres.</p>

    <form method="post" action="<?= e(base_url('redefinir-senha')) ?>">
      <?= csrf_campo() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <label>Nova senha
        <input type="password" name="senha" minlength="8" required autofocus autocomplete="new-password">
      </label>
      <label class="mt-3">Confirmar senha
        <input type="password" name="confirmar" minlength="8" required autocomplete="new-password">
      </label>
      <button type="submit" class="btn btn-primario btn-bloco">Salvar nova senha</button>
    </form>
  </div>
</div>
