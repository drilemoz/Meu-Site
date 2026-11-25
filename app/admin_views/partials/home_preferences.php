<?php
require_once __DIR__.'/../../home_news.php';
$homeCategories=isset($db)?$db->query('SELECT id,name,slug FROM categories ORDER BY sort_order,name,id')->fetchAll(PDO::FETCH_ASSOC):[];
$homePrefs=igua_home_preferences($homeCategories,setting('home_category_ids',''),setting('home_region_category',''));
?>
<section class="home-settings" aria-labelledby="home-settings-heading">
  <h2 id="home-settings-heading">Seções da página inicial</h2>
  <p class="muted">Selecione as editorias que terão uma seção própria. Cada notícia aparece uma única vez na página inicial; seções sem matérias disponíveis ficam ocultas.</p>
  <input type="hidden" name="home_preferences_present" value="1">
  <fieldset class="home-settings-categories"><legend>Editorias</legend>
    <?php foreach ($homeCategories as $homeCat): ?>
      <label><input type="checkbox" name="home_category_ids[]" value="<?=h($homeCat['id'])?>" <?=in_array((int)$homeCat['id'],$homePrefs['ids'],true)?'checked':''?>> <?=h($homeCat['name'])?></label>
    <?php endforeach; ?>
    <?php if (!$homeCategories): ?><p class="muted">Nenhuma categoria cadastrada.</p><?php endif; ?>
  </fieldset>
  <label class="home-region-select">Categoria de Acontece na região
    <select name="home_region_category"><option value="none" <?=$homePrefs['region']===0?'selected':''?>>Não exibir</option>
    <?php foreach ($homeCategories as $homeCat): ?><option value="<?=h($homeCat['id'])?>" <?=$homePrefs['region']===(int)$homeCat['id']?'selected':''?>><?=h($homeCat['name'])?></option><?php endforeach; ?>
    </select>
  </label>
  <p class="muted tiny">Use uma categoria exclusiva de notícias regionais. Ela terá prioridade após Cidade e não ganhará uma segunda seção, mesmo que esteja marcada acima. As outras editorias seguem a ordem cadastrada em Categorias. Últimas notícias e Mais lidas continuam abrangendo todas as categorias.</p>
</section>
