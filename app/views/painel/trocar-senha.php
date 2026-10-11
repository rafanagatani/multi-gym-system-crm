<?php
/** Troca de senha do usuario logado. */
$semLayoutPainel = !Auth::academiaId() || $obrigatoria;
?>
<?php if (!$semLayoutPainel): ?>
  <div class="cabecalho-pagina">
    <div><h1>Trocar senha</h1><p>Atualize sua senha de acesso ao sistema.</p></div>
  </div>
<?php endif; ?>

<div class="card" style="max-width:440px">
  <div class="card-corpo">
    <?php if ($obrigatoria): ?>
      <div class="alerta alerta-aviso">
        Sua senha foi definida pelo administrador. Escolha uma nova senha para continuar.
      </div>
    <?php elseif ($semLayoutPainel): ?>
      <h2 style="font-size:18px;margin-bottom:12px">Trocar senha</h2>
    <?php endif; ?>

    <form method="post" action="<?= e(base_url('painel/trocar-senha')) ?>">
      <?= csrf_campo() ?>
      <?php if (!$obrigatoria): ?>
        <label>Senha atual
          <input type="password" name="senha_atual" required autocomplete="current-password">
        </label>
      <?php endif; ?>
      <label class="mt-3">Nova senha (mín. 8)
        <input type="password" name="senha" minlength="8" required autocomplete="new-password">
      </label>
      <label class="mt-3">Confirmar nova senha
        <input type="password" name="confirmar" minlength="8" required autocomplete="new-password">
      </label>
      <button type="submit" class="btn btn-primario btn-bloco">Salvar nova senha</button>
    </form>
  </div>
</div>
