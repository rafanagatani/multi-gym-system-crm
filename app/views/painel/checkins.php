<?php
/** Terminal de check-in. */
require_once BASE_PATH . '/app/views/layout/icones.php';
?>

<div class="grid" style="grid-template-columns:2fr 1fr">

  <div>
    <div class="card mb-4"><div class="card-corpo centro" style="padding:30px">
      <div class="kpi-rotulo">Check-ins hoje</div>
      <div class="contador-grande"><?= (int) $total ?></div>
      <div class="pequeno suave mt-2"><?= e(dia_semana((int) date('w'))) ?>, <?= e(date('d/m/Y')) ?></div>
    </div></div>

    <div class="card"><div class="card-corpo">
      <label for="busca-aluno">Buscar aluno (nome ou CPF) — <kbd>F2</kbd></label>
      <input type="search" id="busca-aluno" class="busca-gg" autocomplete="off"
             placeholder="Digite o nome ou CPF e pressione Enter…"
             data-url="<?= e(base_url('painel/checkins/buscar')) ?>">

      <div class="flex gap-3 mt-2 minusculo suave" style="flex-wrap:wrap">
        <span><kbd>F2</kbd> focar busca</span>
        <span><kbd>Enter</kbd> confirmar o primeiro</span>
        <span><kbd>Esc</kbd> limpar</span>
        <span><kbd>F5</kbd> atualizar lista</span>
      </div>

      <div class="lista-resultado" id="resultado-busca"></div>
    </div></div>

    <form method="post" action="<?= e(base_url('painel/checkins/registrar')) ?>" id="form-checkin" class="oculto">
      <?= csrf_campo() ?>
      <input type="hidden" name="student_id"><input type="hidden" name="student_name">
    </form>
  </div>

  <div class="card" style="align-self:start"><div class="card-corpo">
    <h3 class="mb-3" style="font-size:15px">Últimos check-ins</h3>
    <?php if (!$recentes): ?>
      <p class="suave pequeno">Nenhum check-in registrado ainda.</p>
    <?php else: ?>
      <?php foreach ($recentes as $c): ?>
        <div class="linha-recente">
          <span class="forte truncar"><?= e($c['student_name']) ?></span>
          <span class="suave" style="flex:none;margin-left:10px">
            <?= $c['date'] === date('Y-m-d') ? '' : e(data_br($c['date'], 'd/m')) . ' ' ?><?= e(hora_curta($c['time'])) ?>
          </span>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div></div>

</div>
