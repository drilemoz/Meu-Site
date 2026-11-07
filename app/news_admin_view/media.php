    <section class="news-editor-step" data-step="media" hidden>
      <h3>3. Imagens e documentos</h3><p class="muted">Escolha uma foto existente ou envie pelo gerenciador. Use apenas imagens autorizadas.</p>
      <div class="news-media-actions" aria-label="Escolher imagens para notícia">
        <button type="button" class="btn outline" data-media-pick="cover" title="Escolher ou enviar imagem de capa">🖼️ Mídias</button>
        <button type="button" class="btn outline" id="news-library-clear" title="Limpar capa escolhida na biblioteca">✕ Limpar</button>
        <button type="button" class="btn outline" data-media-pick="gallery" title="Selecionar várias fotos">▦ Galeria</button>
      </div>
      <input type="hidden" name="library_cover" id="news-library-cover" value="">
      <p id="news-library-selected" class="news-media-feedback" role="status" aria-live="polite">Capa: <?= !empty($row['image'])?'imagem atual':'nenhuma imagem selecionada' ?>.</p>
      <div id="media-gallery-selected" class="media-picked-gallery"></div>
      <?php if($row['image']): ?><figure class="image-preview news-cover-preview"><img class="edit-thumb" data-image-fallback="/assets/img/cidade.jpg" src="<?= h(asset_image($row['image'])) ?>" alt="Capa atual"><figcaption>Capa atual</figcaption></figure><?php endif; ?>
      <div class="form-grid news-cover-meta">
        <label class="full">Legenda da capa<textarea rows="2" name="image_caption" maxlength="360"><?= h($row['image_caption']??'') ?></textarea></label>
        <label class="full">Crédito da capa<input name="image_credit" maxlength="180" value="<?= h($row['image_credit']??'') ?>"></label>
      </div>
      <details class="news-optional news-direct-upload"><summary>Envio direto e documentos (opcional)</summary>
        <p class="muted">Para fotos, prefira Mídias ou Galeria. O gerenciador otimiza os arquivos e permite reutilizá-los.</p>
        <label class="full">Enviar capa diretamente<input name="image" type="file" accept="image/jpeg,image/png,image/webp"><small>Limite atual do PHP: <?= h((string)ini_get('upload_max_filesize')) ?>. Fotos grandes são otimizadas quando possível.</small><small id="news-image-upload-feedback" role="status" aria-live="polite"></small></label>
        <label class="full">Adicionar fotos diretamente<input name="gallery_files[]" multiple type="file" accept="image/jpeg,image/png,image/webp" data-upload-input="gallery"><small id="news-gallery-upload-feedback" role="status" aria-live="polite"></small></label>
        <label class="full">Documentos (PDF, TXT ou CSV)<input name="attachment_files[]" multiple type="file" accept=".pdf,.txt,.csv,application/pdf,text/plain,text/csv" data-upload-input="attachment"></label>
        <div class="news-uploads-preview" id="news-new-media-preview"></div>
      </details>
      <?php if($media): ?><h3>Arquivos já anexados</h3><div class="news-existing-media"><?php foreach($media as $m): ?><div class="news-existing-item"><strong><?= $m['kind']==='image'?'📷 Foto':'📎 Anexo' ?>: <?= h($m['original_name']) ?></strong><?php if($m['kind']==='image'): ?><img data-image-fallback="/assets/img/cidade.jpg" src="<?= h(media_url($m['filename'])) ?>" alt="Foto da galeria" loading="lazy"><?php endif; ?><label>Legenda / descrição<input name="media_caption[<?= (int)$m['id'] ?>]" maxlength="360" value="<?= h($m['caption']) ?>"></label><label>Crédito<input name="media_credit[<?= (int)$m['id'] ?>]" maxlength="180" value="<?= h($m['credit']) ?>"></label><label>Ordem<input type="number" min="0" max="1000" name="media_order[<?= (int)$m['id'] ?>]" value="<?= (int)$m['sort_order'] ?>"></label><label><input type="checkbox" name="remove_media[]" value="<?= (int)$m['id'] ?>"> Remover vínculo ao salvar</label></div><?php endforeach; ?></div><?php endif; ?>
    </section>
