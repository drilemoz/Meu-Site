<?php
/** Atualizações — UI compacta, telas avançadas recolhidas (1.0.54). */
declare(strict_types=1);
require_ability('manage_settings',$me);
$source=igua_updater_source();$release=null;$remoteError='';
try{$release=igua_updater_remote_release(false);}catch(Throwable $exc){$remoteError='A consulta ao GitHub falhou. Verifique a conexão e os logs.';}
$last=igua_updater_last_job();$journal=$last['journal']??null;
$recentUpdates=igua_updater_history(6);
$installedIntegrity=igua_updater_installed_integrity($journal);
$ready=class_exists('ZipArchive');
$preflight=igua_updater_preflight($db);
$preview=$_SESSION['igua_update_preview']??null;
unset($_SESSION['igua_update_preview']);
$hasSource=!empty($source['owner'])&&!empty($source['repo']);
$hasNewRelease=$release && !empty($release['newer']);
$hasDownload=$hasNewRelease&&!empty($release['url']);
$originLabel=$hasSource?$source['owner'].'/'.$source['repo']:'Origem não identificada';
?>
<section class="panel updater-panel" aria-labelledby="updater-overview-title">
  <div class="updater-header">
    <h2 id="updater-overview-title">Atualizações do sistema</h2>
    <details class="updater-help"><summary aria-label="Como funcionam as atualizações" title="Como funcionam as atualizações">?</summary>
      <div class="updater-help-content">O painel consulta releases do GitHub e aceita pacotes v2 sem assinatura digital. SHA-256 detecta alterações acidentais, mas não comprova a origem do ZIP; instale somente arquivos confiáveis. Antes da instalação, faz backup lógico do MySQL e dos arquivos que serão trocados. Não modifica fotos, credenciais nem bancos com revisões incompatíveis. A consulta ocorre ao abrir esta tela ou ao tocar em Verificar.</div>
    </details>
  </div>
  <div class="updater-overview">
    <div><span>Instalada</span><strong><?=h(igua_updater_version())?></strong></div>
    <div><span>Disponível</span><strong><?=h((string)($release['version']??'—'))?></strong></div>
    <div><span>Status</span><strong class="<?= $hasNewRelease?'updater-status-new':'updater-status-neutral' ?>"><?= $hasNewRelease?'Atualização disponível':($hasSource?($remoteError?'Falha na consulta':($release && ($release['version']??'')===igua_updater_version()?'Você já está na versão mais recente':'Sem nova versão publicada')):'Configure a origem') ?></strong></div>
  </div>
  <div class="updater-actions">
    <form method="post" action="<?=h(admin_link('updates'))?>">
      <?=csrf_field()?><input type="hidden" name="operation" value="check">
      <button class="btn outline" type="submit" aria-label="Verificar novas versões">↻ Verificar</button>
    </form>
    <?php if($hasSource): ?><a class="updater-subtle-link" href="<?=h('https://github.com/'.$source['owner'].'/'.$source['repo'].'/releases')?>" target="_blank" rel="noopener noreferrer" aria-label="Ver releases no GitHub">Releases ↗</a><?php endif; ?>
    <button type="button" class="btn primary updater-upload-trigger" data-updater-open="updater-upload-dialog" aria-haspopup="dialog" aria-controls="updater-upload-dialog" <?=!$ready?'disabled title="PHP ZipArchive indisponível"':''?>><span class="updater-upload-trigger-long">Enviar atualização</span><span class="updater-upload-trigger-short">Enviar ZIP</span></button>
  </div>
  <div class="updater-quick-tools" aria-label="Configurações e diagnóstico do atualizador">
    <button type="button" class="updater-quick-link" data-updater-open="updater-source-dialog" aria-haspopup="dialog" aria-controls="updater-source-dialog" title="Configurar fonte oficial">
      <span aria-hidden="true">⚙</span><span><small>Fonte oficial</small><strong><?=h($originLabel)?></strong></span>
    </button>
    <button type="button" class="updater-quick-link <?= $preflight['ok']?'is-ok':'is-error' ?>" data-updater-open="updater-check-dialog" aria-haspopup="dialog" aria-controls="updater-check-dialog" title="Abrir vistoria do servidor">
      <span aria-hidden="true"><?= $preflight['ok']?'✓':'!' ?></span><span><small>Servidor</small><strong><?= $preflight['ok']?'Pronto para verificar':'Verificar problemas' ?></strong></span>
    </button>
  </div>
  <?php if(!$ready): ?><div class="alert warning">Para instalar atualizações, o PHP precisa da extensão ZipArchive. Sodium é opcional para os pacotes antigos assinados.</div><?php endif; ?>
  <?php if($remoteError): ?><div class="alert warning"><?=h($remoteError)?></div><?php endif; ?>
  <?php if(is_file(igua_updater_maintenance())): ?><div class="alert error">Atualização interrompida. Recupere o código antes de continuar.</div><?php endif; ?>
</section>
<?php if($hasNewRelease): ?>
<section class="panel updater-panel" aria-labelledby="updater-install-title">
  <div class="updater-header"><h2 id="updater-install-title">Instalação pelo GitHub</h2>
    <details class="updater-help"><summary aria-label="Segurança da instalação">?</summary><div class="updater-help-content">Instalações exigem senha administrativa, confirmação e backup. O ZIP v2 pode não ter assinatura, por isso confirme a origem antes de instalar.</div></details>
  </div>
  <?php if($hasDownload): ?>
    <div class="updater-release">
      <div><span class="updater-pill">Nova versão</span><strong><?=h($release['version'])?></strong></div>
      <details><summary>Novidades desta versão</summary><div class="updater-release-notes"><?=nl2br(h((string)($release['notes']??'Sem notas de versão.')))?></div></details>
    </div>
    <form class="updater-install-form" data-updater-progress-form method="post" action="<?=h(admin_link('updates'))?>">
      <?=csrf_field()?><input type="hidden" name="operation" value="install_remote">
      <label class="updater-check"><input type="checkbox" name="confirm_install" value="yes" required> Confirmei a origem do ZIP e autorizo a instalação e o backup prévio.</label>
      <label>Senha administrativa<input type="password" name="confirm_password" autocomplete="current-password" required></label>
      <button class="btn primary" type="submit">Instalar v<?=h($release['version'])?></button>
    </form>
  <?php elseif($hasNewRelease): ?>
    <p class="updater-hint">Versão <?=h($release['version'])?> publicada, mas ainda sem ZIP de atualização compatível. Confirme a origem do pacote antes de instalá-lo.</p>
  <?php else: ?><p class="updater-hint">Nenhuma atualização disponível para instalar automaticamente.</p><?php endif; ?>
</section>
<?php endif; ?>
<dialog id="updater-upload-dialog" class="updater-dialog updater-upload-dialog" aria-labelledby="updater-upload-title" aria-modal="true">
  <div class="updater-dialog-head">
    <div class="updater-upload-heading"><span class="updater-upload-icon" aria-hidden="true">⇧</span><div><h2 id="updater-upload-title">Enviar atualização</h2><small>Arquivo ZIP de atualização</small></div></div>
    <button type="button" class="updater-dialog-close" data-updater-close aria-label="Fechar janela de atualização">×</button>
  </div>
  <form id="updater-upload-form" class="updater-install-form updater-upload-form" data-updater-progress-form data-updater-confirm-flow="manual" method="post" enctype="multipart/form-data" action="<?=h(admin_link('updates'))?>">
    <?=csrf_field()?><input type="hidden" name="operation" value="upload">
    <div class="updater-form-fields" data-updater-fields>
      <label class="updater-upload-label">Pacote de atualização<input type="file" name="update_zip" accept=".zip,application/zip" required></label>
      <label>Senha administrativa<input type="password" name="confirm_password" autocomplete="current-password" required></label>
      <p class="updater-upload-note">Use <strong>Atualizacao-vX.Y.Z.zip</strong>. O pacote de código-fonte não serve para instalar.</p>
      <button class="btn primary updater-submit" type="submit">Enviar <span aria-hidden="true">→</span></button>
    </div>
    <div class="updater-confirm-step" data-updater-confirm-step hidden>
      <span class="updater-stage-tag">Confirmação final</span>
      <p class="updater-confirm-file" data-updater-filename></p>
      <p>O sistema vai validar o ZIP e preparar backups antes de substituir os arquivos. Não feche a página durante o processamento.</p>
      <label class="updater-check"><input type="checkbox" name="confirm_install" value="yes" data-updater-ack required disabled> Confirmo que o arquivo é confiável e autorizo a instalação.</label>
      <div class="updater-confirm-actions">
        <button type="button" class="btn outline" data-updater-back>Voltar</button>
        <button type="button" class="btn primary" data-updater-accept>Confirmar e enviar</button>
      </div>
    </div>
  </form>
</dialog>
<dialog id="updater-source-dialog" class="updater-dialog" aria-labelledby="updater-source-title" aria-modal="true">
  <div class="updater-dialog-head"><h2 id="updater-source-title">⚙ Fonte oficial</h2><button type="button" class="updater-dialog-close" data-updater-close aria-label="Fechar configurações">×</button></div>
  <div class="updater-source-line"><span class="updater-source-label">GitHub</span><strong><?=h($originLabel)?></strong>
    <?php if($hasSource): ?><span class="updater-tag"><?=($source['origin']??'')==='manual'?'Personalizado':'Detectado'?></span><?php endif; ?>
  </div>
  <?php if(!$hasSource): ?><p class="updater-hint">O repositório ainda não foi identificado. Configure uma vez para receber versões pelo GitHub.</p><?php endif; ?>
  <p class="updater-hint">A origem oficial é usada apenas para procurar releases. Pacotes v2 podem vir sem assinatura; a origem não é comprovada por SHA-256. Repositórios GitHub devem ser verificados antes de instalar.</p>
    <form method="post" class="updater-source-form" action="<?=h(admin_link('updates'))?>">
      <?=csrf_field()?><input type="hidden" name="operation" value="source">
      <label>Proprietário<input name="owner" type="text" maxlength="39" required value="<?=h($source['owner']??'')?>" autocapitalize="off" autocomplete="off"></label>
      <label>Repositório<input name="repo" type="text" maxlength="100" required value="<?=h($source['repo']??'')?>" autocapitalize="off" autocomplete="off"></label>
      <button class="btn outline" type="submit">Salvar origem</button>
    </form>
  <?php if(($source['origin']??'')==='manual'): ?>
  <form method="post" action="<?=h(admin_link('updates'))?>" class="updater-reset-form">
    <?=csrf_field()?><input type="hidden" name="operation" value="source_reset">
    <button class="btn outline" type="submit">Restaurar origem do projeto</button>
  </form>
  <?php endif; ?>
</dialog>
<dialog id="updater-check-dialog" class="updater-dialog" aria-labelledby="updater-check-title" aria-modal="true">
  <div class="updater-dialog-head"><h2 id="updater-check-title">Vistoria do servidor</h2><button type="button" class="updater-dialog-close" data-updater-close aria-label="Fechar vistoria">×</button></div>
  <p class="updater-hint">Verifica extensões, permissões e o banco sem modificar dados. As cotas e a continuidade do PHP-FPM dependem da hospedagem.</p>
  <div class="updater-compact-result <?= $preflight['ok']?'updater-good':'updater-bad' ?>">
    <strong><?= $preflight['ok']?'✓ Sem bloqueios detectados':'! Atualização bloqueada' ?></strong>
    <span><?= (int)$preflight['failures'] ?> falha(s) · <?= (int)$preflight['warnings'] ?> aviso(s)</span>
  </div>
  <details class="updater-details"><summary>Ver verificações e simular um ZIP</summary>
    <div class="updater-checks">
    <?php foreach($preflight['checks'] as $check): ?>
      <div><strong><?= $check['status']==='ok'?'✓':($check['status']==='falha'?'✕':'!') ?> <?=h($check['name'])?></strong><span><?=h($check['detail'])?></span></div>
    <?php endforeach; ?>
    </div>
    <form method="post" enctype="multipart/form-data" action="<?=h(admin_link('updates'))?>" class="updater-install-form" data-updater-progress-form>
      <?=csrf_field()?><input type="hidden" name="operation" value="preview">
      <label class="updater-upload-label">Simular ZIP de atualização<input type="file" name="update_zip" accept=".zip,application/zip" required></label>
      <button class="btn outline" type="submit">Simular sem instalar</button>
    </form>
    <?php if(is_array($preview) && (int)($preview['at']??0)>time()-900): ?>
      <div class="updater-compact-result <?=!empty($preview['preflight']['ok'])?'updater-good':'updater-bad'?>">Simulação v<?=h($preview['version']??'?')?>: <?= (int)($preview['files']??0) ?> arquivos (<?=($preview['signature_status']??'')==='signed'?'assinado':'sem assinatura'?>) — <?=!empty($preview['preflight']['ok'])?'sem bloqueios':'com bloqueios'?>.</div>
      <?php if(is_array($preview['changes']??null)): $counts=$preview['changes']['counts']; ?>
        <div class="updater-diff-summary" aria-label="Alterações previstas no ZIP">
          <span><strong><?= (int)($counts['changed']??0) ?></strong> substituídos</span>
          <span><strong><?= (int)($counts['new']??0) ?></strong> novos</span>
          <span><strong><?= (int)($counts['identical']??0) ?></strong> iguais</span>
          <?php if(!empty($counts['unsafe'])): ?><span class="updater-alert"><strong><?= (int)$counts['unsafe'] ?></strong> inseguros</span><?php endif; ?>
        </div>
        <?php if(!empty($preview['changes']['examples'])): ?>
          <details class="updater-details"><summary>Ver exemplos de arquivos afetados</summary>
            <ul class="updater-history-list">
            <?php foreach($preview['changes']['examples'] as $change): ?>
              <li><code><?=h($change['path'])?></code><span><?=h($change['type']==='new'?'Novo':($change['type']==='changed'?'Substituído':'Inseguro'))?></span></li>
            <?php endforeach; ?>
            </ul>
          </details>
        <?php endif; ?>
      <?php endif; ?>
      <?php foreach(($preview['preflight']['checks']??[]) as $item): ?>
      <p class="updater-hint"><?=h($item['name']??'')?> — <?=h($item['status']??'')?>: <?=h($item['detail']??'')?></p>
      <?php endforeach; ?>
    <?php endif; ?>
  </details>
</dialog>
<section class="panel updater-panel" aria-labelledby="updater-history-title">
  <div class="updater-header"><h2 id="updater-history-title">Histórico e recuperação</h2>
    <details class="updater-help"><summary aria-label="Sobre recuperação de atualizações" title="Sobre recuperação de atualizações">?</summary><div class="updater-help-content">A recuperação restaura arquivos de código de uma atualização anterior. Não reverte alterações feitas posteriormente no banco MySQL ou no conteúdo editorial.</div></details>
  </div>
  <?php if(!empty($installedIntegrity['available'])): ?>
    <div class="updater-compact-result <?=($installedIntegrity['status']??'')==='ok'?'updater-good':'updater-bad'?>">
      <strong><?=($installedIntegrity['status']??'')==='ok'?'✓ Código da última atualização íntegro':'! Divergência no código instalado'?></strong>
      <span><?= (int)($installedIntegrity['verified']??0) ?> de <?= (int)($installedIntegrity['total']??0) ?> arquivos verificados</span>
    </div>
    <?php if(!empty($installedIntegrity['issues'])): ?><details class="updater-details"><summary>Arquivos divergentes</summary>
      <ul class="updater-history-list">
        <?php foreach($installedIntegrity['issues'] as $issue): ?>
          <li><code><?=h($issue['path'])?></code><span><?=h($issue['status']==='missing'?'Ausente':($issue['status']==='changed'?'Modificado':'Caminho inseguro'))?></span></li>
        <?php endforeach; ?>
      </ul>
      <p class="updater-hint">Não faça restauração automática sem identificar a origem dessas alterações.</p>
    </details><?php endif; ?>
  <?php elseif(($installedIntegrity['status']??'')==='version_mismatch'): ?>
    <p class="updater-hint">O código instalado difere da versão registrada na última atualização. A integridade dessa instalação não pode ser confirmada pelo histórico atual.</p>
  <?php endif; ?>
  <?php if($journal): ?>
    <div class="updater-source-line"><strong><?=h($journal['from']??'?')?> → <?=h($journal['to']??'?')?></strong><span class="updater-tag"><?=h($journal['status']??'?')?></span></div>
    <p class="updater-hint">Registrado em <?=h($journal['at']??'?')?>.</p>
    <?php if(in_array(($journal['status']??''),['prepared','complete','recovery_needed','applying'],true)): ?>
      <details class="updater-details"><summary>Restaurar arquivos da versão anterior</summary>
        <form method="post" action="<?=h(admin_link('updates'))?>" class="updater-install-form">
          <?=csrf_field()?><input type="hidden" name="operation" value="rollback">
          <label>Senha administrativa (apenas para restauração)<input type="password" name="confirm_password" required autocomplete="current-password"></label>
          <button class="btn outline" type="submit">Confirmar restauração</button>
        </form>
        <p class="updater-hint">O banco MySQL não é restaurado automaticamente.</p>
      </details>
    <?php endif; ?>
  <?php else: ?><p class="updater-hint">Nenhuma atualização instalada por este mecanismo.</p><?php endif; ?>
  <?php if(count($recentUpdates)>0): ?>
    <details class="updater-details"><summary>Consultar histórico (<?=count($recentUpdates)?> operações recentes)</summary>
      <ul class="updater-history-list">
        <?php foreach($recentUpdates as $history): ?>
          <li><span><strong><?=h($history['from'])?> → <?=h($history['to'])?></strong>
            <small><?=h($history['at'])?></small></span>
            <span class="updater-tag"><?=h(['complete'=>'Concluída','rolled_back'=>'Restaurada','prepared'=>'Preparada','applying'=>'Em execução','recovery_needed'=>'Recuperação pendente','preparing'=>'Preparando'][$history['status']]??'Indefinido')?></span></li>
        <?php endforeach; ?>
      </ul>
      <p class="updater-hint">Histórico somente leitura. A recuperação continua limitada à última operação.</p>
    </details>
  <?php endif; ?>
</section>

<script src="/assets/js/updater-dialogs.js?v=<?=h(igua_updater_version())?>" defer></script>
<script src="/assets/js/updater-progress.js?v=<?=h(igua_updater_version())?>" defer></script>
