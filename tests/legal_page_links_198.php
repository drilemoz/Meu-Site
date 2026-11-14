<?php
declare(strict_types=1);
/** Regressão de acesso público vs. prévia privada nas páginas legais. */
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/apps_legal.php';
function legal198(bool $value, string $message): void {
    if (!$value) throw new RuntimeException('Página legal 198: '.$message);
}
$view=(string)file_get_contents(__DIR__.'/../app/admin_views/routes/legal_apps.php');
$script=(string)file_get_contents(__DIR__.'/../public/assets/js/legal-apps-admin.js');
$style=(string)file_get_contents(__DIR__.'/../public/assets/css/modules/17-legal-apps.css');
legal198(str_contains($view, "\$pg['status']==='published' && \$app['status']==='published'"), 'Acesso público condicionado à publicação de app e página');
legal198(str_contains($view,'>Ver página</a>'), 'Link público visível');
legal198(str_contains($view,'legal_preview=1'), 'Prévia administrativa disponível para rascunhos');
legal198(str_contains($view,'>Prévia</a>'), 'Rascunho explicitamente identificado como prévia');
legal198(str_contains($view,'target="_blank" rel="noopener noreferrer"'), 'Links abrem outra aba com proteção noopener');
legal198(str_contains($view,'data-legal-copy='), 'Copiar URL preservado');
legal198(str_contains($view,'>Editar</a>'), 'Editar preservado');
legal198(str_contains($script,"get('legal_preview')==='1'"), 'Prévia automática apenas com flag explícita');
legal198(str_contains($script,'document.querySelector(\'[data-legal-preview-open]\')'), 'Mesmo modal autenticado existente');
legal198(str_contains($style,'grid-template-columns:repeat(3,minmax(0,1fr))'), 'Três ações responsivas');
legal198(igua_app_page_url('uaicortes','privacidade')==='/uaicortes/privacidade', 'URL permanente mantida');
legal198(igua_app_page_url('uaicortes','termos')==='/uaicortes/termos', 'URL permanente termos');
$public=(string)file_get_contents(__DIR__.'/../app/public_views/routes/legal_app.php');
legal198(str_contains($public,'published_revision_id'), 'Público usa revisão efetivamente publicada');
echo "OK: links publicados, prévia autenticada, ações e URLs permanentes\n";
