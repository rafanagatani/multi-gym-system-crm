<?php
/** Listagem e cadastro de alunos. */
require_once BASE_PATH . '/app/views/layout/icones.php';
?>

<div class="cabecalho-pagina">
  <div>
    <h1>Alunos</h1>
    <p><?= count($alunos) ?> aluno(s) encontrado(s)</p>
  </div>
  <button type="button" class="btn btn-primario" data-abrir="modal-aluno"><?= icone('mais', 16) ?> Novo aluno</button>
</div>

<!-- ------------------------------------------------ filtros -->
<div class="card mb-4"><div class="card-corpo-sm">
  <form method="get" action="<?= e(base_url('painel/alunos')) ?>" class="flex gap-3" style="flex-wrap:wrap;align-items:flex-end">
    <div class="g-1" style="min-width:210px">
      <label class="minusculo suave">Buscar</label>
      <input type="search" name="q" value="<?= e($filtros['busca']) ?>" placeholder="Nome, CPF ou e-mail…">
    </div>
    <div style="min-width:150px">
      <label class="minusculo suave">Plano</label>
      <select name="plano">
        <option value="">Todos os planos</option>
        <?php foreach ($nomesPlano as $np): ?>
          <option value="<?= e($np) ?>"<?= $filtros['plano'] === $np ? ' selected' : '' ?>><?= e($np) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="min-width:140px">
      <label class="minusculo suave">Status</label>
      <select name="status">
        <option value="">Todos</option>
        <?php foreach (['active', 'trial', 'inactive', 'blocked'] as $s): ?>
          <option value="<?= $s ?>"<?= $filtros['status'] === $s ? ' selected' : '' ?>><?= e(rotulo($s)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="min-width:150px">
      <label class="minusculo suave">Pagamento</label>
      <select name="pagamento">
        <option value="">Todos</option>
        <?php foreach (['em_dia', 'pendente', 'atrasado'] as $s): ?>
          <option value="<?= $s ?>"<?= $filtros['pgto'] === $s ? ' selected' : '' ?>><?= e(rotulo($s)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn btn-contorno"><?= icone('busca', 15) ?> Filtrar</button>
    <a class="btn btn-fantasma" href="<?= e(base_url('painel/alunos')) ?>">Limpar</a>
  </form>
</div></div>

<!-- ------------------------------------------------ lista -->
<?php if (!$alunos): ?>
  <div class="card"><p class="vazio">Nenhum aluno encontrado. Cadastre o primeiro!</p></div>
<?php else: ?>
  <div class="grid grid-3">
    <?php foreach ($alunos as $a):
      $atrasado = $a['payment_status'] === 'atrasado';
      $campos = [
        'id' => $a['id'], 'name' => $a['name'], 'email' => $a['email'], 'phone' => $a['phone'],
        'cpf' => $a['cpf'], 'birth_date' => $a['birth_date'], 'gender' => $a['gender'],
        'address' => $a['address'], 'emergency_contact' => $a['emergency_contact'],
        'medical_notes' => $a['medical_notes'], 'objetivo' => $a['objetivo'],
        'plan_id' => $a['plan_id'], 'plan_start' => $a['plan_start'], 'plan_end' => $a['plan_end'],
        'payment_status' => $a['payment_status'], 'status' => $a['status'],
      ];
    ?>
      <div class="card card-hover<?= $atrasado ? ' card-atencao' : '' ?>">
        <div class="card-corpo-sm">
          <div class="flex gap-3 itens-centro">
            <div class="avatar"><?= e(iniciais($a['name'])) ?></div>
            <div class="g-1" style="min-width:0">
              <div class="forte truncar"><?= e($a['name']) ?></div>
              <div class="pequeno suave truncar"><?= e($a['plan_name'] ?: 'Sem plano') ?></div>
              <div class="flex gap-1 mt-1" style="flex-wrap:wrap">
                <span class="badge <?= $a['status'] === 'active' ? 'badge-verde' : ($a['status'] === 'trial' ? 'badge-azul' : 'badge') ?>"><?= e(rotulo($a['status'])) ?></span>
                <?php if ($atrasado): ?><span class="badge badge-vermelho"><?= icone('alerta', 11) ?> Atrasado</span><?php endif; ?>
              </div>
            </div>
          </div>

          <div class="pequeno suave mt-3" style="display:grid;gap:2px">
            <?php if ($a['phone']): ?><div><?= e($a['phone']) ?></div><?php endif; ?>
            <?php if ($a['plan_end']): ?><div>Vence em <?= e(data_br($a['plan_end'])) ?></div><?php endif; ?>
          </div>

          <div class="flex gap-2 mt-3">
            <button type="button" class="btn btn-contorno btn-pq g-1"
                    data-editar="modal-aluno" data-titulo="Editar aluno"
                    data-campos='<?= e(json_encode($campos, JSON_UNESCAPED_UNICODE)) ?>'>Editar</button>
            <?php if ($a['phone']): ?>
              <a class="btn btn-verde btn-pq" target="_blank" rel="noopener"
                 href="https://wa.me/55<?= e(so_digitos($a['phone'])) ?>" title="Falar no WhatsApp"><?= icone('whatsapp', 13) ?></a>
            <?php endif; ?>
            <?php if (Auth::temPapel(['admin']) || Auth::superAdmin()): ?>
              <form method="post" action="<?= e(base_url('painel/alunos/excluir')) ?>"
                    data-confirmar="Excluir <?= e($a['name']) ?>? Esta ação não pode ser desfeita.">
                <?= csrf_campo() ?>
                <input type="hidden" name="id" value="<?= e($a['id']) ?>">
                <button class="btn btn-fantasma btn-pq" title="Excluir"><?= icone('lixeira', 13) ?></button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- ------------------------------------------------ modal -->
<div class="modal" id="modal-aluno">
  <div class="modal-fundo" data-fechar></div>
  <div class="modal-caixa modal-lg">
    <div class="modal-topo">
      <h3 data-titulo>Novo aluno</h3>
      <button type="button" class="modal-fechar" data-fechar aria-label="Fechar">&times;</button>
    </div>
    <div class="modal-corpo">
      <form method="post" action="<?= e(base_url('painel/alunos/salvar')) ?>">
        <?= csrf_campo() ?>
        <input type="hidden" name="id" value="">

        <div class="secao-titulo">Dados pessoais</div>
        <div class="grid grid-2" style="gap:12px">
          <label class="col-2">Nome completo *<input type="text" name="name" required></label>
          <label>E-mail<input type="email" name="email"></label>
          <label>WhatsApp<input type="text" name="phone" placeholder="(11) 91234-5678"></label>
          <label>CPF<input type="text" name="cpf"></label>
          <label>Nascimento<input type="date" name="birth_date"></label>
          <label>Gênero
            <select name="gender">
              <option value="">—</option><option value="M">Masculino</option>
              <option value="F">Feminino</option><option value="Outro">Outro</option>
            </select>
          </label>
          <label>Objetivo
            <select name="objetivo">
              <option value="">—</option>
              <?php foreach (['Emagrecimento', 'Hipertrofia', 'Saúde', 'Condicionamento', 'Reabilitação'] as $g): ?>
                <option value="<?= e($g) ?>"><?= e($g) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="col-2">Endereço<input type="text" name="address"></label>
        </div>

        <div class="secao-titulo">Plano e situação</div>
        <div class="grid grid-2" style="gap:12px">
          <label class="col-2">Plano
            <select name="plan_id">
              <option value="">Sem plano</option>
              <?php foreach ($planos as $p): ?>
                <option value="<?= e($p['id']) ?>"><?= e($p['name']) ?> — <?= e(brl($p['price'])) ?> / <?= (int) $p['duration_months'] ?>m</option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>Início do plano<input type="date" name="plan_start"></label>
          <label>Vencimento<input type="date" name="plan_end"></label>
          <label>Status
            <select name="status">
              <option value="active">Ativo</option><option value="trial">Trial</option>
              <option value="inactive">Inativo</option><option value="blocked">Bloqueado</option>
            </select>
          </label>
          <label>Pagamento
            <select name="payment_status">
              <option value="em_dia">Em dia</option><option value="pendente">Pendente</option>
              <option value="atrasado">Atrasado</option>
            </select>
          </label>
        </div>

        <div class="secao-titulo">Saúde e emergência</div>
        <div class="grid grid-2" style="gap:12px">
          <label class="col-2">Contato de emergência<input type="text" name="emergency_contact" placeholder="(11) 90000-0000 — Familiar"></label>
          <label class="col-2">Observações médicas<textarea name="medical_notes" rows="3" placeholder="Lesões, restrições, medicações…"></textarea></label>
        </div>

        <button type="submit" class="btn btn-primario btn-bloco">Salvar aluno</button>
      </form>
    </div>
  </div>
</div>
