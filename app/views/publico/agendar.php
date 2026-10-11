<?php
/**
 * Pagina publica de matricula: /agendar/{slug}
 */
require_once BASE_PATH . '/app/views/layout/icones.php';

$cor = $academia['cor_primaria'] ?: '#D97634';
$waTelefone = so_digitos($academia['telefone'] ?? '');

$modalidades = [];
foreach ($aulas as $a) {
    if (!empty($a['modality'])) {
        $modalidades[$a['modality']] = true;
    }
}
$modalidades = array_keys($modalidades);

$endereco = trim(implode(', ', array_filter([
    trim(($academia['endereco_rua'] ?? '') . ' ' . ($academia['endereco_numero'] ?? '')),
    $academia['endereco_bairro'] ?? '',
    trim(($academia['endereco_cidade'] ?? '') . ($academia['endereco_uf'] ? '/' . $academia['endereco_uf'] : '')),
])));
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Matrícula online · <?= e($academia['name']) ?></title>
<meta name="description" content="Agende sua aula experimental grátis, comece um trial de 7 dias ou escolha seu plano na <?= e($academia['name']) ?>.">
<link rel="stylesheet" href="<?= e(base_url('assets/css/app.css')) ?>">
</head>
<body class="pub" data-cor="<?= e($cor) ?>">

<header class="pub-topo">
  <div class="logo-quadro" style="background:<?= e($cor) ?>">
    <?php if (!empty($academia['logo_url'])): ?>
      <img src="<?= e($academia['logo_url']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:9px">
    <?php else: ?>
      <?= icone('halteres', 18) ?>
    <?php endif; ?>
  </div>
  <div>
    <div class="forte" style="font-size:17px;color:#fff"><?= e($academia['name']) ?></div>
    <div class="minusculo" style="color:rgba(255,255,255,.6)">Matrícula online · Aula experimental grátis</div>
  </div>
</header>

<main class="pub-corpo">

  <?php foreach ((array) flash() as $f): ?>
    <div class="alerta <?= $f['tipo'] === 'erro' ? 'alerta-erro' : 'alerta-aviso' ?>"><?= e($f['msg']) ?></div>
  <?php endforeach; ?>

  <?php if ($enviado): ?>
    <!-- ------------------------------------------ confirmação -->
    <div class="pub-card centro" style="max-width:460px;margin:40px auto">
      <div style="color:var(--verde);display:flex;justify-content:center;margin-bottom:12px"><?= icone('check', 52) ?></div>
      <h2 style="font-size:22px">Cadastro recebido!</h2>
      <p class="suave mt-2">
        <strong><?= e($academia['name']) ?></strong> vai entrar em contato pelo WhatsApp para confirmar
        <strong><?= e($enviado) ?></strong>.
      </p>
      <?php if ($waTelefone !== ''): ?>
        <a class="btn btn-bloco" style="background:<?= e($cor) ?>;color:#fff;height:46px" target="_blank" rel="noopener"
           href="https://wa.me/55<?= e($waTelefone) ?>?text=<?= e(rawurlencode('Olá! Acabei de me inscrever em "' . $enviado . '" pelo site da ' . $academia['name'] . '. Pode confirmar?')) ?>">
          <?= icone('whatsapp', 16) ?> Falar agora no WhatsApp
        </a>
      <?php endif; ?>
      <a class="btn btn-contorno btn-bloco" href="<?= e(base_url('agendar/' . $academia['slug'])) ?>">Fazer outro cadastro</a>
    </div>

  <?php else: ?>

    <div class="pub-abas">
      <button class="pub-aba ativa" data-aba="experimental">Aula experimental</button>
      <button class="pub-aba" data-aba="trial">Trial 7 dias</button>
      <button class="pub-aba" data-aba="planos">Ver planos</button>
    </div>

    <!-- ------------------------------------------ aula experimental -->
    <div class="painel-aba ativa" data-painel="experimental">
      <div class="pub-card">
        <h2 style="font-size:20px;display:flex;align-items:center;gap:8px">
          <span style="color:<?= e($cor) ?>"><?= icone('ia', 20) ?></span> Aula experimental grátis
        </h2>
        <p class="suave pequeno">Escolha a modalidade e o horário. Sem compromisso.</p>

        <form method="post" action="<?= e(base_url('agendar/' . $academia['slug'])) ?>" id="form-experimental" class="mt-4">
          <?= csrf_campo() ?>
          <input type="hidden" name="type" value="aula_experimental">
          <input type="hidden" name="modality" value="">
          <input type="hidden" name="schedule_id" value="">
          <input type="text" name="website" class="oculto" tabindex="-1" autocomplete="off" aria-hidden="true">

          <?php if (!$modalidades): ?>
            <p class="suave">Esta academia ainda não publicou a grade de aulas. Use a aba <strong>Trial 7 dias</strong> ou fale no WhatsApp.</p>
          <?php else: ?>
            <label class="mb-2">1. Modalidade</label>
            <div class="flex gap-2" style="flex-wrap:wrap">
              <?php foreach ($modalidades as $m): ?>
                <button type="button" class="chip" data-modalidade="<?= e($m) ?>"><?= e($m) ?></button>
              <?php endforeach; ?>
            </div>

            <div id="passo-horario" class="oculto mt-4">
              <label class="mb-2">2. Dia e horário</label>
              <div class="grid grid-3" style="gap:8px">
                <?php foreach ($aulas as $a): if (empty($a['modality'])) { continue; } ?>
                  <button type="button" class="slot oculto" data-slot="<?= e($a['id']) ?>" data-mod="<?= e($a['modality']) ?>">
                    <div class="t"><?= e(dia_semana((int) $a['day_of_week'], true)) ?> · <?= e(hora_curta($a['start_time'])) ?></div>
                    <div class="minusculo suave"><?= e($a['name']) ?><?= $a['instructor_name'] ? ' · ' . e($a['instructor_name']) : '' ?></div>
                  </button>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="mt-4" style="padding-top:18px;border-top:1px solid var(--borda)">
              <label class="mb-2">3. Seus dados</label>
              <div class="grid grid-2" style="gap:12px">
                <label class="minusculo suave">Nome *<input type="text" name="name" required></label>
                <label class="minusculo suave">WhatsApp *<input type="text" name="phone" required placeholder="(11) 91234-5678"></label>
                <label class="minusculo suave">E-mail<input type="email" name="email"></label>
                <label class="minusculo suave">Idade<input type="number" name="idade" min="12" max="99"></label>
              </div>

              <label class="mt-3 mb-2">Objetivo</label>
              <div class="grid grid-3" style="gap:8px">
                <?php foreach (['Emagrecimento', 'Hipertrofia', 'Saúde'] as $g): ?>
                  <label class="radio-caixa"><input type="radio" name="objetivo" value="<?= e($g) ?>"> <?= e($g) ?></label>
                <?php endforeach; ?>
              </div>

              <button type="submit" class="btn btn-bloco" style="background:<?= e($cor) ?>;color:#fff;height:48px;font-size:15px">
                Agendar aula experimental
              </button>
            </div>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <!-- ------------------------------------------ trial -->
    <div class="painel-aba" data-painel="trial">
      <div class="pub-card">
        <h2 style="font-size:20px;display:flex;align-items:center;gap:8px">
          <span style="color:<?= e($cor) ?>"><?= icone('calendario', 20) ?></span> Trial de 7 dias grátis
        </h2>
        <p class="suave pequeno">Acesso completo por 7 dias. Sem cartão e sem fidelidade.</p>

        <form method="post" action="<?= e(base_url('agendar/' . $academia['slug'])) ?>" class="mt-4">
          <?= csrf_campo() ?>
          <input type="hidden" name="type" value="trial_7dias">
          <input type="text" name="website" class="oculto" tabindex="-1" autocomplete="off" aria-hidden="true">

          <label>Nome completo *<input type="text" name="name" required></label>
          <div class="grid grid-2 mt-3" style="gap:12px">
            <label>WhatsApp *<input type="text" name="phone" required></label>
            <label>E-mail<input type="email" name="email"></label>
          </div>
          <label class="mt-3">Idade<input type="number" name="idade" min="12" max="99"></label>

          <button type="submit" class="btn btn-bloco" style="background:<?= e($cor) ?>;color:#fff;height:48px;font-size:15px">
            Começar meu trial de 7 dias
          </button>
        </form>
      </div>
    </div>

    <!-- ------------------------------------------ planos -->
    <div class="painel-aba" data-painel="planos">
      <?php if (!$planos): ?>
        <div class="pub-card centro"><p class="suave">Nenhum plano publicado ainda. Fale com a recepção pelo WhatsApp.</p></div>
      <?php else: ?>
        <div class="grid grid-3 mt-6">
          <?php foreach ($planos as $p): ?>
            <div class="pub-card" style="margin-top:0;display:flex;flex-direction:column<?= (int) $p['featured'] ? ';box-shadow:0 0 0 2px ' . e($cor) : '' ?>">
              <?php if ((int) $p['featured']): ?>
                <div class="minusculo forte" style="color:<?= e($cor) ?>;text-transform:uppercase;letter-spacing:.1em">Mais popular</div>
              <?php endif; ?>
              <h3 style="font-size:18px;margin-top:4px"><?= e($p['name']) ?></h3>
              <div style="font-size:30px;font-weight:800;margin-top:8px">
                <?= (float) $p['price'] === 0.0 ? 'Grátis' : e(brl($p['price'])) ?>
                <span class="pequeno suave" style="font-weight:600">/ <?= (int) $p['duration_months'] ?>m</span>
              </div>
              <p class="pequeno suave mt-2 g-1"><?= e($p['description'] ?: '') ?></p>

              <form method="post" action="<?= e(base_url('agendar/' . $academia['slug'])) ?>" class="mt-3">
                <?= csrf_campo() ?>
                <input type="hidden" name="type" value="interesse_plano">
                <input type="hidden" name="plan_id" value="<?= e($p['id']) ?>">
                <input type="text" name="website" class="oculto" tabindex="-1" autocomplete="off" aria-hidden="true">
                <label class="minusculo suave">Seu nome *<input type="text" name="name" required></label>
                <label class="minusculo suave mt-2">WhatsApp *<input type="text" name="phone" required></label>
                <button type="submit" class="btn btn-bloco" style="background:<?= e($cor) ?>;color:#fff">Quero este plano</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  <?php endif; ?>

  <!-- ------------------------------------------ rodapé -->
  <div class="centro mt-8" style="color:rgba(255,255,255,.5);font-size:12px">
    <?php if ($endereco): ?><p><?= e($endereco) ?></p><?php endif; ?>
    <?php if ($academia['telefone']): ?><p><?= e($academia['telefone']) ?></p><?php endif; ?>
    <p class="mt-3"><?= e($academia['name']) ?> · <?= date('Y') ?></p>
  </div>
</main>

<script src="<?= e(base_url('assets/js/app.js')) ?>"></script>
</body>
</html>
