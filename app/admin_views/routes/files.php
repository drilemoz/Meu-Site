<?php
declare(strict_types=1);
$folder=isset($_GET['folder'])&&is_string($_GET['folder'])?(string)$_GET['folder']:'';
try{$folder=igua_files_relative($folder);$items=igua_files_list($folder);}catch(Throwable $e){$folder='';$items=[];echo '<div class="alert error">'.h($e->getMessage()).'</div>';}
$encodedFolder=rawurlencode($folder);
$base=admin_link('files');
$breadcrumbs=$folder===''?[]:explode('/',$folder);
$editScope=($_GET['scope']??'')==='special'?'special':'managed';
$editFile=isset($_GET['edit'])&&is_string($_GET['edit'])?$_GET['edit']:'';
$editPath=null;$editText='';$editHash='';$editError='';
if($editFile!==''){
 try{
    $editPath=$editScope==='special'?igua_files_special($editFile):igua_files_resolve($editFile,false);
    if($editPath===null || ($editScope==='managed' && !in_array(igua_files_extension($editFile),['txt','csv'],true)))throw new InvalidArgumentException('Este arquivo não pode ser editado.');
    $editText=is_file($editPath)?igua_files_text_read($editPath):'';
    $editHash=is_file($editPath)?hash_file('sha256',$editPath):'missing';
 }catch(Throwable $e){$editError=$e->getMessage();$editPath=null;}
}
?>
<div class="files-manager">
 <div class="panel files-intro">
  <h2>📁 Gerenciador de Arquivos</h2>
  <p>Gerencie documentos e imagens na pasta pública <code>/uploads/arquivos/</code>, pelo celular. Arquivos de sistema, credenciais e códigos PHP não ficam disponíveis.</p>
  <div class="files-intro-links"><a class="btn outline" href="<?=h(admin_link('media'))?>">🖼️ Biblioteca de mídias</a><a class="btn outline" href="<?=h(admin_link('backups'))?>">💾 Backups</a></div>
 </div>
 <div class="panel files-special">
  <h2>Arquivos essenciais do site</h2>
  <p class="muted">Editáveis com cuidado. Alterações em <code>robots.txt</code> afetam robôs de busca; <code>ads.txt</code> identifica vendedores de anúncios.</p>
  <?php foreach(['ads.txt','robots.txt'] as $name): ?>
   <div class="files-row"><span class="files-icon">📄</span><div class="files-main"><strong><?=h($name)?></strong><small>/<?=h($name)?><?=is_file(IGUA_PUBLIC.'/'.$name)?' · Publicado':' · Ainda não criado'?></small></div><a class="btn outline small" href="<?=h($base.'&scope=special&edit='.rawurlencode($name).'&folder='.$encodedFolder)?>">Editar</a></div>
  <?php endforeach; ?>
 </div>
 <?php if($editFile!==''): ?>
 <section class="panel files-editor" id="editor"><h2>Editar <?=h($editFile)?></h2>
  <?php if($editPath!==null): ?><form action="<?=h(admin_link('files_save'))?>" method="post" class="files-form">
   <?=csrf_field()?><input type="hidden" name="operation" value="save"><input type="hidden" name="scope" value="<?=h($editScope)?>"><input type="hidden" name="file" value="<?=h($editFile)?>"><input type="hidden" name="folder" value="<?=h($folder)?>"><input type="hidden" name="sha256" value="<?=h($editHash)?>">
   <label for="files-content">Conteúdo UTF-8 (máximo 128 KiB)</label><textarea id="files-content" name="content" rows="12" spellcheck="false" maxlength="131072"><?=h($editText)?></textarea>
   <div class="files-actions"><button class="btn primary" type="submit">Salvar alterações</button><a class="btn outline" href="<?=h($base.'&folder='.$encodedFolder)?>">Cancelar</a></div>
  </form><?php else: ?><p class="alert error"><?=h($editError)?></p><?php endif; ?>
 </section>
 <?php endif; ?>
 <section class="panel files-browser">
  <h2>Meus arquivos</h2>
  <nav class="files-crumbs" aria-label="Caminho atual"><a href="<?=h($base)?>">📂 Arquivos</a><?php $current='';foreach($breadcrumbs as $crumb):$current=($current===''?'':$current.'/').$crumb;?><span>›</span><a href="<?=h($base.'&folder='.rawurlencode($current))?>"><?=h($crumb)?></a><?php endforeach; ?></nav>
  <div class="files-toolbar">
   <form method="post" action="<?=h(admin_link('files_save'))?>" enctype="multipart/form-data" class="files-upload">
    <?=csrf_field()?><input type="hidden" name="operation" value="upload"><input type="hidden" name="folder" value="<?=h($folder)?>">
    <label for="files-upload">Enviar arquivo</label><input id="files-upload" name="file" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,.txt,.csv" required><button class="btn primary" type="submit">Enviar</button>
   </form>
   <form method="post" action="<?=h(admin_link('files_save'))?>" class="files-mkdir">
    <?=csrf_field()?><input type="hidden" name="operation" value="mkdir"><input type="hidden" name="folder" value="<?=h($folder)?>">
    <label for="files-folder-name">Nova pasta</label><input id="files-folder-name" name="name" maxlength="100" required pattern="[A-Za-z0-9][A-Za-z0-9_-]*" placeholder="exemplo-documentos"><button class="btn outline" type="submit">Criar pasta</button>
   </form>
  </div>
  <label for="files-filter" class="files-filter-label">Filtrar esta pasta</label><input id="files-filter" type="search" placeholder="Pesquisar nome..." autocomplete="off">
  <div class="files-list" id="files-list">
    <?php if($folder!==''): $parent=dirname($folder);if($parent==='.')$parent='';?><a class="files-back" href="<?=h($base.'&folder='.rawurlencode($parent))?>">← Voltar à pasta anterior</a><?php endif; ?>
    <?php if(!$items): ?><p class="muted files-empty">Esta pasta está vazia.</p><?php endif; ?>
    <?php foreach($items as $item): $relative=($folder===''?'':$folder.'/').$item['name'];$isDir=$item['dir']; ?>
     <div class="files-row" data-file-name="<?=h(strtolower($item['name']))?>">
      <span class="files-icon" aria-hidden="true"><?=$isDir?'📁':'📄'?></span>
      <div class="files-main"><strong><?=h($item['name'])?></strong><small><?=$isDir?'Pasta':h(number_format(((int)$item['size'])/1024,1,',','.').' KiB')?> · <?=h(date('d/m/Y H:i',(int)$item['modified']))?></small></div>
      <div class="files-row-actions">
       <?php if($isDir): ?><a class="btn outline small" href="<?=h($base.'&folder='.rawurlencode($relative))?>">Abrir</a>
       <?php else: ?><a class="btn outline small" href="<?=h(admin_link('files_download').'&file='.rawurlencode($relative))?>">Baixar</a><?php if(in_array(igua_files_extension($item['name']),['txt','csv'],true)): ?><a class="btn outline small" href="<?=h($base.'&folder='.$encodedFolder.'&edit='.rawurlencode($relative).'#editor')?>">Editar</a><?php endif; ?><?php endif; ?>
       <details class="files-options"><summary aria-label="Opções de <?=h($item['name'])?>">⋮</summary><div class="files-options-content">
        <form method="post" action="<?=h(admin_link('files_save'))?>" class="files-rename">
          <?=csrf_field()?><input type="hidden" name="operation" value="rename"><input type="hidden" name="folder" value="<?=h($folder)?>"><input type="hidden" name="file" value="<?=h($relative)?>"><label>Renomear<input type="text" name="name" value="<?=h($item['name'])?>" maxlength="100" required></label><button class="btn outline small" type="submit">Renomear</button>
        </form>
        <form method="post" action="<?=h(admin_link('files_save'))?>" data-confirm="Excluir definitivamente <?=h($item['name'])?>? Esta ação não poderá ser desfeita.">
         <?=csrf_field()?><input type="hidden" name="operation" value="delete"><input type="hidden" name="folder" value="<?=h($folder)?>"><input type="hidden" name="file" value="<?=h($relative)?>"><button class="btn danger small" type="submit">Excluir definitivamente</button>
        </form>
       </div></details>
      </div>
     </div>
    <?php endforeach; ?>
  </div>
  <p class="files-footnote">Extensões aceitas: JPG, PNG, WebP, PDF, TXT e CSV. Limite: 8 MiB por arquivo. Pastas devem estar vazias antes de excluir. Não envie senhas, informações privadas ou arquivos executáveis; tudo nesta pasta pode ser acessado por URL pública.</p>
 </section>
</div>
<script defer src="/assets/js/admin-files.js?v=<?=h(igua_updater_version())?>"></script>
