<?php
/** Configuracoes da academia. */
require_once BASE_PATH . '/app/views/layout/icones.php';
$a = $academia;
?>

<div class="cabecalho-pagina">
  <div>
    <h1>Configurações</h1>
    <p>Dados, aparência, link público e cobrança da sua academia.</p>
  </div>
</div>

<?php if (!empty($a['slug'])): ?>
  <div class="mb-6" style="max-width:640px">
    <?php $slug = $a['slug']; require BASE_PATH . '/app/views/layout/_link_publico.php'; ?>
  </div>
<?php endif; ?>

<div class="abas">
  <button class="aba ativa" data-aba="academia">Academia</button>
  <button class="aba" data-aba="aparencia">Aparência</button>
  <button class="aba" data-aba="acessos">Acessos</button>
  <button class="aba" data-aba="cobranca">Cobrança</button>
</div>

<!-- ------------------------------------------------ academia -->
<div class="painel-aba ativa" data-painel="academia">
  <div class="card" style="max-width:760px"><div class="card-corpo">
    <form method="post" action="<?= e(base_url('painel/configuracoes/salvar')) ?>">
      <?= csrf_campo() ?>
      <input type="hidden" name="aba" value="academia">

      <div class="secao-titulo">Identificação</div>
      <div class="grid grid-2" style="gap:12px">
        <label class="col-2">Nome da academia *<input type="text" name="name" required value="<?= e($a['name']) ?>"></label>
        <label>Link público (slug)
          <input type="text" name="slug" value="<?= e($a['slug']) ?>" placeholder="minha-academia">
        </label>
        <label>CNPJ<input type="text" name="cnpj" value="<?= e($a['cnpj']) ?>"></label>
        <label>E-mail de contato<input type="email" name="email" value="<?= e($a['email']) ?>"></label>
        <label>Telefone / WhatsApp<input type="text" name="telefone" value="<?= e($a['telefone']) ?>"></label>
      </div>
      <p class="dica">O slug forma o endereço público: <code><?= e(base_url('agendar/')) ?><?= e($a['slug'] ?: 'sua-academia') ?></code></p>

      <div class="secao-titulo">Endereço</div>
      <div class="grid grid-3" style="gap:12px">
        <label>CEP<input type="text" name="endereco_cep" value="<?= e($a['endereco_cep']) ?>"></label>
        <label class="col-2">Rua<input type="text" name="endereco_rua" value="<?= e($a['endereco_rua']) ?>"></label>
        <label>Número<input type="text" name="endereco_numero" value="<?= e($a['endereco_numero']) ?>"></label>
        <label>Bairro<input type="text" name="endereco_bairro" value="<?= e($a['endereco_bairro']) ?>"></label>
        <label>Cidade<input type="text" name="endereco_cidade" value="<?= e($a['endereco_cidade']) ?>"></label>
        <label>UF<input type="text" name="endereco_uf" maxlength="2" value="<?= e($a['endereco_uf']) ?>"></label>
      </div>

      <div class="secao-titulo">Operação</div>
      <div class="grid grid-2" style="gap:12px">
        <label>Horário de funcionamento
          <input type="text" name="business_hours" value="<?= e($a['business_hours']) ?>" placeholder="Seg-Sex 06h-23h · Sáb 08h-18h">
        </label>
        <label>Capacidade padrão por aula
          <input type="number" name="capacity_per_class" min="1" value="<?= (int) $a['capacity_per_class'] ?>">
        </label>
      </div>

      <button type="submit" class="btn btn-primario btn-bloco">Salvar dados</button>
    </form>
  </div></div>
</div>

<!-- ------------------------------------------------ aparência -->
<div class="painel-aba" data-painel="aparencia">
  <div class="card" style="max-width:520px"><div class="card-corpo">
    <form method="post" action="<?= e(base_url('painel/configuracoes/salvar')) ?>">
      <?= csrf_campo() ?>
      <input type="hidden" name="aba" value="aparencia">

      <label>Cor principal</label>
      <div class="flex gap-2 itens-centro">
        <input type="color" name="cor_primaria" value="<?= e($a['cor_primaria'] ?: '#D97634') ?>"
               oninput="document.getElementById('cor-hex').value = this.value">
        <input type="text" id="cor-hex" value="<?= e($a['cor_primaria'] ?: '#D97634') ?>" readonly class="mono">
      </div>
      <p class="dica">Usada na página pública de matrícula da sua academia.</p>

      <label>URL do logotipo
        <input type="url" name="logo_url" value="<?= e($a['logo_url']) ?>" placeholder="https://…/logo.png">
      </label>
      <p class="dica">Hospede a imagem na sua conta e cole o endereço aqui.</p>

      <button type="submit" class="btn btn-primario btn-bloco">Salvar aparência</button>
    </form>
  </div></div>
</div>

<!-- ------------------------------------------------ acessos -->
<div class="painel-aba" data-painel="acessos">
  <div class="card"><div class="card-corpo card-zero tabela-rolagem">
    <table class="tabela">
      <thead><tr><th>Nome</th><th>E-mail</th><th>Função</th><th>Status</th><th>Último acesso</th></tr></thead>
      <tbody>
      <?php foreach ($usuarios as $u): ?>
        <tr>
          <td class="forte"><?= e($u['nome'] ?: '—') ?></td>
          <td class="pequeno"><?= e($u['email']) ?></td>
          <td><span class="badge"><?= e(rotulo($u['role'])) ?></span></td>
          <td><span class="badge <?= (int) $u['ativo'] ? 'badge-verde' : 'badge-vermelho' ?>"><?= (int) $u['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
          <td class="pequeno suave"><?= e(data_br($u['ultimo_login'], 'd/m/Y H:i')) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$usuarios): ?>
        <tr><td colspan="5" class="vazio">Nenhum acesso cadastrado.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div></div>
  <p class="pequeno suave mt-3">
    Crie novos acessos em <a href="<?= e(base_url('painel/equipe')) ?>">Equipe</a>, marcando “Criar login no sistema”.
  </p>
</div>

<!-- ------------------------------------------------ cobrança -->
<div class="painel-aba" data-painel="cobranca">
  <div class="card" style="max-width:520px"><div class="card-corpo">
    <div class="secao-titulo">Assinatura da plataforma</div>
    <table class="tabela">
      <tbody>
        <tr><td class="suave">Plano contratado</td><td class="direita forte"><?= e(rotulo($a['plano'])) ?></td></tr>
        <tr><td class="suave">Situação</td><td class="direita">
          <span class="badge <?= ['active' => 'badge-verde', 'trial' => 'badge-ambar', 'blocked' => 'badge-vermelho'][$a['status']] ?? 'badge' ?>">
            <?= e(rotulo($a['status'])) ?>
          </span>
        </td></tr>
        <tr><td class="suave">Trial até</td><td class="direita"><?= e(data_br($a['trial_ate'])) ?></td></tr>
        <tr><td class="suave">Valor mensal</td><td class="direita forte"><?= e(brl($a['valor_mensal'])) ?></td></tr>
        <tr><td class="suave">Dia de vencimento</td><td class="direita">Dia <?= (int) $a['dia_vencimento'] ?></td></tr>
      </tbody>
    </table>
    <p class="dica mt-3">Para mudar de plano ou regularizar a cobrança, fale com o administrador da plataforma.</p>
  </div></div>
</div>
