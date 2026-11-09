    <section class="news-editor-step" data-step="classify" hidden>
      <p class="news-question-tag">PASSO 7 · CLASSIFICAÇÃO</p>
      <h3>Em qual categoria entra esta notícia?</h3><p class="muted">Escolha a categoria principal. Outras informações são opcionais.</p>
      <div class="news-taxonomy-main"><label>Categoria principal<select name="category_id" required><option value="">Selecione uma categoria</option><?php foreach($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= selected((string)$row['category_id'],(string)$c['id']) ?>><?= h($c['name']) ?></option><?php endforeach; ?></select></label><?php if(role_allowed((string)$me['role'],'manage_taxonomy')): ?><button type="button" class="btn outline small" data-news-taxonomy="category">+ Nova</button><?php endif; ?></div><div id="news-category-suggest" aria-live="polite"></div>
      <div class="news-source-section"><div class="news-taxonomy-heading"><strong>Fontes e assinatura</strong><small>Informe a origem da informação; confira antes de publicar.</small></div>
      <div class="form-grid"><label class="full">Fonte principal cadastrada<select name="source_id"><option value="0">Fonte manual / não cadastrada</option><?php foreach($sources as $s): ?><option value="<?= (int)$s['id'] ?>" <?= selected((string)($row['source_id']??''),(string)$s['id']) ?>><?= h($s['name']) ?></option><?php endforeach; ?></select></label>
      <?php if(role_allowed((string)$me['role'],'manage_taxonomy')): ?><button type="button" class="btn outline small news-add-source" data-news-taxonomy="source">+ Cadastrar fonte</button><?php endif; ?>
      <label>Nome da fonte<input name="source_name" maxlength="160" value="<?= h($row['source_name']) ?>"></label><label>URL HTTPS<input name="source_url" type="url" maxlength="600" value="<?= h($row['source_url']) ?>"></label></div>
      <small class="news-taxonomy-hint">A fonte cadastrada selecionada tem prioridade sobre os campos manuais. A versão atual registra uma fonte principal por notícia.</small></div>
      <details class="news-optional" <?= $extra?'open':'' ?>><summary>Autor e categorias adicionais</summary><div class="form-grid">
        <div class="full"><label class="news-field-title">Categorias adicionais</label><div class="news-category-chips"><?php foreach($cats as $c): ?><label><input type="checkbox" name="extra_categories[]" value="<?= (int)$c['id'] ?>" <?= in_array((int)$c['id'],$extra,true)?'checked':'' ?>> <?= h($c['name']) ?></label><?php endforeach; ?></div></div>
        <label>Assinatura<select name="author_profile_id"><option value="0">Conta / Redação</option><?php foreach($profiles as $p): ?><option value="<?= (int)$p['id'] ?>" <?= selected((string)($row['author_profile_id']??''),(string)$p['id']) ?>><?= h($p['name']) ?></option><?php endforeach; ?></select></label>

      </div></details>
      <?php if(role_allowed((string)$me['role'],'manage_taxonomy')): ?>
      <dialog id="news-taxonomy-dialog" class="igua-modal igua-modal--narrow news-seo-dialog" aria-labelledby="news-taxonomy-title"><div class="news-seo-dialog-head"><h4 id="news-taxonomy-title">Novo cadastro</h4><button type="button" class="btn outline small" id="news-taxonomy-close" aria-label="Fechar">✕</button></div>
        <label>Nome<input id="news-taxonomy-name" maxlength="160" autocomplete="off" placeholder="Nome"></label>
        <label id="news-taxonomy-url-wrap" hidden>Endereço da fonte (HTTPS, opcional)<input id="news-taxonomy-url" type="url" maxlength="600" autocomplete="off" placeholder="https://..."></label>
        <p id="news-taxonomy-error" role="status" aria-live="polite"></p><button type="button" class="btn primary" id="news-taxonomy-save">Salvar e selecionar</button></dialog>
      <?php endif; ?>
    </section>
    <section class="news-editor-step" data-step="seo" hidden>
      <p class="news-question-tag">PASSO 8 · BUSCADORES</p>
      <h3>Como a notícia poderá aparecer no Google?</h3><p class="muted">Prévia ilustrativa: o Google pode exibir outro título, trecho ou comprimento. A análise desta etapa é local. O Gemini IA só é consultado ao tocar no botão específico.</p>
      
      <section id="news-seo-audit" class="seo-editor-audit news-seo-compact" aria-label="Análise SEO da notícia"><div class="seo-audit-heading"><strong>Otimização editorial</strong><span id="news-seo-score" aria-live="polite">0/100</span></div><progress id="news-seo-progress" max="100" value="0" aria-label="Pontuação da revisão editorial"></progress><div class="news-seo-audit-actions"><small id="news-seo-summary">Avaliação local, não é nota do Google.</small></div></section>
      <!-- Campos persistidos continuam no formulário original; os pop-ups editam cópias temporárias. -->
      <input id="news-url-slug" type="hidden" name="url_slug" value="<?= h($row['slug']??'') ?>">
      <input type="hidden" name="seo_title" value="<?= h($row['seo_title']??'') ?>">
      <input type="hidden" name="seo_description" value="<?= h($row['seo_description']??'') ?>">
      <div class="news-seo-iconbar" role="group" aria-label="Ferramentas de SEO">
        <button type="button" class="news-seo-iconbtn" data-news-seo-tool="slug" title="Editar endereço" aria-label="Editar endereço da notícia"><span aria-hidden="true">🔗</span><small>Endereço</small></button>
        <button type="button" class="news-seo-iconbtn" data-news-seo-tool="generate" title="Gerar descrição" aria-label="Gerar sugestão de descrição SEO"><span aria-hidden="true">✧</span><small>Automático</small></button>
        <button type="button" class="news-seo-iconbtn news-gemini-icon" data-news-gemini="seo" title="Sugerir descrição SEO com Gemini" aria-label="Sugerir descrição SEO com Gemini"><span class="igua-gemini-mark" aria-hidden="true">✦</span><small>Gemini IA</small></button>
        <button type="button" class="news-seo-iconbtn" data-news-seo-tool="edit" title="Editar SEO" aria-label="Editar título e descrição SEO"><span aria-hidden="true">⚙</span><small>Editar</small></button>
        <button type="button" class="news-seo-iconbtn" id="news-seo-dialog-open" title="Ver diagnóstico" aria-label="Ver diagnóstico editorial"><span aria-hidden="true">☑</span><small>Análise</small></button>
      </div>
      <dialog id="news-seo-tool-dialog" class="igua-modal igua-modal--medium news-seo-dialog" aria-labelledby="news-seo-tool-title">
        <div class="news-seo-dialog-head"><h4 id="news-seo-tool-title">Editar SEO</h4><button type="button" class="btn outline small" id="news-seo-tool-x" aria-label="Fechar sem salvar">✕</button></div>
        <div id="news-seo-tool-slug" class="news-seo-tool-panel" hidden>
          <label for="news-seo-draft-slug">Endereço da notícia (slug)<input id="news-seo-draft-slug" maxlength="<?= $id?240:120 ?>" autocomplete="off" placeholder="Gerar automaticamente da manchete"></label>
          <button type="button" class="btn outline small" id="news-seo-suggest-slug">Gerar da manchete</button>
          <p class="muted tiny">Prévia do endereço</p><code id="news-url-preview" class="news-url-preview" data-pretty="<?= setting('seo_pretty_urls','0')==='1'?'1':'0' ?>"><?= h(url(['p'=>'noticia','slug'=>(string)(($row['slug']??'')?:'exemplo-da-noticia')])) ?></code>
          <p class="muted tiny"><?= $id?'Ao mudar o endereço publicado, os links antigos redirecionam para o novo.':'Deixe vazio para gerar automaticamente a partir do título.' ?></p>
        </div>
        <div id="news-seo-tool-edit" class="news-seo-tool-panel" hidden>
          <label for="news-seo-draft-title">Título SEO<input id="news-seo-draft-title" maxlength="220" placeholder="Usar a manchete"></label>
          <label for="news-seo-draft-description">Descrição para pesquisas <small id="news-meta-count">0/160 recomendado</small><textarea id="news-seo-draft-description" maxlength="320" rows="4" placeholder="Descreva a notícia em frase completa"></textarea></label>
        </div>
        <div id="news-seo-tool-generate" class="news-seo-tool-panel" hidden>
          <p class="muted tiny">Sugestão local feita com o texto da matéria. Revise antes de aplicar. Não há chamada de IA.</p>
          <label for="news-seo-draft-generated">Descrição sugerida<textarea id="news-seo-draft-generated" maxlength="320" rows="4"></textarea></label>
        </div>
        <div class="news-seo-dialog-actions"><button type="button" class="btn outline" id="news-seo-tool-cancel">Cancelar</button><button type="button" class="btn primary" id="news-seo-tool-save">Salvar ajustes</button></div>
      </dialog>
      <dialog id="news-seo-dialog" class="igua-modal igua-modal--narrow news-seo-dialog" aria-labelledby="news-seo-dialog-title">
        <div class="news-seo-dialog-head"><h4 id="news-seo-dialog-title">Diagnóstico editorial</h4><button type="button" class="btn outline small" id="news-seo-dialog-close" aria-label="Fechar diagnóstico">✕</button></div>
        <p class="muted tiny">Indicador local de preenchimento. Não é pontuação do Google.</p>
        <div class="news-diag-score"><strong id="news-diag-score">0/100</strong><progress id="news-diag-progress" max="100" value="0" aria-label="Pontuação editorial"></progress></div>
        <ul id="news-seo-checklist" class="seo-checklist news-seo-actionable"></ul>
        <details id="news-diagnostic-edit" class="news-diagnostic-edit"><summary>Ajustar SEO aqui</summary>
          <label for="news-diag-draft-title">Título SEO<input id="news-diag-draft-title" maxlength="220" placeholder="Usar manchete"></label>
          <label for="news-diag-draft-description">Descrição SEO<textarea id="news-diag-draft-description" maxlength="320" rows="3"></textarea></label>
          <label for="news-diag-draft-slug">Endereço (slug)<input id="news-diag-draft-slug" maxlength="<?= $id?240:120 ?>" placeholder="Gerado da manchete"></label>
        </details>
        <div class="news-seo-dialog-actions"><button type="button" class="btn outline" id="news-seo-dialog-done">Cancelar</button><button type="button" class="btn primary" id="news-seo-diag-save">Salvar ajustes</button></div>
      </dialog>
      <div class="news-seo-preview"><small>Prévia aproximada nos buscadores</small><strong id="news-preview-title"><?= h($row['seo_title']?:$row['title']) ?></strong><span id="news-preview-desc"><?= h($row['seo_description']?:$row['summary']) ?></span></div>
      <?php if(role_allowed($me['role'],'publish')): ?><label class="checkbox-line"><input type="checkbox" name="featured" value="1" <?= !empty($row['featured'])?'checked':'' ?>> <span>Destacar na página inicial</span></label><?php endif; ?>

    </section>
