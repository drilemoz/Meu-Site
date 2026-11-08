<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/analytics.php';
require_once __DIR__.'/../app/analytics_admin.php';
function check168(bool $ok,string $desc):void {if(!$ok)throw new RuntimeException($desc);echo 'OK: '.$desc.PHP_EOL;}
check168(analytics_period(['days'=>'today'])==='today' && analytics_period(['days'=>'24h'])==='24h','períodos curtos reconhecidos');
check168(analytics_period(['days'=>'7'])===7 && analytics_period(['days'=>'30'])===30 && analytics_period(['days'=>'90'])===90,'períodos legados preservados');
check168(analytics_period(['days'=>['24h']])===30 && analytics_period(['days'=>'24h;DROP'])===30,'entrada de período inválida rejeitada');
$now=new DateTimeImmutable('2026-11-06 19:07:22',new DateTimeZone('America/Sao_Paulo'));
$t=analytics_time_range('today',$now);
check168($t['params']===['2026-11-06','2026-11-06','2026-11-06 00:00:00','2026-11-06 19:07:22'],'hoje começa às 00h de Brasília');
$h=analytics_time_range('24h',$now);
check168($h['params']===['2026-11-05','2026-11-06','2026-11-05 19:07:22','2026-11-06 19:07:22'],'24h móvel inclui intervalo do dia anterior');
$d=analytics_time_range(7,$now);
check168($d['params']===['2026-10-31','2026-11-06','2026-10-31 00:00:00','2026-11-06 19:07:22'],'7 dias inclui o dia corrente');
check168(count(analytics_complete_series([], $t))===20 && count(analytics_complete_series([], $h))===25 && count(analytics_complete_series([], $d))===7,'séries preenchidas inclusive sem dados');
$series=analytics_complete_series([['bucket'=>'2026-11-06 13:00:00','views'=>3,'bots'=>0,'clicks'=>1]],$t);
check168($series[13]['views']===3 && $series[13]['clicks']===1 && $series[12]['views']===0,'agrupa horas e preserva zeros');
check168(analytics_ga4_measurement_id(' g-a1b2c3d4 ')==='G-A1B2C3D4','normaliza ID GA4');
foreach(['','G-','G-<script>','G-ABC!456','G-ABCDE12345;alert(1)','GTM-AAAA'] as $invalid)check168(analytics_ga4_measurement_id($invalid)==='','rejeita ID inválido '.substr($invalid,0,25));

// Teste dos parâmetros de consulta sem depender de MariaDB na bancada.
class FakeAnalyticsPDO168 extends PDO {
    public array $queries=[];
    public function __construct(){}
    public function prepare(string $query,array $options=[]): PDOStatement|false {
        $this->queries[]=$query;
        return new FakeAnalyticsStatement168();
    }
}
class FakeAnalyticsStatement168 extends PDOStatement {
    public array $params=[];
    public function __construct(){}
    public function execute(?array $params=null): bool {
        $this->params=$params??[];
        if(count($this->params)!==4)throw new RuntimeException('Consulta com parâmetros divergentes');
        return true;
    }
    public function fetch(int $mode=PDO::FETCH_DEFAULT,int $cursorOrientation=PDO::FETCH_ORI_NEXT,int $cursorOffset=0): mixed {return ['views'=>0,'bots'=>0,'visitors_day'=>0,'clicks'=>0];}
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT,...$args): array {return [];}
}
$db=new FakeAnalyticsPDO168();
$out=analytics_admin_data($db,'24h',$now);
check168(count($db->queries)===10,'resumo, gráfico e oito dimensões executados');
check168(str_contains($db->queries[1],'DATE_FORMAT(occurred_at') && str_contains($db->queries[1],'GROUP BY bucket'),'consulta horária usa data e hora');
check168(str_contains($db->queries[0],'occurred_at >= ?')&&str_contains($db->queries[0],'occurred_at <= ?')&&str_contains($db->queries[0],'event_day >= ?'),'filtro exato com índice existente de dia');
check168($out['period']==='24h'&&$out['hourly']===true,'formato de dados retorna granularidade');
$view=file_get_contents(__DIR__.'/../app/public_views/layout.php');
check168(str_contains($view,'analytics_ga4_public_id()')&&str_contains($view,'ga4ConsentConfig') && str_contains($view,'ga4-consent.js'),'tag GA4 condicional no portal');
check168(!str_contains($view,'googletagmanager.com/gtag/js'),'nenhum script Google estático sem consentimento');
$controller=file_get_contents(__DIR__.'/../app/admin_controllers/system.php');
check168(str_contains($controller,'analytics_ga4_measurement_id($measurement)') && str_contains($controller,'$db->beginTransaction()'),'salvamento do ID e ativação com validação e transação');
check168(!str_contains(file_get_contents(__DIR__.'/../app/analytics_admin.php'),'GTM-'),'integração limitada ao identificador de medição GA4');
echo "Métricas v1.0.68 aprovadas (sem substituir teste MariaDB real).\n";
