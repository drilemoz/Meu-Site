<?php
declare(strict_types=1);
/** Identidade editorial configurável. Os créditos de autoria são constantes do produto. */
const IGUA_DEVELOPER_NAME = 'Adriedson Lemos';
const IGUA_DEVELOPER_EMAIL = 'drilemoz@gmail.com';
const IGUA_DEVELOPER_PHONE_LABEL = '(37) 9 9874-7642';
const IGUA_DEVELOPER_PHONE_URI = '+5537998747642';
const IGUA_DEVELOPER_WEBSITE = 'https://adriedsonlemos.vercel.app';

/** Uma única validação para instalação e administração. */
function igua_validate_brand_name(string $raw): string {
    $name=trim(preg_replace('/\s+/u',' ',$raw)??'');
    if (ulen($name)<2 || ulen($name)>80 || preg_match('/[\x00-\x1f\x7f<>]/u',$name)) {
        throw new InvalidArgumentException('Informe um nome de portal válido entre 2 e 80 caracteres, sem marcas HTML.');
    }
    return $name;
}
function igua_brand_name(): string {
    $name=trim(setting('site_name','Meu Site'));
    return $name !== '' ? $name : 'Meu Site';
}
function igua_brand_description(): string {
    return setting('seo_default_description','Notícias, reportagens e informações da nossa região.');
}
function igua_brand_version(): string {
    static $version=null;
    if ($version===null) {
        $value=trim((string)@file_get_contents(IGUA_ROOT.'/VERSION'));
        $version=preg_match('/^\d+\.\d+\.\d+$/D',$value)?$value:'indisponível';
    }
    return $version;
}
/** Acessível também no login e instalador, sem conexão com o banco. */
function igua_developer_footer(): void { ?>
<div class="ig-footer-credit" aria-label="Informações do sistema e desenvolvedor">
  <span class="ig-footer-version">Versão <?= h(igua_brand_version()) ?></span>
  <span class="ig-footer-separator" aria-hidden="true">•</span>
  <button type="button" class="ig-footer-developer" data-developer-contact aria-haspopup="dialog" aria-controls="igDeveloperDialog">Desenvolvido por <strong><?= h(IGUA_DEVELOPER_NAME) ?></strong></button>
</div>
<dialog id="igDeveloperDialog" class="ig-developer-dialog" aria-labelledby="igDeveloperTitle" aria-describedby="igDeveloperDescription">
 <div class="ig-dialog-top"><span class="ig-dialog-icon" aria-hidden="true">✉</span><button type="button" class="ig-dialog-close" data-developer-close aria-label="Fechar janela">×</button></div>
 <h2 id="igDeveloperTitle">Contato do desenvolvedor</h2>
 <p id="igDeveloperDescription">Escolha como entrar em contato com <?= h(IGUA_DEVELOPER_NAME) ?>.</p>
 <div class="ig-dialog-links">
  <a href="<?= h(IGUA_DEVELOPER_WEBSITE) ?>" target="_blank" rel="noopener noreferrer"><span aria-hidden="true">⌂</span><span><strong>Meu site</strong><small>adriedsonlemos.vercel.app</small></span><span aria-hidden="true">↗</span></a>
  <a href="mailto:<?= h(IGUA_DEVELOPER_EMAIL) ?>"><span aria-hidden="true">✉</span><span><strong>Enviar e-mail</strong><small><?= h(IGUA_DEVELOPER_EMAIL) ?></small></span><span aria-hidden="true">↗</span></a>
  <a href="tel:<?= h(IGUA_DEVELOPER_PHONE_URI) ?>"><span aria-hidden="true">☎</span><span><strong>Ligar</strong><small><?= h(IGUA_DEVELOPER_PHONE_LABEL) ?></small></span><span aria-hidden="true">↗</span></a>
 </div>
</dialog>
<script src="/assets/js/developer-contact.js" defer></script>
<?php }
