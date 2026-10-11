<?php
/** Grade semanal de aulas com arrastar e soltar. */
require_once BASE_PATH . '/app/views/layout/icones.php';

$dias  = [1, 2, 3, 4, 5, 6];
$horas = range(6, 22);
?>

<div class="cabecalho-pagina">
  <div>
    <h1>Agenda</h1>
    <p>Arraste uma aula para outro dia ou horário. Clique para editar.</p>
  </div>
  <button type="button" class="btn btn-primario" data-abrir="modal-aula"><?= icone('mais', 16) ?> Nova aula</button>
</div>

<div class="card"><div class="card-corpo-sm tabela-rolagem">
  <div class="agenda-grade" id="agenda-grade">
    <div></div>
    <?php foreach ($dias as $d): ?>
      <div class="agenda-cab"><?= e(dia_semana($d)) ?></div>
    <?php endforeach; ?>

    <?php foreach ($horas as $h): ?>
      <div class="agenda-hora"><?= $h ?>h</div>
      <?php foreach ($dias as $d): ?>
        <div class="agenda-celula" data-dia="<?= $d ?>" data-hora="<?= $h ?>">
          <?php foreach ($grade[$d . ':' . $h] ?? [] as $a):
            $campos = [
              'id' => $a['id'], 'name' => $a['name'], 'modality' => $a['modality'],
              'day_of_week' => $a['day_of_week'], 'start_time' => hora_curta($a['start_time']),
              'end_time' => hora_curta($a['end_time']), 'instructor_id' => $a['instructor_id'],
              'max_capacity' => $a['max_capacity'],
            ];
          ?>
            <div class="agenda-aula mod-<?= e($a['modality'] ?: 'Outro') ?>"
                 data-id="<?= e($a['id']) ?>"
                 data-editar="modal-aula" data-titulo="Editar aula"
                 data-campos='<?= e(json_encode($campos, JSON_UNESCAPED_UNICODE)) ?>'
                 title="<?= e($a['name']) ?> — <?= e(hora_curta($a['start_time'])) ?> às <?= e(hora_curta($a['end_time'])) ?>">
              <div class="nome"><?= e($a['name']) ?></div>
              <div class="meta"><?= e(hora_curta($a['start_time'])) ?>–<?= e(hora_curta($a['end_time'])) ?></div>
              <?php if ($a['instructor_name']): ?>
                <div class="meta truncar"><?= e($a['instructor_name']) ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </div>
</div></div>

<?php if (!$aulas): ?>
  <p class="suave pequeno mt-3">Nenhuma aula cadastrada ainda. Clique em “Nova aula” para montar sua grade.</p>
<?php endif; ?>

<!-- formulário oculto usado pelo arrastar e soltar -->
<form method="post" action="<?= e(base_url('painel/agenda/mover')) ?>" id="form-mover" class="oculto">
  <?= csrf_campo() ?>
  <input type="hidden" name="id"><input type="hidden" name="dia"><input type="hidden" name="hora">
</form>

<div class="modal" id="modal-aula">
  <div class="modal-fundo" data-fechar></div>
  <div class="modal-caixa">
    <div class="modal-topo">
      <h3 data-titulo>Nova aula</h3>
      <button type="button" class="modal-fechar" data-fechar aria-label="Fechar">&times;</button>
    </div>
    <div class="modal-corpo">
      <form method="post" action="<?= e(base_url('painel/agenda/salvar')) ?>">
        <?= csrf_campo() ?>
        <input type="hidden" name="id" value="">

        <label>Nome da aula *<input type="text" name="name" required placeholder="Funcional manhã"></label>

        <div class="grid grid-2 mt-3" style="gap:12px">
          <label>Modalidade *
            <select name="modality" required>
              <?php foreach (AgendaController::MODALIDADES as $m): ?>
                <option value="<?= e($m) ?>"><?= e($m) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>Dia da semana *
            <select name="day_of_week" required>
              <?php foreach ([1, 2, 3, 4, 5, 6, 0] as $d): ?>
                <option value="<?= $d ?>"><?= e(dia_semana($d)) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>Hora de início *<input type="time" name="start_time" required value="07:00"></label>
          <label>Hora de término *<input type="time" name="end_time" required value="08:00"></label>
          <label>Professor
            <select name="instructor_id">
              <option value="">Sem professor</option>
              <?php foreach ($professores as $p): ?>
                <option value="<?= e($p['id']) ?>"><?= e($p['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>Capacidade *<input type="number" name="max_capacity" min="1" required value="20"></label>
        </div>

        <button type="submit" class="btn btn-primario btn-bloco">Salvar aula</button>
      </form>

      <form method="post" action="<?= e(base_url('painel/agenda/excluir')) ?>" class="mt-3"
            data-confirmar="Remover esta aula da grade?" id="form-excluir-aula">
        <?= csrf_campo() ?>
        <input type="hidden" name="id" value="">
        <button class="btn btn-fantasma" style="width:100%"><?= icone('lixeira', 14) ?> Remover aula da grade</button>
      </form>
    </div>
  </div>
</div>

<script>
// Espelha o id da aula em edicao no formulario de exclusao.
document.addEventListener('click', function (ev) {
  var btn = ev.target.closest('[data-editar="modal-aula"]');
  if (!btn) return;
  try {
    var campos = JSON.parse(btn.dataset.campos || '{}');
    var f = document.getElementById('form-excluir-aula');
    f.querySelector('[name=id]').value = campos.id || '';
    f.style.display = campos.id ? '' : 'none';
  } catch (e) { /* ignora */ }
});
document.querySelector('[data-abrir="modal-aula"]').addEventListener('click', function () {
  var f = document.getElementById('form-excluir-aula');
  f.querySelector('[name=id]').value = '';
  f.style.display = 'none';
  var form = document.querySelector('#modal-aula form');
  form.reset();
  form.querySelector('[name=id]').value = '';
});
document.getElementById('form-excluir-aula').style.display = 'none';
</script>
