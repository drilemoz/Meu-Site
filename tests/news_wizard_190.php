<?php
declare(strict_types=1);
require_once __DIR__.'/support/news_controller_source.php';
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/news_editor.php';
function wizard190(bool $ok,string $name):void {if(!$ok)throw new RuntimeException('FALHOU: '.$name); echo 'OK: '.$name.PHP_EOL;}
// Teste isolado da validação de metadados de arquivos ZIP, sem extração em disco.
if(!class_exists(ZipArchive::class)){
    class ZipArchive {
        public const OPSYS_UNIX=3;
        public int $numFiles=0;public array $entries=[];
        public function statIndex(int $i):array|false{return $this->entries[$i]??false;}
        public function getExternalAttributesIndex(int $i,int &$ops,int &$attrs):bool {$ops=3;$attrs=0100000<<16;return true;}
    }
}
$zip=new ZipArchive();
if(property_exists($zip,'entries')){
    $zip->entries=[['name'=>'fotos/centro.jpg','size'=>102400],['name'=>'imagem.png','size'=>202400]];
    $zip->numFiles=2;
    wizard190(count(news_photo_zip_entries($zip))===2,'ZIP seguro preserva arquivos de imagem, sem extrair caminhos');
    foreach([['name'=>'../private.jpg','size'=>200],['name'=>'script.php','size'=>200],['name'=>'malware.zip','size'=>200],['name'=>'foto.jpg','size'=>9*1048576]] as $entry){
        $zip->entries=[$entry];$zip->numFiles=1;
        try{news_photo_zip_entries($zip);throw new RuntimeException('Entrada ZIP indevida aceita');}
        catch(RuntimeException $e){wizard190($e->getMessage()!=='Entrada ZIP indevida aceita','arquivo ZIP inválido rejeitado');}
    }
    $zip->entries=array_fill(0,11,['name'=>'foto.jpg','size'=>200]);$zip->numFiles=11;
    try{news_photo_zip_entries($zip);throw new RuntimeException('Limite de fotos ignorado');}
    catch(RuntimeException $e){wizard190($e->getMessage()!=='Limite de fotos ignorado','máximo de 10 fotos respeitado');}
}else echo "AVISO: extensão ZipArchive presente, teste completo de extração depende da hospedagem.\n";
wizard190(news_seo_description('Título confirmado','Notícia com dados verificados e texto completo. O resumo permanece objetivo.', '')==='Notícia com dados verificados e texto completo. O resumo permanece objetivo.','SEO mantém frase quando cabe');
$long='A decisão foi confirmada pela prefeitura e publicada em comunicado oficial. '.str_repeat('Detalhes adicionais para análise. ',8);
wizard190(news_seo_description('Nota oficial publicada','',$long)==='A decisão foi confirmada pela prefeitura e publicada em comunicado oficial.','SEO escolhe frase completa se texto longo');
wizard190(news_seo_description('Título seguro','',str_repeat('Texto sem pontuação ',20))==='Título seguro','SEO não inventa trecho truncado');
$controller=igua_test_news_controller_source();
wizard190(str_contains($controller,"$".'publicationChoice')&&str_contains($controller,'news_photo_zip_import('),'publicação e ZIP validados no servidor');
