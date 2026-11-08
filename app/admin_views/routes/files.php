<?php
declare(strict_types=1);
// Exploração de leitura da raiz do site. Somente uploads/arquivos e ads/robots aceitam gravação.
$base=admin_link('files');
$folder=isset($_GET['folder'])&&is_string($_GET['folder'])?$_GET['folder']:'';
$items=[];$truncated=false;$browseError='';$page=isset($_GET['page'])?max(1,min(50,(int)$_GET['page'])):1;$pages=1;$total=0;
try{
    $folder=igua_files_project_relative($folder);
    $listing=igua_files_project_list($folder,$page);
    $items=$listing['items'];$truncated=$listing['truncated'];$page=$listing['page'];$pages=$listing['pages'];$total=$listing['total'];
}catch(Throwable $e){$browseError=$e->getMessage();$folder='';$listing=igua_files_project_list('');$items=$listing['items'];$truncated=$listing['truncated'];$page=1;$pages=$listing['pages'];$total=$listing['total'];}
$encodedFolder=rawurlencode($folder);
$crumbs=$folder===''?[]:explode('/',$folder);
$managed=igua_files_project_is_managed($folder);
$clipboard=igua_fm_clipboard_info($_SESSION);
$zipReady=class_exists('ZipArchive');
$selected=isset($_GET['view'])&&is_string($_GET['view'])?$_GET['view']:'';
$editing=isset($_GET['edit'])&&is_string($_GET['edit'])?$_GET['edit']:'';
if($editing!=='')$selected=$editing;
$selectedPath=null;$selectedContent=null;$selectedError='';$selectedHash='';$canEdit=false;$selectedSize=null;$isImage=false;
if($selected!==''){
    try{
        $selected=igua_files_project_relative($selected);
        $selectedPath=igua_files_project_resolve($selected,false);
        $selectedSize=filesize($selectedPath);
        $canEdit=igua_files_project_editable($selected);
        $isImage=str_starts_with($selected,'public/') && in_array(igua_files_extension($selected),['jpg','jpeg','png','webp','gif'],true);
        if(igua_files_project_text_previewable($selected)){
            try{
                $selectedContent=igua_files_text_read($selectedPath);
                $selectedHash=hash_file('sha256',$selectedPath);
            }catch(InvalidArgumentException $e){$selectedError=$e->getMessage();}
        }
        if($editing!=='' && (!$canEdit || $selectedContent===null))throw new InvalidArgumentException('Este arquivo é somente leitura.');
    }catch(Throwable $e){$selectedError=$e->getMessage();$selectedPath=null;$selectedContent=null;}
}
?>
<div class="files-manager">
 <section class="panel files-explorer" aria-labelledby="files-location-heading">
  <div class="files-explorer-head">
   <div><h2 id="files-location-heading">📁 Arquivos do projeto</h2><small>Raiz do site · acesso do administrador</small></div>
   <a class="btn outline small" href="<?=h($base)?>" title="Voltar para a raiz do projeto">⌂ Raiz</a>
  </div>
  <nav class="files-crumbs" aria-label="Caminho atual">
   <a href="<?=h($base)?>">/ site</a>
   <?php $current='';foreach($crumbs as $crumb):$current=($current===''?'':$current.'/').$crumb; ?>
    <span aria-hidden="true">›</span><a href="<?=h($base.'&folder='.rawurlencode($current))?>"><?=h($crumb)?></a>
   <?php endforeach; ?>
  </nav>
  <?php if($browseError!==''): ?><p class="alert error"><?=h($browseError)?></p><?php endif; ?>
  <?php if($selected!==''): ?>
   <div class="files-detail">
    <div class="files-detail-head">
     <a class="btn outline small" href="<?=h($base.'&folder='.$encodedFolder)?>">← Voltar aos arquivos</a>
     <?php if($selectedPath!==null): ?><a class="btn outline small" href="<?=h(admin_link('files_download').'&file='.rawurlencode($selected))?>">↓ Baixar</a><?php endif; ?>
    </div>
    <h3>📄 <?=h(basename($selected))?></h3>
    <?php if($selectedPath===null): ?><p class="alert error"><?=h($selectedError)?></p>
    <?php else: ?>
     <p class="files-meta"><?=h($selected)?> · <?=h(number_format((float)$selectedSize/1024,1,',','.'))?> KiB<?= $canEdit?' · Editável':' · Somente leitura' ?> · Permissões <?=h(igua_fm_permissions($selected))?></p>
     <?php if($editing!=='' && $canEdit && $selectedContent!==null): ?>
      <form action="<?=h(admin_link('files_save'))?>" method="post" class="files-edit-form">
       <?=csrf_field()?><input type="hidden" name="operation" value="save">
       <input type="hidden" name="scope" value="<?=igua_files_project_is_special($selected)?'special':'managed'?>">
       <input type="hidden" name="file" value="<?=h(igua_files_project_is_special($selected)?basename($selected):$selected)?>">
       <input type="hidden" name="folder" value="<?=h($folder)?>">
       <input type="hidden" name="sha256" value="<?=h($selectedHash)?>">
       <label for="files-content">Conteúdo UTF-8 (até 128 KiB)</label>
       <textarea id="files-content" name="content" rows="14" spellcheck="false" maxlength="131072"><?=h($selectedContent)?></textarea>
       <div class="files-actions"><button class="btn primary" type="submit">Salvar alterações</button><a class="btn outline" href="<?=h($base.'&folder='.$encodedFolder.'&view='.rawurlencode($selected))?>">Cancelar</a></div>
      </form>
     <?php else: ?>
      <?php if($selectedContent!==null): ?><pre class="files-code" tabindex="0"><code><?=h($selectedContent)?></code></pre>
      <?php elseif($isImage):
        $src='/'.implode('/',array_map('rawurlencode',explode('/',substr($selected,strlen('public/'))))); ?>
        <img class="files-image-preview" src="<?=h($src)?>" alt="Prévia de <?=h(basename($selected))?>" loading="lazy">
      <?php else: ?><p class="files-hint"><?=h($selectedError!==''?$selectedError:'Não há prévia deste formato. Use Baixar para abrir o arquivo no dispositivo.')?></p><?php endif; ?>
      <?php if($canEdit && $selectedContent!==null): ?><a class="btn primary" href="<?=h($base.'&folder='.$encodedFolder.'&edit='.rawurlencode($selected))?>">Editar arquivo</a><?php endif; ?>
     <?php endif; ?>
    <?php endif; ?>
   </div>
  <?php else: ?>
   <div class="files-browser-toolbar">
    <label for="files-filter" class="files-filter-label">Pesquisar nesta pasta</label>
    <input id="files-filter" type="search" placeholder="Buscar arquivos ou pastas..." autocomplete="off">
   </div>
   <div class="files-status-card <?= $managed?'is-managed':'is-readonly' ?>">
    <div class="files-status-copy">
     <strong><?= $managed?'Pasta com edição liberada':'Explorador do projeto' ?></strong>
     <small><?= $managed?'Aqui você pode enviar, mover, compactar, excluir e ajustar permissões dos arquivos públicos desta pasta.':'Nesta área você navega pelo projeto, vê o código em leitura e baixa seleções em ZIP. Alterações continuam restritas à pasta de uploads.' ?></small>
    </div>
    <?php if(!$managed): ?><a class="btn outline small files-jump-link" href="<?=h($base.'&folder='.rawurlencode('public/uploads/arquivos'))?>">↑ Ir para uploads</a><?php endif; ?>
   </div>
   <?php if(!$managed): ?>
    <form id="files-bulk" method="post" action="<?=h(admin_link('files_save'))?>" class="files-readonly-tools files-operations">
     <?=csrf_field()?><input type="hidden" name="folder" value="<?=h($folder)?>">
     <div class="files-selection-head"><label class="files-check-all"><input id="files-select-all" type="checkbox"> Selecionar até 20</label><span id="files-selected-count">0 selecionados</span></div>
     <div class="files-bulk-buttons files-bulk-buttons-compact">
      <button class="btn outline small" type="submit" name="operation" value="export_zip" data-files-needs-selection <?=$zipReady?'':'disabled title="ZipArchive indisponível na hospedagem"'?>>↓ Baixar seleção em ZIP</button>
     </div>
     <p class="files-operations-note">Áreas protegidas e arquivos privados serão omitidos da exportação. Para enviar ou editar arquivos públicos, use a pasta de uploads.</p>
    </form>
   <?php endif; ?>
   <?php if($managed): ?>
    <div class="files-operations" aria-label="Ações de arquivos">
     <div class="files-operations-head">
      <strong>Comandos desta pasta</strong>
      <small>Selecione até 20 itens por operação. ZIP, copiar, mover e permissões atuam apenas nesta área pública gerenciada.</small>
     </div>
     <form id="files-bulk" method="post" action="<?=h(admin_link('files_save'))?>" class="files-bulk-form">
      <?=csrf_field()?><input type="hidden" name="folder" value="<?=h($folder)?>">
      <div class="files-selection-head"><label class="files-check-all"><input id="files-select-all" type="checkbox"> Selecionar até 20</label><span id="files-selected-count" aria-live="polite">0 selecionados</span></div>
      <div class="files-bulk-buttons files-bulk-buttons-grid">
       <button class="btn outline small" type="submit" name="operation" value="copy" data-files-needs-selection>⧉ Copiar</button>
       <button class="btn outline small" type="submit" name="operation" value="cut" data-files-needs-selection>✂ Recortar</button>
       <button class="btn outline small" type="submit" name="operation" value="zip" data-files-needs-selection <?=$zipReady?'':'disabled title="ZipArchive indisponível na hospedagem"'?>>▣ Compactar ZIP</button>
       <label class="files-inline-field">Nome do ZIP <input type="text" name="name" maxlength="100" value="arquivos.zip" aria-label="Nome do arquivo ZIP"></label>
      </div>
     </form>
     <details class="files-danger-bulk"><summary>Excluir selecionados</summary>
      <form method="post" action="<?=h(admin_link('files_save'))?>" id="files-bulk-delete" data-confirm="Excluir permanentemente os arquivos e pastas selecionados? Não há lixeira nesta versão.">
       <?=csrf_field()?><input type="hidden" name="folder" value="<?=h($folder)?>"><input type="hidden" name="operation" value="bulk_delete">
       <label>Digite EXCLUIR para confirmar <input name="confirm" required pattern="EXCLUIR" autocomplete="off" placeholder="EXCLUIR"></label>
       <button class="btn danger small" type="submit">Excluir definitivamente</button>
      </form>
     </details>
    </div>
    <?php if($clipboard): ?>
     <div class="files-clipboard"><strong>📋 <?=count($clipboard['items'])?> item(ns) para <?=($clipboard['mode']==='cut'?'mover':'copiar')?></strong>
      <form method="post" action="<?=h(admin_link('files_save'))?>"><?=csrf_field()?><input type="hidden" name="folder" value="<?=h($folder)?>"><button class="btn primary small" name="operation" value="paste" type="submit">Colar nesta pasta</button><button class="btn outline small" name="operation" value="clipboard_clear" type="submit">Limpar</button></form>
     </div>
    <?php endif; ?>
    <details class="files-create-menu">
     <summary>＋ Novo arquivo ou pasta</summary>
     <div class="files-toolbar">
      <form method="post" action="<?=h(admin_link('files_save'))?>" enctype="multipart/form-data">
       <?=csrf_field()?><input type="hidden" name="operation" value="upload"><input type="hidden" name="folder" value="<?=h($folder)?>">
       <label for="files-upload">Enviar arquivo (até 8 MiB)</label><input id="files-upload" name="file" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,.txt,.csv,.zip" required>
       <button class="btn primary" type="submit">Enviar</button>
      </form>
      <form method="post" action="<?=h(admin_link('files_save'))?>">
       <?=csrf_field()?><input type="hidden" name="operation" value="mkdir"><input type="hidden" name="folder" value="<?=h($folder)?>">
       <label for="files-folder-name">Nome da nova pasta</label><input id="files-folder-name" name="name" maxlength="100" required pattern="[A-Za-z0-9][A-Za-z0-9_-]*" placeholder="nova-pasta">
       <button class="btn outline" type="submit">Criar pasta</button>
      </form>
     </div>
     <p class="files-hint">JPG, PNG, WebP, PDF, TXT, CSV e ZIP validado, até 8 MiB. Os arquivos desta pasta podem ser públicos por URL; não envie dados privados.</p>
    </details>
   <?php endif; ?>
   <div class="files-list" id="files-list">
    <?php if($folder!==''): $parent=dirname($folder);if($parent==='.')$parent=''; ?>
     <a class="files-back" href="<?=h($base.'&folder='.rawurlencode($parent))?>">← Pasta anterior</a>
    <?php endif; ?>
    <?php if(!$items): ?><p class="files-empty">Pasta vazia ou sem arquivos disponíveis.</p><?php endif; ?>
    <?php foreach($items as $item):
      $rel=($folder===''?'':$folder.'/').$item['name'];
      $isDir=$item['dir'];$blocked=$item['blocked'];
      $writable=igua_files_project_is_managed($rel) && !$blocked;
    ?>
     <div class="files-row" data-file-name="<?=h(strtolower($item['name']))?>">
      <?php if(!$blocked && $rel!=='public/uploads/arquivos'): ?><input class="files-select" type="checkbox" name="selected[]" value="<?=h($rel)?>" form="files-bulk" aria-label="Selecionar <?=h($item['name'])?>"><?php endif; ?>
      <span class="files-icon" aria-hidden="true"><?=$blocked?'🔒':($isDir?'📁':'📄')?></span>
      <?php if($blocked): ?>
       <div class="files-main"><strong><?=h($item['name'])?></strong><small>Protegido · acesso bloqueado</small></div>
      <?php else: ?>
       <a class="files-main files-entry-link" href="<?=h($base.'&folder='.rawurlencode($isDir?$rel:$folder).($isDir?'':'&view='.rawurlencode($rel)))?>">
        <strong><?=h($item['name'])?></strong><small><?=$isDir?'Pasta':h(number_format(((float)$item['size'])/1024,1,',','.').' KiB')?><?php if($item['modified']!==null): ?> · <?=h(date('d/m/Y H:i',(int)$item['modified']))?><?php endif; ?> · <?=h(igua_fm_permissions($rel))?></small>
       </a>
       <div class="files-row-actions">
        <?php if($isDir): ?><a class="btn outline small" href="<?=h($base.'&folder='.rawurlencode($rel))?>" aria-label="Abrir <?=h($item['name'])?>">Abrir</a>
        <?php else: ?><a class="btn outline small" href="<?=h($base.'&folder='.$encodedFolder.'&view='.rawurlencode($rel))?>">Ver</a><?php endif; ?>
        <?php if($writable && !($isDir && $rel==='public/uploads/arquivos')): ?>
         <details class="files-options"><summary aria-label="Comandos de <?=h($item['name'])?>">⋮</summary><div class="files-options-content">
          <strong class="files-options-title">Comandos · <?=h($item['name'])?></strong>
          <form method="post" action="<?=h(admin_link('files_save'))?>">
           <?=csrf_field()?><input type="hidden" name="folder" value="<?=h($folder)?>"><input type="hidden" name="selected[]" value="<?=h($rel)?>">
           <div class="files-option-pair"><button class="btn outline small" name="operation" value="copy">⧉ Copiar</button><button class="btn outline small" name="operation" value="cut">✂ Recortar</button></div>
          </form>
          <form method="post" action="<?=h(admin_link('files_save'))?>">
           <?=csrf_field()?><input type="hidden" name="folder" value="<?=h($folder)?>"><input type="hidden" name="selected[]" value="<?=h($rel)?>">
           <label>Compactar como<input name="name" type="text" value="<?=h(pathinfo($item['name'], PATHINFO_FILENAME).'-compactado.zip')?>" maxlength="100" required></label>
           <button class="btn outline small" type="submit" name="operation" value="zip" <?=$zipReady?'':'disabled'?>>▣ Compactar ZIP</button>
          </form>
          <?php if(!$isDir && igua_files_extension($item['name'])==='zip'): ?>
           <form method="post" action="<?=h(admin_link('files_save'))?>" data-confirm="Extrair ZIP em uma pasta nova, sem sobrescrever arquivos?">
            <?=csrf_field()?><input type="hidden" name="folder" value="<?=h($folder)?>"><input type="hidden" name="file" value="<?=h($rel)?>"><button class="btn outline small" type="submit" name="operation" value="unzip" <?=$zipReady?'':'disabled'?>>↧ Descompactar</button>
           </form>
          <?php endif; ?>
          <form method="post" action="<?=h(admin_link('files_save'))?>">
           <?=csrf_field()?><input type="hidden" name="operation" value="rename"><input type="hidden" name="folder" value="<?=h($folder)?>"><input type="hidden" name="file" value="<?=h($rel)?>">
           <label>Renomear<input name="name" type="text" value="<?=h($item['name'])?>" maxlength="100" required></label><button class="btn outline small" type="submit">Renomear</button>
          </form>
          <form method="post" action="<?=h(admin_link('files_save'))?>">
           <?=csrf_field()?><input type="hidden" name="operation" value="chmod"><input type="hidden" name="folder" value="<?=h($folder)?>"><input type="hidden" name="file" value="<?=h($rel)?>">
           <label>Permissões atuais: <?=h(igua_fm_permissions($rel))?>
            <select name="mode"><?php foreach(($isDir?['0755','0700']:['0644','0600']) as $mode): ?><option value="<?=h($mode)?>" <?=igua_fm_permissions($rel)===$mode?'selected':''?>><?=h($mode)?></option><?php endforeach; ?></select>
           </label><button class="btn outline small" type="submit">Aplicar permissões</button>
          </form>
          <form method="post" action="<?=h(admin_link('files_save'))?>" data-confirm="Excluir <?=h($item['name'])?> definitivamente? Pastas devem estar vazias nessa ação.">
           <?=csrf_field()?><input type="hidden" name="operation" value="delete"><input type="hidden" name="folder" value="<?=h($folder)?>"><input type="hidden" name="file" value="<?=h($rel)?>"><button class="btn danger small" type="submit">Excluir</button>
          </form>
         </div></details>
        <?php endif; ?>
       </div>
      <?php endif; ?>
     </div>
    <?php endforeach; ?>
   </div>
   <?php if($pages>1): ?><nav class="files-pagination" aria-label="Páginas de arquivos">
    <?php if($page>1): ?><a class="btn outline small" href="<?=h($base.'&folder='.$encodedFolder.'&page='.($page-1))?>">← Anterior</a><?php endif; ?>
    <span>Página <?=h((string)$page)?> de <?=h((string)$pages)?> · <?=h((string)$total)?> itens</span>
    <?php if($page<$pages): ?><a class="btn outline small" href="<?=h($base.'&folder='.$encodedFolder.'&page='.($page+1))?>">Próxima →</a><?php endif; ?>
   </nav><?php endif; ?>
   <?php if($truncated): ?><p class="files-hint">Mais de 6.000 entradas nesta pasta. A listagem foi limitada para proteger o desempenho; use FTP para diretórios muito grandes.</p><?php endif; ?>
   <p class="files-footer-note">A leitura e o download abrangem arquivos acessíveis do projeto. Copiar, mover, compactar, extrair, excluir, enviar e alterar permissões funcionam apenas em <code>public/uploads/arquivos</code>. Pastas privadas, credenciais e código de produção não podem ser alterados por esta ferramenta. Os arquivos gerenciados podem ser públicos.</p>
  <?php endif; ?>
 </section>
</div>
<script defer src="/assets/js/admin-files.js?v=<?=h(igua_updater_version())?>"></script>
