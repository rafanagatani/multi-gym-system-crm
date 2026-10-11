<?php
/** Equipe da academia. */
require_once BASE_PATH . '/app/views/layout/icones.php';

$credencial = $_SESSION['_credencial'] ?? null;
unset($_SESSION['_credencial']);
?>

<div class="cabecalho-pagina">
  <div>
    <h1>Equipe</h1>
    <p>Professores, recepção e administradores da academia.</p>
  </div>
  <?php if (Auth::temPapel(['admin']) || Auth::superAdmin()): ?>
    <button type="button" class="btn btn-primario" data-abrir="modal-membro"><?= icone('mais', 16) ?> Novo membro</button>
  <?php endif; ?>
</div>

<?php if ($credencial): ?>
  <div class="alerta alerta-aviso">
    <strong>Acesso criado para <?= e($credencial['nome']) ?></strong>
    <p>E-mail: <code><?= e($credencial['email']) ?></code> · Senha: <code class="mono"><?= e($credencial['senha']) ?></code></p>
    <p class="pequeno">Esta senha aparece uma única vez. O usuário deve trocá-la no primeiro acesso.</p>
    <button type="button" class="btn btn-contorno btn-pq mt-2"
            data-copiar="E-mail: <?= e($credencial['email']) ?> | Senha: <?= e($credencial['senha']) ?>">Copiar credenciais</button>
  </div>
<?php endif; ?>

<?php if (!$membros): ?>
  <div class="card"><p class="vazio">Nenhum membro cadastrado.</p></div>
<?php else: ?>
  <div class="grid grid-3">
    <?php foreach ($membros as $m):
      $campos = [
        'id' => $m['id'], 'name' => $m['name'], 'email' => $m['email'], 'telefone' => $m['telefone'],
        'role' => $m['role'], 'especialidade' => $m['especialidade'],
        'comissao_pct' => $m['comissao_pct'], 'active' => $m['active'],
      ];
    ?>
      <div class="card"<?= (int) $m['active'] ? '' : ' style="opacity:.6"' ?>>
        <div class="card-corpo">
          <div class="flex gap-3 itens-centro">
            <div class="avatar avatar-gg"><?= e(iniciais($m['name'])) ?></div>
            <div class="g-1" style="min-width:0">
              <div class="forte truncar"><?= e($m['name']) ?></div>
              <div class="pequeno suave truncar"><?= e($m['email']) ?></div>
              <div class="flex gap-1 mt-1">
                <span class="badge"><?= e(rotulo($m['role'])) ?></span>
                <?php if (!(int) $m['active']): ?><span class="badge badge-vermelho">Inativo</span><?php endif; ?>
              </div>
            </div>
          </div>

          <?php if ($m['especialidade']): ?>
            <p class="pequeno suave mt-3"><?= e($m['especialidade']) ?></p>
          <?php endif; ?>

          <?php if (in_array($m['role'], ['professor', 'personal'], true)): ?>
            <div class="grid grid-3 mt-3" style="gap:10px;padding-top:12px;border-top:1px solid var(--borda)">
              <div>
                <div class="minusculo suave flex gap-1 itens-centro"><?= icone('alunos', 12) ?> Alunos</div>
                <div class="forte" style="font-size:18px"><?= (int) $m['alunos'] ?></div>
              </div>
              <div>
                <div class="minusculo suave flex gap-1 itens-centro"><?= icone('agenda', 12) ?> Aulas</div>
                <div class="forte" style="font-size:18px"><?= (int) $m['aulas'] ?></div>
              </div>
              <div>
                <div class="minusculo suave flex gap-1 itens-centro"><?= icone('subindo', 12) ?> Ocupação</div>
                <div class="forte" style="font-size:18px"><?= (int) $m['ocupacao'] ?>%</div>
              </div>
            </div>
          <?php endif; ?>

          <?php if (Auth::temPapel(['admin']) || Auth::superAdmin()): ?>
            <div class="flex gap-2 mt-4">
              <button type="button" class="btn btn-contorno btn-pq g-1"
                      data-editar="modal-membro" data-titulo="Editar membro"
                      data-campos='<?= e(json_encode($campos, JSON_UNESCAPED_UNICODE)) ?>'>Editar</button>
              <form method="post" action="<?= e(base_url('painel/equipe/excluir')) ?>"
                    data-confirmar="Remover <?= e($m['name']) ?> da equipe?">
                <?= csrf_campo() ?>
                <input type="hidden" name="id" value="<?= e($m['id']) ?>">
                <button class="btn btn-fantasma btn-pq" title="Remover"><?= icone('lixeira', 13) ?></button>
              </form>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="modal" id="modal-membro">
  <div class="modal-fundo" data-fechar></div>
  <div class="modal-caixa">
    <div class="modal-topo">
      <h3 data-titulo>Novo membro</h3>
      <button type="button" class="modal-fechar" data-fechar aria-label="Fechar">&times;</button>
    </div>
    <div class="modal-corpo">
      <form method="post" action="<?= e(base_url('painel/equipe/salvar')) ?>">
        <?= csrf_campo() ?>
        <input type="hidden" name="id" value="">

        <div class="grid grid-2" style="gap:12px">
          <label class="col-2">Nome *<input type="text" name="name" required></label>
          <label>E-mail *<input type="email" name="email" required></label>
          <label>Telefone<input type="text" name="telefone"></label>
          <label>Função *
            <select name="role" required>
              <option value="professor">Professor</option><option value="personal">Personal</option>
              <option value="recepcao">Recepção</option><option value="admin">Admin</option>
            </select>
          </label>
          <label>Comissão (%)<input type="number" name="comissao_pct" step="0.5" min="0" max="100" value="0"></label>
          <label class="col-2">Especialidade<input type="text" name="especialidade" placeholder="Musculação, Funcional, Pilates…"></label>
        </div>

        <label class="linha-check"><input type="checkbox" name="active" value="1" checked> Membro ativo</label>
        <label class="linha-check"><input type="checkbox" name="criar_acesso" value="1"> Criar login no sistema (gera senha automática)</label>
        <p class="dica">A senha aparece na tela uma única vez após salvar.</p>

        <button type="submit" class="btn btn-primario btn-bloco">Salvar membro</button>
      </form>
    </div>
  </div>
</div>
