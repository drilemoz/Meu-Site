<?php
declare(strict_types=1);
$root=dirname(__DIR__);
function ui166(bool $ok,string $message):void {
    if (!$ok) throw new RuntimeException('UI 1.0.66: '.$message);
    echo 'OK: '.$message.PHP_EOL;
}
$read=static fn(string $name):string=>(string)file_get_contents($root.'/'.$name);
ui166(version_compare(trim($read('VERSION')),'1.0.66','>='),'versão sequencial');
$health=$read('app/admin_views/health.php');
ui166(str_contains($health,'$attention[]=$check') && str_contains($health,'$normal[]=$check'),'problemas segregados de verificações normais');
ui166(str_contains($health,'if (!$attention)') && str_contains($health,'health-row') && str_contains($health,'health-priority'),'erros e atenção nunca ocultos');
ui166(str_contains($health,'name="filename"') && str_contains($health,'csrf_field()') && str_contains($health,'action="/admin.php?action=health_verify"'),'verificação do backup mantém CSRF e formulário');
ui166(str_contains($health,'igua_health_log_lines()') && str_contains($health,'<details class="panel health-fold"'),'eventos técnicos recolhíveis');
$users=$read('app/admin_views/routes/users.php');
ui166(str_contains($users,'admin-users-table') && str_contains($users,'data-label="Conta"'),'contas em tabela com marcação para cartões móveis');
ui166(str_contains($users,"(int)\$u['id']!==(int)\$me['id']") && str_contains($users,'data-confirm="Alterar acesso deste usuário?"'),'autodesativação vetada e confirmação preservada');
ui166(str_contains($users,"admin_link('save_user')") && str_contains($users,"admin_link('toggle_user')") && substr_count($users,'csrf_field()')>=2,'salvar/alternar usuários mantém POST e CSRF');
ui166(str_contains($users,'name="name"') && str_contains($users,'name="email"') && str_contains($users,'name="role"') && str_contains($users,'name="password"'),'campos de usuário preservados');
$settings=$read('app/admin_views/routes/settings.php');
ui166(str_contains($settings,'admin-settings-fold') && str_contains($settings,'</div></details>') && str_contains($settings,"admin_link('save_settings')"),'SEO recolhível dentro do formulário de salvamento');
foreach(['site_name','site_tagline','city','contact_email','seo_title_template','seo_default_description','seo_site_url','seo_social_image','seo_pretty_urls','seo_index'] as $field)
    ui166(str_contains($settings,'name="'.$field.'"'),'campo preservado: '.$field);
ui166(str_contains($settings,'Indexação exige portal pronto') && str_contains($settings,'URLs amigáveis exigem roteamento funcional'),'avisos importantes continuam explícitos');
$js=$read('public/assets/js/admin-ui.js');
ui166(str_contains($js,"document.addEventListener('invalid'") && str_contains($js,"fold.open = true"),'formulário abre seção com campo inválido antes do aviso');
$module=$read('public/assets/css/modules/15-archive.css');
$css=$read('public/assets/css/style.css');
ui166(str_contains($module,'1.0.66 — segunda fase') && str_contains($css,'1.0.66 — segunda fase'),'CSS fonte e consolidado sincronizados');
ui166(str_contains($module,'width:44px!important') && str_contains($module,'height:44px!important') && str_contains($module,"'actions actions'"),'botões de notícias têm 44×44 px e linha independente');
ui166(str_contains($module,'.admin-users-table') && str_contains($module,'.health-ok-row'),'estilos móveis de usuários e diagnósticos presentes');
ui166(str_contains($read('app/admin_views/routes/dashboard.php'),'admin-dashboard-health'),'acesso rápido à Saúde presente na visão geral');

// Exercita a view da Saúde com eventos simulados, sem acessar banco nem dados do usuário.
function igua_health_checks(PDO $db): array {return [
 ['name'=>'Banco','status'=>'error','detail'=>'Conexão indisponível','suggest'=>'Verificar credenciais'],
 ['name'=>'Disco','status'=>'warning','detail'=>'Espaço baixo','suggest'=>'Liberar espaço'],
 ['name'=>'PHP','status'=>'ok','detail'=>'Execução normal','suggest'=>''],
 ['name'=>'Versão','status'=>'info','detail'=>'Sem dados externos','suggest'=>''],
];}
function list_backups(): array {return [['name'=>'manual-demo.json','size'=>1048576,'date'=>'06/11/2026']];}
function h($value): string {return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function igua_brand_version(): string {return '1.0.66';}
function csrf_field(): string {return '<input type="hidden" name="csrf" value="token-ficticio">';}
function igua_health_log_lines(): array {return ['Evento técnico sem segredos'];}
class UI166FakePDO extends PDO {public function __construct() {}}
require_once $root.'/app/admin_views/health.php';
ob_start();igua_health_admin_view(new UI166FakePDO());$html=(string)ob_get_clean();
ui166(str_contains($html,'Pontos de atenção') && str_contains($html,'Conexão indisponível') && str_contains($html,'Liberar espaço'),'avisos rendem visíveis e com recomendações');
ui166(str_contains($html,'Verificações normais e informações') && str_contains($html,'Detalhes de PHP'),'verificações sem problemas recolhíveis com ajuda');
ui166(str_contains($html,'manual-demo.json') && str_contains($html,'name="csrf"'),'backup continua auditável com CSRF');
ui166(str_contains($html,'Evento técnico sem segredos'),'logs técnicos ainda acessíveis');
ui166(substr_count($html,'<details')===substr_count($html,'</details>'),'elementos expansíveis estão balanceados');
echo 'UI 1.0.66: verificações concluídas, testes reais no navegador e hospedagem pendentes.'.PHP_EOL;
