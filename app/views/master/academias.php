<?php
/** Lista de academias com acoes administrativas. */
require_once BASE_PATH . '/app/views/layout/icones.php';

$credencial = $_SESSION['_credencial_academia'] ?? null;
unset($_SESSION['_credencial_academia']);

$badge = ['active' => 'badge-verde', 'trial' => 'badge-ambar', 'blocked' => 'badge-vermelho'];
?>

<div class="cabecalho-pagina">
  <div>
    <h1>Academias</h1>
    <p><?= count($academias) ?> academia(s) cadastrada(s).</p>
  </div>
  <a class="btn btn-primario" href="<?= e(base_url('master/nova-academia')) ?>"><?= icone('mais', 16) ?> Nova academia</a>
</div>

<?php if ($credencial): ?>
  <div class="card mb-6" style="border-color:#FCD34D;background:#FFFBEB"><div class="card-corpo">
    <div class="flex itens-centro gap-2 mb-2" style="color:#B45309">
      <?= icone('chave', 18) ?><h3 style="font-size:16px">Credenciais geradas — <?= e($credencial['academia']) ?></h3>
    </div>
    <div class="aviso-vermelho mb-3"><?= icone('alerta', 14) ?> Esta senha aparece uma única vez. Salve agora.</div>

    <div class="grid grid-2" style="gap:12px">
      <div><div class="minusculo suave">E-mail do admin</div><div class="forte"><?= e($credencial['email']) ?></div></div>
      <div><div class="minusculo suave">Senha</div><div class="mono forte" style="font-size:16px"><?= e($credencial['senha']) ?></div></div>
      <?php if ($credencial['link']): ?>
        <div class="col-2"><div class="minusculo suave">Link público de matrícula</div>
          <div class="mono pequeno truncar"><?= e($credencial['link']) ?></div></div>
      <?php endif; ?>
    </div>

    <?php
      $textoTudo = "Academia: {$credencial['academia']}\nE-mail: {$credencial['email']}\nSenha: {$credencial['senha']}"
                 . ($credencial['link'] ? "\nLink público: {$credencial['link']}" : '');
      $msgWa = "Olá {$credencial['nome']}! Sua academia {$credencial['academia']} foi criada.\n\n"
             . "Acesso:\nE-mail: {$credencial['email']}\nSenha: {$credencial['senha']}\n\n"
             . ($credencial['link'] ? "Link público de matrícula:\n{$credencial['link']}\n\n" : '')
             . 'Troque sua senha no primeiro acesso.';
    ?>
    <div class="flex gap-2 mt-4" style="flex-wrap:wrap">
      <button type="button" class="btn btn-contorno" data-copiar="<?= e($textoTudo) ?>"><?= icone('copiar', 14) ?> Copiar tudo</button>
      <?php if ($credencial['whatsapp']): ?>
        <a class="btn btn-verde" target="_blank" rel="noopener"
           href="https://wa.me/55<?= e($credencial['whatsapp']) ?>?text=<?= e(rawurlencode($msgWa)) ?>">
          <?= icone('whatsapp', 14) ?> Enviar por WhatsApp
        </a>
      <?php endif; ?>
      <?php if ($credencial['link']): ?>
        <a class="btn btn-contorno" href="<?= e($credencial['link']) ?>" target="_blank" rel="noopener"><?= icone('externo', 14) ?> Abrir link público</a>
      <?php endif; ?>
    </div>
  </div></div>
<?php endif; ?>

<form method="get" action="<?= e(base_url('master/academias')) ?>" class="flex gap-3 mb-4" style="flex-wrap:wrap;align-items:flex-end">
  <div style="min-width:240px" class="g-1">
    <label class="minusculo suave">Buscar</label>
    <input type="search" name="q" value="<?= e($filtros['busca']) ?>" placeholder="Nome, slug ou e-mail do dono…">
  </div>
  <div style="min-width:160px">
    <label class="minusculo suave">Status</label>
    <select name="status">
      <option value="">Todos</option>
      <?php foreach (['active', 'trial', 'blocked'] as $s): ?>
        <option value="<?= $s ?>"<?= $filtros['status'] === $s ? ' selected' : '' ?>><?= e(rotulo($s)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <button class="btn btn-contorno"><?= icone('busca', 15) ?> Filtrar</button>
  <a class="btn btn-fantasma" href="<?= e(base_url('master/academias')) ?>">Limpar</a>
</form>

<?php if (!$academias): ?>
  <div class="card"><p class="vazio">Nenhuma academia encontrada.</p></div>
<?php else: ?>
  <div class="card"><div class="card-corpo card-zero tabela-rolagem">
    <table class="tabela">
      <thead><tr>
        <th>Nome</th><th>Link público</th><th>Admin</th><th>Plano</th><th>Status</th>
        <th class="direita">Alunos</th><th class="direita">Mensal</th><th>Criada em</th><th class="direita">Ações</th>
      </tr></thead>
      <tbody>
      <?php foreach ($academias as $a): ?>
        <tr>
          <td class="forte"><?= e($a['name']) ?></td>
          <td class="pequeno">
            <?php if ($a['slug']): ?>
              <a href="<?= e(base_url('agendar/' . $a['slug'])) ?>" target="_blank" rel="noopener">/agendar/<?= e($a['slug']) ?></a>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td class="pequeno suave"><?= e($a['admin_email'] ?: $a['owner_email'] ?: '—') ?></td>
          <td><span class="badge badge-contorno"><?= e(rotulo($a['plano'])) ?></span></td>
          <td><span class="badge <?= $badge[$a['status']] ?? 'badge' ?>"><?= e(rotulo($a['status'])) ?></span></td>
          <td class="direita"><?= (int) $a['alunos'] ?></td>
          <td class="direita forte"><?= e(brl($a['valor_mensal'])) ?></td>
          <td class="pequeno suave"><?= e(data_br($a['created_at'])) ?></td>
          <td class="direita" style="white-space:nowrap">
            <form method="post" action="<?= e(base_url('master/academias/resetar')) ?>" style="display:inline"
                  data-confirmar="Gerar uma nova senha para o admin de <?= e($a['name']) ?>? A senha atual deixa de funcionar.">
              <?= csrf_campo() ?>
              <input type="hidden" name="academy_id" value="<?= e($a['id']) ?>">
              <button class="btn btn-fantasma btn-pq" title="Resetar senha do admin"><?= icone('chave', 13) ?></button>
            </form>

            <?php if ($a['status'] === 'blocked'): ?>
              <form method="post" action="<?= e(base_url('master/academias/status')) ?>" style="display:inline">
                <?= csrf_campo() ?>
                <input type="hidden" name="academy_id" value="<?= e($a['id']) ?>">
                <input type="hidden" name="status" value="active">
                <button class="btn btn-verde btn-pq" title="Reativar"><?= icone('play', 13) ?> Reativar</button>
              </form>
            <?php else: ?>
              <form method="post" action="<?= e(base_url('master/academias/status')) ?>" style="display:inline"
                    data-confirmar="Suspender <?= e($a['name']) ?>? O acesso do time e o link público ficam bloqueados.">
                <?= csrf_campo() ?>
                <input type="hidden" name="academy_id" value="<?= e($a['id']) ?>">
                <input type="hidden" name="status" value="blocked">
                <button class="btn btn-fantasma btn-pq" style="color:var(--vermelho)" title="Suspender"><?= icone('pausar', 13) ?></button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
<?php endif; ?>
