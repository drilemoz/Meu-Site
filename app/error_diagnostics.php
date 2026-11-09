<?php
declare(strict_types=1);
/** Diagnóstico apenas leitura. Jamais expor caminhos absolutos, mensagens PHP ou credenciais. */
function igua_error_diagnostics_snapshot(PDO $db): array {
    $checks=[];
    $add=static function(string $name, string $state, string $text)use(&$checks):void{
        $checks[]=['name'=>$name,'state'=>$state,'text'=>$text];
    };
    $add('PHP-FPM / PHP', 'info', 'PHP '.PHP_VERSION.' · '.PHP_SAPI.'. A versão do Nginx deve ser conferida no servidor.');
    $add('Tempo máximo PHP', 'info', (string)ini_get('max_execution_time').' s · memória: '.(string)ini_get('memory_limit').'.');
    $add('Uploads PHP','info', 'Por arquivo: '.(string)ini_get('upload_max_filesize').' · POST: '.(string)ini_get('post_max_size').'.');
    $storage=is_dir(IGUA_STORAGE.'/logs') && is_readable(IGUA_STORAGE.'/logs');
    $add('Logs privados',$storage?'ok':'warning',$storage?'Área privada disponível para leitura.':'Logs inacessíveis; confirme permissões em storage/logs.');
    $uploads=is_dir(IGUA_PUBLIC.'/uploads')&&is_writable(IGUA_PUBLIC.'/uploads');
    $add('Pasta de mídias',$uploads?'ok':'warning',$uploads?'Uploads disponíveis para o PHP.':'Verifique escrita em public/uploads e permissões do PHP-FPM.');
    try {
        $count=(int)$db->query('SELECT COUNT(*) FROM media_library WHERE deleted_at IS NULL')->fetchColumn();
        $add('Biblioteca de mídias','ok',$count.' imagem(ns) cadastrada(s). Os arquivos físicos podem precisar de verificação.');
        $rows=$db->query('SELECT filename FROM media_library WHERE deleted_at IS NULL ORDER BY id DESC LIMIT 5')->fetchAll(PDO::FETCH_COLUMN);
        $missing=0;
        foreach($rows as $filename){if(!media_path((string)$filename))$missing++;}
        $add('Amostra de arquivos',$missing?'warning':'ok',count($rows).' verificados; '.$missing.' não localizados. Amostra limitada às 5 mídias recentes.');
    }catch(Throwable $e){igua_health_log('MEDIA_DIAGNOSTIC_FAILED',$e);
        $add('Biblioteca de mídias','error','Consulta ao banco falhou. Verifique os eventos técnicos e permissões do MariaDB.');}
    try {
        $cfg=igua_gemini_read();
        $ready=$cfg!==null&&$cfg['enabled'];
        $add('Gemini', $ready?'ok':'warning', $ready?'Configuração habilitada; não confirma cota, acesso ao modelo ou rede.':'Chave ausente ou integração desativada. Abra Integrações para testar sob demanda.');
    }catch(Throwable $e){igua_health_log('GEMINI_CONFIG_DIAGNOSTIC_FAILED',$e);
        $add('Gemini','warning','Configuração privada não pôde ser lida. Confira permissões do armazenamento.');}
    $add('Erros Nginx e PHP-FPM','info','A aplicação não lê automaticamente journalctl ou /var/log/nginx. Consulte-os por SSH com permissão administrativa, sem expor logs brutos na web.');
    return $checks;
}
/** Endpoint registra somente códigos fixos, nunca mensagens ou URL da requisição. */
function igua_error_diagnostics_client_code(mixed $value): ?string {
    if(!is_string($value))return null;
    $allowed=['MEDIA_HTTP','MEDIA_TIMEOUT','GEMINI_HTTP','GEMINI_TIMEOUT','JS_RUNTIME','JS_PROMISE'];
    return in_array($value,$allowed,true)?$value:null;
}
