<?php
/** Painel consolidado da plataforma. */
require_once BASE_PATH . '/app/views/layout/icones.php';

$grCresc = [
    'rotulos' => array_column($crescimento, 'mes'),
    'series'  => [['nome' => 'Academias', 'cor' => '#DC2626', 'valores' => array_column($crescimento, 'n')]],
];
$badge = ['active' => 'badge-verde', 'trial' => 'badge-ambar', 'blocked' => 'badge-vermelho'];
?>

<div class="cabecalho-pagina">
  <div>
    <h1>Painel Master</h1>
    <p>Visão consolidada de todas as academias da plataforma.</p>
  </div>
  <a class="btn btn-primario" href="<?= e(base_url('master/nova-academia')) ?>"><?= icone('mais', 16) ?> Nova academia</a>
</div>

<div class="grid grid-3">
  <div class="kpi">
    <div class="kpi-topo"><span class="kpi-rotulo">Receita recorrente (MRR)</span><span class="kpi-icone"><?= icone('subindo', 18) ?></span></div>
    <div class="kpi-valor"><?= e(brl($kpi['mrr'])) ?></div>
    <div class="kpi-nota">Somatório das academias ativas</div>
  </div>
  <div class="kpi">
    <div class="kpi-topo"><span class="kpi-rotulo">Academias ativas</span><span class="kpi-icone"><?= icone('predio', 18) ?></span></div>
    <div class="kpi-valor"><?= (int) $kpi['ativas'] ?></div>
    <div class="kpi-nota">de <?= (int) $kpi['total'] ?> no total</div>
  </div>
  <div class="kpi">
    <div class="kpi-topo"><span class="kpi-rotulo">Alunos na plataforma</span><span class="kpi-icone"><?= icone('alunos', 18) ?></span></div>
    <div class="kpi-valor"><?= (int) $kpi['alunos'] ?></div>
  </div>
  <div class="kpi">
    <div class="kpi-topo"><span class="kpi-rotulo">Em trial</span><span class="kpi-icone"><?= icone('relogio', 18) ?></span></div>
    <div class="kpi-valor"><?= (int) $kpi['trial'] ?></div>
  </div>
  <div class="kpi<?= $kpi['bloqueadas'] ? ' kpi-alerta' : '' ?>">
    <div class="kpi-topo"><span class="kpi-rotulo">Suspensas</span><span class="kpi-icone"><?= icone('alerta', 18) ?></span></div>
    <div class="kpi-valor"><?= (int) $kpi['bloqueadas'] ?></div>
  </div>
  <div class="kpi">
    <div class="kpi-topo"><span class="kpi-rotulo">Ticket médio por academia</span><span class="kpi-icone"><?= icone('carteira', 18) ?></span></div>
    <div class="kpi-valor"><?= e(brl($kpi['ativas'] ? $kpi['mrr'] / $kpi['ativas'] : 0)) ?></div>
  </div>
</div>

<div class="card mt-6"><div class="card-corpo">
  <h3 class="mb-3" style="font-size:15px">Crescimento da plataforma (12 meses)</h3>
  <div class="gr-caixa"><canvas class="grafico" data-tipo="linha" data-dados='<?= e(json_encode($grCresc)) ?>'></canvas></div>
</div></div>

<h2 class="mt-8 mb-3" style="font-size:17px">Academias cadastradas</h2>

<?php if (!$academias): ?>
  <div class="card"><p class="vazio">Nenhuma academia cadastrada. Comece criando a primeira.</p></div>
<?php else: ?>
  <div class="card"><div class="card-corpo card-zero tabela-rolagem">
    <table class="tabela">
      <thead><tr>
        <th>Academia</th><th>Cidade</th><th>Plano</th><th>Status</th>
        <th class="direita">Alunos</th><th class="direita">Check-ins/mês</th>
        <th class="direita">Mensal</th><th>Último acesso</th>
      </tr></thead>
      <tbody>
      <?php foreach ($academias as $a): ?>
        <tr>
          <td class="forte"><?= e($a['name']) ?></td>
          <td class="suave pequeno"><?= e(trim(($a['endereco_cidade'] ?? '') . ($a['endereco_uf'] ? '/' . $a['endereco_uf'] : '')) ?: '—') ?></td>
          <td><span class="badge badge-contorno"><?= e(rotulo($a['plano'])) ?></span></td>
          <td><span class="badge <?= $badge[$a['status']] ?? 'badge' ?>"><?= e(rotulo($a['status'])) ?></span></td>
          <td class="direita"><?= (int) $a['alunos'] ?></td>
          <td class="direita"><?= (int) $a['checkins_mes'] ?></td>
          <td class="direita forte"><?= e(brl($a['valor_mensal'])) ?></td>
          <td class="pequeno suave"><?= e(data_br($a['ultimo_acesso'], 'd/m/Y')) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
<?php endif; ?>
