<?php
/** Cartões editoriais compartilhados entre Notícias e a visão geral do painel. */
declare(strict_types=1);

function igua_news_relative_date(array $article, ?DateTimeImmutable $now=null): string {
    $tz=new DateTimeZone('America/Sao_Paulo');
    $now=($now??new DateTimeImmutable('now',$tz))->setTimezone($tz);
    $isPublished=($article['status']??'')==='published';
    $raw=$isPublished?($article['published_at']??null):($article['created_at']??null);
    if (!is_string($raw)||trim($raw)==='')return $isPublished?'Data indisponível':'Rascunho';
    // date_create_immutable retorna false quando a entrada é inválida, sem lançar
    // DateMalformedStringException no PHP 8.3+. Isso também evita o erro
    // de propriedades dinâmicas observado no runner do CI com data inválida.
    try {
        $date = date_create_immutable(trim($raw), $tz);
        $errors = DateTimeImmutable::getLastErrors();
    } catch (Throwable) {
        return 'Data indisponível';
    }
    if (!$date instanceof DateTimeImmutable) return 'Data indisponível';
    if (is_array($errors) && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0)) {
        return 'Data indisponível'; // rejeita datas ajustadas, como 30/02.
    }
    $date = $date->setTimezone($tz);
    $today=$now->setTime(0,0);$day=$date->setTime(0,0);
    $interval=(int)$today->diff($day)->days;
    $future=$day>$today;
    if($future)return $interval===1?'Amanhã':'Em '.$interval.' dias';
    if($interval===0)return 'Hoje';
    if($interval===1)return $isPublished?'Ontem':'Criado ontem';
    return ($isPublished?'Há ':'Criado há ').$interval.' dias';
}

function igua_news_admin_card(array $a, array $me, int $page=1, string $filter='all', string $query=''): void {
    $id=(int)$a['id'];
    $published=(string)($a['status']??'')==='published';
    $scheduled=$published && !empty($a['published_at']) && strtotime((string)$a['published_at'])>time();
    $statusLabel=$scheduled?'Agendada':($published?'Publicada':'Rascunho');
    $canEdit=can_edit_article($me,$a);
    $canToggle=$canEdit && role_allowed((string)$me['role'],'publish');
    $category=(string)($a['category']??'Geral');
    $relative=igua_news_relative_date($a);
    ?>
    <article class="news-list-card" aria-label="Notícia: <?= h((string)$a['title']) ?>">
      <div class="news-list-card-head"><strong class="news-list-card-title"><?= h((string)$a['title']) ?></strong><span class="status <?= h($published?'published':'draft') ?>"><?= h($statusLabel) ?></span></div>
      <div class="news-list-card-meta"><span><?= h($category) ?> <span aria-hidden="true">·</span> <time title="<?= h((string)($published?($a['published_at']??''):($a['created_at']??''))) ?>"><?= h($relative) ?></time></span><span aria-label="<?= (int)($a['views']??0) ?> leituras">◉ <?= (int)($a['views']??0) ?></span><?php if(!empty($a['is_demo'])): ?><span class="tag-demo">Exemplo</span><?php endif; ?></div>
      <div class="news-list-card-actions" aria-label="Ações da notícia">
        <a href="/admin.php?action=edit&amp;id=<?= $id ?>" title="Editar notícia" aria-label="Editar notícia"><span aria-hidden="true">✎</span></a>
        <form method="post" action="<?= h(admin_link('duplicate_article')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><button type="submit" title="Duplicar notícia" aria-label="Duplicar notícia como rascunho" data-confirm="Criar uma cópia privada desta notícia como rascunho? Fotos da galeria e anexos não serão copiados."><span aria-hidden="true">⧉</span></button></form>
        <?php if(role_allowed((string)$me['role'],'delete')): ?><form method="post" action="<?= h(admin_link('delete_article')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><button type="submit" class="news-card-delete" title="Excluir notícia" aria-label="Excluir notícia" data-confirm="Excluir esta notícia permanentemente?"><svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" fill="none" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14M10 10v6M14 10v6"/></svg></button></form><?php endif; ?>
        <?php if($canToggle): ?><form method="post" action="<?= h(admin_link('toggle_article_status')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="page" value="<?= $page ?>"><input type="hidden" name="status_filter" value="<?= h($filter) ?>"><input type="hidden" name="q_filter" value="<?= h($query) ?>"><button class="news-card-toggle <?= $published?'is-on':'is-off' ?>" type="submit" title="<?= $published?'Mover para rascunho':'Publicar agora' ?>" aria-label="<?= $published?'Mover para rascunho':'Publicar agora' ?>" data-confirm="<?= $published?'Mover esta notícia para rascunho?':'Publicar esta notícia agora?' ?>"><span aria-hidden="true"><?= $published?'●':'○' ?></span></button></form><?php endif; ?>
      </div>
    </article>
    <?php
}
