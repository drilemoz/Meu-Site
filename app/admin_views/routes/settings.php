<?php
$seoSiteName=igua_brand_name();
$seoDescription=igua_brand_description();
$seoSiteUrl=(string)setting('seo_site_url','');
$seoTitleTemplate=(string)setting('seo_title_template','%title% | %site%');
$seoExampleTitle=str_replace(['%title%','%site%'],['Últimas notícias',$seoSiteName],$seoTitleTemplate);
$seoSocialImage=(string)setting('seo_social_image','');
$seoHomeTitle=(string)setting('seo_home_title','');
$seoFavicon=(string)setting('seo_favicon_image','');
$seoPageTitles=['onibus'=>'Horários de ônibus','sobre'=>'Sobre o portal','contato'=>'Contato','anuncie'=>'Anuncie','privacidade'=>'Política de Privacidade','termos'=>'Termos de Uso'];
$seoExampleTitle=$seoHomeTitle!==''?$seoHomeTitle:$seoExampleTitle;
?>
<section class="panel form-panel" aria-labelledby="settings-identity-heading">
  <div class="compact-heading"><h2 id="settings-identity-heading">Identidade do portal</h2><details class="compact-help"><summary aria-label="Ajuda da identidade do portal">?</summary><div class="compact-help-content">Nome, frase e contato do portal. A publicação de SEO pode ser ajustada abaixo.</div></details></div>
  <form id="site-settings-form" method="post" action="<?=h(admin_link('save_settings'))?>">
    <?=csrf_field()?>
    <div class="form-grid">
      <label class="full">Nome do portal<input name="site_name" maxlength="80" minlength="2" required value="<?=h($seoSiteName)?>"></label>
      <label class="full">Frase do portal<input name="site_tagline" maxlength="200" value="<?=h(setting('site_tagline'))?>"></label>
      <label>Cidade / região<input name="city" maxlength="100" value="<?=h(setting('city'))?>"></label>
      <label>E-mail de contato<input name="contact_email" type="email" maxlength="190" value="<?=h(setting('contact_email'))?>"></label>
    </div>

    <section class="seo-settings seo-portal-center" id="seo-portal-center" aria-labelledby="seo-portal-title">
      <div class="seo-portal-heading"><div><h2 id="seo-portal-title">SEO e compartilhamento</h2><p class="muted">Veja a apresentação do portal e faça ajustes sem sair da página.</p></div><a class="btn outline small" href="<?=h(admin_link('sitemap'))?>">⌕ Sitemap</a></div>

      <div class="seo-portal-health" aria-live="polite">
        <div class="seo-portal-health-head"><strong>Diagnóstico editorial</strong><span id="seo-portal-status" class="seo-portal-state">Não verificado</span></div>
        <p id="seo-portal-summary" class="muted">Verifique título, descrição, imagem social, domínio HTTPS e indexação.</p>
        <button type="button" class="btn outline small" id="seo-portal-check">⌕ Verificar SEO</button>
      </div>

      <div class="seo-portal-preview-heading"><h3>Prévia dos resultados</h3><span class="muted tiny">Aproximada</span></div>
      <div class="seo-portal-tabs" role="tablist" aria-label="Tipo de prévia SEO">
        <button class="seo-portal-tab" type="button" id="seo-tab-google" role="tab" aria-selected="true" aria-controls="seo-preview-google" tabindex="0">Google</button>
        <button class="seo-portal-tab" type="button" id="seo-tab-social" role="tab" aria-selected="false" aria-controls="seo-preview-social" tabindex="-1">WhatsApp / Facebook</button>
      </div>
      <section class="seo-portal-google" id="seo-preview-google" role="tabpanel" aria-labelledby="seo-tab-google">
        <p id="seo-preview-url" class="seo-portal-url"><?=h($seoSiteUrl!==''?$seoSiteUrl:'Endereço HTTPS ainda não configurado')?></p>
        <strong id="seo-preview-title" class="seo-portal-blue-title"><?=h($seoExampleTitle)?></strong>
        <p id="seo-preview-description"><?=h($seoDescription)?></p>
      </section>
      <section class="seo-portal-social" id="seo-preview-social" role="tabpanel" aria-labelledby="seo-tab-social" hidden>
        <div class="seo-portal-social-visual"><img id="seo-social-preview-image" alt="Prévia da imagem de compartilhamento" referrerpolicy="no-referrer" hidden><span id="seo-social-preview-empty">Nenhuma imagem válida selecionada</span></div>
        <div class="seo-portal-social-text"><small id="seo-social-domain"><?=h($seoSiteUrl!==''?$seoSiteUrl:'Domínio não configurado')?></small><strong id="seo-social-preview-title"><?=h($seoExampleTitle)?></strong><p id="seo-social-preview-description"><?=h($seoDescription)?></p></div>
      </section>

      <div class="seo-portal-image-row">
        <div><strong>Imagem para redes sociais</strong><p class="muted" id="seo-image-status">Conferir endereço e prévia da imagem.</p></div>
        <button type="button" class="btn outline small" data-media-pick="social">▧ Escolher imagem</button>
      </div>
      <div class="seo-portal-image-row seo-favicon-row">
        <div class="seo-favicon-description"><img id="seo-favicon-preview" src="<?=h($seoFavicon!==''?$seoFavicon:'/assets/img/favicon.svg')?>" alt="Favicon configurado" width="40" height="40"><div><strong>Ícone do site (favicon)</strong><p class="muted" id="seo-favicon-status">Aparece nas abas do navegador. Prefira imagem quadrada de 48×48 px ou maior.</p></div></div>
        <button type="button" class="btn outline small" data-media-pick="favicon">▧ Escolher favicon</button>
      </div>
      <div class="seo-portal-commands">
        <button class="btn outline small" type="button" data-seo-dialog-open="seo-content-dialog">✎ Título e descrição</button>
        <button class="btn outline small" type="button" data-seo-dialog-open="seo-pages-dialog">▤ Títulos das páginas</button>
        <button class="btn outline small" type="button" data-seo-dialog-open="seo-advanced-dialog">⚙ Ajustes técnicos</button>
        <button class="btn outline small" type="button" data-seo-dialog-open="seo-diagnostic-dialog">✓ Diagnóstico</button>
      </div>
      <p class="muted tiny">As prévias são ilustrativas. O Google pode apresentar outro título ou trecho; nenhuma alteração é salva antes de confirmar o formulário.</p>
    </section>
    <div class="form-actions seo-portal-save"><button type="submit" class="btn primary">Salvar configurações</button><span id="seo-pending-changes" class="muted tiny" role="status">Nenhuma alteração nesta sessão</span></div>

    <dialog id="seo-content-dialog" class="igua-modal seo-portal-dialog" aria-labelledby="seo-content-title">
      <div class="seo-portal-dialog-heading"><h2 id="seo-content-title">Título e descrição</h2><button type="button" class="seo-portal-dialog-close" data-seo-dialog-close aria-label="Fechar">×</button></div>
      <div class="seo-portal-dialog-body">
        <p class="muted">A descrição é utilizada na página inicial quando não há uma descrição específica.</p>
        <label>Modelo de título <span class="muted tiny">Use %title% e, opcionalmente, %site%</span><input name="seo_title_template" maxlength="220" required value="<?=h($seoTitleTemplate)?>"></label>
        <label>Descrição geral do portal<textarea name="seo_default_description" maxlength="320" rows="4" id="seo-description-field"><?=h($seoDescription)?></textarea></label>
        <p id="seo-description-count" class="muted tiny">0/320 caracteres</p>
        <p class="muted tiny">O texto pode ser exibido de forma diferente nos buscadores. Evite cortar frases ou repetir palavras-chave.</p>
      </div>
      <div class="seo-portal-dialog-footer"><button class="btn outline" type="button" data-seo-dialog-close="cancel">Cancelar</button><button type="button" class="btn primary" data-seo-dialog-close="apply">Aplicar na prévia</button></div>
    </dialog>
    <dialog id="seo-pages-dialog" class="igua-modal seo-portal-dialog" aria-labelledby="seo-pages-title">
      <div class="seo-portal-dialog-heading"><h2 id="seo-pages-title">Títulos das páginas</h2><button type="button" class="seo-portal-dialog-close" data-seo-dialog-close aria-label="Fechar">×</button></div>
      <div class="seo-portal-dialog-body">
        <p class="muted">Defina o texto completo da aba do navegador e do título SEO. Em branco, o título original do portal continua valendo.</p>
        <label>Página inicial<input name="seo_home_title" maxlength="220" placeholder="Ex.: Iguatama Online" value="<?=h($seoHomeTitle)?>"></label>
        <p class="muted tiny">Exemplo: escreva apenas <strong>Iguatama Online</strong> para não adicionar “Últimas notícias”.</p>
        <?php foreach($seoPageTitles as $route=>$label): ?>
        <label><?=h($label)?><input name="seo_page_title_<?=h($route)?>" maxlength="220" placeholder="<?=h($label)?> | <?=h($seoSiteName)?>" value="<?=h(setting('seo_page_title_'.$route,''))?>"></label>
        <?php endforeach; ?>
        <p class="muted tiny">Notícias têm título SEO no editor de notícias; categorias usam seus nomes; páginas de aplicativos têm editor próprio. Estes campos não modificam os títulos visíveis H1.</p>
      </div>
      <div class="seo-portal-dialog-footer"><button class="btn outline" type="button" data-seo-dialog-close="cancel">Cancelar</button><button class="btn primary" type="button" data-seo-dialog-close="apply">Aplicar na prévia</button></div>
    </dialog>
    <dialog id="seo-advanced-dialog" class="igua-modal seo-portal-dialog" aria-labelledby="seo-advanced-title">
      <div class="seo-portal-dialog-heading"><h2 id="seo-advanced-title">Configurações avançadas</h2><button type="button" class="seo-portal-dialog-close" data-seo-dialog-close aria-label="Fechar">×</button></div>
      <div class="seo-portal-dialog-body">
        <label>Endereço público oficial (HTTPS)<input name="seo_site_url" type="url" maxlength="240" placeholder="https://seudominio.com.br" value="<?=h($seoSiteUrl)?>"></label>
        <label>Imagem padrão das redes sociais (URL HTTPS ou imagem da biblioteca)<input name="seo_social_image" type="text" inputmode="url" maxlength="600" placeholder="https://seudominio.com.br/capa.webp" value="<?=h($seoSocialImage)?>"></label>
        <p class="muted tiny">A imagem deve apontar para um arquivo real, não apenas para o domínio do portal.</p>
        <label>Favicon (PNG, JPG, WebP ou ICO)<input name="seo_favicon_image" type="text" maxlength="600" placeholder="/uploads/seu-icone.png" value="<?=h($seoFavicon)?>"></label>
        <p class="muted tiny">Escolha uma imagem pela biblioteca acima ou cole o endereço HTTPS de um ícone. Deixe vazio para usar o ícone padrão do portal.</p>
        <label class="checkbox-line"><input type="checkbox" name="seo_pretty_urls" value="1" <?=setting('seo_pretty_urls','0')==='1'?'checked':''?>><span>Usar URLs amigáveis (/noticia/nome-do-endereco)</span></label>
        <p class="muted tiny">URLs amigáveis exigem roteamento funcional. Verifique o Apache ou o roteador PHP antes de ativar.</p>
        <label class="checkbox-line"><input type="checkbox" name="seo_index" value="1" <?=setting('seo_index','0')==='1'?'checked':''?>><span>Permitir indexação pelos buscadores</span></label>
        <p class="seo-portal-sensitive">Indexação exige portal pronto e URL oficial válida. Desativar a indexação pode reduzir a visibilidade das páginas públicas; haverá confirmação ao salvar.</p>
        <a href="<?=h(admin_link('sitemap'))?>">Administrar sitemap, robots.txt e ads.txt ↗</a>
      </div>
      <div class="seo-portal-dialog-footer"><button class="btn outline" type="button" data-seo-dialog-close="cancel">Cancelar</button><button class="btn primary" type="button" data-seo-dialog-close="apply">Aplicar na prévia</button></div>
    </dialog>
    <dialog id="seo-diagnostic-dialog" class="igua-modal seo-portal-dialog" aria-labelledby="seo-diagnostic-title">
      <div class="seo-portal-dialog-heading"><h2 id="seo-diagnostic-title">Diagnóstico de SEO</h2><button type="button" class="seo-portal-dialog-close" data-seo-dialog-close aria-label="Fechar">×</button></div>
      <div class="seo-portal-dialog-body"><p class="muted">Verificações locais dos campos configurados. Elas não medem indexação ou posicionamento no Google.</p><ul id="seo-diagnostic-list" class="seo-portal-checklist"></ul><p class="muted tiny">Para conferir HTTP, robots.txt e sitemap no servidor, abra a Central de Sitemap e Indexação.</p></div>
      <div class="seo-portal-dialog-footer"><a class="btn outline" href="<?=h(admin_link('sitemap'))?>">Central de Sitemap</a><button class="btn primary" type="button" data-seo-dialog-close>Fechar</button></div>
    </dialog>
  </form>
</section>
