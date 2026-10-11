<?php
/** Relatorios gerenciais. */
require_once BASE_PATH . '/app/views/layout/icones.php';

$grFat = [
    'rotulos' => array_column($faturamento, 'mes'),
    'series'  => [
        ['nome' => 'Receita', 'cor' => '#22C55E', 'valores' => array_column($faturamento, 'receita')],
        ['nome' => 'Despesa', 'cor' => '#EF4444', 'valores' => array_column($faturamento, 'despesa')],
    ],
];
$grRet = [
    'rotulos' => array_column($retencao, 'mes'),
    'series'  => [['nome' => 'Retenção %', 'cor' => '#D97634', 'valores' => array_column($retencao, 'pct')]],
];
$grEvol = [
    'rotulos' => array_column($evolucao, 'mes'),
    'series'  => [['nome' => 'Alunos', 'cor' => '#D97634', 'valores' => array_column($evolucao, 'n')]],
];
$grHora = [
    'rotulos' => array_map(static fn($h) => $h . 'h', array_keys($porHora)),
    'series'  => [['nome' => 'Check-ins', 'cor' => '#D97634', 'valores' => array_values($porHora)]],
];
$cores = ['#D97634', '#E8935D', '#22C55E', '#3B82F6', '#A855F7', '#EAB308', '#EC4899', '#06B6D4'];
$grMod = ['fatias' => []];
foreach ($modalidades as $i => $m) {
    $grMod['fatias'][] = ['rotulo' => $m['modalidade'], 'valor' => (int) $m['n'], 'cor' => $cores[$i % count($cores)]];
}
?>

<div class="cabecalho-pagina nao-imprimir">
  <div>
    <h1>Relatórios</h1>
    <p>Desempenho da academia com base nos dados registrados.</p>
  </div>
  <button type="button" class="btn btn-primario" onclick="window.print()"><?= icone('imprimir', 16) ?> Exportar PDF</button>
</div>

<div class="oculto" style="display:none" id="cabecalho-impressao">
  <h1><?= e($academia['name'] ?? 'Relatório') ?></h1>
  <p class="suave">Gerado em <?= e(date('d/m/Y H:i')) ?></p>
</div>

<div class="grid grid-4 mb-6">
  <div class="kpi">
    <div class="kpi-topo"><span class="kpi-rotulo">Alunos ativos</span><span class="kpi-icone"><?= icone('alunos', 18) ?></span></div>
    <div class="kpi-valor"><?= (int) $resumo['alunos_ativos'] ?></div>
  </div>
  <div class="kpi">
    <div class="kpi-topo"><span class="kpi-rotulo">Receita recorrente</span><span class="kpi-icone"><?= icone('subindo', 18) ?></span></div>
    <div class="kpi-valor"><?= e(brl($resumo['mrr'])) ?></div>
    <div class="kpi-nota">Soma dos planos ativos / mês</div>
  </div>
  <div class="kpi">
    <div class="kpi-topo"><span class="kpi-rotulo">Ticket médio</span><span class="kpi-icone"><?= icone('carteira', 18) ?></span></div>
    <div class="kpi-valor"><?= e(brl($resumo['ticket_medio'])) ?></div>
    <div class="kpi-nota">Recebimentos deste mês</div>
  </div>
  <div class="kpi">
    <div class="kpi-topo"><span class="kpi-rotulo">Check-ins no mês</span><span class="kpi-icone"><?= icone('checkin', 18) ?></span></div>
    <div class="kpi-valor"><?= (int) $resumo['checkins_mes'] ?></div>
  </div>
</div>

<div class="grid grid-2">
  <div class="card"><div class="card-corpo">
    <h3 style="font-size:15px">Faturamento dos últimos 12 meses</h3>
    <p class="pequeno suave mb-3">Receita recebida × despesas</p>
    <div class="gr-caixa gr-caixa-alta"><canvas class="grafico" data-tipo="barra" data-dados='<?= e(json_encode($grFat)) ?>'></canvas></div>
    <div class="gr-legenda">
      <span><i class="gr-cor" style="background:#22C55E"></i> Receita</span>
      <span><i class="gr-cor" style="background:#EF4444"></i> Despesa</span>
    </div>
  </div></div>

  <div class="card"><div class="card-corpo">
    <h3 style="font-size:15px">Retenção mensal</h3>
    <p class="pequeno suave mb-3">% de alunos do mês anterior que voltaram a treinar</p>
    <div class="gr-caixa gr-caixa-alta"><canvas class="grafico" data-tipo="linha" data-dados='<?= e(json_encode($grRet)) ?>'></canvas></div>
  </div></div>

  <div class="card"><div class="card-corpo">
    <h3 style="font-size:15px">Modalidades mais procuradas</h3>
    <p class="pequeno suave mb-3">Check-ins dos últimos 90 dias</p>
    <div class="gr-caixa gr-caixa-alta"><canvas class="grafico" data-tipo="pizza" data-dados='<?= e(json_encode($grMod)) ?>'></canvas></div>
    <div class="gr-legenda">
      <?php foreach ($grMod['fatias'] as $f): ?>
        <span><i class="gr-cor" style="background:<?= e($f['cor']) ?>"></i> <?= e($f['rotulo']) ?> (<?= (int) $f['valor'] ?>)</span>
      <?php endforeach; ?>
    </div>
  </div></div>

  <div class="card"><div class="card-corpo">
    <h3 style="font-size:15px">Horários mais cheios</h3>
    <p class="pequeno suave mb-3">Check-ins por hora nos últimos 30 dias</p>
    <div class="gr-caixa gr-caixa-alta"><canvas class="grafico" data-tipo="barra" data-dados='<?= e(json_encode($grHora)) ?>'></canvas></div>
  </div></div>
</div>

<div class="card mt-4"><div class="card-corpo">
  <h3 style="font-size:15px">Evolução da base de alunos</h3>
  <p class="pequeno suave mb-3">Total de alunos cadastrados ao longo dos 12 meses</p>
  <div class="gr-caixa"><canvas class="grafico" data-tipo="linha" data-dados='<?= e(json_encode($grEvol)) ?>'></canvas></div>
</div></div>

<style>@media print{#cabecalho-impressao{display:block!important;margin-bottom:18px}}</style>
