<?php
/** Dashboard da academia. */
require_once BASE_PATH . '/app/views/layout/icones.php';

$grHora = [
    'rotulos' => array_map(static fn($h) => $h . 'h', array_keys($porHora)),
    'series'  => [['nome' => 'Check-ins', 'cor' => '#D97634', 'valores' => array_values($porHora)]],
];
$grPlano = [
    'rotulos' => array_column($porPlano, 'plano'),
    'series'  => [['nome' => 'Alunos', 'cor' => '#E8935D', 'valores' => array_map('intval', array_column($porPlano, 'n'))]],
];
$grEvol = [
    'rotulos' => array_column($evolucao, 'mes'),
    'series'  => [['nome' => 'Alunos', 'cor' => '#D97634', 'valores' => array_column($evolucao, 'n')]],
];
?>

<div class="cabecalho-pagina">
  <div>
    <h1>Dashboard</h1>
    <p>Bem-vindo<?= $academia ? ' à ' . e($academia['name']) : '' ?>.</p>
  </div>
  <a class="btn btn-primario" href="<?= e(base_url('painel/checkins')) ?>"><?= icone('checkin', 16) ?> Terminal de check-in</a>
</div>

<?php if (!empty($academia['slug'])): ?>
  <div class="mb-6"><?php $slug = $academia['slug']; require BASE_PATH . '/app/views/layout/_link_publico.php'; ?></div>
<?php endif; ?>

<!-- ------------------------------------------------ KPIs -->
<div class="grid grid-4">
  <div class="kpi">
    <div class="kpi-topo"><span class="kpi-rotulo">Check-ins hoje</span><span class="kpi-icone"><?= icone('checkin', 18) ?></span></div>
    <div class="kpi-valor"><?= (int) $kpi['checkins_hoje'] ?></div>
    <div class="kpi-nota">Movimentação do dia</div>
  </div>
  <div class="kpi">
    <div class="kpi-topo"><span class="kpi-rotulo">Check-ins na semana</span><span class="kpi-icone"><?= icone('calendario', 18) ?></span></div>
    <div class="kpi-valor"><?= (int) $kpi['checkins_sem'] ?></div>
    <div class="kpi-nota">Últimos 7 dias</div>
  </div>
  <div class="kpi">
    <div class="kpi-topo"><span class="kpi-rotulo">Alunos ativos</span><span class="kpi-icone"><?= icone('alunos', 18) ?></span></div>
    <div class="kpi-valor"><?= (int) $kpi['alunos_ativos'] ?></div>
  </div>
  <div class="kpi">
    <div class="kpi-topo"><span class="kpi-rotulo">Novos cadastros (mês)</span><span class="kpi-icone"><?= icone('usuario+', 18) ?></span></div>
    <div class="kpi-valor"><?= (int) $kpi['novos_mes'] ?></div>
  </div>
</div>

<div class="grid grid-4 mt-4">
  <div class="kpi">
    <div class="kpi-topo"><span class="kpi-rotulo">Faturamento (mês)</span><span class="kpi-icone"><?= icone('carteira', 18) ?></span></div>
    <div class="kpi-valor"><?= e(brl($kpi['faturamento'])) ?></div>
    <div class="kpi-nota">Recebido neste mês</div>
  </div>
  <div class="kpi<?= $kpi['atrasados'] ? ' kpi-alerta' : '' ?>">
    <div class="kpi-topo"><span class="kpi-rotulo">Mensalidades atrasadas</span><span class="kpi-icone"><?= icone('alerta', 18) ?></span></div>
    <div class="kpi-valor"><?= (int) $kpi['atrasados'] ?></div>
    <?php if ($kpi['atrasados']): ?>
      <div class="kpi-nota"><a href="<?= e(base_url('painel/financeiro')) ?>">Cobrar inadimplentes</a></div>
    <?php endif; ?>
  </div>
  <div class="kpi">
    <div class="kpi-topo"><span class="kpi-rotulo">Aulas de hoje</span><span class="kpi-icone"><?= icone('agenda', 18) ?></span></div>
    <div class="kpi-valor"><?= count($aulasHoje) ?></div>
    <div class="kpi-nota"><?= e(dia_semana((int) date('w'))) ?></div>
  </div>
  <div class="kpi kpi-ia">
    <div class="kpi-topo"><span class="kpi-rotulo">Alertas da IA</span><span class="kpi-icone" style="color:var(--laranja)"><?= icone('ia', 18) ?></span></div>
    <div class="kpi-valor"><?= (int) $totalOport ?></div>
    <div class="kpi-nota"><a href="<?= e(base_url('painel/ai-growth')) ?>">Ver oportunidades</a></div>
  </div>
</div>

<!-- ------------------------------------------------ gráficos -->
<div class="grid grid-2 mt-6">
  <div class="card"><div class="card-corpo">
    <h3 class="mb-3" style="font-size:15px">Check-ins por hora (hoje)</h3>
    <div class="gr-caixa"><canvas class="grafico" data-tipo="barra" data-dados='<?= e(json_encode($grHora)) ?>'></canvas></div>
  </div></div>

  <div class="card"><div class="card-corpo">
    <h3 class="mb-3" style="font-size:15px">Alunos por plano</h3>
    <div class="gr-caixa"><canvas class="grafico" data-tipo="barra" data-dados='<?= e(json_encode($grPlano)) ?>'></canvas></div>
  </div></div>
</div>

<div class="card mt-4"><div class="card-corpo">
  <h3 class="mb-3" style="font-size:15px">Evolução da base de alunos (12 meses)</h3>
  <div class="gr-caixa"><canvas class="grafico" data-tipo="linha" data-dados='<?= e(json_encode($grEvol)) ?>'></canvas></div>
</div></div>

<!-- ------------------------------------------------ aulas e IA -->
<div class="grid mt-4" style="grid-template-columns:2fr 1fr">
  <div class="card"><div class="card-corpo">
    <h3 class="mb-3" style="font-size:15px">Aulas de hoje</h3>
    <?php if (!$aulasHoje): ?>
      <p class="vazio">Nenhuma aula programada para hoje.</p>
    <?php else: ?>
      <?php foreach ($aulasHoje as $a): ?>
        <div class="linha-resultado mb-2">
          <div>
            <div class="forte"><?= e($a['name']) ?></div>
            <div class="pequeno suave">
              <?= e($a['instructor_name'] ?: 'Sem professor') ?> ·
              <?= e(hora_curta($a['start_time'])) ?>–<?= e(hora_curta($a['end_time'])) ?>
            </div>
          </div>
          <div class="direita">
            <div class="forte"><?= (int) $a['inscritos'] ?>/<?= (int) $a['max_capacity'] ?></div>
            <div class="pequeno suave">presenças</div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div></div>

  <div class="card card-ia"><div class="card-corpo">
    <div class="flex itens-centro gap-2 mb-3" style="color:var(--laranja)">
      <?= icone('ia', 16) ?><h3 style="font-size:15px;color:var(--texto)">Alertas da IA</h3>
    </div>
    <?php if (!$oportunidades): ?>
      <p class="suave pequeno">Nenhuma oportunidade detectada. Sua base está em dia.</p>
    <?php else: ?>
      <?php foreach ($oportunidades as $o): ?>
        <div class="barra-ia">
          <div class="forte pequeno"><?= e($o['titulo']) ?></div>
          <div class="minusculo suave mt-1"><?= e($o['impacto']) ?></div>
        </div>
      <?php endforeach; ?>
      <a class="btn btn-contorno btn-pq" href="<?= e(base_url('painel/ai-growth')) ?>">Ver todas</a>
    <?php endif; ?>
  </div></div>
</div>
