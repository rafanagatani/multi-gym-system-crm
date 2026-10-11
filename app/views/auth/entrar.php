<div class="card">
  <div class="card-corpo">
    <h2 style="font-size:18px;margin-bottom:4px">Entrar no sistema</h2>
    <p class="suave pequeno mb-4">Use o e-mail e a senha cadastrados pela sua academia.</p>

    <form method="post" action="<?= e(base_url('entrar')) ?>">
      <?= csrf_campo() ?>
      <label>E-mail
        <input type="email" name="email" required autocomplete="username" autofocus
               value="<?= e($_COOKIE['ultimo_email'] ?? '') ?>">
      </label>
      <label class="mt-3">Senha
        <input type="password" name="senha" required autocomplete="current-password">
      </label>
      <button type="submit" class="btn btn-primario btn-bloco">Entrar</button>
    </form>

    <p class="centro mt-4 pequeno">
      <a class="suave" href="<?= e(base_url('esqueci-senha')) ?>">Esqueci minha senha</a>
    </p>
  </div>
</div>
