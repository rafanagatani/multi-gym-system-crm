<?php
/** Planos da academia. */
require_once BASE_PATH . '/app/views/layout/icones.php';
?>

<div class="cabecalho-pagina">
  <div>
    <h1>Planos</h1>
    <p><?= count($planos) ?> plano(s) cadastrado(s)</p>
  </div>
  <button type="button" class="btn btn-primario" data-abrir="modal-plano"><?= icone('mais', 16) ?> Novo plano</button>
</div>

<?php if (!$planos): ?>
  <div class="card"><p class="vazio">Nenhum plano cadastrado. Crie o primeiro para começar a matricular alunos.</p></div>
<?php else: ?>
  <div class="grid grid-3">
    <?php foreach ($planos as $p):
      $campos = [
        'id' => $p['id'], 'name' => $p['name'], 'description' => $p['description'],
        'price' => $p['price'], 'duration_months' => $p['duration_months'],
        'included_classes' => $p['included_classes'], 'total_checkins' => $p['total_checkins'],
        'featured' => $p['featured'], 'active' => $p['active'],
      ];
      $incluidas = array_filter(array_map('trim', explode(',', (string) $p['included_classes'])));
    ?>
      <div class="card<?= (int) $p['featured'] ? ' card-destaque' : '' ?>"<?= (int) $p['active'] ? '' : ' style="opacity:.6"' ?>>
        <div class="card-corpo">
          <div class="flex entre itens-centro gap-2">
            <h3 style="font-size:17px"><?= e($p['name']) ?></h3>
            <?php if ((int) $p['featured']): ?><span class="badge badge-laranja"><?= icone('estrela', 11) ?> Destaque</span><?php endif; ?>
            <?php if (!(int) $p['active']): ?><span class="badge">Inativo</span><?php endif; ?>
          </div>

          <p class="pequeno suave mt-1" style="min-height:34px"><?= e($p['description'] ?: '') ?></p>

          <div class="flex" style="align-items:baseline;gap:5px;margin-top:12px">
            <span style="font-size:34px;font-weight:800;color:var(--laranja)"><?= e(brl($p['price'])) ?></span>
            <span class="pequeno suave">/ <?= (int) $p['duration_months'] ?>m</span>
          </div>

          <div class="mt-4 pequeno" style="display:grid;gap:7px">
            <div class="flex gap-2 itens-centro"><span class="verde"><?= icone('check', 14) ?></span>
              <?= (int) $p['duration_months'] ?> <?= (int) $p['duration_months'] === 1 ? 'mês' : 'meses' ?> de acesso</div>
            <?php foreach ($incluidas as $c): ?>
              <div class="flex gap-2 itens-centro"><span class="verde"><?= icone('check', 14) ?></span><?= e($c) ?></div>
            <?php endforeach; ?>
            <?php if ($p['total_checkins']): ?>
              <div class="flex gap-2 itens-centro"><span class="verde"><?= icone('check', 14) ?></span><?= (int) $p['total_checkins'] ?> check-ins</div>
            <?php endif; ?>
            <div class="flex gap-2 itens-centro suave"><?= icone('alunos', 14) ?><?= (int) $p['alunos'] ?> aluno(s) neste plano</div>
          </div>

          <div class="flex gap-2 mt-4">
            <button type="button" class="btn btn-contorno g-1"
                    data-editar="modal-plano" data-titulo="Editar plano"
                    data-campos='<?= e(json_encode($campos, JSON_UNESCAPED_UNICODE)) ?>'>Editar</button>
            <?php if (Auth::temPapel(['admin']) || Auth::superAdmin()): ?>
              <form method="post" action="<?= e(base_url('painel/planos/excluir')) ?>"
                    data-confirmar="Remover o plano <?= e($p['name']) ?>?">
                <?= csrf_campo() ?>
                <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                <button class="btn btn-fantasma" title="Remover"><?= icone('lixeira', 14) ?></button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="modal" id="modal-plano">
  <div class="modal-fundo" data-fechar></div>
  <div class="modal-caixa">
    <div class="modal-topo">
      <h3 data-titulo>Novo plano</h3>
      <button type="button" class="modal-fechar" data-fechar aria-label="Fechar">&times;</button>
    </div>
    <div class="modal-corpo">
      <form method="post" action="<?= e(base_url('painel/planos/salvar')) ?>">
        <?= csrf_campo() ?>
        <input type="hidden" name="id" value="">

        <label>Nome do plano *<input type="text" name="name" required placeholder="Mensal, Trimestral, Anual…"></label>
        <label class="mt-3">Descrição<input type="text" name="description" placeholder="Acesso livre à musculação, horário comercial."></label>

        <div class="grid grid-2 mt-3" style="gap:12px">
          <label>Preço (R$) *<input type="number" name="price" step="0.01" min="0" required value="0"></label>
          <label>Duração (meses) *<input type="number" name="duration_months" min="1" required value="1"></label>
        </div>

        <label class="mt-3">Modalidades incluídas
          <input type="text" name="included_classes" placeholder="Musculação, Funcional, Pilates">
        </label>
        <p class="dica">Separe por vírgula. Deixe vazio para acesso livre.</p>

        <label>Limite de check-ins no período
          <input type="number" name="total_checkins" min="1" placeholder="Deixe vazio para ilimitado">
        </label>

        <label class="linha-check"><input type="checkbox" name="featured" value="1"> Destacar este plano</label>
        <label class="linha-check"><input type="checkbox" name="active" value="1" checked> Plano ativo (aparece no link público)</label>

        <button type="submit" class="btn btn-primario btn-bloco">Salvar plano</button>
      </form>
    </div>
  </div>
</div>
