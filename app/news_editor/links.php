<?php
declare(strict_types=1);
/** Links editoriais independentes do cadastro global de fontes. Não faz requisições externas. */
function news_reference_links(mixed $raw,bool $strict=true): array {
    if (!is_string($raw) && $raw!==null) {
        if ($strict) throw new RuntimeException('Formato dos links inválido.');
        return [];
    }
    $raw=(string)$raw;
    if (strlen($raw)>21000) {
        if ($strict) throw new RuntimeException('Links de referência acima do limite.');
        return [];
    }
    $links=[];
    foreach (preg_split('/\r\n|\r|\n/',$raw) as $line) {
        $url=trim($line);
        if ($url==='') continue;
        $parts=parse_url($url);
        $valid=strlen($url)<=2048 && !preg_match('/[\x00-\x20\x7f]/',$url)
            && filter_var($url,FILTER_VALIDATE_URL)!==false && is_array($parts)
            && in_array(strtolower($parts['scheme']??''),['http','https'],true)
            && !empty($parts['host']) && !isset($parts['user']) && !isset($parts['pass']);
        if (!$valid) {
            if ($strict) throw new RuntimeException('Informe um link HTTP ou HTTPS válido por linha, com até 2048 caracteres.');
            continue;
        }
        if (isset($links[$url])) continue;
        if (count($links)>=10) {
            if ($strict) throw new RuntimeException('Adicione até 10 links de referência por notícia.');
            break;
        }
        $links[$url]=$url;
    }
    return array_values($links);
}
