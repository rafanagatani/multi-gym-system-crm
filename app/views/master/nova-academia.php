<?php
/** Cadastro completo de academia + admin de acesso. */
require_once BASE_PATH . '/app/views/layout/icones.php';
?>

<div class="cabecalho-pagina">
  <div>
    <h1>Nova academia</h1>
    <p>Cadastro da academia e criação do administrador de acesso.</p>
  </div>
  <a class="btn btn-contorno" href="<?= e(base_url('master/academias')) ?>"><?= icone('voltar', 15) ?> Voltar</a>
</div>

<form method="post" action="<?= e(base_url('master/nova-academia')) ?>" style="max-width:880px">
  <?= csrf_campo() ?>

  <div class="card mb-4"><div class="card-corpo">
    <div class="secao-titulo flex itens-centro gap-2"><?= icone('predio', 14) ?> Dados da academia</div>
    <div class="grid grid-2" style="gap:14px">
      <label>Nome *<input type="text" name="nome" id="campo-nome" required></label>
      <label>Link público (slug) *
        <input type="text" name="slug" id="campo-slug" required placeholder="forca-total">
      </label>
      <label>CNPJ<input type="text" name="cnpj"></label>
      <label>Cor principal
        <span class="flex gap-2 itens-centro">
          <input type="color" name="cor_primaria" value="#D97634" oninput="document.getElementById('hexcor').value=this.value">
          <input type="text" id="hexcor" value="#D97634" readonly class="mono">
        </span>
      </label>
      <label>Capacidade por aula<input type="number" name="capacidade" min="1" value="30"></label>
      <label>Mensalidade básica (R$)<input type="number" name="mensalidade_basica" step="0.01" min="0" value="89"></label>
    </div>
    <p class="dica">O slug forma o endereço: <code><?= e(base_url('agendar/')) ?></code><span id="preview-slug">sua-academia</span></p>
  </div></div>

  <div class="card mb-4"><div class="card-corpo">
    <div class="secao-titulo flex itens-centro gap-2"><?= icone('predio', 14) ?> Endereço</div>
    <div class="grid grid-3" style="gap:14px">
      <label>CEP<input type="text" name="endereco_cep"></label>
      <label class="col-2">Rua<input type="text" name="endereco_rua"></label>
      <label>Número<input type="text" name="endereco_numero"></label>
      <label>Bairro<input type="text" name="endereco_bairro"></label>
      <label>Cidade<input type="text" name="endereco_cidade"></label>
      <label>UF<input type="text" name="endereco_uf" maxlength="2" style="text-transform:uppercase"></label>
    </div>
  </div></div>

  <div class="card mb-4"><div class="card-corpo">
    <div class="secao-titulo flex itens-centro gap-2"><?= icone('whatsapp', 14) ?> Contato</div>
    <div class="grid grid-3" style="gap:14px">
      <label>Telefone<input type="text" name="telefone"></label>
      <label>WhatsApp<input type="text" name="whatsapp" placeholder="Usado para enviar as credenciais"></label>
      <label>E-mail<input type="email" name="email"></label>
    </div>
  </div></div>

  <div class="card mb-4"><div class="card-corpo">
    <div class="secao-titulo flex itens-centro gap-2"><?= icone('carteira', 14) ?> Plano e cobrança</div>
    <div class="grid grid-3" style="gap:14px">
      <label>Plano
        <select name="plano">
          <option value="starter">Starter</option>
          <option value="pro" selected>Professional</option>
          <option value="premium">Enterprise</option>
        </select>
      </label>
      <label>Valor mensal (R$)<input type="number" name="valor_mensal" step="0.01" min="0" value="197"></label>
      <label>Dia de vencimento<input type="number" name="dia_vencimento" min="1" max="28" value="10"></label>
    </div>
  </div></div>

  <div class="card mb-4"><div class="card-corpo">
    <div class="secao-titulo flex itens-centro gap-2"><?= icone('alunos', 14) ?> Administrador de acesso</div>
    <div class="grid grid-2" style="gap:14px">
      <label>Nome do admin *<input type="text" name="nome_admin" required></label>
      <label>E-mail do admin *<input type="email" name="email_admin" required></label>
    </div>
    <p class="dica">O login é criado com senha gerada automaticamente, exibida na tela depois de salvar.</p>
  </div></div>

  <div class="flex gap-3">
    <button type="submit" class="btn btn-primario btn-gg">Criar academia e admin</button>
    <a class="btn btn-contorno btn-gg" href="<?= e(base_url('master/academias')) ?>">Cancelar</a>
  </div>
</form>

<script>
(function () {
  var nome = document.getElementById('campo-nome');
  var slug = document.getElementById('campo-slug');
  var pre  = document.getElementById('preview-slug');
  var tocado = false;

  function limpar(v) {
    return v.toLowerCase()
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
  }
  nome.addEventListener('input', function () {
    if (tocado) return;
    slug.value = limpar(nome.value);
    pre.textContent = slug.value || 'sua-academia';
  });
  slug.addEventListener('input', function () {
    tocado = slug.value !== '';
    slug.value = limpar(slug.value);
    pre.textContent = slug.value || 'sua-academia';
  });
})();
</script>
