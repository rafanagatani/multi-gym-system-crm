/* =====================================================================
   Academia System - JavaScript da aplicacao
   Sem dependencias externas.
   ===================================================================== */
(function () {
  'use strict';

  /* ---------------- utilidades ---------------- */
  const $  = (s, ctx) => (ctx || document).querySelector(s);
  const $$ = (s, ctx) => Array.from((ctx || document).querySelectorAll(s));

  /* ---------------- menu lateral (mobile) ---------------- */
  document.addEventListener('click', (ev) => {
    if (ev.target.closest('[data-menu]')) {
      document.body.classList.toggle('menu-aberto');
    } else if (document.body.classList.contains('menu-aberto') && !ev.target.closest('.sidebar')) {
      document.body.classList.remove('menu-aberto');
    }
  });

  /* ---------------- modais ---------------- */
  function abrirModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    m.classList.add('aberto');
    const foco = m.querySelector('input:not([type=hidden]),select,textarea');
    if (foco) setTimeout(() => foco.focus(), 40);
  }
  function fecharModal(m) {
    if (m) m.classList.remove('aberto');
  }
  window.abrirModal = abrirModal;

  document.addEventListener('click', (ev) => {
    const abrir = ev.target.closest('[data-abrir]');
    if (abrir) {
      ev.preventDefault();
      abrirModal(abrir.dataset.abrir);
      return;
    }
    if (ev.target.closest('[data-fechar]') || ev.target.classList.contains('modal-fundo')) {
      fecharModal(ev.target.closest('.modal'));
    }
  });

  document.addEventListener('keydown', (ev) => {
    if (ev.key === 'Escape') $$('.modal.aberto').forEach(fecharModal);
  });

  /* ---------------- abas ---------------- */
  document.addEventListener('click', (ev) => {
    const aba = ev.target.closest('.aba,.pub-aba');
    if (!aba || !aba.dataset.aba) return;
    const grupo = aba.parentElement;
    $$('.aba,.pub-aba', grupo).forEach((b) => b.classList.remove('ativa'));
    aba.classList.add('ativa');
    const escopo = grupo.parentElement;
    $$('.painel-aba', escopo).forEach((p) => {
      p.classList.toggle('ativa', p.dataset.painel === aba.dataset.aba);
    });
  });

  /* ---------------- confirmacao em formularios ---------------- */
  document.addEventListener('submit', (ev) => {
    const msg = ev.target.dataset.confirmar;
    if (msg && !window.confirm(msg)) ev.preventDefault();
  });

  /* ---------------- copiar para a area de transferencia ---------------- */
  document.addEventListener('click', async (ev) => {
    const btn = ev.target.closest('[data-copiar]');
    if (!btn) return;
    ev.preventDefault();
    const txt = btn.dataset.copiar;
    try {
      await navigator.clipboard.writeText(txt);
    } catch (e) {
      const t = document.createElement('textarea');
      t.value = txt;
      document.body.appendChild(t);
      t.select();
      document.execCommand('copy');
      t.remove();
    }
    const original = btn.textContent;
    btn.textContent = 'Copiado!';
    setTimeout(() => { btn.textContent = original; }, 1600);
  });

  /* ---------------- preencher modal de edicao ---------------- */
  // <button data-editar="idDoModal" data-campos='{"nome":"valor"}'>
  document.addEventListener('click', (ev) => {
    const btn = ev.target.closest('[data-editar]');
    if (!btn) return;
    ev.preventDefault();
    const modal = document.getElementById(btn.dataset.editar);
    if (!modal) return;
    let campos = {};
    try { campos = JSON.parse(btn.dataset.campos || '{}'); } catch (e) { campos = {}; }
    Object.keys(campos).forEach((nome) => {
      const campo = modal.querySelector('[name="' + nome + '"]');
      if (!campo) return;
      if (campo.type === 'checkbox') campo.checked = !!Number(campos[nome]);
      else campo.value = campos[nome] === null ? '' : campos[nome];
    });
    const titulo = modal.querySelector('[data-titulo]');
    if (titulo && btn.dataset.titulo) titulo.textContent = btn.dataset.titulo;
    abrirModal(btn.dataset.editar);
  });

  /* ---------------- agenda: arrastar e soltar ---------------- */
  function iniciarAgenda() {
    const grade = $('#agenda-grade');
    if (!grade) return;

    $$('.agenda-aula', grade).forEach((aula) => {
      aula.setAttribute('draggable', 'true');
      aula.addEventListener('dragstart', (ev) => {
        ev.dataTransfer.setData('text/plain', aula.dataset.id);
        ev.dataTransfer.effectAllowed = 'move';
      });
    });

    $$('.agenda-celula', grade).forEach((cel) => {
      cel.addEventListener('dragover', (ev) => { ev.preventDefault(); cel.classList.add('alvo'); });
      cel.addEventListener('dragleave', () => cel.classList.remove('alvo'));
      cel.addEventListener('drop', (ev) => {
        ev.preventDefault();
        cel.classList.remove('alvo');
        const id = ev.dataTransfer.getData('text/plain');
        if (!id) return;
        const form = $('#form-mover');
        if (!form) return;
        form.querySelector('[name=id]').value = id;
        form.querySelector('[name=dia]').value = cel.dataset.dia;
        form.querySelector('[name=hora]').value = cel.dataset.hora;
        form.submit();
      });
    });
  }

  /* ---------------- check-ins: busca ao vivo ---------------- */
  function iniciarCheckin() {
    const campo = $('#busca-aluno');
    if (!campo) return;
    const lista = $('#resultado-busca');
    const url = campo.dataset.url;
    let timer = null;

    async function buscar() {
      const termo = campo.value.trim();
      if (termo.length < 2) { lista.innerHTML = ''; return; }
      try {
        const r = await fetch(url + '?q=' + encodeURIComponent(termo), { headers: { 'X-Requested-With': 'fetch' } });
        const alunos = await r.json();
        if (!alunos.length) {
          lista.innerHTML = '<p class="suave pequeno">Nenhum aluno encontrado.</p>';
          return;
        }
        lista.innerHTML = alunos.map((a, i) => `
          <div class="linha-resultado">
            <div>
              <div class="forte">${escapar(a.name)}</div>
              ${a.cpf ? `<div class="pequeno suave">${escapar(a.cpf)}</div>` : ''}
            </div>
            <button type="button" class="btn btn-primario" data-checkin="${escapar(a.id)}"
                    data-nome="${escapar(a.name)}"${i === 0 ? ' data-primeiro="1"' : ''}>Fazer check-in</button>
          </div>`).join('');
      } catch (e) {
        lista.innerHTML = '<p class="suave pequeno">Falha na busca. Tente novamente.</p>';
      }
    }

    campo.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(buscar, 220); });

    campo.addEventListener('keydown', (ev) => {
      if (ev.key !== 'Enter') return;
      ev.preventDefault();
      const primeiro = lista.querySelector('[data-primeiro]');
      if (primeiro) primeiro.click();
    });

    lista.addEventListener('click', (ev) => {
      const btn = ev.target.closest('[data-checkin]');
      if (!btn) return;
      const form = $('#form-checkin');
      form.querySelector('[name=student_id]').value = btn.dataset.checkin;
      form.querySelector('[name=student_name]').value = btn.dataset.nome;
      form.submit();
    });

    document.addEventListener('keydown', (ev) => {
      if (ev.key === 'F2') { ev.preventDefault(); campo.focus(); campo.select(); }
      else if (ev.key === 'F5') { ev.preventDefault(); location.reload(); }
      else if (ev.key === 'Escape' && document.activeElement === campo) {
        campo.value = ''; lista.innerHTML = '';
      }
    });

    campo.focus();
  }

  function escapar(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  /* =====================================================================
     Graficos (canvas puro - barras, linha e pizza)
     Uso: <canvas class="grafico" data-tipo="barra"
                  data-dados='{"rotulos":[],"series":[{"nome":"","cor":"","valores":[]}]}'>
     ===================================================================== */
  const CORES = ['#D97634', '#E8935D', '#22C55E', '#3B82F6', '#A855F7', '#EAB308', '#EC4899', '#06B6D4'];

  function desenharGraficos() {
    $$('canvas.grafico').forEach(desenhar);
  }

  function desenhar(cv) {
    let cfg;
    try { cfg = JSON.parse(cv.dataset.dados || '{}'); } catch (e) { return; }
    const tipo = cv.dataset.tipo || 'barra';
    const dpr = window.devicePixelRatio || 1;
    const larg = cv.clientWidth || cv.parentElement.clientWidth || 480;
    const alt  = cv.clientHeight || cv.parentElement.clientHeight || 260;
    // width/height sao pixels do buffer; o tamanho exibido vem do CSS.
    cv.width = Math.round(larg * dpr);
    cv.height = Math.round(alt * dpr);
    cv.style.width = larg + 'px';
    cv.style.height = alt + 'px';
    const g = cv.getContext('2d');
    g.setTransform(dpr, 0, 0, dpr, 0, 0);
    g.clearRect(0, 0, larg, alt);

    if (tipo === 'pizza') return pizza(g, larg, alt, cfg);

    const rotulos = cfg.rotulos || [];
    const series = cfg.series || [];
    if (!rotulos.length || !series.length) {
      g.fillStyle = '#A1A1AA';
      g.font = '13px system-ui,sans-serif';
      g.textAlign = 'center';
      g.fillText('Sem dados para exibir', larg / 2, alt / 2);
      return;
    }

    const padE = 46, padD = 10, padT = 14, padB = 30;
    const areaL = larg - padE - padD;
    const areaA = alt - padT - padB;

    let max = 0;
    series.forEach((s) => s.valores.forEach((v) => { if (+v > max) max = +v; }));
    if (max <= 0) max = 1;
    max = escalaBonita(max);

    // eixo Y + grade
    g.strokeStyle = '#E4E4E7';
    g.fillStyle = '#A1A1AA';
    g.font = '11px system-ui,sans-serif';
    g.textAlign = 'right';
    g.lineWidth = 1;
    for (let i = 0; i <= 4; i++) {
      const y = padT + areaA - (areaA * i / 4);
      g.beginPath();
      g.moveTo(padE, y + 0.5);
      g.lineTo(larg - padD, y + 0.5);
      g.stroke();
      g.fillText(formatarNum(max * i / 4), padE - 7, y + 4);
    }

    // rotulos X (encurtados para caber na largura disponivel)
    g.textAlign = 'center';
    const passo = areaL / rotulos.length;
    const pular = Math.ceil(rotulos.length / Math.max(1, Math.floor(areaL / 52)));
    const maxChars = Math.max(4, Math.floor((passo * pular) / 6.2));
    rotulos.forEach((r, i) => {
      if (i % pular !== 0) return;
      let txt = String(r);
      if (txt.length > maxChars) txt = txt.slice(0, maxChars - 1) + '…';
      g.fillText(txt, padE + passo * i + passo / 2, alt - 10);
    });

    if (tipo === 'linha') {
      series.forEach((s, si) => {
        const cor = s.cor || CORES[si % CORES.length];
        g.strokeStyle = cor;
        g.lineWidth = 3;
        g.lineJoin = 'round';
        g.beginPath();
        s.valores.forEach((v, i) => {
          const x = padE + passo * i + passo / 2;
          const y = padT + areaA - (areaA * (+v / max));
          i === 0 ? g.moveTo(x, y) : g.lineTo(x, y);
        });
        g.stroke();
        g.fillStyle = cor;
        s.valores.forEach((v, i) => {
          const x = padE + passo * i + passo / 2;
          const y = padT + areaA - (areaA * (+v / max));
          g.beginPath();
          g.arc(x, y, 3.5, 0, Math.PI * 2);
          g.fill();
        });
      });
      return;
    }

    // barras (agrupadas)
    const larguraGrupo = passo * 0.68;
    const larguraBarra = larguraGrupo / series.length;
    series.forEach((s, si) => {
      g.fillStyle = s.cor || CORES[si % CORES.length];
      s.valores.forEach((v, i) => {
        const h = areaA * (+v / max);
        const x = padE + passo * i + (passo - larguraGrupo) / 2 + larguraBarra * si;
        const y = padT + areaA - h;
        arredondado(g, x, y, Math.max(1, larguraBarra - 2), h, 4);
      });
    });
  }

  function arredondado(g, x, y, w, h, r) {
    if (h <= 0) return;
    r = Math.min(r, w / 2, h);
    g.beginPath();
    g.moveTo(x, y + h);
    g.lineTo(x, y + r);
    g.quadraticCurveTo(x, y, x + r, y);
    g.lineTo(x + w - r, y);
    g.quadraticCurveTo(x + w, y, x + w, y + r);
    g.lineTo(x + w, y + h);
    g.closePath();
    g.fill();
  }

  function pizza(g, larg, alt, cfg) {
    const fatias = cfg.fatias || [];
    const total = fatias.reduce((s, f) => s + (+f.valor || 0), 0);
    if (!total) {
      g.fillStyle = '#A1A1AA';
      g.font = '13px system-ui,sans-serif';
      g.textAlign = 'center';
      g.fillText('Sem dados para exibir', larg / 2, alt / 2);
      return;
    }
    const cx = larg / 2, cy = alt / 2, raio = Math.min(larg, alt) / 2 - 18;
    let ang = -Math.PI / 2;
    fatias.forEach((f, i) => {
      const fatia = (+f.valor / total) * Math.PI * 2;
      g.fillStyle = f.cor || CORES[i % CORES.length];
      g.beginPath();
      g.moveTo(cx, cy);
      g.arc(cx, cy, raio, ang, ang + fatia);
      g.closePath();
      g.fill();
      if (fatia > 0.28) {
        const meio = ang + fatia / 2;
        g.fillStyle = '#fff';
        g.font = '700 12px system-ui,sans-serif';
        g.textAlign = 'center';
        g.fillText(Math.round((+f.valor / total) * 100) + '%', cx + Math.cos(meio) * raio * 0.65, cy + Math.sin(meio) * raio * 0.65 + 4);
      }
      ang += fatia;
    });
  }

  function escalaBonita(max) {
    const exp = Math.pow(10, Math.floor(Math.log10(max)));
    const n = max / exp;
    const passo = n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10;
    return passo * exp;
  }

  function formatarNum(v) {
    if (v >= 1000000) return (v / 1000000).toFixed(v % 1000000 ? 1 : 0) + 'M';
    if (v >= 1000) return (v / 1000).toFixed(v % 1000 ? 1 : 0) + 'k';
    return String(Math.round(v * 100) / 100);
  }

  /* ---------------- baixar o QR Code como PNG ---------------- */
  document.addEventListener('click', (ev) => {
    const btn = ev.target.closest('[data-baixar-qr]');
    if (!btn) return;
    ev.preventDefault();
    const svg = btn.closest('.card-link')?.querySelector('svg');
    if (!svg) return;

    const escala = 6;
    const lado = (svg.viewBox.baseVal.width || 33) * escala;
    const blob = new Blob([new XMLSerializer().serializeToString(svg)], { type: 'image/svg+xml' });
    const url = URL.createObjectURL(blob);
    const img = new Image();

    img.onload = () => {
      const cv = document.createElement('canvas');
      cv.width = cv.height = lado;
      const g = cv.getContext('2d');
      g.imageSmoothingEnabled = false;
      g.fillStyle = '#fff';
      g.fillRect(0, 0, lado, lado);
      g.drawImage(img, 0, 0, lado, lado);
      URL.revokeObjectURL(url);
      const a = document.createElement('a');
      a.download = btn.dataset.baixarQr + '.png';
      a.href = cv.toDataURL('image/png');
      a.click();
    };
    img.onerror = () => URL.revokeObjectURL(url);
    img.src = url;
  });

  /* ---------------- pagina publica de matricula ---------------- */
  function iniciarPublica() {
    const raiz = $('#form-experimental');
    if (!raiz) return;
    const cor = document.body.dataset.cor || '#D97634';

    $$('[data-modalidade]', raiz).forEach((chip) => {
      chip.addEventListener('click', () => {
        $$('[data-modalidade]', raiz).forEach((c) => { c.classList.remove('sel'); c.style.background = ''; });
        chip.classList.add('sel');
        chip.style.background = cor;
        raiz.querySelector('[name=modality]').value = chip.dataset.modalidade;
        raiz.querySelector('[name=schedule_id]').value = '';
        $$('[data-slot]', raiz).forEach((s) => {
          const mostrar = s.dataset.mod === chip.dataset.modalidade;
          s.classList.toggle('oculto', !mostrar);
          s.classList.remove('sel');
          s.style.background = '';
        });
        $('#passo-horario').classList.remove('oculto');
      });
    });

    $$('[data-slot]', raiz).forEach((slot) => {
      slot.addEventListener('click', () => {
        $$('[data-slot]', raiz).forEach((s) => { s.classList.remove('sel'); s.style.background = ''; });
        slot.classList.add('sel');
        slot.style.background = cor;
        raiz.querySelector('[name=schedule_id]').value = slot.dataset.slot;
      });
    });

    $$('.radio-caixa', raiz).forEach((cx) => {
      cx.addEventListener('click', () => {
        $$('.radio-caixa', raiz).forEach((o) => o.classList.remove('sel'));
        cx.classList.add('sel');
      });
    });
  }

  /* ---------------- inicializacao ---------------- */
  document.addEventListener('DOMContentLoaded', () => {
    iniciarAgenda();
    iniciarCheckin();
    iniciarPublica();
    desenharGraficos();
  });

  let redimensionar;
  window.addEventListener('resize', () => {
    clearTimeout(redimensionar);
    redimensionar = setTimeout(desenharGraficos, 180);
  });
  window.addEventListener('beforeprint', desenharGraficos);
})();
