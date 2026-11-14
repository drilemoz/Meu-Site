<?php
declare(strict_types=1);
/** Meu Site 1.0.60: regressões estáticas da navegação e fluxos, sem banco real. */
require __DIR__.'/../app/bootstrap.php';
function v160(bool $ok,string $msg):void{if(!$ok)throw new RuntimeException('Falha 1.0.60: '.$msg);echo "OK: $msg\n";}
$root=dirname(__DIR__);
$layout=file_get_contents($root.'/app/admin_views/layout.php');
$article=file_get_contents($root.'/app/admin_views/routes/articles.php');
$user=file_get_contents($root.'/app/admin_views/routes/users.php');
$elections=file_get_contents($root.'/app/elections/admin_view.php');
$media=file_get_contents($root.'/app/media_admin.php');
$updater=file_get_contents($root.'/app/updater.php');
$css=file_get_contents($root.'/public/assets/css/style.css');
$main=file_get_contents($root.'/public/assets/js/main.js');
$js=file_get_contents($root.'/public/assets/js/admin-ui.js');
v160(version_compare(trim(file_get_contents($root.'/VERSION')),'1.0.60','>='),'contratos da versão 1.0.60 permanecem em versões posteriores');
v160(str_contains($layout,'id="admin-nav-dialog"') && str_contains($layout,'id="admin-nav-toggle"') && !str_contains($layout,'class="admin-subnav"'),'menu único e recolhível à direita, sem barra interna');
v160(str_contains($layout,'role_allowed(') && str_contains($layout,'csrf_field()') && str_contains($layout,"'maintenance'"),'permissões, conta, logout e CSRF no menu preservados');
v160(str_contains($layout,'id="admin-confirm-dialog"') && str_contains($layout,'id="admin-message-dialog"'),'janelas de confirmação e avisos no layout');
v160(str_contains($js,'e.stopImmediatePropagation()') && str_contains($js,"confirmDialog.returnValue = 'cancel'") && str_contains($js,'approved.add(btn)'),'ação confirmada somente após aceitação; Escape não aprova');
v160(str_contains($main,"!document.body.classList.contains('admin-body')"),'confirmação pública legada preservada, admin usa diálogos próprios');
v160(str_contains($article,'id="news-filter-dialog"') && str_contains($article,'id="news-filter-open"'),'lupa abre filtros em janela');
v160(str_contains($article,'igua_news_admin_card(') && is_file($root.'/app/admin_views/partials/news_cards.php') && str_contains(file_get_contents($root.'/app/admin_views/partials/news_cards.php'),'news-card-delete'),'cartões compartilham quatro ações editoriais');
v160(str_contains(file_get_contents($root.'/app/admin_views/partials/news_cards.php'),'csrf_field()') && str_contains(file_get_contents($root.'/app/admin_views/partials/news_cards.php'),'can_edit_article'),'ações editoriais preservam CSRF e permissões');
v160(str_contains($user,'admin-collapsible-form') && str_contains($user,"\$editing?'open'") && str_contains($user,'save_user'),'formulário usuário recolhido e pré-preenchido em edição');
v160(str_contains($elections,'admin-collapsible-form') && str_contains($elections,"\$selected?'open'") && str_contains($elections,'save_election'),'eleições recolhidas, edição e submissão preservadas');
v160(str_contains($media,'media-upload-details') && str_contains($css,'.media-window{display:flex;flex-direction:column;height:100dvh'),'biblioteca em tela cheia com envio recolhido');
v160(str_contains($updater,'já está instalada. Nenhuma alteração foi necessária.') && str_contains($updater,'version_compare('),'atualizador diferencia versão repetida de versão antiga');
v160(str_contains($css,'.admin-nav-drawer::backdrop') && str_contains($css,'.news-articles-table .action-cell'),'estilos móveis e máscara do menu presentes');
echo "Inspeção estática aprovada; UX visual e fluxo MariaDB/PHP-FPM ainda exigem validação real.\n";
