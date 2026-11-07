<?php
declare(strict_types=1);
function media128(bool $ok,string $name): void {if(!$ok)throw new RuntimeException('Falha: '.$name);echo "OK: $name\n";}
$view=file_get_contents(__DIR__.'/../app/media_admin.php');
$js=file_get_contents(__DIR__.'/../public/assets/js/media-library.js');
$css=file_get_contents(__DIR__.'/../public/assets/css/style.css');
media128(str_contains($view,'data-media-search-toggle') && str_contains($view,'data-media-search-box'),'pesquisa da galeria ganhou botão de ícone');
media128(str_contains($view,'data-media-upload-name') && str_contains($view,'envio começa automaticamente'),'upload informa envio automático');
media128(str_contains($js,"upload.addEventListener('change'") && str_contains($js,'performUpload'),'seleção de arquivos dispara envio automático');
media128(str_contains($css,'.media-search-toggle') && str_contains($css,'.status-toggle'),'estilos responsivos para busca compacta e publicação rápida');
echo "Mídia e lista 1.0.28: testes aprovados.\n";
