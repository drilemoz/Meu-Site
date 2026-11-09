<?php
declare(strict_types=1);
$backupItems=list_backups();
$backupTotal=count($backupItems);
$backupBytes=array_sum(array_map(static fn(array $b)=>(int)$b['size'],$backupItems));
$policy=igua_backup_policy();$pins=igua_backup_pins();
$pending=igua_backup_prune_plan($backupItems,$policy,$pins);
$all=($_GET['all']??'')==='1';
$page=$all?max(1,min(1000,(int)($_GET['page']??1))):1;
$perPage=$all?15:5;
$pages=max(1,(int)ceil($backupTotal/$perPage));$page=min($page,$pages);
$visible=array_slice($backupItems,($page-1)*$perPage,$perPage);
$manual=count(array_filter($backupItems,static fn($b)=>str_starts_with($b['name'],'manual-')));
$readableBytes=static function(int $bytes):string{return $bytes>=1048576?number_format($bytes/1048576,1,',','.').' MB':number_format($bytes/1024,1,',','.').' KB';};
?>
<div class="backup-center">
 <div class="panel backup-section backup-section--intro">
  <div class="backup-section-head"><div><h2>Backups e migração</h2><p class="muted">Crie uma cópia ou leve o portal para outra hospedagem.</p></div>
   <details class="compact-help backup-top-help"><summary aria-label="Ajuda sobre backups" title="Ajuda">?</summary><div class="compact-help-content"><strong>Entenda as opções</strong><p><b>Somente banco de dados:</b> notícias e registros, sem fotos nem anexos. <b>Backup completo: banco + mídias:</b> fotos referenciadas, sem código. <b>Migração:</b> inclui código, instalador e dados para outra hospedagem. O ZIP não faz backup de todo o servidor ou de credenciais privadas.</p></div></details>
  </div>
  <div class="backup-choice-grid">
   <section class="backup-choice"><div class="backup-choice-label"><span aria-hidden="true">▣</span><div><strong>Banco de dados</strong><small>JSON/GZIP</small></div></div>
     <form method="post" action="<?=h(admin_link('create_backup'))?>"><?=csrf_field()?><button class="btn primary" type="submit">Gerar backup</button></form>
   </section>
   <section class="backup-choice"><div class="backup-choice-label"><span aria-hidden="true">▤</span><div><strong>Banco + mídias</strong><small>ZIP completo</small></div></div>
     <?php if(class_exists('ZipArchive')): ?><form method="post" action="<?=h(admin_link('download_bundle'))?>"><?=csrf_field()?><button class="btn outline" type="submit">Baixar ZIP</button></form>
     <?php else: ?><small class="backup-muted">Extensão ZIP indisponível.</small><?php endif; ?>
   </section>
  </div>
  <div class="backup-migration-action"><div><strong>🚚 Migrar hospedagem</strong><small>Código, instalador, banco e arquivos em um ZIP portátil.</small></div>
   <?php if(class_exists('ZipArchive')): ?><form method="post" action="<?=h(admin_link('download_migration'))?>"><?=csrf_field()?><button class="btn primary" type="submit" data-confirm="Gerar o pacote portátil? Pode demorar e exigir espaço em disco. Guarde-o em local privado.">Baixar pacote de migração ↓</button></form>
   <?php else: ?><small class="backup-muted">Ative a extensão PHP ZipArchive para gerar o pacote.</small><?php endif; ?>
  </div>
  <a class="backup-restore-link" href="<?=h(admin_link('backup_restore'))?>"><span aria-hidden="true">↺</span> Restaurar dados <span aria-hidden="true">›</span></a>
 </div>
 <section class="panel backup-section" aria-labelledby="backups-store-title">
  <div class="backup-section-head"><div><h2 id="backups-store-title">Armazenamento</h2><p class="muted">Cópias JSON/GZIP privadas do portal.</p></div>
   <details class="compact-help"><summary aria-label="Ajuda sobre armazenamento" title="Ajuda">?</summary><div class="compact-help-content">Backups manuais e preventivos não são apagados pela rotação. Arquivos fixados também são preservados. A limpeza usa somente os limites de retenção dos backups automáticos. Arquivos ZIP gerados para download são temporários.</div></details>
  </div>
  <div class="backup-metrics"><div><strong><?=h((string)$backupTotal)?></strong><span>arquivos</span></div><div><strong><?=h($readableBytes($backupBytes))?></strong><span>armazenados</span></div><div><strong><?=h((string)$manual)?></strong><span>manuais</span></div></div>
  <details class="backup-policy"><summary>⚙ Retenção e limpeza</summary>
   <div class="backup-policy-body">
    <form method="post" action="<?=h(admin_link('backup_policy_save'))?>" class="backup-policy-form"><?=csrf_field()?>
     <label>Manter diários<input type="number" name="daily" min="1" max="14" value="<?=h((string)$policy['daily'])?>" required></label>
     <label>Manter semanais<input type="number" name="weekly" min="1" max="8" value="<?=h((string)$policy['weekly'])?>" required></label>
     <button class="btn outline small" type="submit">Salvar limites</button>
    </form>
    <p class="backup-policy-note">A rotação ocorre após novos backups. Manuais, preventivos e backups protegidos permanecem intactos.</p>
    <p class="backup-policy-note"><strong><?=h((string)count($pending))?></strong> backup(s) automático(s) antigo(s) elegível(is) para limpeza agora.</p>
    <?php if($pending): ?><form method="post" action="<?=h(admin_link('backup_prune'))?>" class="backup-prune-form" data-confirm="Excluir permanentemente <?=h((string)count($pending))?> backups automáticos antigos? Esta ação não tem lixeira.">
      <?=csrf_field()?><label>Senha administrativa<input type="password" name="confirm_password" autocomplete="current-password" required></label>
      <label>Digite EXCLUIR<input type="text" name="confirm" required pattern="EXCLUIR" autocomplete="off"></label>
      <button class="btn danger small" type="submit">Limpar backups antigos</button>
     </form><?php endif; ?>
   </div>
  </details>
 </section>
 <section class="panel backup-section" aria-labelledby="backups-list-title">
  <div class="backup-section-head"><div><h2 id="backups-list-title">Backups disponíveis</h2><p class="muted"><?= $all?'Todos os backups':'5 backups mais recentes' ?> · armazenados fora da pasta pública</p></div>
   <?php if(!$all && $backupTotal>5): ?><a class="btn outline small" href="<?=h(admin_link('backups').'&all=1')?>">Ver todos</a><?php elseif($all): ?><a class="btn outline small" href="<?=h(admin_link('backups'))?>">Recentes</a><?php endif; ?>
  </div>
  <?php if(!$backupItems): ?><p class="backup-empty">Nenhum backup disponível. Use <strong>Gerar backup</strong> para criar o primeiro.</p><?php endif; ?>
  <div class="backup-file-list">
   <?php foreach($visible as $b):$name=$b['name'];$isPinned=in_array($name,$pins,true);$kind=strtok($name,'-');$kindLabel=['manual'=>'Manual','daily'=>'Diário','weekly'=>'Semanal','safety'=>'Preventivo'][$kind]??'Backup'; ?>
    <article class="backup-file-row">
     <span class="backup-file-icon" aria-hidden="true">▤</span>
     <div class="backup-file-meta"><strong><?=h($b['date'])?></strong><small><?=h($kindLabel)?> · <?=h($readableBytes((int)$b['size']))?><?= $isPinned?' · Protegido':'' ?></small><details class="backup-filename-fold"><summary>Nome do arquivo</summary><code><?=h($name)?></code></details></div>
     <div class="backup-file-actions"><a class="btn outline small" href="<?=h(admin_link('download').'&file='.rawurlencode($name))?>" aria-label="Baixar backup de <?=h($b['date'])?>">↓</a>
      <details class="backup-item-options"><summary aria-label="Mais ações para <?=h($name)?>" title="Mais ações">⋮</summary><div class="backup-item-menu">
       <form method="post" action="<?=h(admin_link('backup_pin'))?>"><?=csrf_field()?><input type="hidden" name="file" value="<?=h($name)?>"><input type="hidden" name="mode" value="<?=$isPinned?'unprotect':'protect'?>"><button class="btn outline small" type="submit"><?=$isPinned?'Desproteger':'Proteger backup'?></button></form>
       <?php if(!$isPinned): ?><details class="backup-delete-fold"><summary>Excluir backup</summary><form method="post" action="<?=h(admin_link('backup_delete'))?>" data-confirm="Excluir este backup permanentemente? Sem lixeira.">
        <?=csrf_field()?><input type="hidden" name="file" value="<?=h($name)?>"><label>Senha<input name="confirm_password" type="password" required autocomplete="current-password"></label><label>Digite EXCLUIR<input name="confirm" type="text" pattern="EXCLUIR" required autocomplete="off"></label><button type="submit" class="btn danger small">Excluir definitivamente</button></form></details><?php endif; ?>
      </div></details>
     </div>
    </article>
   <?php endforeach; ?>
  </div>
  <?php if($all && $pages>1): ?><nav class="backup-pagination" aria-label="Páginas dos backups"><?php if($page>1): ?><a class="btn outline small" href="<?=h(admin_link('backups').'&all=1&page='.($page-1))?>">← Anterior</a><?php endif; ?><span><?=h((string)$page)?> / <?=h((string)$pages)?></span><?php if($page<$pages): ?><a class="btn outline small" href="<?=h(admin_link('backups').'&all=1&page='.($page+1))?>">Próxima →</a><?php endif; ?></nav><?php endif; ?>
 </section>
</div>
