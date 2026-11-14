<?php
declare(strict_types=1);
/** IguaNews: serviço editorial, módulo format. Requer bootstrap.php no ponto de entrada. */

/** Meta descrição local: preserva frases completas; se nenhuma cabe, pede revisão
 * por meio do fallback ao título, em vez de inventar um final com reticências.
 * O Google controla o snippet e pode mostrar outro trecho.
 */
function news_seo_description(string $title,string $summary,string $body): string {
    $clean=static fn(string $s):string=>trim(preg_replace('/\s+/u',' ',strip_tags($s))??'');
    $v=$clean($summary!==''?$summary:($body!==''?$body:$title));
    if(ulen($v)<=155)return $v;
    preg_match_all('/[^.!?]+[.!?]+(?=\s|$)/u',$v,$matches);
    foreach($matches[0]??[] as $sentence){
        $sentence=$clean($sentence);
        if(ulen($sentence)>=35&&ulen($sentence)<=155)return $sentence;
    }
    $safeTitle=$clean($title);
    return ulen($safeTitle)<=155?$safeTitle:'';
}

function news_inline_format(string $text): string {
    $escaped=h($text);
    $escaped=preg_replace('/\*\*([^*\n]{1,400})\*\*/u','<strong>$1</strong>',$escaped)??$escaped;
    return preg_replace('/(?<!\*)\*([^*\n]{1,400})\*(?!\*)/u','<em>$1</em>',$escaped)??$escaped;
}

function news_text_format(string $text): string {
    $lines=preg_split('/\R/u',trim($text))?:[];$html='';$paragraph=[];$list=[];
    $flushP=static function()use(&$paragraph,&$html){if($paragraph){$html.='<p>'.implode('<br>',array_map('news_inline_format',$paragraph)).'</p>';$paragraph=[];}};
    $flushL=static function()use(&$list,&$html){if($list){$html.='<ul class="news-list">'.implode('',$list).'</ul>';$list=[];}};
    foreach($lines as $line){$line=trim($line);
        if($line===''){$flushP();$flushL();continue;}
        if(preg_match('/^##\s+(.+)$/u',$line,$m)){$flushP();$flushL();$html.='<h2>'.news_inline_format($m[1]).'</h2>';continue;}
        if(preg_match('/^-\s+(.+)$/u',$line,$m)){$flushP();$list[]='<li>'.news_inline_format($m[1]).'</li>';continue;}
        $flushL();$paragraph[]=$line;
    }
    $flushP();$flushL();return $html;
}

function news_render_blocks(array $blocks): void {
    $facts=array_values(array_filter($blocks,static fn($b)=>$b['kind']==='fact'));
    if($facts){echo '<div class="article-facts">';foreach($facts as $b){echo '<div class="fact-item"><span>'.h($b['heading']).'</span><strong>'.h($b['content']).'</strong></div>';}echo '</div>';}
    foreach($blocks as $b){$heading=h($b['heading']);$kind=$b['kind'];$content=$b['content'];
        if($kind==='fact')continue;
        if($kind==='heading')echo '<h2>'.h($b['heading']?:$content).'</h2>';
        elseif($kind==='quote')echo '<blockquote class="news-quote">'.news_text_format($content).'</blockquote>';
        elseif($kind==='list'){echo '<ul class="news-list">';foreach(preg_split('/\R/u',$content)?:[] as $line)if(trim($line)!=='')echo '<li>'.h(ltrim(trim($line),'-• ')).'</li>';echo '</ul>';}
        elseif($kind==='section'){
            if($b['collapsed'])echo '<details class="news-expand"><summary>'.$heading.'</summary>'.news_text_format($content).'</details>';
            else echo '<section class="news-section"><h2>'.$heading.'</h2>'.news_text_format($content).'</section>';
        }
        else echo news_text_format($content);
    }
}

