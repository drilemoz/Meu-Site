<?php
declare(strict_types=1);
/** Ferramentas administrativas, restritas ao depósito público do gerenciador. */
const IGUA_FM_MAX_ITEMS = 300;
const IGUA_FM_MAX_TOTAL_BYTES = 32 * 1024 * 1024;
const IGUA_FM_MAX_SELECTION = 20;

function igua_fm_managed(string $path, ?bool $directory = null): string {
    $path = igua_files_project_managed_relative($path);
    if ($path === '') return igua_files_prepare_dir('');
    $found = $directory === true ? igua_files_resolve($path, true) :
        ($directory === false ? igua_files_resolve($path, false) : (igua_files_resolve($path, false) ?? igua_files_resolve($path, true)));
    if ($found === null) throw new InvalidArgumentException('Arquivo ou pasta não encontrado(a) na área gerenciada.');
    return $found;
}
function igua_fm_selection($raw): array {
    if (!is_array($raw) || count($raw) < 1 || count($raw) > IGUA_FM_MAX_SELECTION)
        throw new InvalidArgumentException('Selecione de 1 a 20 arquivos ou pastas.');
    $result = [];
    foreach ($raw as $item) {
        if (!is_string($item)) throw new InvalidArgumentException('Seleção inválida.');
        $relative = igua_files_project_relative($item);
        if ($relative === 'public/uploads/arquivos' || isset($result[$relative]))
            throw new InvalidArgumentException('Seleção repetida ou contém a pasta principal.');
        igua_fm_managed($relative);
        $result[$relative] = $relative;
    }
    foreach ($result as $item) foreach ($result as $other) {
        if ($item !== $other && str_starts_with($item, $other.'/'))
            throw new InvalidArgumentException('Não selecione uma pasta e um arquivo dentro dela ao mesmo tempo.');
    }
    return array_values($result);
}
/** Valida árvore inteira antes de operações potencialmente destrutivas. */
function igua_fm_tree(string $path, int &$count, int &$bytes): void {
    if (is_link($path)) throw new InvalidArgumentException('Links simbólicos não podem ser movimentados.');
    if (++$count > IGUA_FM_MAX_ITEMS) throw new InvalidArgumentException('Mais de 300 itens. Divida a operação.');
    if (is_file($path)) {
        if (!igua_files_allowed(basename($path))) throw new InvalidArgumentException('Tipo de arquivo não permitido.');
        $size = filesize($path);
        if ($size === false || $size > IGUA_FILES_MAX_BYTES) throw new InvalidArgumentException('Arquivo individual excede 8 MiB.');
        $bytes += $size;
        if ($bytes > IGUA_FM_MAX_TOTAL_BYTES) throw new InvalidArgumentException('Operação ultrapassa 32 MiB.');
    } elseif (is_dir($path)) {
        foreach (new DirectoryIterator($path) as $entry) {
            if ($entry->isDot()) continue;
            if (!igua_files_name($entry->getFilename())) throw new InvalidArgumentException('Nome inválido dentro da pasta.');
            igua_fm_tree($entry->getPathname(), $count, $bytes);
        }
    } else throw new InvalidArgumentException('Item inválido.');
}
function igua_fm_remove_tree(string $path): void {
    if (is_link($path)) throw new InvalidArgumentException('Links simbólicos proibidos.');
    if (is_dir($path)) {
        foreach (new DirectoryIterator($path) as $item) {
            if (!$item->isDot()) igua_fm_remove_tree($item->getPathname());
        }
        if (!@rmdir($path)) throw new RuntimeException('Não foi possível remover a pasta.');
    } elseif (!@unlink($path)) throw new RuntimeException('Não foi possível remover o arquivo.');
}
function igua_fm_copy_tree(string $source, string $dest): void {
    if (is_dir($source)) {
        if (!@mkdir($dest, 0755)) throw new RuntimeException('Não foi possível criar a pasta de destino.');
        foreach (new DirectoryIterator($source) as $item) {
            if (!$item->isDot()) igua_fm_copy_tree($item->getPathname(), $dest.'/'.$item->getFilename());
        }
    } else {
        $input = @fopen($source, 'rb');
        if ($input === false) throw new RuntimeException('Não foi possível ler o arquivo.');
        $out = @fopen($dest, 'x');
        if ($out === false) { fclose($input); throw new RuntimeException('O destino já existe ou não permite gravação.'); }
        try {
            $copied = stream_copy_to_stream($input, $out, IGUA_FILES_MAX_BYTES + 1);
            if ($copied === false || $copied !== filesize($source)) throw new RuntimeException('Cópia incompleta.');
        } finally { fclose($input); fclose($out); }
        @chmod($dest, 0644);
    }
}
function igua_fm_clipboard(array &$session, string $mode, array $selection): void {
    if (!in_array($mode, ['copy','cut'], true)) throw new InvalidArgumentException('Ação inválida.');
    $session['igua_files_clipboard'] = ['mode'=>$mode, 'items'=>igua_fm_selection($selection), 'at'=>time()];
}
function igua_fm_clipboard_info(array $session): ?array {
    $info = $session['igua_files_clipboard'] ?? null;
    if (!is_array($info) || !is_int($info['at']??null) || time() - $info['at'] > 900 || time() < $info['at']) return null;
    return $info;
}
function igua_fm_paste(array &$session, string $folder): int {
    $clip = igua_fm_clipboard_info($session);
    if (!$clip) throw new InvalidArgumentException('A área de transferência expirou. Copie novamente.');
    $destination = igua_fm_managed($folder, true);
    $mode = $clip['mode'];$items = igua_fm_selection($clip['items']);
    $plans=[];$count=0;$bytes=0;
    foreach ($items as $relative) {
        $src = igua_fm_managed($relative);
        $dst = $destination.'/'.basename($src);
        if ($dst === $src || (is_dir($src) && igua_files_is_below($destination,$src)))
            throw new InvalidArgumentException('Não é possível colar uma pasta nela mesma.');
        if (file_exists($dst) || is_link($dst)) throw new InvalidArgumentException('Já existe no destino: '.basename($src));
        igua_fm_tree($src,$count,$bytes);
        $plans[] = [$src,$dst];
    }
    $done=[];
    try {
        foreach ($plans as [$src,$dst]) {
            if ($mode === 'cut') {
                if (!@rename($src,$dst)) throw new RuntimeException('Falha ao mover '.basename($src));
            } else {
                try { igua_fm_copy_tree($src,$dst); }
                catch (Throwable $e) { if (file_exists($dst)) igua_fm_remove_tree($dst); throw $e; }
            }
            $done[] = [$src,$dst];
        }
    } catch (Throwable $e) {
        foreach (array_reverse($done) as [$src,$dst]) {
            if ($mode === 'cut') @rename($dst,$src);
            else if (file_exists($dst)) igua_fm_remove_tree($dst);
        }
        throw $e;
    }
    if ($mode === 'cut') unset($session['igua_files_clipboard']);
    return count($done);
}
function igua_fm_delete_selection(array $selection): int {
    $items=igua_fm_selection($selection); $plan=[];$count=0;$bytes=0;
    foreach ($items as $name) { $path=igua_fm_managed($name);igua_fm_tree($path,$count,$bytes);$plan[]=$path; }
    foreach ($plan as $path) igua_fm_remove_tree($path);
    return count($plan);
}
function igua_fm_permissions(string $relative): string {
    if ($relative === 'public/uploads/arquivos' && !is_dir(IGUA_PUBLIC.'/uploads/arquivos')) return 'A criar';
    $path = igua_files_project_resolve($relative);
    $mode = fileperms($path);
    return $mode === false ? 'Indisponível' : sprintf('%04o', $mode & 07777);
}
function igua_fm_chmod(string $relative, string $mode): void {
    $path=igua_fm_managed($relative);
    $options=is_dir($path)?['0755'=>0755,'0700'=>0700]:['0644'=>0644,'0600'=>0600];
    if (!isset($options[$mode])) throw new InvalidArgumentException('Permissão não permitida para este tipo de item.');
    if (!@chmod($path,$options[$mode])) throw new RuntimeException('A hospedagem não permitiu alterar as permissões.');
}
function igua_fm_zip_add(ZipArchive $zip, string $path, string $name): void {
    if (is_dir($path)) {
        if (!$zip->addEmptyDir($name)) throw new RuntimeException('Falha ao compactar pasta.');
        foreach (new DirectoryIterator($path) as $item) {
            if (!$item->isDot()) igua_fm_zip_add($zip,$item->getPathname(),$name.'/'.$item->getFilename());
        }
    } elseif (!$zip->addFile($path,$name)) throw new RuntimeException('Falha ao compactar arquivo.');
}
function igua_fm_zip_selection(array $selection, string $folder, string $name): string {
    if (!class_exists('ZipArchive')) throw new RuntimeException('Extensão ZIP não disponível nesta hospedagem.');
    if (!igua_files_name($name) || igua_files_extension($name)!=='zip') throw new InvalidArgumentException('Use um nome como documentos.zip.');
    $items=igua_fm_selection($selection);$count=0;$bytes=0;$plans=[];
    foreach($items as $item){$src=igua_fm_managed($item);igua_fm_tree($src,$count,$bytes);$plans[]=[$src,basename($src)];}
    $dest=igua_fm_managed($folder,true).'/'.$name;
    foreach($plans as [$src,$label]) {
        if(is_dir($src) && igua_files_is_below(dirname($dest),$src))
            throw new InvalidArgumentException('Compacte a pasta a partir de sua pasta pai para evitar inclusão do próprio ZIP.');
    }
    if (file_exists($dest) || is_link($dest)) throw new InvalidArgumentException('Já existe um ZIP com esse nome.');
    $temporary=tempnam(dirname($dest),'.igua-');
    if ($temporary === false) throw new RuntimeException('Sem permissão para preparar ZIP.');
    $zip=new ZipArchive();
    try {
        if ($zip->open($temporary,ZipArchive::OVERWRITE)!==true) throw new RuntimeException('Falha ao abrir ZIP temporário.');
        try {foreach($plans as [$src,$label])igua_fm_zip_add($zip,$src,$label);}
        finally {if(!$zip->close())throw new RuntimeException('Falha ao finalizar ZIP.');}
        if (filesize($temporary)>IGUA_FILES_MAX_BYTES) throw new InvalidArgumentException('ZIP excede 8 MiB. Divida a seleção.');
        if (file_exists($dest) || !@rename($temporary,$dest)) throw new RuntimeException('Não foi possível salvar o ZIP.');
        @chmod($dest,0644);
    } finally {if(is_file($temporary))@unlink($temporary);}
    return $name;
}
/** Pré-validação integral antes de gravar qualquer conteúdo extraído. */
function igua_fm_zip_plan(ZipArchive $zip): array {
    $count=$zip->numFiles;$total=0;$seen=[];$plan=[];
    if($count<1 || $count>IGUA_FM_MAX_ITEMS)throw new InvalidArgumentException('ZIP vazio ou com mais de 300 entradas.');
    for($i=0;$i<$count;$i++) {
        $stat=$zip->statIndex($i);
        if(!is_array($stat))throw new InvalidArgumentException('Entrada ZIP inválida.');
        $raw=$stat['name'];$isDir=str_ends_with($raw,'/');$name=rtrim($raw,'/');
        if($name==='' || strlen($name)>400)throw new InvalidArgumentException('Nome ZIP inválido.');
        igua_files_relative($name);
        if(isset($seen[$name]))throw new InvalidArgumentException('ZIP contém caminhos duplicados.');
        $seen[$name]=$isDir?'dir':'file';
        $parts=explode('/',$name);
        if(count($parts)>12)throw new InvalidArgumentException('ZIP excede profundidade de 12 pastas.');
        foreach($parts as $j=>$part) {
            if($j===0)continue;
            $parent=implode('/',array_slice($parts,0,$j));
            if(($seen[$parent]??'')==='file')throw new InvalidArgumentException('ZIP contém conflito de arquivo/pasta.');
        }
        if(!$isDir && !igua_files_allowed(basename($name)))throw new InvalidArgumentException('Extensão não autorizada no ZIP: '.basename($name));
        $size=(int)($stat['size']??-1);$compressed=(int)($stat['comp_size']??-1);
        if($size<0 || $compressed<0 || (!$isDir && $size>IGUA_FILES_MAX_BYTES))throw new InvalidArgumentException('Arquivo extraído muito grande.');
        if($size>0 && $compressed<=0)throw new InvalidArgumentException('ZIP possui compressão suspeita.');
        $total+=$size;
        if($total>IGUA_FM_MAX_TOTAL_BYTES)throw new InvalidArgumentException('ZIP excede 32 MiB descompactados.');
        if(method_exists($zip,'getExternalAttributesIndex') && $zip->getExternalAttributesIndex($i,$opsys,$attributes)) {
            if ((($attributes >> 16) & 0170000) === 0120000)throw new InvalidArgumentException('ZIP possui link simbólico.');
        }
        $plan[]=[$i,$name,$isDir,$size];
    }
    foreach($plan as [$i,$name,$isDir]){
        $parts=explode('/',$name);
        for($j=1;$j<count($parts);$j++) {
            $p=implode('/',array_slice($parts,0,$j));
            if(($seen[$p]??'')==='file')throw new InvalidArgumentException('ZIP contém arquivo usado como pasta.');
        }
    }
    return $plan;
}
function igua_fm_zip_check(string $path): void {
    if (!class_exists('ZipArchive')) throw new RuntimeException('A extensão ZIP é necessária para verificar o arquivo.');
    $zip=new ZipArchive();
    if($zip->open($path)!==true)throw new InvalidArgumentException('Arquivo ZIP inválido.');
    try {igua_fm_zip_plan($zip);}finally{$zip->close();}
}
function igua_fm_zip_extract(string $relative): string {
    $file=igua_fm_managed($relative,false);
    if (igua_files_extension($file)!=='zip')throw new InvalidArgumentException('Selecione um arquivo .zip.');
    if(!class_exists('ZipArchive'))throw new RuntimeException('Extensão ZIP não disponível nesta hospedagem.');
    $zip=new ZipArchive();
    if($zip->open($file)!==true)throw new InvalidArgumentException('Não foi possível abrir o ZIP.');
    try {
        $plan=igua_fm_zip_plan($zip);
        $folderName=pathinfo($file,PATHINFO_FILENAME).'-extraido';
        if(!igua_files_name($folderName))throw new InvalidArgumentException('Nome de destino inválido.');
        $dest=dirname($file).'/'.$folderName;
        if(is_link($dest) || file_exists($dest))throw new InvalidArgumentException('Pasta de extração já existe: '.$folderName);
        if(!@mkdir($dest,0755))throw new RuntimeException('Sem permissão para criar a pasta de extração.');
        try {
            foreach($plan as [$index,$name,$isDir,$size]) {
                $target=$dest.'/'.$name;
                $parent=dirname($target);
                if(!is_dir($parent) && !@mkdir($parent,0755,true))throw new RuntimeException('Falha ao criar subpasta do ZIP.');
                if($isDir){if(!is_dir($target) && !@mkdir($target,0755))throw new RuntimeException('Falha ao extrair pasta.');continue;}
                $stream=$zip->getStream($zip->getNameIndex($index));
                if($stream===false)throw new RuntimeException('Falha ao ler entrada ZIP.');
                $out=@fopen($target,'x');
                if($out===false){fclose($stream);throw new RuntimeException('Arquivo repetido no ZIP.');}
                try{$copied=stream_copy_to_stream($stream,$out,$size+1);}
                finally{fclose($out);fclose($stream);}
                if($copied!==$size)throw new RuntimeException('Conteúdo ZIP incompleto.');
                @chmod($target,0644);
            }
        } catch(Throwable $e) {igua_fm_remove_tree($dest);throw $e;}
    } finally{$zip->close();}
    return $folderName;
}

/** Exportação autenticada de arquivos do projeto, sem publicar cópia no webroot. */
function igua_fm_export_walk(ZipArchive $zip, string $path, string $relative, int &$count, int &$bytes): void {
    if (is_link($path)) return;
    $parts=explode('/',$relative);
    foreach($parts as $part) {
        if(!igua_files_project_name($part) || igua_files_project_protected($part))return;
    }
    if (++$count > 1000) throw new InvalidArgumentException('Exportação ultrapassou 1.000 itens. Selecione menos pastas.');
    if (is_file($path)) {
        $size=filesize($path);
        if($size===false || $size>IGUA_FILES_MAX_BYTES)throw new InvalidArgumentException('Arquivo acima de 8 MiB na exportação.');
        $bytes+=$size;
        if($bytes>64*1024*1024)throw new InvalidArgumentException('Exportação ultrapassou 64 MiB.');
        if(!$zip->addFile($path,$relative))throw new RuntimeException('Falha ao adicionar arquivo ao ZIP.');
    } elseif(is_dir($path)) {
        if(!$zip->addEmptyDir($relative))throw new RuntimeException('Falha ao compactar pasta.');
        foreach(new DirectoryIterator($path) as $item) {
            if($item->isDot() || $item->isLink())continue;
            $name=$item->getFilename();
            if(!igua_files_project_name($name) || igua_files_project_protected($name))continue;
            igua_fm_export_walk($zip,$item->getPathname(),$relative.'/'.$name,$count,$bytes);
        }
    }
}
function igua_fm_export_zip(array $raw): void {
    if(!class_exists('ZipArchive'))throw new RuntimeException('Extensão ZIP indisponível na hospedagem.');
    if(count($raw)<1 || count($raw)>IGUA_FM_MAX_SELECTION)throw new InvalidArgumentException('Selecione de 1 a 20 itens.');
    $items=[];
    foreach($raw as $item) {
        if(!is_string($item))throw new InvalidArgumentException('Seleção inválida.');
        $relative=igua_files_project_relative($item);
        if($relative==='')throw new InvalidArgumentException('Selecione arquivos ou pastas, não a raiz.');
        $path=igua_files_project_resolve($relative);
        $items[$relative]=$path;
    }
    foreach($items as $rel=>$path)foreach($items as $other=>$otherPath) {
        if($rel!==$other && str_starts_with($rel,$other.'/'))
            throw new InvalidArgumentException('Selecione a pasta ou os itens dela, não ambos.');
    }
    $temporary=tempnam(sys_get_temp_dir(),'igua-export-');
    if($temporary===false)throw new RuntimeException('Sem espaço temporário para exportação.');
    $zip=new ZipArchive();
    try {
        if($zip->open($temporary, ZipArchive::OVERWRITE)!==true)throw new RuntimeException('Não foi possível criar ZIP.');
        $count=0;$bytes=0;
        try {foreach($items as $relative=>$path)igua_fm_export_walk($zip,$path,$relative,$count,$bytes);}
        finally {if(!$zip->close())throw new RuntimeException('Não foi possível finalizar ZIP.');}
        $size=filesize($temporary);
        if($size===false || $size>64*1024*1024)throw new InvalidArgumentException('ZIP excede 64 MiB.');
        header('Cache-Control: private, no-store, max-age=0');
        header('X-Content-Type-Options: nosniff');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="arquivos-projeto.zip"');
        header('Content-Length: '.$size);
        readfile($temporary);
    } finally{if(is_file($temporary))@unlink($temporary);}
}
