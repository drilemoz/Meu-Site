<?php
require_once __DIR__.'/../../home_news.php';
$allHomeCats=isset($db)?$db->query('SELECT id,name,slug FROM categories ORDER BY sort_order,name,id')->fetchAll(PDO::FETCH_ASSOC):[];
$homeCategories=igua_portal_active_categories($allHomeCats,setting('portal_active_categories',''));
$rawPortal=setting('portal_options','');$portal=igua_portal_options($rawPortal);
$homePrefs=igua_portal_home_prefs($homeCategories,setting('home_category_ids',''),setting('home_region_category',''),$rawPortal);
$blockNames=['featured'=>'Destaque','bus'=>'Próximo ônibus','recent'=>'Últimas notícias','sections'=>'Seções de categorias','popular'=>'Mais lidas'];
?>
<section class="home-settings" aria-labelledby="home-settings-heading">
<h2 id="home-settings-heading">Organização do portal</h2><p class="muted">Ative apenas o que deseja mostrar. Seções vazias ficam ocultas, sem repetir notícias. A primeira posição é 1; empates seguem a ordem atual.</p>
<input type="hidden" name="home_preferences_present" value="1"><input type="hidden" name="portal_options_present" value="1">
<?php foreach($portal['order'] as $i=>$key): ?><div class="portal-setting-row"><label class="checkbox-line"><input type="checkbox" name="portal_<?=h($key)?>" value="1" <?=$portal[$key]?'checked':''?>> <?=h($blockNames[$key])?></label><label>Posição <input type="number" min="1" max="5" name="portal_order_<?=h($key)?>" value="<?=$i+1?>" required></label></div><?php endforeach; ?>
<div class="form-grid"><label>Últimas notícias (quantidade)<input type="number" name="portal_recent_count" min="1" max="8" required value="<?=$portal['recent_count']?>"></label><label>Notícias por seção<input type="number" name="portal_section_count" min="1" max="3" required value="<?=$portal['section_count']?>"></label><label>Idade máxima em dias (exceto destaque fixo)<input type="number" name="portal_max_age" min="1" max="90" required value="<?=$portal['max_age']?>"></label></div>
<label class="checkbox-line"><input type="checkbox" name="portal_labels" value="1" <?=$portal['labels']?'checked':''?>> Mostrar nome da categoria nos cartões da página inicial</label>
<fieldset class="home-settings-categories"><legend>Categorias com seção própria</legend><?php foreach($homeCategories as $c):?><label><input type="checkbox" name="home_category_ids[]" value="<?=h($c['id'])?>" <?=in_array((int)$c['id'],$homePrefs['ids'],true)?'checked':''?>> <?=h($c['name'])?></label><?php endforeach;?></fieldset>
<label>Seção regional (opcional)<select name="home_region_category"><option value="none">Não exibir separadamente</option><?php foreach($homeCategories as $c):?><option value="<?=h($c['id'])?>" <?=$homePrefs['region']===(int)$c['id']?'selected':''?>><?=h($c['name'])?></option><?php endforeach;?></select></label>
<a href="<?=h(admin_link('categories'))?>">Gerenciar categorias e aplicar modelo local →</a>
<h3>Navegação e identidade visual</h3><p class="muted">Ônibus também é ocultado dos menus ao desativar seu bloco. Os endereços antigos continuam acessíveis.</p>
<div class="form-grid"><?php foreach(['nav_local'=>'Atalho local','nav_events'=>'Segundo atalho'] as $key=>$title):?><label><?=h($title)?><select name="portal_<?=h($key)?>"><option value="auto" <?=$portal[$key]==='auto'?'selected':''?>>Automático</option><option value="none" <?=$portal[$key]==='none'?'selected':''?>>Ocultar</option><?php foreach($allHomeCats as $c):?><option value="<?=h($c['id'])?>" <?=$portal[$key]===(string)$c['id']?'selected':''?>><?=h($c['name'])?></option><?php endforeach;?></select></label><?php endforeach;?>
<label>Texto acima do nome<input name="portal_heading" maxlength="80" value="<?=h($portal['heading'])?>"></label><label>Cor principal<input type="color" name="portal_accent" value="<?=h($portal['accent'])?>"></label><label class="full">Logotipo (caminho na biblioteca)<input name="portal_logo" maxlength="600" placeholder="/uploads/minha-logo.png" value="<?=h($portal['logo'])?>"><small>Vazio mantém o desenho padrão. Envie a imagem em Mídias e copie o caminho.</small></label></div>
</section>
