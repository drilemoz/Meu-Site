    <section class="news-editor-step" data-step="review" hidden>
      <p class="news-question-tag">PASSO 9 · REVISÃO E PUBLICAÇÃO</p>
      <h3>Confira a notícia antes de finalizar</h3><p class="muted">Você pode voltar a qualquer etapa para corrigir informações. O salvamento não publica sem sua escolha.</p>
      <?php
        // Para quem pode publicar, uma notícia nova/rascunho começa com "Publicar agora".
        // A escolha é apenas visual até o envio explícito do formulário no passo 9.
        $mayPublish=role_allowed((string)$me['role'],'publish');
        $scheduledOriginal=($row['status']??'')==='published'
            && !empty($row['published_at']) && strtotime((string)$row['published_at'])>time();
        $publicationDefault=!$mayPublish?'draft':($scheduledOriginal?'scheduled':'now');
      ?>
      <div class="news-publish-row">
        <label class="news-publish-choice" for="news-publication-choice">
          <span class="news-publish-label">Publicação</span>
          <span class="news-publish-select-wrap"><select id="news-publication-choice" name="publication_choice" required aria-describedby="news-publish-help">
            <?php if($mayPublish): ?><option value="now" <?= $publicationDefault==='now'?'selected':'' ?>>Publicar agora</option><?php endif; ?>
            <option value="draft" <?= $publicationDefault==='draft'?'selected':'' ?>>Rascunho</option>
            <?php if($mayPublish): ?><option value="scheduled" <?= $publicationDefault==='scheduled'?'selected':'' ?>>Agendar</option><?php endif; ?>
          </select></span>
        </label>
        <button class="btn primary news-publish-submit" id="news-submit" type="submit" hidden><?= $mayPublish?'Publicar notícia ✓':'Salvar rascunho ✓' ?></button>
      </div>
      <div id="news-schedule-control" class="news-schedule-control" hidden>
        <label>Data e horário (America/São Paulo)<input name="publish_at" type="datetime-local" value="<?= h(!empty($row['published_at'])?date('Y-m-d\TH:i',strtotime($row['published_at'])):'') ?>"></label>
        <small>O horário deve estar no futuro. Verifique o fuso da hospedagem.</small>
      </div>
      <p class="muted tiny news-publish-help" id="news-publish-help">A escolha só é aplicada ao tocar no botão de finalizar. Rascunhos não aparecem no site.</p>
      <div class="news-review-card" aria-live="polite">
        <p class="news-review-eyebrow" id="news-review-category">Categoria</p>
        <h4 id="news-review-title">Título da reportagem</h4>
        <p id="news-review-summary">Resumo da reportagem</p>
        <div id="news-review-body" class="news-review-body"></div>
        <div class="news-review-counts" id="news-review-counts"></div>
      </div>
      <input type="hidden" name="status" value="<?= h((string)($row['status']??'draft')) ?>">
      <p class="news-inline-help">Antes de publicar, confirme fatos, direitos de uso das imagens, fontes e créditos. A prévia SEO não garante a aparência final no Google.</p>
    </section>
    <div class="news-editor-footer"><button type="button" class="btn outline" id="news-prev" hidden>← Voltar</button><small id="news-save-status" aria-live="polite">Alterações ainda não salvas</small><button type="button" class="btn outline" id="news-next">Continuar →</button></div>
  </form>
</div>

<dialog id="news-gemini-dialog" class="igua-modal igua-modal--medium news-gemini-dialog" aria-labelledby="news-gemini-heading">
 <div class="news-seo-dialog-head"><h3 id="news-gemini-heading">Sugestão Gemini</h3><button type="button" class="btn outline small" data-news-gemini-close aria-label="Fechar janela">×</button></div>
 <p class="muted tiny">Ao solicitar, o texto da notícia é enviado à API Gemini. Verifique nomes, números, datas e fontes antes de aplicar.</p>
 <p id="news-gemini-feedback" role="status" aria-live="polite">Pronto para consultar.</p>
 <label for="news-gemini-suggestion">Sugestão para revisão<textarea id="news-gemini-suggestion" rows="6" maxlength="12000" spellcheck="true"></textarea></label>
 <div class="news-seo-dialog-actions"><button type="button" class="btn outline" data-news-gemini-close>Cancelar</button><button type="button" class="btn primary" id="news-gemini-apply" disabled>Aplicar sugestão</button></div>
</dialog>
