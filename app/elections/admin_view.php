<?php
declare(strict_types=1);
/** Só é chamado após sessão, ACL e migrações no controlador central. */
function meu_elections_admin_view(PDO $db): void {
    $items=meu_elections_list($db);
    $id=max(0,(int)($_GET['id']??0));
    $selected=$id>0?meu_election_find($db,$id):null;
    $editCandidate=null;
    $candidates=$selected?meu_candidates_list($db,$id):[];
    $cid=max(0,(int)($_GET['candidate']??0));
    foreach($candidates as $row)if((int)$row['id']===$cid)$editCandidate=$row;
    $enabled=setting('elections_enabled','0')==='1';
    $defaults=['title'=>'','election_year'=>(string)date('Y'),'election_scope'=>'municipal','office'=>'mayor','round_number'=>1,'state_code'=>'MG','municipality'=>'','municipality_ibge'=>'','election_date'=>'','source_url'=>''];
    $e=$selected??$defaults;
    $scope=(string)$e['election_scope'];
    ?>
    <div class="panel form-panel">
      <div class="panel-heading"><div><h2>🗳️ Resultados Eleitorais</h2><p class="muted">Cadastro reutilizável de eleições e candidaturas. Ainda não há votos, gráficos, importação nem exibição pública nesta etapa.</p></div></div>
      <form method="post" action="<?= h(admin_link('toggle_elections_module')) ?>">
        <?= csrf_field() ?><input type="hidden" name="enabled" value="<?= $enabled?'0':'1' ?>">
        <p class="muted tiny">Módulo: <strong><?= $enabled?'Habilitado para administração':'Desativado' ?></strong>. Habilitar não publica dados eleitorais.</p>
        <button class="btn outline small" type="submit"><?= $enabled?'Desativar módulo':'Ativar módulo' ?></button>
      </form>
      <p class="muted tiny">Fontes externas não são consultadas automaticamente. Cada eleição cadastrada é apenas um rascunho administrativo; não constitui resultado oficial nem confirmação do registro de candidatura.</p>
    </div>
    <?php if(!$enabled){?><div class="alert warning">Ative o módulo acima para cadastrar eleições e candidatos. Dados anteriores permanecem salvos.</div><?php return;} ?>
    <div class="panel">
      <div class="panel-heading"><h2>Eleições cadastradas</h2><a class="btn outline small" href="<?= h(admin_link('elections')) ?>#election-form" data-admin-reveal="election-form">+ Nova eleição</a></div>
      <?php if(!$items):?><p class="muted">Nenhuma eleição cadastrada. Use + Nova eleição para começar.</p>
      <?php else: ?><div class="table-wrap"><table><thead><tr><th>Eleição</th><th>Ano</th><th>Cargo / turno</th><th>Candidaturas</th><th>Status</th><th>Ação</th></tr></thead><tbody>
      <?php foreach($items as $row): ?><tr><td><strong><?= h($row['title']) ?></strong><small class="caption-hint"><?= h(trim($row['municipality'].' '.$row['state_code'])) ?></small></td><td><?= (int)$row['election_year'] ?></td><td><?= h(meu_election_offices($row['election_scope'])[$row['office']]??$row['office']) ?> — <?= (int)$row['round_number'] ?>º</td><td><?= (int)$row['candidate_count'] ?></td><td><?= $row['status']==='archived'?'Arquivada':'Rascunho' ?></td><td><a href="<?= h(admin_link('elections')) ?>&amp;id=<?= (int)$row['id'] ?>#election-form">Abrir / editar</a></td></tr><?php endforeach; ?>
      </tbody></table></div><?php endif; ?>
    </div>
    <details class="panel form-panel admin-collapsible-form" id="election-form" <?= $selected?'open':'' ?>>
      <summary><?= $selected?'Editar eleição':'Cadastrar eleição' ?></summary><?php if($selected): ?><a href="<?= h(admin_link('elections')) ?>#election-form">Nova eleição</a><?php endif; ?>
      <form method="post" action="<?= h(admin_link('save_election')) ?>" class="stacked-form">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($selected['id']??0) ?>">
        <div class="form-grid">
          <label class="full">Título de referência<input required maxlength="180" name="title" value="<?= h((string)$e['title']) ?>" placeholder="Ex.: Eleição municipal — município e ano"></label>
          <label>Ano<input required type="number" min="1990" max="2100" name="election_year" value="<?= (int)$e['election_year'] ?>"></label>
          <label>Abrangência<select name="election_scope" id="election-scope"><option value="municipal" <?= $scope==='municipal'?'selected':'' ?>>Municipal</option><option value="general" <?= $scope==='general'?'selected':'' ?>>Geral</option></select></label>
          <label>Cargo<select name="office" id="election-office" data-original="<?= h((string)$e['office']) ?>"><?php foreach(meu_election_offices($scope) as $key=>$label): ?><option value="<?= h($key) ?>" <?= (string)$e['office']===$key?'selected':'' ?>><?= h($label) ?></option><?php endforeach; ?></select></label>
          <label>Turno<select name="round_number"><option value="1" <?= (int)$e['round_number']===1?'selected':'' ?>>1º turno</option><option value="2" <?= (int)$e['round_number']===2?'selected':'' ?>>2º turno</option></select></label>
          <label>UF (quando aplicável)<input name="state_code" maxlength="2" value="<?= h((string)$e['state_code']) ?>" placeholder="MG"></label>
          <label>Município (eleição municipal)<input name="municipality" maxlength="120" value="<?= h((string)$e['municipality']) ?>"></label>
          <label>Código IBGE (opcional)<input name="municipality_ibge" inputmode="numeric" maxlength="7" pattern="[0-9]{7}" value="<?= h((string)$e['municipality_ibge']) ?>"></label>
          <label>Data (opcional)<input type="date" name="election_date" value="<?= h((string)($e['election_date']??'')) ?>"></label>
          <label class="full">Link de referência HTTPS (opcional)<input type="url" name="source_url" maxlength="400" placeholder="https://www.tse.jus.br/..." value="<?= h((string)$e['source_url']) ?>"></label>
        </div><button type="submit" class="btn primary">Salvar eleição</button>
      </form>
      <?php if($selected): ?><form method="post" action="<?= h(admin_link('archive_election')) ?>" class="stacked-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$id ?>"><input type="hidden" name="archive" value="<?= $selected['status']==='archived'?'0':'1' ?>"><button type="submit" class="btn outline small"><?= $selected['status']==='archived'?'Reabrir rascunho':'Arquivar eleição' ?></button></form><?php endif; ?>
    </details>
    <?php if($selected): ?>
      <div class="panel" id="candidates"><div class="panel-heading"><h2>Candidaturas — <?= h($selected['title']) ?></h2><span class="muted tiny"><?= count($candidates) ?> cadastrada(s)</span></div>
        <?php if(!$candidates):?><p class="muted">Nenhuma candidatura cadastrada.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Número</th><th>Nome na urna</th><th>Partido</th><th>Estado</th><th>Ação</th></tr></thead><tbody><?php foreach($candidates as $c): ?><tr><td><?= h($c['candidate_number']) ?></td><td><strong><?= h($c['ballot_name']) ?></strong><small class="caption-hint"><?= h($c['full_name']) ?></small></td><td><?= h($c['party_abbr']) ?></td><td><?= $c['status']==='withdrawn'?'Retirada no cadastro':'Listada' ?></td><td><a href="<?= h(admin_link('elections')) ?>&amp;id=<?= (int)$id ?>&amp;candidate=<?= (int)$c['id'] ?>#candidate-form">Editar</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
      </div>
      <details class="panel form-panel admin-collapsible-form" id="candidate-form" <?= $editCandidate?'open':'' ?>><summary><?= $editCandidate?'Editar candidatura':'Adicionar candidatura' ?></summary><?php if($editCandidate): ?><a href="<?= h(admin_link('elections')) ?>&amp;id=<?= (int)$id ?>#candidate-form">Novo candidato</a><?php endif; ?>
        <form method="post" action="<?= h(admin_link('save_election_candidate')) ?>" class="stacked-form"><?= csrf_field() ?><input type="hidden" name="election_id" value="<?= (int)$id ?>"><input type="hidden" name="id" value="<?= (int)($editCandidate['id']??0) ?>">
          <div class="form-grid"><label>Nome completo<input required maxlength="160" name="full_name" value="<?= h((string)($editCandidate['full_name']??'')) ?>"></label><label>Nome na urna<input required maxlength="120" name="ballot_name" value="<?= h((string)($editCandidate['ballot_name']??'')) ?>"></label><label>Número<input required inputmode="numeric" pattern="[0-9]{1,6}" maxlength="6" name="candidate_number" value="<?= h((string)($editCandidate['candidate_number']??'')) ?>"></label><label>Sigla partidária<input name="party_abbr" maxlength="30" value="<?= h((string)($editCandidate['party_abbr']??'')) ?>"></label><label class="full">Nome do partido (opcional)<input name="party_name" maxlength="120" value="<?= h((string)($editCandidate['party_name']??'')) ?>"></label></div><button class="btn primary">Salvar candidatura</button>
        </form>
        <?php if($editCandidate): ?><form method="post" action="<?= h(admin_link('toggle_election_candidate')) ?>"><?= csrf_field() ?><input type="hidden" name="election_id" value="<?= (int)$id ?>"><input type="hidden" name="id" value="<?= (int)$editCandidate['id'] ?>"><input type="hidden" name="withdraw" value="<?= $editCandidate['status']==='withdrawn'?'0':'1' ?>"><button class="btn outline small" type="submit"><?= $editCandidate['status']==='withdrawn'?'Restaurar à lista':'Retirar da lista' ?></button></form><?php endif; ?>
        <p class="muted tiny">Candidaturas cadastradas manualmente não foram verificadas no TSE. Não há publicação de votos nesta versão.</p>
      </details>
    <?php endif; ?>
    <script src="/assets/js/elections-admin.js" defer></script>
    <?php
}
