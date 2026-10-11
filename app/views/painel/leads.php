<?php
/** Leads recebidos pelo link publico. */
require_once BASE_PATH . '/app/views/layout/icones.php';

$badgeStatus = ['novo' => 'badge-azul', 'contatado' => 'badge-ambar', 'convertido' => 'badge-verde', 'perdido' => 'badge-vermelho'];
?>

<div class="cabecalho-pagina">
  <div>
    <h1>Leads</h1>
    <p>Cadastros recebidos pelo link público de matrícula<?= $novos ? ' · ' . $novos . ' novo(s)' : '' ?>.</p>
  </div>
</div>

<form method="get" action="<?= e(base_url('painel/leads')) ?>" class="flex gap-3 mb-4" style="flex-wrap:wrap">
  <select name="tipo" onchange="this.form.submit()" style="width:auto;min-width:200px;margin-top:0">
    <option value="">Todos os tipos</option>
    <?php foreach (['aula_experimental', 'trial_7dias', 'interesse_plano'] as $t): ?>
      <option value="<?= $t ?>"<?= $filtros['tipo'] === $t ? ' selected' : '' ?>><?= e(rotulo($t)) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="status" onchange="this.form.submit()" style="width:auto;min-width:170px;margin-top:0">
    <option value="">Todos os status</option>
    <?php foreach (['novo', 'contatado', 'convertido', 'perdido'] as $s): ?>
      <option value="<?= $s ?>"<?= $filtros['status'] === $s ? ' selected' : '' ?>><?= e(rotulo($s)) ?></option>
    <?php endforeach; ?>
  </select>
  <noscript><button class="btn btn-contorno">Filtrar</button></noscript>
</form>

<?php if (!$leads): ?>
  <div class="card"><p class="vazio">Nenhum lead recebido ainda. Divulgue seu link público de matrícula!</p></div>
<?php else: ?>
  <div class="card"><div class="card-corpo card-zero tabela-rolagem">
    <table class="tabela">
      <thead><tr>
        <th>Quando</th><th>Nome</th><th>WhatsApp</th><th>Interesse</th>
        <th>Detalhe</th><th>Status</th><th class="direita">Ações</th>
      </tr></thead>
      <tbody>
      <?php foreach ($leads as $l):
        $detalhe = $l['plano_nome']
            ? 'Plano ' . $l['plano_nome']
            : ($l['aula_nome']
                ? $l['aula_nome'] . ' · ' . dia_semana((int) $l['day_of_week'], true) . ' ' . hora_curta($l['start_time'])
                : ($l['modality'] ?: $l['objetivo'] ?: '—'));
      ?>
        <tr>
          <td class="pequeno suave" style="white-space:nowrap"><?= e(data_br($l['created_at'], 'd/m H:i')) ?></td>
          <td class="forte"><?= e($l['name']) ?></td>
          <td class="mono pequeno"><?= e($l['phone']) ?></td>
          <td><span class="badge badge-contorno"><?= e(rotulo($l['type'])) ?></span></td>
          <td class="pequeno"><?= e($detalhe) ?></td>
          <td><span class="badge <?= $badgeStatus[$l['status']] ?? 'badge' ?>"><?= e(rotulo($l['status'])) ?></span></td>
          <td class="direita" style="white-space:nowrap">
            <a class="btn btn-verde btn-pq" target="_blank" rel="noopener"
               href="https://wa.me/55<?= e(so_digitos($l['phone'])) ?>?text=<?= e(rawurlencode('Olá ' . explode(' ', $l['name'])[0] . '! Recebemos seu cadastro. Podemos confirmar?')) ?>"
               title="Chamar no WhatsApp"><?= icone('whatsapp', 13) ?></a>

            <?php foreach (['contatado' => 'Contatado', 'convertido' => 'Converter', 'perdido' => 'Perdido'] as $st => $rot): ?>
              <?php if ($l['status'] !== $st): ?>
                <form method="post" action="<?= e(base_url('painel/leads/status')) ?>" style="display:inline"
                      <?= $st === 'convertido' ? 'data-confirmar="Converter este lead em aluno?"' : '' ?>>
                  <?= csrf_campo() ?>
                  <input type="hidden" name="id" value="<?= e($l['id']) ?>">
                  <input type="hidden" name="status" value="<?= $st ?>">
                  <button class="btn btn-fantasma btn-pq"><?= e($rot) ?></button>
                </form>
              <?php endif; ?>
            <?php endforeach; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
<?php endif; ?>
