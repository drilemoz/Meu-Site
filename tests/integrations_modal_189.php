<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$view=file_get_contents($root.'/app/admin_views/routes/integrations.php');
$js=file_get_contents($root.'/public/assets/js/integrations-admin.js');
$ui=file_get_contents($root.'/public/assets/js/admin-ui.js');
$css=file_get_contents($root.'/public/assets/css/modules/16-security.css');
function check189(bool $ok,string $message):void {if(!$ok)throw new RuntimeException('189: '.$message);}
check189(str_contains($view,'class="int-tabs" data-integrations-nav aria-label='),'atributo de navegação válido');
check189(!str_contains($view,'class="int-tabs data-integrations-nav'),'atributo corrompido removido');
check189(substr_count($view,'id="int-dialog"')===1&&str_contains($view,'aria-modal="true"'),'dialog nativo único');
check189(substr_count($view,'class="int-guide-row"')===4,'quatro guias detalhados');
foreach(['support.google.com/analytics','developers.google.com/adsense/management','support.google.com/webmasters','developers.google.com/speed/docs'] as $host)check189(str_contains($view,$host),'link de documentação oficial presente: '.$host);
check189(str_contains($view,'data-int-open-panel="google-url-inspection"'),'inspeção abre em janela');
check189(str_contains($js,'dialog.showModal()')&&str_contains($js,'dialog.close()')&&str_contains($js,'restoreCurrent()'),'abre, fecha e restaura os nós de formulários');
check189(str_contains($js,'event.preventDefault()')&&!str_contains($js,'target.scrollIntoView({block:\'nearest\''),'o caminho modal não executa scrollIntoView');
check189(str_contains($ui,"target?.closest?.('#integrations-hub')"),'admin-ui não abre detalhes por hash na Central');
check189(str_contains($css,'100dvh')&&str_contains($css,'overscroll-behavior:contain'),'modo tela inteira e rolagem interna');
check189(str_contains($view,'csrf_field()')&&str_contains($view,'confirm_password'),'forms CSRF e senha continuam');
echo "Integrações 1.0.89: estrutura, guias, formulário, segurança e estilos conferidos.\n";
