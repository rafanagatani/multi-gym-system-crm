<?php
/** Onboarding: primeiro acesso sem academia. */
require_once BASE_PATH . '/app/views/layout/icones.php';
?>
<div style="max-width:620px;margin:0 auto">
  <h1 style="font-size:23px">Vamos configurar sua academia</h1>
  <p class="suave mt-1">Leva menos de 2 minutos. Você ajusta tudo depois nas configurações.</p>

  <form method="post" action="<?= e(base_url('painel/onboarding')) ?>" class="card mt-4">
    <?= csrf_campo() ?>
    <div class="card-corpo">

      <div class="secao-titulo">Dados da academia</div>
      <label>Nome da academia *<input type="text" name="nome" required autofocus></label>
      <div class="grid grid-2 mt-3" style="gap:12px">
        <label>Telefone / WhatsApp<input type="text" name="telefone"></label>
        <label>E-mail de contato<input type="email" name="email" value="<?= e(Auth::email()) ?>"></label>
      </div>

      <div class="secao-titulo">Aparência</div>
      <label>Cor principal</label>
      <div class="flex gap-2 itens-centro">
        <input type="color" name="cor_primaria" value="#D97634" oninput="document.getElementById('hexcor').value=this.value">
        <input type="text" id="hexcor" value="#D97634" readonly class="mono">
      </div>

      <div class="secao-titulo">Planos iniciais</div>
      <p class="dica">Deixe em branco o que não usar. Dá para editar depois.</p>
      <?php foreach ([['Mensal', 89, 1], ['Trimestral', 249, 3], ['Anual', 899, 12]] as [$n, $p, $m]): ?>
        <div class="flex gap-2 mb-2">
          <input type="text" name="plano_nome[]" value="<?= e($n) ?>" placeholder="Nome" style="margin-top:0">
          <input type="number" name="plano_preco[]" value="<?= $p ?>" step="0.01" min="0" placeholder="Preço" style="margin-top:0;max-width:120px">
          <input type="number" name="plano_meses[]" value="<?= $m ?>" min="1" placeholder="Meses" style="margin-top:0;max-width:100px">
        </div>
      <?php endforeach; ?>

      <div class="secao-titulo">Equipe (opcional)</div>
      <label>E-mails da equipe, um por linha
        <textarea name="equipe" rows="3" placeholder="recepcao@suaacademia.com.br"></textarea>
      </label>
      <p class="dica">Cadastra o pessoal na lista de equipe. O login você cria depois, na tela Equipe.</p>

      <button type="submit" class="btn btn-primario btn-bloco">Criar minha academia</button>
      <p class="centro pequeno suave mt-3">Você ganha 14 dias de trial gratuito.</p>
    </div>
  </form>
</div>
