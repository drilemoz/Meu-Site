<?php
declare(strict_types=1);
function media_admin_modal(bool $embedded=false): void { ?>
<<?php echo $embedded ? 'section' : 'div'; ?> class="media-modal<?= $embedded ? ' media-inline' : '' ?>" id="media-manager" <?= $embedded ? '' : 'hidden role="dialog" aria-modal="true"' ?> data-csrf="<?= h(csrf_token()) ?>" aria-label="Gerenciador de mídias">
 <div class="media-window">
  <header class="media-header"><div><h2>🖼️ Gerenciador de Mídias</h2><small>Biblioteca e envios organizados por ano e mês</small></div><?php if(!$embedded): ?><button type="button" class="media-close" aria-label="Fechar">×</button><?php endif; ?></header>
  <div class="media-manager-body"><section class="media-main"><p class="media-choice-hint" hidden>Toque em uma foto para defini-la como capa ou em <strong>+ Na matéria</strong> para adicionar à galeria.</p>
   <div class="media-tools"><button type="button" data-media-back>← Voltar</button><span class="media-path">Biblioteca</span><button type="button" class="media-search-toggle" data-media-search-toggle aria-label="Pesquisar imagens" title="Pesquisar">🔎</button><label class="media-search-box" data-media-search-box><input type="search" data-media-search placeholder="Pesquisar título, nome e crédito" aria-label="Pesquisar imagens"></label><button type="button" data-media-trash>🗑 Lixeira</button></div>
   <div class="media-grid" aria-live="polite"></div>
   <div class="media-paging"><button type="button" data-media-prev>←</button><span class="media-pages"></span><button type="button" data-media-next>→</button></div>
   <p class="media-status" role="status" aria-live="polite"></p>
  </section><aside class="media-side">
   <details class="media-upload-details"><summary>+ Enviar novas fotos</summary><form class="media-upload-form"><div class="igua-file-picker"><label class="igua-file-button">📎 Escolher arquivos<input data-media-upload data-file-enhanced="1" class="igua-native-file" type="file" accept="image/jpeg,image/png,image/webp" multiple aria-label="Escolher novas imagens"></label><span class="igua-file-name" data-media-upload-name>Nenhum arquivo</span></div><p class="muted tiny">Destino automático: <?= date('Y') ?> / <?= date('m') ?>. O envio começa automaticamente após selecionar os arquivos. Otimização WebP quando disponível. Até 10 fotos por envio, limite do servidor PHP: <?= h((string)ini_get('upload_max_filesize')) ?>.</p><button type="submit" class="btn primary">Enviar fotos</button></form></details>
   <div class="media-details" hidden><button type="button" class="media-details-close">← Fechar detalhes</button><img class="media-details-preview" src="" alt="Visualização da foto"><p class="media-details-name"></p><form class="media-details-form"><input type="hidden" name="id"><label>Título<input name="title" maxlength="200"></label><label>Legenda<textarea name="caption" maxlength="360" rows="2"></textarea></label><label>Crédito<input name="credit" maxlength="180"></label><label>Texto alternativo<input name="alt_text" maxlength="220"></label><button type="submit" class="btn primary">Salvar informações</button></form><button type="button" class="media-details-delete btn outline">Mover para lixeira</button></div>
  </aside></div>
 </div>
</<?php echo $embedded ? 'section' : 'div'; ?>>
<?php }
function media_admin_json(array $data,int $status=200): never {http_response_code($status);header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
