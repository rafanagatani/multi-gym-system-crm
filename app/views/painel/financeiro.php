<?php
/** Financeiro: receitas, despesas e inadimplencia. */
require_once BASE_PATH . '/app/views/layout/icones.php';

$receitas = array_values(array_filter($lancamentos, static fn($l) => $l['type'] === 'receita'));
$despesas = array_values(array_filter($lancamentos, static fn($l) => $l['type'] === 'despesa'));
$inad     = array_values(array_filter($lancamentos, static fn($l) => $l['type'] === 'receita' && in_array($l['status'], ['pendente', 'atrasado'], true)));

/** Tabela de lancamentos. */
$tabela = static function (array $linhas, bool $comCobranca, string $mes): void {
    if (!$linhas) {
        echo '<div class="card"><p class="vazio">Nenhum lançamento neste período.</p></div>';
        return;
    }
    ?>
    <div class="card"><div class="card-corpo card-zero tabela-rolagem">
      <table class="tabela">
        <thead><tr>
          <th>Data</th><th>Categoria</th><th>Descrição</th><th>Aluno</th>
          <th class="direita">Valor</th><th>Status</th><th></th>
        </tr></thead>
        <tbody>
        <?php foreach ($linhas as $l):
          $campos = [
            'id' => $l['id'], 'type' => $l['type'], 'category' => $l['category'],
            'description' => $l['description'], 'amount' => $l['amount'], 'date' => $l['date'],
            'due_date' => $l['due_date'], 'payment_method' => $l['payment_method'],
            'status' => $l['status'], 'student_id' => $l['student_id'],
          ];
          $badge = ['pago' => 'badge-verde', 'pendente' => 'badge-ambar', 'atrasado' => 'badge-vermelho', 'cancelado' => 'badge'][$l['status']] ?? 'badge';
        ?>
          <tr>
            <td class="pequeno"><?= e(data_br($l['date'])) ?></td>
            <td><?= e($l['category'] ?: '—') ?></td>
            <td><?= e($l['description'] ?: '—') ?></td>
            <td class="pequeno suave"><?= e($l['student_name'] ?: '—') ?></td>
            <td class="direita forte <?= $l['type'] === 'receita' ? 'verde' : 'vermelho' ?>">
              <?= $l['type'] === 'receita' ? '' : '- ' ?><?= e(brl($l['amount'])) ?>
            </td>
            <td><span class="badge <?= $badge ?>"><?= e(rotulo($l['status'])) ?></span></td>
            <td class="direita" style="white-space:nowrap">
              <?php if ($comCobranca && $l['status'] !== 'pago'): ?>
                <form method="post" action="<?= e(base_url('painel/financeiro/baixar')) ?>" style="display:inline">
                  <?= csrf_campo() ?>
                  <input type="hidden" name="id" value="<?= e($l['id']) ?>">
                  <input type="hidden" name="mes" value="<?= e($mes) ?>">
                  <button class="btn btn-verde btn-pq">Dar baixa</button>
                </form>
              <?php endif; ?>
              <button type="button" class="btn btn-fantasma btn-pq"
                      data-editar="modal-lancamento" data-titulo="Editar lançamento"
                      data-campos='<?= e(json_encode($campos, JSON_UNESCAPED_UNICODE)) ?>'>Editar</button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div></div>
    <?php
};
?>

<div class="cabecalho-pagina">
  <div>
    <h1>Financeiro</h1>
    <p>Receitas, despesas e inadimplência do período.</p>
  </div>
  <div class="flex gap-2 itens-centro">
    <form method="get" action="<?= e(base_url('painel/financeiro')) ?>">
      <input type="month" name="mes" value="<?= e($mes) ?>" onchange="this.form.submit()" style="margin-top:0">
    </form>
    <button type="button" class="btn btn-primario" data-abrir="modal-lancamento"><?= icone('mais', 16) ?> Novo lançamento</button>
  </div>
</div>

<div class="grid grid-4 mb-6">
  <div class="kpi">
    <div class="kpi-topo"><span class="kpi-rotulo">Receita prevista</span><span class="kpi-icone"><?= icone('carteira', 18) ?></span></div>
    <div class="kpi-valor"><?= e(brl($kpi['prevista'])) ?></div>
  </div>
  <div class="kpi">
    <div class="kpi-topo"><span class="kpi-rotulo">Receita recebida</span><span class="kpi-icone verde"><?= icone('subindo', 18) ?></span></div>
    <div class="kpi-valor verde"><?= e(brl($kpi['recebida'])) ?></div>
  </div>
  <div class="kpi<?= $kpi['inadimplencia'] > 0 ? ' kpi-alerta' : '' ?>">
    <div class="kpi-topo"><span class="kpi-rotulo">Inadimplência</span><span class="kpi-icone"><?= icone('alerta', 18) ?></span></div>
    <div class="kpi-valor"><?= e(brl($kpi['inadimplencia'])) ?></div>
  </div>
  <div class="kpi">
    <div class="kpi-topo">
      <span class="kpi-rotulo">Resultado do mês</span>
      <span class="kpi-icone <?= $kpi['lucro'] >= 0 ? 'verde' : 'vermelho' ?>"><?= icone($kpi['lucro'] >= 0 ? 'subindo' : 'descendo', 18) ?></span>
    </div>
    <div class="kpi-valor <?= $kpi['lucro'] >= 0 ? 'verde' : 'vermelho' ?>"><?= e(brl($kpi['lucro'])) ?></div>
    <div class="kpi-nota">Recebido − despesas</div>
  </div>
</div>

<div class="abas">
  <button class="aba ativa" data-aba="receitas">Receitas (<?= count($receitas) ?>)</button>
  <button class="aba" data-aba="despesas">Despesas (<?= count($despesas) ?>)</button>
  <button class="aba" data-aba="inad">Inadimplência (<?= count($inad) ?>)</button>
</div>

<div class="painel-aba ativa" data-painel="receitas"><?php $tabela($receitas, true, $mes); ?></div>
<div class="painel-aba" data-painel="despesas"><?php $tabela($despesas, false, $mes); ?></div>
<div class="painel-aba" data-painel="inad"><?php $tabela($inad, true, $mes); ?></div>

<div class="modal" id="modal-lancamento">
  <div class="modal-fundo" data-fechar></div>
  <div class="modal-caixa">
    <div class="modal-topo">
      <h3 data-titulo>Novo lançamento</h3>
      <button type="button" class="modal-fechar" data-fechar aria-label="Fechar">&times;</button>
    </div>
    <div class="modal-corpo">
      <form method="post" action="<?= e(base_url('painel/financeiro/salvar')) ?>">
        <?= csrf_campo() ?>
        <input type="hidden" name="id" value="">

        <div class="grid grid-2" style="gap:12px">
          <label>Tipo *
            <select name="type" required><option value="receita">Receita</option><option value="despesa">Despesa</option></select>
          </label>
          <label>Status *
            <select name="status" required>
              <option value="pendente">Pendente</option><option value="pago">Pago</option>
              <option value="atrasado">Atrasado</option><option value="cancelado">Cancelado</option>
            </select>
          </label>
          <label>Categoria
            <input type="text" name="category" list="categorias" placeholder="Mensalidade, Aluguel…">
            <datalist id="categorias">
              <option>Mensalidade</option><option>Matrícula</option><option>Avaliação física</option>
              <option>Produtos</option><option>Aluguel</option><option>Energia</option>
              <option>Água</option><option>Salários</option><option>Marketing</option>
              <option>Manutenção</option><option>Limpeza</option>
            </datalist>
          </label>
          <label>Valor (R$) *<input type="number" name="amount" step="0.01" min="0.01" required></label>
          <label>Data *<input type="date" name="date" required value="<?= e(date('Y-m-d')) ?>"></label>
          <label>Vencimento<input type="date" name="due_date"></label>
          <label class="col-2">Descrição<input type="text" name="description"></label>
          <label class="col-2">Aluno (para mensalidades)
            <select name="student_id">
              <option value="">—</option>
              <?php foreach ($alunos as $a): ?>
                <option value="<?= e($a['id']) ?>"><?= e($a['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="col-2">Forma de pagamento
            <select name="payment_method">
              <option value="">—</option>
              <?php foreach (['PIX', 'Dinheiro', 'Cartão de crédito', 'Cartão de débito', 'Boleto', 'Transferência'] as $fp): ?>
                <option value="<?= e($fp) ?>"><?= e($fp) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>

        <button type="submit" class="btn btn-primario btn-bloco">Salvar lançamento</button>
      </form>
    </div>
  </div>
</div>
