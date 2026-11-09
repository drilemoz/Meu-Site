<?php declare(strict_types=1); ?>
<div class="backup-center">
 <section class="panel backup-section">
  <div class="backup-section-head"><div><h2>Recuperar o portal</h2><p class="muted">Selecione o tipo de arquivo que deseja restaurar.</p></div><details class="compact-help backup-top-help"><summary aria-label="Ajuda sobre restauração" title="Ajuda">?</summary><div class="compact-help-content"><strong>Antes de restaurar</strong><p>O arquivo JSON/GZIP recupera o banco, sem fotos. O ZIP completo restaura também as mídias referenciadas. A restauração pode substituir registros e não possui rollback integral de banco e arquivos. Guarde uma cópia externa antes da operação. O instalador de migração para hospedagem nova é o <code>/setup.php</code>, não esta tela.</p></div></details></div>
  <div class="backup-restore-banner"><strong>Atenção: esta ação substitui dados do portal atual.</strong><span>Confira o arquivo e mantenha um backup externo antes de continuar.</span></div>
  <div class="backup-restore-types">
   <details class="backup-restore-option"><summary><span aria-hidden="true">▣</span><span><strong>Restaurar banco de dados</strong><small>JSON ou JSON.GZ · não restaura imagens</small></span><span aria-hidden="true">⌄</span></summary>
    <form method="post" action="<?=h(admin_link('restore_backup'))?>" enctype="multipart/form-data" class="backup-restore-form"><?=csrf_field()?>
     <label>Arquivo do backup<input type="file" name="backup" accept=".json,.gz" required></label>
     <label>Senha administrativa<input type="password" name="confirm_password" autocomplete="current-password" required></label>
     <button class="btn danger" type="submit" data-confirm="Substituir todo o banco de dados pelo conteúdo do arquivo?">Restaurar dados</button>
    </form>
   </details>
   <details class="backup-restore-option"><summary><span aria-hidden="true">▤</span><span><strong>Restaurar pacote completo</strong><small>ZIP · banco e mídias</small></span><span aria-hidden="true">⌄</span></summary>
    <?php if(class_exists('ZipArchive')): ?><form method="post" action="<?=h(admin_link('restore_bundle'))?>" enctype="multipart/form-data" class="backup-restore-form"><?=csrf_field()?>
     <label>Pacote ZIP<input type="file" name="bundle" accept=".zip" required></label><label>Senha administrativa<input type="password" name="confirm_password" autocomplete="current-password" required></label>
     <label class="checkbox-line"><input type="checkbox" name="confirm_overwrite" value="1" required> Confirmo a substituição dos dados atuais.</label>
     <button class="btn danger" type="submit" data-confirm="Restaurar ZIP, mídias e substituir o banco?">Restaurar pacote ZIP</button>
    </form><?php else: ?><p class="backup-policy-note">A extensão PHP ZIP está indisponível nesta hospedagem.</p><?php endif; ?>
   </details>
  </div>
  <a class="backup-back-link" href="<?=h(admin_link('backups'))?>">← Voltar aos backups</a>
 </section>
</div>
