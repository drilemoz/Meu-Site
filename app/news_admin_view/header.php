<div class="panel news-editor-shell">
  <div class="news-editor-top"><div><h2><?= $id ? 'Editar matéria' : 'Criar notícia' ?></h2><p class="muted">Um passo por vez, com revisão completa antes de salvar ou publicar.</p></div><div class="news-editor-top-actions"><a class="btn outline small" href="/admin.php?action=articles">✕ Sair</a><?php if($id>0 && ($row['status']??'')==='published' && role_allowed((string)($me['role']??''),'manage_settings')): ?><a class="btn outline small" href="/admin.php?action=integrations&amp;page=article%3A<?= (int)$id ?>#paginas">⌕ Índice Google</a><?php endif; ?></div></div>
  <?php if($prefill): ?><div class="alert success"><strong>Metadados carregados para revisão.</strong> Confira todos os fatos e redija conteúdo próprio. Nenhum texto integral ou foto foi copiado. <?php if(!empty($prefill['author'])): ?>Autor citado: <?= h($prefill['author']) ?>. <?php endif; ?><?php if(!empty($prefill['published'])): ?>Data indicada: <?= h($prefill['published']) ?>. <?php endif; ?><?php if(!empty($prefill['image_reference']) && filter_var($prefill['image_reference'],FILTER_VALIDATE_URL) && str_starts_with($prefill['image_reference'],'https://')): ?><a href="<?= h($prefill['image_reference']) ?>" target="_blank" rel="noreferrer noopener">Imagem referenciada (não importada)</a><?php endif; ?></div><?php endif; ?>
  <?php if($savedDraft): ?><div class="news-draft-notice" id="news-draft-notice" data-draft-key="<?= h((string)($me['id']??0).':'.$id) ?>"><span>Rascunho automático encontrado (<?= h($savedDraft['updated_at']) ?>).</span><div class="news-draft-actions"><button type="button" class="btn outline small" id="recover-news-draft">Recuperar</button><button type="button" class="btn outline small" id="ignore-news-draft">Ignorar</button></div><small class="muted">Fotos e anexos locais não fazem parte do rascunho automático.</small></div><?php endif; ?>
  <div class="news-step-nav"><p class="news-step-progress" id="news-step-progress" aria-live="polite">Etapa 1 de 9 · Título</p><button type="button" class="btn outline small" id="news-steps-toggle" aria-expanded="false" aria-controls="news-steps">☰ Etapas</button></div>
  <nav class="news-editor-steps" id="news-steps" aria-label="Etapas do assistente" hidden>
    <button type="button" data-tab="title" class="active" aria-current="step" aria-label="Etapa 1: Título"><span>1</span><small>Título</small></button>
    <button type="button" data-tab="summary" aria-label="Etapa 2: Descrição"><span>2</span><small>Descrição</small></button>
    <button type="button" data-tab="blocks" aria-label="Etapa 3: Matéria"><span>3</span><small>Matéria</small></button>
    <button type="button" data-tab="photos" aria-label="Etapa 4: Fotos"><span>4</span><small>Fotos</small></button>
    <button type="button" data-tab="credits" aria-label="Etapa 5: Créditos"><span>5</span><small>Créditos</small></button>
    <button type="button" data-tab="attachments" aria-label="Etapa 6: Anexos"><span>6</span><small>Anexos</small></button>
    <button type="button" data-tab="classify" aria-label="Etapa 7: Classificar"><span>7</span><small>Classificar</small></button>
    <button type="button" data-tab="seo" aria-label="Etapa 8: SEO"><span>8</span><small>SEO</small></button>
    <button type="button" data-tab="review" aria-label="Etapa 9: Revisar"><span>9</span><small>Revisar</small></button>
  </nav>
  <form id="news-form" data-php-post-limit="<?= igua_ini_bytes((string)ini_get('post_max_size')) ?>" data-has-cover="<?= empty($row['image'])?'0':'1' ?>" data-cover-alt="<?= h((string)($row['cover_alt']??'')) ?>" method="post" enctype="multipart/form-data" action="/admin.php?action=save_article">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" id="news-blocks-json" name="blocks_json" value="">
    <section class="news-editor-step active" data-step="title">
      <p class="news-question-tag">PASSO 1 · MANCHETE</p>
      <h3>Qual é o título da notícia?</h3><p class="muted">Informe o fato principal com clareza. Não é preciso escrever tudo em letras maiúsculas.</p>
      <label class="news-main-input">Título da reportagem<input name="title" maxlength="220" minlength="5" required value="<?= h($row['title']) ?>" placeholder="O que aconteceu?" autocomplete="off"></label>
      <div class="news-gemini-action"><button type="button" class="news-gemini-icon news-gemini-labeled" data-news-gemini="title" title="Sugerir manchete com Gemini" aria-label="Sugerir manchete com Gemini"><span class="igua-gemini-mark" aria-hidden="true">✦</span><span>Gemini IA</span></button></div>
      <p class="news-field-counter" id="news-title-counter">0/220 caracteres</p>
    </section>
    <section class="news-editor-step" data-step="summary" hidden>
      <p class="news-question-tag">PASSO 2 · RESUMO</p>
      <h3>Qual é a pequena descrição?</h3><p class="muted">Escreva uma ou duas frases completas. Recomendação: 120 a 155 caracteres, mas o Google pode alterar o trecho exibido.</p>
      <label class="news-main-input">Resumo da notícia<textarea name="summary" maxlength="480" rows="5" placeholder="Resuma o fato principal, com informações verificadas."><?= h($row['summary']) ?></textarea></label>
      <div class="news-gemini-action"><button type="button" class="news-gemini-icon news-gemini-labeled" data-news-gemini="summary" title="Sugerir descrição com Gemini" aria-label="Sugerir descrição com Gemini"><span class="igua-gemini-mark" aria-hidden="true">✦</span><span>Gemini IA</span></button></div>
      <p class="news-field-counter" id="news-summary-counter">0/155 caracteres recomendados</p>
      <p class="news-inline-help" id="news-summary-help" role="status">Não cortaremos a descrição automaticamente no meio de uma frase.</p>
    </section>
