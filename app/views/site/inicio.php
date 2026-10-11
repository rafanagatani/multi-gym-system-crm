<?php
/**
 * Landing page do Academia System.
 */
require_once BASE_PATH . '/app/views/layout/icones.php';

$modulos = [
    ['alunos',     'Alunos',        'Ficha completa, histórico, frequência e situação de pagamento.'],
    ['checkin',    'Check-ins',     'Registro rápido por nome ou CPF. Fila em tempo real no balcão.'],
    ['planos',     'Planos',        'Mensal, trimestral, anual ou sem fidelidade. Você define.'],
    ['financeiro', 'Financeiro',    'Mensalidades, inadimplência e fluxo de caixa do mês.'],
    ['agenda',     'Agenda de aulas', 'Grade semanal com arrastar e soltar, turmas e capacidade.'],
    ['ia',         'AI Growth',     'Detecta quem sumiu, quem atrasou e quem vai cancelar.'],
    ['relatorios', 'Relatórios',    'Faturamento, retenção, modalidades e horários de pico.'],
    ['equipe',     'Equipe',        'Professores, recepção e acessos com permissão por função.'],
    ['config',     'Configurações', 'Marca, horários, link público de matrícula e QR Code.'],
];

$dores = [
    'Alunos somem e você só descobre quando a mensalidade não entra',
    'Inadimplência controlada em planilha — e quase ninguém cobra',
    'Check-in no caderno, sem dado nenhum de frequência real',
    'Sem visão de faturamento, churn e ticket médio: decisão no escuro',
    'Equipe sem sistema próprio, tudo no WhatsApp pessoal',
    'Renovação manual de plano — recorrência perdida todo mês',
];

$passos = [
    ['01', 'Cadastre sua academia', 'Marca, planos e horários em poucos minutos. Sem precisar de TI.'],
    ['02', 'Importe seus alunos',   'Cadastro direto ou migração da sua base atual.'],
    ['03', 'Ative o check-in',      'Busca por nome ou CPF, com atalhos de teclado no balcão.'],
    ['04', 'Deixe a IA trabalhar',  'O sistema aponta quem some, quem atrasa e o que fazer.'],
];

$bullets = [
    'Detecta queda de frequência',
    'Avisa de planos vencendo',
    'Lembra de aniversariantes',
    'Reativa inativos de +14 dias',
];

$insights = [
    ['ALTA',    'sev-alta',    'Aluno sem aparecer há 49 dias',      'Reativar em 24h vale R$ 149,90/mês'],
    ['URGENTE', 'sev-urgente', '12 planos vencendo esta semana',     'R$ 4.600 em risco — renovar agora'],
    ['ATENÇÃO', 'sev-atencao', 'Frequência caiu 60% em um aluno',    'Conversa preventiva evita o cancelamento'],
];

$planos = [
    ['Starter',    197, 'Para academias começando', false, ['Até 100 alunos', 'Check-in ilimitado', 'Financeiro completo', 'Agenda de aulas', 'Link público de matrícula']],
    ['Pro',        397, 'Mais escolhido',            true,  ['Até 500 alunos', 'AI Growth completo', 'Relatórios de retenção', 'Leads e conversão', 'Multi-modalidade']],
    ['Enterprise', 697, 'Rede de academias',         false, ['Alunos ilimitados', 'Multi-unidade', 'Painel Master consolidado', 'Marca personalizada', 'Suporte prioritário']],
];

$faq = [
    ['Preciso de cartão para testar?', 'Não. São 14 dias grátis, sem cartão e sem pegadinha. Você só continua se quiser.'],
    ['Roda em hospedagem comum?', 'Sim. O sistema é PHP + MySQL e instala em qualquer hospedagem cPanel, sem servidor dedicado.'],
    ['Consigo receber matrículas pelo site?', 'Sim. Cada academia tem um link público com aula experimental, trial e planos — com QR Code pronto para o balcão.'],
    ['Funciona em mais de uma unidade?', 'Sim. O painel Master mostra todas as academias, com faturamento e alunos consolidados.'],
    ['Meus dados ficam comigo?', 'Ficam. O banco roda na sua própria hospedagem — você tem acesso total e pode exportar quando quiser.'],
    ['Posso cancelar quando quiser?', 'Pode. Sem fidelidade e sem multa.'],
];
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($appNome) ?> — gestão completa para academias</title>
<meta name="description" content="Pare de perder alunos. Gestão de alunos, check-in, planos, financeiro, agenda e inteligência que recupera quem some — tudo num sistema só.">
<meta property="og:title" content="<?= e($appNome) ?> — gestão completa para academias">
<meta property="og:description" content="Pare de perder alunos. Reative com inteligência.">
<meta property="og:type" content="website">
<link rel="stylesheet" href="<?= e(base_url('assets/css/app.css')) ?>">
</head>
<body class="lp">

<!-- ------------------------------------------------ navegação -->
<header class="lp-nav">
  <div class="container lp-nav-in">
    <a class="lp-marca" href="<?= e(base_url('/')) ?>">
      <span class="lp-logo">AS</span><?= e($appNome) ?>
    </a>
    <nav class="lp-links">
      <a href="#modulos">Módulos</a>
      <a href="#como-funciona">Como funciona</a>
      <a href="#planos">Planos</a>
      <a href="#duvidas">Dúvidas</a>
    </nav>
    <div class="lp-acoes">
      <a class="btn btn-vidro" href="<?= e(base_url('entrar')) ?>">Entrar</a>
      <a class="btn btn-gradiente" href="<?= e(base_url('entrar')) ?>">Começar grátis</a>
    </div>
  </div>
</header>

<!-- ------------------------------------------------ hero -->
<section class="lp-hero">
  <div class="container lp-hero-in">
    <div class="lp-selo"><?= icone('ia', 13) ?> Gestão inteligente para academias</div>
    <h1 class="lp-h1">
      Pare de perder alunos.<br>
      Reative com <span class="gradiente-texto">inteligência</span>.
    </h1>
    <p class="lp-sub">
      Alunos, check-ins, planos, financeiro e agenda num sistema só — com alertas automáticos
      de quem sumiu, quem atrasou e quem está prestes a cancelar.
    </p>
    <div class="lp-cta">
      <a class="btn btn-gradiente btn-gg" href="<?= e(base_url('entrar')) ?>">Começar grátis por 14 dias <?= icone('seta', 16) ?></a>
      <a class="btn btn-vidro btn-gg" href="#modulos">Ver os módulos</a>
    </div>
    <p class="lp-nota">Sem cartão · Instala em hospedagem cPanel · Multi-unidade</p>
  </div>
</section>

<!-- ------------------------------------------------ números -->
<section class="lp-stats">
  <div class="container grid grid-4">
    <div><div class="lp-stat-n">9</div><div class="lp-stat-l">módulos integrados</div></div>
    <div><div class="lp-stat-n">5 min</div><div class="lp-stat-l">para começar a usar</div></div>
    <div><div class="lp-stat-n">100%</div><div class="lp-stat-l">dos dados na sua hospedagem</div></div>
    <div><div class="lp-stat-n">R$ 0</div><div class="lp-stat-l">de licença de servidor</div></div>
  </div>
</section>

<!-- ------------------------------------------------ módulos -->
<section class="lp-secao" id="modulos">
  <div class="container">
    <div class="lp-cabeca">
      <div class="lp-olho">9 módulos · 1 plataforma</div>
      <h2 class="lp-h2">Tudo que sua academia precisa</h2>
      <p>Sem gambiarra e sem planilha. Da entrada do aluno ao relatório de retenção, num único lugar.</p>
    </div>
    <div class="grid grid-3">
      <?php foreach ($modulos as [$ic, $tit, $desc]): ?>
        <div class="lp-modulo">
          <div class="lp-modulo-icone"><?= icone($ic, 21) ?></div>
          <h3><?= e($tit) ?></h3>
          <p><?= e($desc) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ------------------------------------------------ dores -->
<section class="lp-secao lp-secao-cinza">
  <div class="container">
    <div class="lp-cabeca">
      <div class="lp-olho" style="color:#DC2626">Problemas reais</div>
      <h2 class="lp-h2">Você já se viu nisso?</h2>
    </div>
    <div class="grid grid-3">
      <?php foreach ($dores as $dor): ?>
        <div class="lp-dor">
          <div class="lp-dor-icone"><?= icone('alerta', 19) ?></div>
          <p><?= e($dor) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ------------------------------------------------ como funciona -->
<section class="lp-secao" id="como-funciona">
  <div class="container">
    <div class="lp-cabeca">
      <div class="lp-olho">Do zero ao primeiro check-in</div>
      <h2 class="lp-h2">Como funciona</h2>
    </div>
    <div class="grid grid-4">
      <?php foreach ($passos as [$n, $t, $d]): ?>
        <div class="lp-passo">
          <div class="lp-passo-n"><?= e($n) ?></div>
          <h3><?= e($t) ?></h3>
          <p><?= e($d) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ------------------------------------------------ AI Growth -->
<section class="lp-secao lp-secao-escura">
  <div class="container grid grid-2" style="align-items:center;gap:48px">
    <div>
      <div class="lp-selo" style="margin-bottom:20px"><?= icone('ia', 13) ?> AI Growth</div>
      <h2 class="lp-h2" style="color:#fff">A inteligência que segura seu aluno</h2>
      <p style="color:rgba(255,255,255,.62);font-size:17px;margin-top:16px">
        O sistema cruza check-ins, planos e pagamentos para achar os padrões que passam
        despercebidos na correria do balcão — e já entrega a mensagem pronta para enviar.
      </p>
      <div class="grid grid-2 mt-6" style="gap:14px">
        <?php foreach ($bullets as $b): ?>
          <div class="lp-ia-bullet">
            <span class="lp-ia-check"><?= icone('check', 14) ?></span>
            <span class="forte" style="color:rgba(255,255,255,.9);font-size:13.5px"><?= e($b) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div>
      <?php foreach ($insights as [$sev, $cls, $tit, $sub]): ?>
        <div class="lp-insight">
          <span class="lp-insight-sev <?= e($cls) ?>"><?= e($sev) ?></span>
          <div class="g-1">
            <div class="forte" style="color:#fff"><?= e($tit) ?></div>
            <div class="minusculo" style="color:rgba(255,255,255,.6);margin-top:2px"><?= e($sub) ?></div>
          </div>
          <span style="color:var(--amarelo);flex:none"><?= icone('ia', 16) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ------------------------------------------------ planos -->
<section class="lp-secao lp-secao-cinza" id="planos">
  <div class="container">
    <div class="lp-cabeca">
      <div class="lp-olho">Sem pegadinha</div>
      <h2 class="lp-h2">Planos transparentes</h2>
      <p>14 dias grátis em qualquer plano. Sem cartão.</p>
    </div>
    <div class="grid grid-3" style="align-items:start">
      <?php foreach ($planos as [$nome, $preco, $desc, $destaque, $itens]): ?>
        <div class="lp-plano<?= $destaque ? ' lp-plano-top' : '' ?>">
          <?php if ($destaque): ?><div class="lp-plano-tag">Mais escolhido</div><?php endif; ?>
          <div class="lp-plano-desc"><?= e($desc) ?></div>
          <h3><?= e($nome) ?></h3>
          <div class="lp-preco">R$ <?= (int) $preco ?><small>/mês</small></div>
          <ul class="lp-lista">
            <?php foreach ($itens as $item): ?>
              <li><?= icone('check', 15) ?><span><?= e($item) ?></span></li>
            <?php endforeach; ?>
          </ul>
          <a class="btn <?= $destaque ? 'btn-gradiente' : 'btn-escuro' ?> btn-bloco" href="<?= e(base_url('entrar')) ?>">Começar grátis</a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ------------------------------------------------ dúvidas -->
<section class="lp-secao" id="duvidas">
  <div class="container" style="max-width:760px">
    <div class="lp-cabeca">
      <div class="lp-olho">Dúvidas</div>
      <h2 class="lp-h2">Perguntas frequentes</h2>
    </div>
    <?php foreach ($faq as [$p, $r]): ?>
      <details class="lp-faq">
        <summary><?= e($p) ?></summary>
        <p><?= e($r) ?></p>
      </details>
    <?php endforeach; ?>
  </div>
</section>

<!-- ------------------------------------------------ CTA final -->
<section class="lp-final">
  <div class="container" style="max-width:720px">
    <div style="display:flex;justify-content:center;margin-bottom:14px"><?= icone('raio', 44) ?></div>
    <h2 class="lp-h2">Sua academia merece mais que uma planilha</h2>
    <p>Comece grátis hoje. Em 14 dias você decide.</p>
    <div class="lp-cta">
      <a class="btn btn-gg" style="background:#fff;color:var(--zinc-900);font-weight:800" href="<?= e(base_url('entrar')) ?>">
        Começar agora <?= icone('seta', 16) ?>
      </a>
    </div>
  </div>
</section>

<!-- ------------------------------------------------ rodapé -->
<footer class="lp-rodape">
  <div class="container grid grid-4">
    <div>
      <div class="lp-marca mb-3"><span class="lp-logo">AS</span><?= e($appNome) ?></div>
      <p class="minusculo">A plataforma que faz sua academia rodar com a mesma disciplina de um bom treino.</p>
    </div>
    <div>
      <h4>Produto</h4>
      <ul>
        <li><a href="#modulos">Módulos</a></li>
        <li><a href="#como-funciona">Como funciona</a></li>
        <li><a href="#planos">Planos</a></li>
      </ul>
    </div>
    <div>
      <h4>Sistema</h4>
      <ul>
        <li><a href="<?= e(base_url('entrar')) ?>">Entrar</a></li>
        <li><a href="<?= e(base_url('esqueci-senha')) ?>">Recuperar senha</a></li>
      </ul>
    </div>
    <div>
      <h4>Suporte</h4>
      <ul>
        <li><a href="#duvidas">Perguntas frequentes</a></li>
      </ul>
    </div>
  </div>
  <div class="container lp-rodape-base">
    <span><?= e($appNome) ?> © <?= date('Y') ?> · Feito para academias brasileiras</span>
    <a href="<?= e(base_url('entrar')) ?>">Entrar</a>
  </div>
</footer>

<script src="<?= e(base_url('assets/js/app.js')) ?>"></script>
</body>
</html>
