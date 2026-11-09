    <section class="news-editor-step" data-step="photos" hidden>
      <p class="news-question-tag">PASSO 4 · FOTOGRAFIAS</p>
      <h3>Quer adicionar fotografias?</h3><p class="muted">Escolha imagens da biblioteca ou envie fotos do celular. Defina a capa ou adicione fotos à matéria.</p>
      <div class="news-media-actions" aria-label="Escolher imagens da biblioteca">
        <button type="button" class="btn outline" data-media-pick="choose">▦ Escolher da biblioteca</button>
        <button type="button" class="btn outline" id="news-library-clear" title="Remover seleção de capa da biblioteca">✕ Remover capa</button>
      </div>
      <input type="hidden" name="library_cover" id="news-library-cover" value=""><input type="hidden" name="remove_cover" id="news-remove-cover" value="0">
      <p id="news-library-selected" class="news-media-feedback" role="status" aria-live="polite">Capa: <?= !empty($row['image'])?'imagem atual':'nenhuma imagem selecionada' ?>.</p>
      <div id="media-gallery-selected" class="media-picked-gallery"></div>
      <div id="news-local-photo-preview" class="news-local-photo-preview" aria-live="polite"></div>
      <?php if($row['image']): ?><figure class="image-preview news-cover-preview"><img class="edit-thumb" data-image-fallback="/assets/img/cidade.jpg" src="<?= h(asset_image($row['image'])) ?>" alt="Capa atual"><figcaption>Capa atual</figcaption></figure><?php endif; ?>
      <div class="news-direct-upload news-upload-wizard">
        <p class="news-file-heading">Enviar novas fotos do celular</p>
        <div class="news-upload-actions" aria-label="Selecionar arquivos do celular">
          <label class="news-upload-tile"><span>⬆ Foto de capa</span><input name="image" type="file" accept="image/jpeg,image/png,image/webp" aria-label="Selecionar foto de capa"></label>
          <label class="news-upload-tile"><span>▣ Várias fotos</span><input name="gallery_files[]" multiple type="file" accept="image/jpeg,image/png,image/webp" data-upload-input="gallery" aria-label="Selecionar várias fotos"></label>
          <label class="news-upload-tile"><span>▤ ZIP</span><input name="photo_zip" type="file" accept=".zip,application/zip,application/x-zip-compressed" aria-label="Selecionar ZIP de fotos"></label>
        </div>
        <div class="news-upload-feedback"><small id="news-image-upload-feedback" role="status" aria-live="polite"></small><small id="news-gallery-upload-feedback" role="status" aria-live="polite"></small><small id="news-photo-zip-feedback" role="status" aria-live="polite"></small></div>
        <small>Até 10 fotos JPG, PNG ou WebP por ZIP (25 MB compactados / 64 MB descompactados). Upload sujeito ao limite PHP de <?= h((string)ini_get('upload_max_filesize')) ?>.</small>
      </div>
      <label class="checkbox-line"><input name="auto_cover" type="checkbox" value="1" checked> <span>Usar automaticamente a primeira foto como capa, se nenhuma capa foi escolhida</span></label>
    </section>
    <section class="news-editor-step" data-step="credits" hidden>
      <p class="news-question-tag">PASSO 5 · LEGENDAS</p>
      <h3>Deseja informar legenda ou crédito das fotos?</h3><p class="muted">Campos opcionais. Para fotos enviadas agora, os créditos são preenchidos abaixo. Use somente imagens autorizadas.</p>
      <div class="form-grid news-cover-meta">
        <label class="full">Legenda da capa<textarea rows="2" name="image_caption" maxlength="360"><?= h($row['image_caption']??'') ?></textarea></label>
        <label class="full">Crédito da capa<input name="image_credit" maxlength="180" value="<?= h($row['image_credit']??'') ?>"></label>
      </div>
      <div class="news-uploads-preview" id="news-new-media-preview"></div>
      <?php if($media): ?><h3>Fotografias já vinculadas</h3><div class="news-existing-media"><?php foreach($media as $m): if($m['kind']!=='image')continue; ?><div class="news-existing-item"><strong><?= $m['kind']==='image'?'📷 Foto':'📎 Anexo' ?>: <?= h($m['original_name']) ?></strong><?php if($m['kind']==='image'): ?><img data-image-fallback="/assets/img/cidade.jpg" src="<?= h(media_url($m['filename'])) ?>" alt="Foto da galeria" loading="lazy"><?php endif; ?><label>Legenda / descrição<input name="media_caption[<?= (int)$m['id'] ?>]" maxlength="360" value="<?= h($m['caption']) ?>"></label><label>Crédito<input name="media_credit[<?= (int)$m['id'] ?>]" maxlength="180" value="<?= h($m['credit']) ?>"></label><label>Ordem<input type="number" min="0" max="1000" name="media_order[<?= (int)$m['id'] ?>]" value="<?= (int)$m['sort_order'] ?>"></label><label><input type="checkbox" name="remove_media[]" value="<?= (int)$m['id'] ?>"> Remover vínculo ao salvar</label></div><?php endforeach; ?></div><?php endif; ?>
    </section>
    <section class="news-editor-step" data-step="attachments" hidden>
      <p class="news-question-tag">PASSO 6 · DOCUMENTOS</p>
      <h3>Esta reportagem tem algum anexo?</h3><p class="muted">Adicione documentos para consulta do leitor, ou avance sem anexos.</p>
      <div class="news-direct-upload news-upload-wizard">
        <label class="news-upload-tile news-attachment-picker"><span>📎 Adicionar anexos</span><input name="attachment_files[]" multiple type="file" accept=".pdf,.txt,.csv,.json,.docx,.xlsx,.pptx,.odt,.ods,.odp,.rtf,.jpg,.jpeg,.png,.webp,.mp3,.wav,.mp4,.zip" data-upload-input="attachment" aria-label="Selecionar vários anexos"></label><small>PDF, documentos, planilhas, imagens, áudio, vídeo MP4 e ZIP. Até 10 arquivos de 12 MB cada. Arquivos executáveis não são aceitos.</small>
      </div>
      <?php if($media): ?><h3>Anexos já vinculados</h3><div class="news-existing-media"><?php foreach($media as $m): if($m['kind']!=='attachment')continue; ?><div class="news-existing-item"><strong><?= $m['kind']==='image'?'📷 Foto':'📎 Anexo' ?>: <?= h($m['original_name']) ?></strong><?php if($m['kind']==='image'): ?><img data-image-fallback="/assets/img/cidade.jpg" src="<?= h(media_url($m['filename'])) ?>" alt="Foto da galeria" loading="lazy"><?php endif; ?><label>Legenda / descrição<input name="media_caption[<?= (int)$m['id'] ?>]" maxlength="360" value="<?= h($m['caption']) ?>"></label><label>Crédito<input name="media_credit[<?= (int)$m['id'] ?>]" maxlength="180" value="<?= h($m['credit']) ?>"></label><label>Ordem<input type="number" min="0" max="1000" name="media_order[<?= (int)$m['id'] ?>]" value="<?= (int)$m['sort_order'] ?>"></label><label><input type="checkbox" name="remove_media[]" value="<?= (int)$m['id'] ?>"> Remover vínculo ao salvar</label></div><?php endforeach; ?></div><?php endif; ?>
    </section>
