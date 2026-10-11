<?php
/** Configuracoes globais da plataforma. */
require_once BASE_PATH . '/app/views/layout/icones.php';
?>

<div class="cabecalho-pagina">
  <div>
    <h1>Configurações do sistema</h1>
    <p>Ajustes globais da plataforma.</p>
  </div>
</div>

<div class="card mb-4" style="max-width:720px"><div class="card-corpo">
  <form method="post" action="<?= e(base_url('master/configuracoes')) ?>">
    <?= csrf_campo() ?>

    <div class="secao-titulo">Identidade</div>
    <label>Nome da plataforma
      <input type="text" name="app_name" value="<?= e($config['app_name'] ?? 'Academia System') ?>">
    </label>

    <div class="secao-titulo">Envio de e-mail</div>
    <div class="grid grid-2" style="gap:12px">
      <label>E-mail remetente
        <input type="email" name="email_remetente" value="<?= e($config['email_remetente'] ?? '') ?>"
               placeholder="nao-responda@seudominio.com.br">
      </label>
      <label>Nome do remetente
        <input type="text" name="email_from_name" value="<?= e($config['email_from_name'] ?? 'Academia System') ?>">
      </label>
    </div>

    <button type="submit" class="btn btn-primario btn-bloco">Salvar configurações</button>
  </form>
</div></div>

<div class="card mb-4" style="max-width:720px;border-color:#FCD34D;background:#FFFBEB"><div class="card-corpo">
  <div class="flex itens-centro gap-2 forte mb-2" style="color:#B45309">
    <?= icone('email', 16) ?> Envio de e-mail — <?= !empty($emailCfg['ativo']) ? 'ATIVO' : 'DESATIVADO' ?>
  </div>

  <?php if (empty($emailCfg['ativo'])): ?>
    <p class="pequeno">
      O sistema está sem envio de e-mail. Recuperação de senha, credenciais de acesso e avisos de lead
      não saem — as senhas geradas continuam sendo exibidas na tela.
    </p>
    <div class="card mt-3" style="background:#fff"><div class="card-corpo-sm">
      <div class="forte pequeno flex itens-centro gap-2 mb-2"><?= icone('info', 14) ?> Como ativar</div>
      <ol class="pequeno suave" style="padding-left:20px;display:grid;gap:5px;margin:0">
        <li>No cPanel, crie uma conta de e-mail (ex.: <code>nao-responda@seudominio.com.br</code>).</li>
        <li>Abra <code>config/config.php</code> no gerenciador de arquivos.</li>
        <li>Em <code>'email'</code>, troque <code>'ativo' =&gt; false</code> por <code>true</code>.</li>
        <li>Preencha <code>'remetente'</code> com o endereço criado.</li>
        <li>Salve. O sistema passa a usar a função <code>mail()</code> do servidor.</li>
      </ol>
    </div></div>
  <?php else: ?>
    <p class="pequeno">
      Remetente configurado: <code><?= e($emailCfg['remetente']) ?></code>.
      Se as mensagens não chegarem, verifique SPF/DKIM do domínio no cPanel.
    </p>
  <?php endif; ?>
</div></div>

<div class="card" style="max-width:720px"><div class="card-corpo">
  <div class="secao-titulo">Ambiente</div>
  <table class="tabela">
    <tbody>
      <tr><td class="suave">Versão do PHP</td><td class="direita mono"><?= e(PHP_VERSION) ?></td></tr>
      <tr><td class="suave">URL base</td><td class="direita mono pequeno"><?= e(base_url()) ?></td></tr>
      <tr><td class="suave">Fuso horário</td><td class="direita mono"><?= e(date_default_timezone_get()) ?></td></tr>
      <tr><td class="suave">Modo</td><td class="direita mono"><?= e($appCfg['ambiente'] ?? 'producao') ?></td></tr>
      <tr><td class="suave">Instalador presente</td>
        <td class="direita">
          <?php if (is_file(BASE_PATH . '/instalar.php')): ?>
            <span class="badge badge-vermelho">Sim — apague instalar.php</span>
          <?php else: ?>
            <span class="badge badge-verde">Não</span>
          <?php endif; ?>
        </td>
      </tr>
    </tbody>
  </table>
</div></div>
