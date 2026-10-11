<?php
/** AI Growth: oportunidades detectadas nos dados da academia. */
require_once BASE_PATH . '/app/views/layout/icones.php';

$sevBadge  = ['alta' => 'badge-vermelho', 'media' => 'badge-ambar', 'baixa' => 'badge-azul'];
$sevRotulo = ['alta' => 'Alta', 'media' => 'Média', 'baixa' => 'Baixa'];
?>

<div class="cabecalho-pagina">
  <div>
    <h1>AI Growth</h1>
    <p>Oportunidades encontradas nos dados reais da sua academia. Cada uma já vem com a mensagem pronta.</p>
  </div>
</div>

<div class="grid grid-3 mb-6">
  <div class="kpi" style="background:linear-gradient(135deg,#D97634,#B85A1F);border:0;color:#fff">
    <div class="kpi-rotulo" style="color:rgba(255,255,255,.85)">Oportunidades ativas</div>
    <div class="kpi-valor" style="color:#fff"><?= count($oportunidades) ?></div>
    <div class="kpi-nota" style="color:rgba(255,255,255,.8)">Atualizadas a cada acesso</div>
  </div>
  <div class="kpi">
    <div class="kpi-rotulo">Receita em risco</div>
    <div class="kpi-valor vermelho"><?= e(brl($emRisco)) ?></div>
    <div class="kpi-nota">Inadimplência + inatividade</div>
  </div>
  <div class="kpi">
    <div class="kpi-rotulo">Potencial de recuperação</div>
    <div class="kpi-valor verde"><?= e(brl($reativacao)) ?></div>
    <div class="kpi-nota">Agindo nas próximas 48h</div>
  </div>
</div>

<?php if (!$oportunidades): ?>
  <div class="card"><div class="card-corpo centro" style="padding:48px">
    <div style="color:var(--verde);display:flex;justify-content:center;margin-bottom:12px"><?= icone('check', 40) ?></div>
    <h3 style="font-size:17px">Nenhuma oportunidade no momento</h3>
    <p class="suave mt-2">Sua base está em dia: ninguém sumiu, ninguém atrasou e nenhum trial está vencendo.</p>
  </div></div>
<?php else: ?>
  <div class="grid grid-2" style="align-items:start">
    <?php foreach ($oportunidades as $o): ?>
      <div class="card card-ia"><div class="card-corpo">
        <div class="flex gap-4" style="align-items:flex-start">
          <div class="ia-icone"><?= icone($o['icone'], 22) ?></div>
          <div class="g-1" style="min-width:0">
            <div class="flex entre gap-2" style="align-items:flex-start">
              <h3 style="font-size:15.5px;line-height:1.3"><?= e($o['titulo']) ?></h3>
              <span class="badge <?= $sevBadge[$o['severidade']] ?? 'badge' ?>" style="flex:none"><?= e($sevRotulo[$o['severidade']] ?? '') ?></span>
            </div>
            <p class="pequeno suave mt-2"><?= e($o['descricao']) ?></p>
            <div class="ia-impacto"><?= icone('ia', 14) ?> <?= e($o['impacto']) ?></div>

            <div class="mt-3">
              <?php foreach (array_slice($o['alvos'], 0, 6) as $alvo):
                $msg = AiGrowthController::mensagem($o, $alvo);
                $tel = so_digitos($alvo['telefone'] ?? '');
              ?>
                <div class="ia-alvo">
                  <div class="g-1" style="min-width:0">
                    <div class="forte pequeno truncar"><?= e($alvo['nome']) ?></div>
                    <div class="minusculo suave truncar"><?= e($alvo['detalhe']) ?></div>
                  </div>
                  <div class="flex gap-1" style="flex:none">
                    <button type="button" class="btn btn-fantasma btn-pq" data-copiar="<?= e($msg) ?>" title="Copiar mensagem"><?= icone('copiar', 13) ?></button>
                    <?php if ($tel !== ''): ?>
                      <a class="btn btn-verde btn-pq" target="_blank" rel="noopener"
                         href="https://wa.me/55<?= e($tel) ?>?text=<?= e(rawurlencode($msg)) ?>">
                        <?= icone('whatsapp', 13) ?> WhatsApp
                      </a>
                    <?php else: ?>
                      <span class="badge" title="Aluno sem telefone cadastrado">Sem telefone</span>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>

              <?php if (count($o['alvos']) > 6): ?>
                <p class="minusculo suave mt-2">+ <?= count($o['alvos']) - 6 ?> aluno(s) nesta mesma situação.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<p class="minusculo suave mt-4">
  As mensagens abrem direto no WhatsApp Web ou no aplicativo, já preenchidas — você revisa antes de enviar.
</p>
